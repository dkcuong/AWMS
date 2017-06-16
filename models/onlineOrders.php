<?php

/*
********************************************************************************
* MODEL                                                                        *
********************************************************************************
*/

class model extends base
{
    public $importer;

    public $barcode = [];

    public $exportInventory = [];

    public $exportInfo = [];

    public $ajax = NULL;

    public $customerProps = [
        'from_company',
        'from_state',
        'from_city',
        'from_address',
        'from_postal',
        'from_country',
        'from_phone',
        'to_company',
        'to_name',
        'shipping_address_street',
        'shipping_address_street_cont',
        'shipping_city',
        'shipping_state',
        'shipping_country',
        'shipping_postal_code',
        'from_phone',
        'signatureID',
        'providerID',
        'packageID',
        'serviceID',
        'billToID'
    ];

    public $recipient = [
        'to_company' => NULL,
        'to_name' => NULL,
        'shipping_address_street' => NULL,
        'shipping_address_street_cont' => NULL,
        'shipping_city' => NULL,
        'shipping_state' => NULL,
        'shipping_postal_code' => NULL,
        'shipping_country' => NULL,
    ];

    // 2 cubic feet (2 * 1728 cubic inches per 1 cubic feet)
    public $upsVolumeLimit = 3456;

    public $datableFilters = [
        'warehouseID',
        'vendorID',
    ];

    /*
    ****************************************************************************
    */

    function getBarcode()
    {
        $sql = 'SELECT      oo.id,
                            order_batch,
                            scanordernumber AS SCAN_SELDAT_ORDER_NUMBER,
                            dateCreated
                FROM        online_orders oo
                LEFT JOIN   neworder n
                ON          n.scanordernumber = oo.SCAN_SELDAT_ORDER_NUMBER
                LEFT JOIN   order_batches ob
                ON          ob.id = n.order_batch
                WHERE order_batch = (
                    SELECT     order_batch
                    FROM       online_orders o
                    LEFT JOIN  neworder no
                    ON         no.scanordernumber = o.SCAN_SELDAT_ORDER_NUMBER
                    WHERE SCAN_SELDAT_ORDER_NUMBER = ?
                    GROUP BY SCAN_SELDAT_ORDER_NUMBER
                )
                GROUP BY SCAN_SELDAT_ORDER_NUMBER';

        return $sql;
    }

    /*
    ****************************************************************************
    */

    function getExportInventory($vendorID, $upcDate)
    {
        $params = $batchData = $dimensions = [];

        $sql = 'SELECT    batchID,
                          SUM(uom) AS quantity
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      statuses s ON s.id = ca.statusID
                WHERE     vendorID = ?
                AND       upcID = ?
                AND       s.shortName = "RK"
                AND       category = "inventory"
                GROUP BY  batchID
                ';

        $limits = $results = [];

        foreach ($upcDate as $upcID => $quantity) {

            $params[] = $vendorID;
            $params[] = $upcID;

            $limits[] = $quantity;
        }

        if (! $limits) {
            return;
        }

        $subqueryCount = count($limits);

        $results = $this->queryUnionResults([
            'limits' => $limits,
            'subqueries' => array_fill(0, $subqueryCount, $sql),
            'mysqlParams' => $params,
            'subqueryCount' => $subqueryCount
        ]);

        if (! $results) {
            return;
        }

        $bathces = new \tables\inventory\batches($this);
        // get volume and weight per piece
        $batchIDs = array_keys($results);

        $dimensions = $bathces->getUnitDimensions($batchIDs);

        foreach ($dimensions as $batchID => $dimension) {

            $upcID = $dimension['upcID'];

            $this->exportInventory[$upcID][$batchID] = [
                'quantity' => $results[$batchID]['quantity'],
                'pieceVolume' => $dimension['volume'],
                'pieceWeight' => $dimension['weight']
            ];
        }
    }

    /*
    ****************************************************************************
    */

    function calculateOrdersVolumes($difference)
    {
        $customerData = [];

        $count = $customerID = 0;

        foreach ($difference as $orderID => $orderInfo) {
            // get an array of volumes per each online order per customer
            $anotherCustomer = ! $customerData
                    || $this->checkCustomerProp($orderInfo, $customerData);

            if ($anotherCustomer) {
                // first run or another orders
                $customerID = $count;

                $this->exportInfo[$customerID]['customerInfo'] = $customerData
                        = $orderInfo;

                $count++;
            }

            $this->exportInfo[$customerID]['onlineOrderIDs'][$orderID]
                    = $this->getOrderVolume($orderInfo);
        }

        foreach ($this->exportInfo as &$info) {
            // sort orders' volumes
            $volumes = $weights = $orderIDs = [];

            // obtain a list of columns an output array will be sorted by
            foreach ($info['onlineOrderIDs'] as $key => $row) {
                if (isset($row['shortage'])) {

                    $this->addShortage($row);

                    continue;
                }

                // add an extra orderIDs array since array_multisort loses keys
                $info['onlineOrderIDs'][$key]['orderID'] = $key;

                $volumes[$key] = $row['volume'];
                $weights[$key] = $row['weight'];
                $orderIDs[$key] = $key;
            }

            // if there is not enough inventory do not sort orders by volume
            // since no export to online_orders_exports will be performed
            if (! $this->shortages) {
                // sort output by volume ASC, weight ASC and orderID ASC
                array_multisort($volumes, SORT_ASC, $weights, SORT_ASC,
                        $orderIDs, SORT_ASC, $info['onlineOrderIDs']);

                $sorted = $info['onlineOrderIDs'];

                $info['onlineOrderIDs'] = [];

                foreach ($sorted as $values) {
                    // restoring keys destroyed by array_multisort function
                    $orderID = $values['orderID'];

                    $info['onlineOrderIDs'][$orderID] = [
                        'volume' => $values['volume'],
                        'weight' => $values['weight']
                    ];
                }
            }
        }
    }

    /*
    ****************************************************************************
    */

    function checkCustomerProp($orderInfo, $customerData)
    {
        foreach ($this->customerProps as $prop) {
            if ($customerData[$prop] != $orderInfo[$prop]) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /*
    ****************************************************************************
    */

    function addShortage($row)
    {
        $shortage = $row['shortage'];

        $key = $shortage['upc'] . $shortage['scanOrderNumber'];

        if (isset($this->shortages[$key])) {
            $this->shortages[$key]['quantity'] += $shortage['quantity'];
        } else {
            $this->shortages[$key] = $shortage;
        }
    }

    /*
    ****************************************************************************
    */

    function getOrderVolume($orderInfo)
    {
        $upcID = $orderInfo['upcID'];

        $volume = $weight = 0;

        $exportInventory = getDefault($this->exportInventory[$upcID], []);

        foreach ($exportInventory as $onlineOrderID => $inventory) {
            if (! $inventory['quantity']) {
                // pieces from this batch were assigned to other orders
                continue;
            }

            $quantity = min($inventory['quantity'], $orderInfo['quantity']);

            $this->exportInventory[$upcID][$onlineOrderID]['quantity'] -= $quantity;
            $orderInfo['quantity'] -= $quantity;

            $volume += $quantity * $inventory['pieceVolume'];
            $weight += $quantity * $inventory['pieceWeight'];

            if (! $orderInfo['quantity']) {
                return [
                    'volume' => $volume,
                    'weight' => $weight
                ];
            }
        }

        return [
            'shortage' => [
                'scanOrderNumber' => $orderInfo['scanOrderNumber'],
                'clientOrderNumber' => $orderInfo['clientOrderNumber'],
                'upc' => $orderInfo['upc'],
                'quantity' => $orderInfo['quantity']
            ]
        ];
    }

    /*
    ****************************************************************************
    */

    function createCarrierExport($nextID)
    {
        $exportInfo = $this->distributeOrdersByVolume($nextID);

        $orderSql = 'INSERT INTO online_orders_exports_orders (
                         exportOrderID,
                         onlineOrderID
                     )
                     VALUES (?, ?)';

        $exportSql = 'INSERT INTO online_orders_exports (
                          package_weight,
                          package_length,
                          package_width,
                          package_height,
                          labelNo,
                          from_company,
                          from_name,
                          from_state,
                          from_city,
                          from_address_1,
                          from_postal,
                          from_country,
                          from_phone,
                          to_company,
                          to_name,
                          to_address_1,
                          to_address_2,
                          to_city,
                          to_state,
                          to_country,
                          to_postal,
                          to_phone,
                          signatureID,
                          providerID,
                          packageID,
                          serviceID,
                          billToID
                      ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?
                      )';

        $count = 0;

        foreach ($exportInfo as $exportID => $orderInfo) {

            $newKey = FALSE;

            foreach ($this->recipient as $field => &$value) {

                $checkValue = $orderInfo['customerInfo'][$field];

                $isIncrement = ! $newKey && $checkValue != $value;

                $count += (int)$isIncrement;

                $newKey = $newKey || $checkValue != $value;

                $value = $checkValue;
            }

            foreach ($orderInfo['orders'] as $orderID) {
                $this->runQuery($orderSql, [$exportID, $orderID]);
            }

            $volume = $orderInfo['volume'];
            $weight = $orderInfo['weight'];

            $dimensions = pow($volume, 1 / 3);

            $height = $length = $width = round($dimensions, 2);

            if ($height != 0) {
                // error made in calculations is added / substracted from height
                $height = round($volume / ($length * $width), 2);
            }

            $params = [$weight, $length, $width, $height, $count];

            foreach ($this->customerProps as $customerProp) {

                $params[] = $orderInfo['customerInfo'][$customerProp];

                if ($customerProp == 'from_company') {
                    // from_company and from_name are of the same value as
                    // online_orders tabel does not shipping_first_name and
                    // shipping_last_name fields but no shipping_company
                    $params[] = $orderInfo['customerInfo'][$customerProp];
                }
            }

            $this->runQuery($exportSql, $params);
        }
    }

    /*
    ****************************************************************************
    */

    function distributeOrdersByVolume($nextID)
    {
        $exportInfo = [];

        foreach ($this->exportInfo as $info) {

            $volume = 0;

            $firstRow = TRUE;

            foreach ($info['onlineOrderIDs'] as $orderID => $values) {

                $volume += $values['volume'];

                if ($firstRow || $volume > $this->upsVolumeLimit) {

                    $exportID = $nextID;

                    $exportInfo[$exportID] = [
                        'customerInfo' => $info['customerInfo'],
                        'volume' => 0,
                        'weight' => 0
                    ];
                    // reset volume count
                    $volume = $values['volume'];

                    $firstRow = FALSE;

                    $nextID++;
                }

                $exportInfo[$exportID]['orders'][] = $orderID;
                $exportInfo[$exportID]['volume'] += $values['volume'];
                $exportInfo[$exportID]['weight'] += $values['weight'];
            }
        }

        return $exportInfo;
    }

    /*
    ****************************************************************************
    */

    function importError()
    {
        $noInventory = getDefault($this->onlineOrders->errors['noInventory']);
        $noMinMaxSetting =
                getDefault($this->onlineOrders->errors['noMinMaxSetting']);

        unset($this->onlineOrders->errors['noInventory']);
        unset($this->onlineOrders->errors['noMinMaxSetting']);

        if ((! isset($this->onlineOrders->errors) || ! $this->onlineOrders->errors)
          && $this->onlineOrders->import) {
            // introducing style alltribute instead of class
         ?>

            <br>
            <div style="border: 1px #9d9 solid; background: #e9ffe9;"
                 class="blockDisplay">Your file has been imported successfully!
                <br />
                <span  style="font-weight: bold;color: green">
                    You must create wave pick for this order before import a
                    new order
                </span>
            </div>

            <?php

            $this->softErrors($noMinMaxSetting, $noInventory);

            return FALSE;
        }

        if ($this->onlineOrders->badRows) {
            $this->errorFile([
                'captionSuffix' => 'with invalid orders.<br>Please export the'
                                  .' list of failed rows, correct them and'
                                  .' resubmit them in a new batch.',
            ]);
        }

        $errors =$this->onlineOrders->errors;

        if (isset($errors['multipleSheets'])) {
            $this->errorFile([
                'captionSuffix' => 'with multiple sheets',
            ]);
        } else if (isset($errors['wrongType'])) {
            $this->errorFile([
                'captionSuffix' => 'that is not a valid Excel file',
            ]);
        } else {

            $this->softErrors($noMinMaxSetting, $noInventory);

            if (isset($errors['warehouse'])) {
                $this->errorFile([
                    'captionSuffix' => 'with no more carton in Mezzanine and '
                        . 'Inventory. List errors UPC below:' ,
                    'errorArray' => $errors['warehouse'],
                ]);
            }
            if (isset($errors['outStock'])) {
                $this->errorFile([
                    'captionSuffix' => 'with no more carton in Inventory.'
                                        . ' List error UPC below:' ,
                    'errorArray' => $errors['outStock'],
                ]);
            }

            // table columns' errors section
            if (isset($errors['missingColumns'])) {
                $this->errorFile([
                    'captionSuffix' => 'with missing columns:',
                    'errorArray' => $errors['missingColumns'],
                ]);
            }

            if (isset($errors['duplicateColumns'])) {
                $this->errorFile([
                    'captionSuffix' => 'with duplicate columns:',
                    'errorArray' => $errors['duplicateColumns'],
                ]);
            }

            if (isset($errors['emptyCaptions'])) {
                $this->errorFile([
                    'captionSuffix' => 'with empty captions for the following'
                                      .' columns:',
                    'errorArray' => $errors['emptyCaptions'],
                ]);
            }

            if (isset($errors['invalidColumns'])) {
                $this->errorFile([
                    'captionSuffix' => 'with invalid columns:',
                    'errorArray' => $errors['invalidColumns'],
                ]);
            }

            if (isset($errors['problemRef'])) {
                $this->errorFile([
                    'captionSuffix' => 'and multiple Order IDs point to each of'
                                      .' the Reference ID(s) below:',
                    'errorArray' => $errors['problemRef'],
                ]);
            }

            if (isset($errors['problemOrderID'])) {
                $this->errorFile([
                    'captionSuffix' => 'and multiple Reference IDs point to'
                                      .' each of the Order ID(s) below:',
                    'errorArray' => $errors['problemOrderID'],
                ]);
            }

            if (isset($errors['missingOrderID'])) {
                $this->errorFile([
                    'captionSuffix' => 'with Order ID(s) that are not present'
                                      .' in DB',
                    'errorArray' => $errors['missingOrderID'],
                ]);
            }

            // table cells' errors section
            if (isset($errors['missingReqs'])) {
                $this->errorDescription([
                    'errorArray' => $errors['missingReqs'],
                    'captionSuffix' => 'are missing required values:',
                ]);
            }

            if (isset($errors['extraColumn'])) {
                $this->errorDescription([
                    'errorArray' => $errors['extraColumn'],
                    'captionSuffix' => 'have empty column caption(s):',
                ]);
            }

            if (isset($errors['nonUTFReqs'])) {
                $this->errorDescription([
                    'errorArray' => $errors['nonUTFReqs'],
                    'captionSuffix' => 'have non UTF character(s):',
                ]);
            }

            if (isset($errors['lengthLimit'])) {
                $this->errorDescription([
                    'errorArray' => $errors['lengthLimit'],
                    'captionSuffix' => 'have excessive width values:',
                ]);
            }

            if (isset($errors['badRefIDs'])) {
                $this->errorDescription([
                    'errorArray' => $errors['badRefIDs'],
                    'captionSuffix' => 'Reference IDs are already in the online'
                                      .' orders table:',
                    'rowSuffix' => '',
                ]);
            }

            if (isset($errors['badOrderIDs'])) {
                $this->errorDescription([
                    'errorArray' => $errors['badOrderIDs'],
                    'captionSuffix' => 'Order IDs are already in the online'
                                      .' orders table:',
                    'rowSuffix' => '',
                ]);
            }

            if (isset($errors['invalidUPCs'])) {
                $this->errorFile([
                    'captionSuffix' => 'with invalid UPCs:',
                    'errorArray' => $errors['invalidUPCs'],
                ]);
            }

            if (isset($errors['wrongUPCs'])) {
                $this->errorFile([
                    'captionSuffix' => 'with UPCs that belong to another Client(s):',
                    'errorArray' => $errors['wrongUPCs'],
                ]);
            }

            if (isset($errors['nonPositiveReqs'])) {
                $this->errorDescription([
                    'errorArray' => $errors['nonPositiveReqs'],
                    'captionSuffix' => 'have nonpositive values:',
                    'rowSuffix' => 'value must be positive:',
                ]);
            }

            if (isset($errors['exceed'])) {
                $this->errorDescription([
                    'errorArray' => $errors['exceed'],
                    'captionSuffix' => 'have excessive values:',
                ]);
            }

            if (isset($errors['invalidReqs'])) {
                $this->errorDescription([
                    'errorArray' => $errors['invalidReqs'],
                    'captionSuffix' => 'have invalid values:',
                    'delimiter' => '<br>',
                ]);
            }

            if (isset($errors['badDate'])) {
                $this->errorDescription([
                    'errorArray' => $errors['badDate'],
                    'captionSuffix' => 'have invalid date format:',
                    'rowSuffix' => 'value must have the following format '
                                   .'(year-month-day hh:mm) - example '
                                   .'(2015-01-27 09:00):',
                ]);
            }
        }
        return TRUE;
    }

    /*
    ****************************************************************************
    */

    function errorDescription($data)
    {
        $captionDescr = $data['captionSuffix'];

        $delimiter = isset($data['delimiter']) ? $data['delimiter'] : ', ';

        $descriptionCaption = getDefault($data['descriptionCaption'], NULL);
        $descriptionValues = getDefault($data['descriptionValues'], []);

        if (isset($data['rowSuffix'])) {
            $rowDescr = $data['rowSuffix'];
        } elseif (substr($captionDescr, 0, 4) == 'are ') {
            $rowDescr = 'is' . substr($captionDescr, 3);
        } elseif (substr($captionDescr, 0, 5) == 'have ') {
            $rowDescr = 'has' . substr($captionDescr, 4);
        } else {
            $rowDescr = $captionDescr;
        }

        $caption = getDefault($data['caption'], 'row'); ?>

        <div class="failedMessage blockDisplay">
            <strong>The following import <?php echo $caption; ?>s <?php echo
                $captionDescr; ?></strong><br> <?php

            $count = 0;

            foreach ($data['errorArray'] as $key => $req) {

                echo ! $count || $delimiter == ', ' ? NULL : $delimiter;

                $rowDescr = $rowDescr ? ' ' . $rowDescr : ':'; ?>

                Spreadsheet <?php echo $caption . ' ' . $key . $rowDescr . ' '
                        . implode($delimiter, $req);

                if (getDefault($descriptionValues[$key])) { ?>

                <br><?php echo $descriptionCaption
                        . implode(',', $descriptionValues[$key]);
                } ?>

                <br> <?php

                $count++;
            } ?>

        </div><?php

        $this->importer->errors[] = TRUE;
    }

    /*
    ****************************************************************************
    */

    function errorFile($descriptions)
    { ?>
        <div class="failedMessage blockDisplay">
            You have submitted a file <?php echo $descriptions['captionSuffix']; ?>
            <br> <?php

            if (isset($descriptions['errorArray'])) {
                echo implode('<br>', array_keys($descriptions['errorArray']));
            } ?>

        </div><?php

        $this->importer->errors[] = TRUE;
    }

    /*
    ****************************************************************************
    */

    function declareDatatableJsVars($model)
    {
        $fields = array_keys($model->fields);

        $fieldKeys = array_flip($fields);

        $this->jsVars['columnNumbers'] = [
            'upsLink' => $fieldKeys['upsLink'],
            'batch' => $fieldKeys['order_batch'],
            'order' => $fieldKeys['scan_seldat_order_number'],
            'carrier' => $fieldKeys['carrier'],
            'noInventory' => $fieldKeys['noInventory'],
        ];

        return $fieldKeys['scan_seldat_order_number'];
    }

    /*
    ****************************************************************************
    */

    function softErrors($noMinMaxSetting, $noInventory)
    {
        if ($noMinMaxSetting) {
            $this->errorFile([
                'captionSuffix' => 'with a UPC that does not have a designated'
                                 . ' Min/Max Setting and there are no more<br>'
                                 . ' available mezzanine locations for this'
                                 . ' client. List error UPC below:',
                'errorArray' => $noMinMaxSetting['description'],
            ]);
        }

        if ($noInventory) {

            $description = getDefault($noInventory['description'], []);

            unset($noInventory['description']);

            $this->errorDescription([
                'errorArray' => $noInventory,
                'captionSuffix' => 'requested more inventory than we have in '
                                 . ' the stock:<br>(Related orders were not'
                                 . ' imported and can be saved to an Excel file'
                                 . ' and imported later in a different batch)',
                'descriptionCaption' => 'rows: ',
                'descriptionValues' => $description,
                'rowSuffix' => 'requests more inventory than available:',
                'caption' => 'upc'
            ]);
        }
    }

    /*
    ****************************************************************************
    */
}
