<?php

namespace common;

class orderChecks extends order
{
    const CHECK_IN = 'Check-In';
    const CHECK_OUT = 'Check-Out';

    const REGULAR_ORDER_PRODUCTS_TABLE_CAPTION = 'Order Products';
    const TRUCK_ORDER_PRODUCTS_TABLE_CAPTION = 'Master Cartons';

    static $errors = [];


    /*
    ****************************************************************************
    */

    static function init($app)
    {
        orderProperties::setFields($app);
    }

    /*
    ****************************************************************************
    */

    static function getOrderInfoQuery($app, $updateOrders)
    {
        if (! $updateOrders) {
            return [];
        }

        $qMarks = $app->getQMarkString($updateOrders);

        $sql = 'SELECT    n.id,
                          userid,
                          first_name,
                          last_name,
                          clientordernumber,
                          customerordernumber,
                          scanOrderNumber,
                          scanworkorder,
                          deptid,
                          bolNumber,
                          clientpickticket,
                          scanpicking,
                          location,
                          shipto,
                          shiptoaddress,
                          shiptocity,
                          numberofcarton,
                          carrierName,
                          additionalshipperinformation,
                          payBy,
                          payByInfo,
                          numberofpiece,
                          totalVolume,
                          totalWeight,
                          startshipdate,
                          canceldate,
                          type,
                          EcoOrReg,
                          service,
                          picklist,
                          packinglist,
                          prebol,
                          commercialinvoice,
                          otherdocumentinform,
                          shiptolabels,
                          ediasn,
                          label,
                          cartoncontent,
                          otherlabelinform,
                          carrier,
                          carriernote,
                          ordernotes,
                          dateentered,
                          username,
                          saleorderid,
                          dcUserID,
                          pickid,
                          samples,
                          pickpack,
                          cartonofcontent,
                          NOpallets,
                          physicalhours,
                          overtimehours,
                          NOrushhours,
                          partyname,
                          partyaddress,
                          partycity,
                          specialinstruction,
                          commodity,
                          statusID,
                          labelinfo,
                          isVAS,
                          orderShipDate,
                          vendorID AS vendor,
                          edi,
                          isPrintUccEdi
                FROM      neworder n
                JOIN      order_batches b ON b.id = n.order_batch
                JOIN      vendors v ON v.id = b.vendorID
                WHERE     scanOrderNumber IN (' . $qMarks . ')';

        $results = $app->queryResults($sql, $updateOrders);

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function getOrderInfo($app, $updateOrders)
    {
        $results = self::getOrderInfoQuery($app, $updateOrders);

        $return = $processed = [];

        // make output array in the same order as $updateOrders
        foreach ($updateOrders as $order) {
            foreach ($results as $result) {
                if ($result['scanOrderNumber'] == $order
                && ! isset($processed[$order])
                ) {

                    $return[] = $result;
                    $processed = $order;
                }
            }
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    static function checkClosedOrders($app, $orders, $orderNumbers)
    {
        $checkResults = $orders->checkIfOrderProcessed($orderNumbers);

        $app->closedOrders = $app->jsVars['processedOrders']
            = $checkResults['processedOrders'];

        $app->canceledOrders = $checkResults['canceledOrders'];
    }

    /*
    ****************************************************************************
    */

    static function getOrdersStatusData($app, $orders, $orderNumbers)
    {
        if ($app->checkType == 'Check-Out') {
            self::checkClosedOrders($app, $orders, $orderNumbers);
        } else {
             // in Order Check In page all orders are not processed
            foreach ($orderNumbers as $orderNumber) {
                $app->closedOrders[$orderNumber] = FALSE;
                $app->jsVars['processedOrders'][$orderNumber] = FALSE;
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function checkRadio($app, $data)
    {
        $post = $data['post'];
        $page = $data['page'];
        $field = $data['field'];
        $input = getDefault($data['input'], NULL);
        $suffix = getDefault($data['suffix'], NULL);

        if (isset($post[$field][$page])) {

            $radio = $app->inputValues[$field][$page] = $post[$field][$page];

            $app->inputValues[$radio][$page] = 'checked';

            if ($input) {

                $note = $field == 'carrier' ? $app->carrierAndNote[$radio] :
                    $radio . $suffix;

                $app->inputValues[$input][$page] = $post[$note][$page];
            }
        } else {
            $app->inputValues[$input][$page] = NULL;
        }
    }

    /*
    ****************************************************************************
    */

    static function getValues($app, $page)
    {
        $post = $app->post;

        foreach ($app->radioNCheck as $field) {
            $app->inputValues[$field][$page] = NULL;
        }

        $param = [
            'post' => $post,
            'page' => $page,
        ];

        //check if it is Ecommerce or Regular
        $param['field'] = 'EcoOrReg';

        self::checkRadio($app, $param);

        //check if shipping is Standard, Rush or Super-Rush
        $param['field'] = 'service';

        self::checkRadio($app, $param);

        //check if it is VAS
        $param['field'] = 'isVAS';

        self::checkRadio($app, $param);

        // check which label is used
        $param['field'] = 'label';
        $param['input'] = 'labelinfo';
        $param['suffix'] = 'info';

        self::checkRadio($app, $param);

        $param['field'] = 'payBy';
        $param['input'] = 'payByInfo';
        $param['suffix'] = 'cost';

        self::checkRadio($app, $param);

        // check which carrier is used
        $param['field'] = 'carrier';
        $param['input'] = 'carriernote';
        $param['suffix'] = NULL;

        self::checkRadio($app, $param);

        // add "checked" to the checkboxes
        foreach ($app->checkBoxes as $field) {
            if (! empty($post[$field][$page])) {
                $app->inputValues[$field][$page] = 'checked';
            }
        }

        self::processGetValues($app, $post, $page);
    }

    /*
    ****************************************************************************
    */

    static function processGetValues($app, $post, $page)
    {
        // for the normal fill-in fields
        $specialFieldsOrig = ['carriernote','labelinfo'];
        $specialFields = array_merge($app->radioNCheck, $specialFieldsOrig);

        foreach ($app->inputFields as $field) {
            if (! in_array($field, $specialFields) && $field != 'statusID') {
                if ($app->isTruckOrderImport && $field == 'numberofcarton') {
                    $post[$field][$page] = 0;
                }

                $app->inputValues[$field][$page] =
                        getDefault($post[$field][$page]);
            }
        }

        $app->inputValues['vendor'][$page] = $post['vendor'][$page];
        $app->inputValues['statusID'][$page] = TRUE;
        self::menuLists($app, $page);
    }

    /*
    ****************************************************************************
    */

    static function menuLists($app, $page)
    {
        //choose user, vendor, shipping from and type
        foreach ($app->menus as $property => $index) {

            $target = isset($app->post[$index][$page]) ?
                $app->post[$index][$page] : $app->dbValues[$page][$index];

            $fieldKeys = array_keys($app->$property);

            foreach ($fieldKeys as $id) {
                 $app->menu[$index][$id][$page] = $id == $target ?
                    'selected' : NULL;
            }
        }
        //for dropdowns in the duplicated pages in Order Checkin
        if (isset ($app->post['buttonFlag']) &&
            $app->post['buttonFlag'] == 'duplicate'
        ) {
            self::processMenuLists($app);
        }
    }

    /*
    ****************************************************************************
    */

    static function processMenuLists($app)
    {
        $rowsCount = $app->post['duplicate'];

        for ($page = 1; $page <= $rowsCount; $page++) {

            foreach ($app->menus as $property => $index) {
                $target = $app->post[$index][0];

                foreach ($app->$property as $id => $row) {
                    $app->menu[$index][$id][$page] = $id == $target ?
                        'selected': NULL;
                }
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function orderNumbersCheck($dataCheck)
    {
        $app = $dataCheck['app'];
        $page = $dataCheck['page'];
        $checkField = $dataCheck['checkOrders'];
        $orders = $dataCheck['classes'];
        $clientID = $dataCheck['clientID'];

        $primary = [
            'assoc' => 'id',
            'field' => $orders->primaryKey
        ];

        foreach ($checkField as $field => $values) {

            $key = key($values);
            $value = reset($values);

            // empty values are being checked by another function
            if ($value) {

                $result = $orders->valid($value, $field, $primary, $clientID);

                $app->checkNumInDB[$page][$key] = $result['valid'];
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function checkInSubmittedValues($app, $data)
    {
        $page = $data['page'];
        $scanOrderNumber = $data['scanOrderNumber'];
        $clientOrderNumber = $data['clientOrderNumber'];
        $orders = $data['orders'];
        $clientID = $data['clientID'];

        $checkOrders = [
            'scanOrderNumber' => [
                'checkScanOrderNum' => $scanOrderNumber
            ],
            'clientordernumber' => [
                'checkClientOrderNum' => $clientOrderNumber
            ]
        ];

        self::orderNumbersCheck([
            'app' => $app,
            'page' => $page,
            'checkOrders' => $checkOrders,
            'clientID' => $clientID,
            'classes' => $orders

        ]);

        foreach ($app->checkEmptyFill as $fieldName) {
            if (! $app->inputValues[$fieldName][$page]) {
                $app->missingValues[$fieldName][$page] = TRUE;
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function checkOutSubmittedValues($app, $data)
    {
        $page = $data['page'];
        $scanOrderNumber = $data['scanOrderNumber'];
        $clientOrderNumber = $data['clientOrderNumber'];
        $orders = $data['orders'];

        $pickListColumn = $app->pickListColumn;
        $cartonContentLabelsColumn = $app->cartonContentLabelsColumn;
        $allowZero = $app->allowZero;

        $optionalFields = array_merge(
            $pickListColumn, $cartonContentLabelsColumn, $app->dbInoreFields
        );

        $mandatoryFields = array_diff($app->dbFields, $optionalFields);

        foreach ($mandatoryFields as $fieldName) {
            $input = trim($app->inputValues[$fieldName][$page]);

            if (in_array($fieldName, $allowZero) && $input == '0') {
                continue;
            }

            if (! $input) {
                $app->missingMandatoryValues[$fieldName][$page] = TRUE;
            }
        }

        // at least one checkbox in Pick List column has to be checked
        $pickListChecked = FALSE;
        foreach ($pickListColumn as $pickListField) {
            if ($app->inputValues[$pickListField][$page]) {
                $pickListChecked = TRUE;
            }
        }

        if (! $pickListChecked) {
            $app->missingValues['PickListColNoCheck'][$page] = TRUE;
        }

        // at least one checkbox in Carton Content Labels column has to be checked
        $isChecked = FALSE;
        foreach ($cartonContentLabelsColumn as $field) {
            if ($app->inputValues[$field][$page]) {
                $isChecked = TRUE;
            }
        }

        if (! $isChecked) {
            $app->missingValues['cartonLabelsColNoCheck'][$page] = TRUE;
        }
    }

    /*
    ****************************************************************************
    */

    static function checkSubmittedValues($app, $page)
    {
        $post = $app->post;

        $orders = new \tables\orders($app);

        $scanOrderNumber = $post['scanOrderNumber'][$page];
        $clientOrderNumber = trim($post['clientordernumber'][$page]);
        $clientID = getDefault($post['vendor'][$page]);

        if ($app->closedOrders[$scanOrderNumber]) {
            return;
        }

        $checkParams = [
            'page' => $page,
            'scanOrderNumber' => $scanOrderNumber,
            'clientOrderNumber' => $clientOrderNumber,
            'orders' => $orders,
            'clientID' => $clientID
        ];

        if ($app->checkType == self::CHECK_IN) {
            self::checkInSubmittedValues($app, $checkParams);
        } else {
            self::checkOutSubmittedValues($app, $checkParams);
        }

        self::processSubmittedValues($app, $page, $scanOrderNumber);
    }

    /*
    ****************************************************************************
    */

    static function processSubmittedValues($app, $page, $scanOrderNumber)
    {
        $post = $app->post;

        foreach ($app->noCheck as $noCheckField => $pair) {
            if (! isset($post[$pair[0]][$page]) && $post[$pair[1]][$page]) {
                $app->missingValues[$noCheckField][$page] = TRUE;
            }
        }

        foreach ($app->noFill as $noFillField => $pair) {
            if (isset($post[$pair[0]][$page]) && ! $post[$pair[1]][$page]) {
                $app->missingValues[$noFillField][$page] = TRUE;
            }
        }

        if (isset($post['label'][$page])
            && ! $app->inputValues['labelinfo'][$page]
        ) {

            $labelName = $app->inputValues['label'][$page];
            $app->missingValues[$labelName][$page] = TRUE;
        }

        if ($post['startshipdate'][$page] > $post['canceldate'][$page]) {
            $app->missingValues['startvscancel'][$page] = TRUE;
        }

        if (isset($app->checkNumInDB[$page]) && $app->checkNumInDB[$page]) {
            foreach ($app->checkNumInDB[$page] as $field => $result) {
                if ($result) {
                    $app->missingValues[$field][$page] = TRUE;
                }
            }
        }

        if (! isset($app->missingValues['scanOrderNumber'][$page])) {

            $scanOrderValid = self::checkScanOrderValid($app, $scanOrderNumber);

            if (! $scanOrderValid) {

                $app->missingValues['scanOrderValid'][$page] = TRUE;
                $app->scanOrderValid[$page] = TRUE;
            }
        }

        if ($scanOrderNumber) {
            if (isset($app->scancanOrderNumbers[$scanOrderNumber])) {
                $app->duplicateNumber['scanOrderNumber'][$page] = TRUE;
            }

            $app->scancanOrderNumbers[$scanOrderNumber] = TRUE;
        }

        foreach ($app->integerValuesOnly as $field => $caption) {
            $value = $post[$field][$page];
            if ($value && ! ctype_digit($value)) {
                $app->integerOnly[$field][$page] = TRUE;
            }
        }

        foreach ($app->nonUTFCheck as $field => $caption) {
            $value = $post[$field][$page];

            if ($value && \format\nonUTF::check($value)) {
                $app->nonUTF[$field][$page] = TRUE;
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function handleProducts($data)
    {
        $app = $data['app'];
        $page = $data['page'];
        $scanOrderNumber = $data['scanOrderNumber'];
        $orders = $data['orders'];
        $cartonsToSplit = $data['cartonsToSplit'];
        $processedCartons = $data['processedCartons'];
        $truckProducts = $data['truckProducts'];
        $ucc128 = $data['ucc128'];

        $tableData = $app->products[$scanOrderNumber] = [];

        $pageUpcs = getDefault($app->post['upc'][$page], []);
        $pageQuantities = getDefault($app->post['quantity'][$page]);

        $processedOrder = $app->closedOrders[$scanOrderNumber];

        if (! $processedOrder) {
            $pageUOMs = getDefault($app->post['uom'][$page], []);
            $pageCartonLocations = getDefault($app->post['cartonLocation'][$page], []);
            $pagePrefixes = getDefault($app->post['prefix'][$page], []);
            $pageSuffixes = getDefault($app->post['suffix'][$page], []);
        }

        for ($row = 0; $row < count($pageUpcs); $row++) {

            $tableData[$row] = [
                'upc' => $pageUpcs[$row],
                'quantity' => $pageQuantities[$row],
            ];

            if ($processedOrder) {
                return [
                    'processedCartons' => $processedCartons,
                    'continue' => TRUE,
                ];
            }

            $pageUOMs[$row] = getDefault($pageUOMs[$row], ['ANY UOM']);

            if (! in_array('ANY UOM', $pageUOMs[$row])) {
                $tableData[$row]['uom'] = $pageUOMs[$row];
            }

            if ($pageCartonLocations[$row] != 'ANY LOCATION') {
                $tableData[$row]['cartonLocation'] = $pageCartonLocations[$row];
            }

            if (getDefault($pagePrefixes[$row]) != 'ANY PREFIX') {
                $tableData[$row]['prefix'] = $pagePrefixes[$row];
            }

            if (getDefault($pageSuffixes[$row]) != 'ANY SUFFIX') {
                $tableData[$row]['suffix'] = $pageSuffixes[$row];
            }
        }

        $vendor = $app->post['vendor'][$page];

        $result = $orders->getSubmittedTableData([
            'orderNumber' => $scanOrderNumber,
            'vendor' => $vendor,
            'tableData' => $tableData,
        ]);

        if ($result['products']) {
            $app->products[$scanOrderNumber] = $result['products'];
        } else {

            $app->missingProducts[$scanOrderNumber] = TRUE;

            if (! $truckProducts) {
                // exit if missing both Master Cartons and Mixed Items Cartons
                return [
                    'processedCartons' => $processedCartons,
                    'continue' => TRUE,
                ];
            }
        }

        if ($result['productErrors']) {
            $app->productErrors[$scanOrderNumber] = $result['productErrors'];
        }

        $checkParams = [
            'products' => $app->products,
            'order' => $scanOrderNumber,
            'vendor' => $vendor,
            'shortageError' => TRUE,
            'cartonsToSplit' => $cartonsToSplit,
            'processedCartons' => $processedCartons,
            'ucc128' => $ucc128,
            'isTruckOrder' => $truckProducts ? FALSE : NULL,
        ];

        $checkResults = $orders->checkSubmittedTableData($checkParams);

        foreach ($checkResults['cartonsToSplit'] as $invID) {
            $cartonsToSplit[$invID] = TRUE;
        }

        if ($checkResults['orderProducts']) {
            $app->orderProducts += $checkResults['orderProducts'];
        }

        $truckOrderCheckResults = [
            'orderProducts' => [],
            'shortageProducts' => [],
            'productErrors' => [],
            'processedCartons' => [],
        ];

        if ($truckProducts) {

            $checkParams['products'] = $truckProducts;
            $checkParams['isTruckOrder'] = TRUE;

            // check inventory from the Mezzanine for a Truck Order
            $truckOrderCheckResults =
                    $orders->checkSubmittedTableData($checkParams);

            $truckOrderProducts = $truckOrderCheckResults['orderProducts'];

            foreach ($truckOrderProducts as $scanOrderNumber => $invIDs) {

                $masterCartons =
                        getDefault($app->orderProducts[$scanOrderNumber], []);

                $app->orderProducts[$scanOrderNumber] =
                        array_merge($masterCartons, $invIDs);
            }
        }

        if ($checkResults['shortageProducts']
         || $truckOrderCheckResults['shortageProducts']) {

            $app->shortageProducts[$scanOrderNumber][] = array_merge(
                    $checkResults['shortageProducts'],
                    $truckOrderCheckResults['shortageProducts']
            );
        }

        if ($checkResults['splitProducts']) {

            $app->splitProducts[$scanOrderNumber] =
                    $checkResults['splitProducts'];

        } elseif ($checkResults['productErrors']
               || $truckOrderCheckResults['productErrors']) {

            $app->productErrors[$scanOrderNumber] = array_merge(
                    $checkResults['productErrors'],
                    $truckOrderCheckResults['productErrors']
            );
        }

        $processedInventory = $checkResults['processedCartons'] +
                $truckOrderCheckResults['processedCartons'];

        return [
            'processedCartons' => $processedInventory,
            'continue' => FALSE,
        ];
    }

    /*
    ****************************************************************************
    */

    static function processWavePicks($params)
    {
        $wavePickProducts = $params['wavePickProducts'];
        $scanOrderNumber = $params['scanOrderNumber'];
        $value = $params['value'];
        $productKey = $params['productKey'];

        $productDescriptions = [];

        foreach ($wavePickProducts as $waveKey => $wavePickProduct) {
            if ($scanOrderNumber == $wavePickProduct['scanOrderNumber']
                && $value['upc'] == $wavePickProduct['upc']
            ) {
                $wavePickProducts[$waveKey]['pieces'] -=
                    $value['pieces'];

                $productDescriptions[$productKey]['pieces'] = 0;
            }
        }

        return [
            'wavePickProducts' => $wavePickProducts,
            'productDescriptions' => $productDescriptions,
        ];
    }

    /*
    ****************************************************************************
    */

    static function processCheckReprintWavePicks($param, $cartonIDs,
            $scanOrderNumber)
    {
        $app = $param['app'];
        $orders = new \tables\orders($app);
        $submittedOrders = $param['submittedOrders'];

        $productDescriptions = $orders->getProductDescription($cartonIDs);
        $wavePickProducts = $orders->getWavePickProducts($submittedOrders);
        $reprintWavePick = [];

        foreach ($productDescriptions as $productKey => $value) {

            $data = self::processWavePicks([
                'wavePickProducts' => $wavePickProducts,
                'scanOrderNumber' => $scanOrderNumber,
                'value' => $value,
                'productKey' => $productKey
            ]);

            $wavePickProducts = $data['wavePickProducts'];
            $productDescriptions = $data['productDescriptions'];
        }

        foreach ($productDescriptions as $value) {
            if ($value['pieces']) {
                // a discrepancy between reserved and submitted values is detected
                $reprintWavePick[$scanOrderNumber] = TRUE;
                break;
            }
        }

        if (isset($reprintWavePick[$scanOrderNumber])) {
            return $reprintWavePick;
        }

        foreach ($wavePickProducts as $wavePickProduct) {
            if ($wavePickProduct['scanOrderNumber'] == $scanOrderNumber
                && $wavePickProduct['pieces']
            ) {
                // new products to the order were introduced
                $reprintWavePick[$scanOrderNumber] = TRUE;
                break;
            }
        }

        return $reprintWavePick;
    }

    /*
    ****************************************************************************
    */

    static function checkReprintWavePicks($param)
    {

        $app = $param['app'];
        $orderProducts = $param['orderProducts'];
        $shortageProducts = $param['shortageProducts'];
        $reprintWavePick = [];

        foreach ($orderProducts as $scanOrderNumber => $products) {
            if (isset($shortageProducts[$scanOrderNumber])) {
                // do not check orders with shortages
                continue;
            }

            $cartonIDs = array_values($products);

            if (! $cartonIDs) {
                // no cartons were reserved for the order
                $reprintWavePick[$scanOrderNumber] = TRUE;
                continue;
            }

            $reprintWavePick = self::processCheckReprintWavePicks($param,
                $cartonIDs, $scanOrderNumber);

        }

        return $reprintWavePick;
    }

    /*
    ****************************************************************************
    */

    static function formSubmit($app, $orders, $orderNumbers, $updateOrders)
    {
        $result = [];

        $app->shortageProducts = $app->orderProducts
            = $cartonsToSplit = $processedCartons = [];

        $truckOrderWaves = new \tables\truckOrderWaves($app);
        $cartons = new \tables\inventory\cartons($app);

        $truckProducts = $truckOrderWaves->getTruckProducts($orderNumbers);

        foreach ($orderNumbers as $page => $scanOrderNumber) {

            self::checkSubmittedValues($app, $page);

            if (! $scanOrderNumber) {
                continue;
            }

            if (getDefault($app->post['orderCategory'][$page]) == 'regularOrder'
            && $truckProducts) {

                $truckOrderWaves->emptyTruckOrder([$scanOrderNumber]);
            }

            if (isset($app->post['upc'][$page]) || $truckProducts) {

                $results = self::handleProducts([
                    'app' => $app,
                    'page' => $page,
                    'scanOrderNumber' => $scanOrderNumber,
                    'orders' => $orders,
                    'cartonsToSplit' => $cartonsToSplit,
                    'processedCartons' => $processedCartons,
                    'truckProducts' => $truckProducts,
                    'ucc128' => $cartons->fields['ucc128']['select']
                ]);

                $processedCartons = $results['processedCartons'];

                if ($results['continue']) {
                    continue;
                }
            } else {
                $app->missingProducts[$scanOrderNumber] = TRUE;
            }
        }

        self::checkDuplicateClientOrderNumber($app);

        if ($app->checkType == 'Check-Out') {
            $app->reprintWavePick = self::checkReprintWavePicks([
                'app' => $app,
                'submittedOrders' => $orderNumbers,
                'orderProducts' => $app->orderProducts,
                'shortageProducts' => $app->shortageProducts
            ]);
        }

        if ($app->shortageProducts) {

            $orderKeys = array_flip($orderNumbers);

            $shortageOrders = array_keys($app->shortageProducts);

            foreach ($shortageOrders as $order) {

                $page = $orderKeys[$order];
                // not need to check pickID or split cartons for
                // orders that have product shortages
                unset($app->missingMandatoryValues['pickid'][$page]);
                unset($app->splitProducts[$order]);
            }

            if (isset($app->missingMandatoryValues['pickid'])
            && ! $app->missingMandatoryValues['pickid']) {

                // delete error message array itself if it is empty
                unset($app->missingMandatoryValues['pickid']);
            }
        }

        //check if all requirements are met. If yes, insert into database
        // products section is not mundatory in Order Check In page
        $onlineOrders = $orders->getOnlineOrderNumbers($orderNumbers);

        $count = 0;

        foreach ($orderNumbers as $orderNumber) {
            if (! isset($onlineOrders[$orderNumber])) {
                unset($app->missingMandatoryValues['first_name'][$count]);
            }

            $count++;
        }

        if (isset($app->missingMandatoryValues['first_name'])
         && ! $app->missingMandatoryValues['first_name']) {

            unset($app->missingMandatoryValues['first_name']);
        }

        if ($app->checkType == 'Check-In') {
            unset($app->missingMandatoryValues['first_name']);
        }

        if (! $app->duplicateNumber && ! $app->missingValues
        && ! $app->missingMandatoryValues && ! $app->integerOnly
        && ! $app->nonUTF && ! $app->isOnHold && ! $app->productErrors
        && ! $app->reprintWavePick && ! $app->splitProducts
        && ($app->checkType == 'Check-In' || ! $app->missingProducts)
        && ! $app->duplicateClientOrderNumber) {

            $app->batches = new \tables\batches($app);

            $app->nextBatchID = $app->batches->getNextID('order_batches');

            $batch = getDefault($app->post['order_batch'], NULL);
            $batchOrderNumbers = getDefault($app->post['batch_orderNumber'],
                    NULL);

            foreach ($app->post['vendor'] as $index => $vendor) {
                $app->vendorsArray[$vendor][] = $orderNumbers[$index];
            }

            if (! $batch) {
                self::insertDefaultBatches($app);
            }

            if (isset($app->post['order_batch'])) {

                $batches = array_combine($batchOrderNumbers, $batch);

                $params = [
                    'app' => $app,
                    'formCount' => $app->duplicate,
                    'batches' => $batches
                ];

                self::insertOrUpdateOrder($params);

            } elseif ($app->checkType == 'Check-Out') {

                $params = [
                    'app' => $app,
                    'formCount' => $app->duplicate,
                    'batches' => FALSE,
                    'orderNumbers' => getDefault($updateOrders, NULL)
                ];

                self::insertOrUpdateOrder($params);
            }

            $app->jsVars['skipCloseConfirm'] = TRUE;
        }
    }

    /*
    ****************************************************************************
    */

   static function insertCheckIn($app, $formCount, $batches, $checkBoxes)
    {
        $orderBatches = [];
        $insertFields = $app->dbFields;
        $fieldDcUserID = 'dcUserID';

        $key = array_search('statusID', $insertFields);

        if ($key !== FALSE) {
            // statusID will be created in updateStatuses() function
            unset($insertFields[$key]);
        }

        for ($page = 0; $page <= $formCount; $page++) {

            $tmpInsertFields = $insertFields;

            if (! $app->inputValues[$fieldDcUserID][$page]) {
                $key = array_search($fieldDcUserID, $tmpInsertFields);
                unset($tmpInsertFields[$key]);
            }

            $sql = self::insertNewOrderQuery($app, $tmpInsertFields);

            $param = self::processInsertFields(
                $app,
                $tmpInsertFields,
                $page,
                $checkBoxes
            );

            $scanOrderNumber = $app->inputValues['scanOrderNumber'][$page];
            $param[] = $batch = $batches[$scanOrderNumber];
            $query = $app->runQuery($sql, $param);
            $app->success[$scanOrderNumber] = $query ? TRUE : FALSE;

            $orderBatches[$batch][$scanOrderNumber] = NULL;
        }

        return $orderBatches;
    }

    /*
    ****************************************************************************
    */

    static function insertCheckOut($app, $formCount, $checkBoxes)
    {
        $app->beginTransaction();

        for ($page = 0; $page <= $formCount; $page++) {

            $scanOrderNumber = $app->inputValues['scanOrderNumber'][$page];

            if ($app->closedOrders[$scanOrderNumber]) {
                // skip orders that were Order Processing Check-Out
                $app->success[$scanOrderNumber] = TRUE;
                continue;
            }

            $param = $sets = [];

            foreach($app->dbFields as $field) {
                if ($field == 'statusID' || $field == 'scanOrderNumber') {
                    // statusID will be updated in updateStatuses() function
                    // clientordernumber and scanOrderNumber need no update
                    continue;
                }

                $sets[] = $field . ' = ?';

                $param[] = self::getNewValue([
                    'app' => $app,
                    'page' => $page,
                    'field' => $field,
                    'checkBoxes' => $checkBoxes,
                ]);
            }

            $param[] = $scanOrderNumber;

            $sql = 'UPDATE  neworder
                    SET     ' . implode(',', $sets) . '
                    WHERE   scanordernumber = ?';

            $app->success[$scanOrderNumber] = $app->runQuery($sql, $param);
        }

        $app->commit();
    }

    /*
    ****************************************************************************
    */

    static function getNewValue($data)
    {
        $app = $data['app'];
        $page = $data['page'];
        $field = $data['field'];
        $checkBoxes = $data['checkBoxes'];

        if (isset($checkBoxes[$field])) {
            return $app->inputValues[$field][$page] == 'checked' ? 1 : 0;
        } else {
            switch ($field) {
                case 'isVAS':
                    return $app->inputValues[$field][$page] == 'yesVAS' ? 1 : 0;
                default:
                    return $app->inputValues[$field][$page];
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function updateOrderStatus($param)
    {
        $app = $param['app'];
        $processed = $param['array'];
        $statusID = $param['statusID'];
        $field = getDefault($param['field'], 'statusID');

        order::getIDs($app, $processed);

        order::updateAndLogStatus([
            'statusID' => $statusID,
            'field' => $field,
            'tableClass' => new \tables\orders($app),
        ]);
    }

    /*
    ****************************************************************************
    */

    static function updateCheckIn($app, $errorStatuses, $orderBatches)
    {
        if (getDefault($app->shortageProducts)) {

            $status = \tables\orders::STATUS_ERROR;

            $statusID = $errorStatuses->getStatusID($status);

            self::updateOrderStatus([
                'app' => $app,
                'array' => array_keys($app->shortageProducts),
                'statusID' => $statusID,
                'field' => 'isError',
            ]);
        }

        $processed = array_diff_key($app->success, $app->shortageProducts);

        if ($processed) {

            $status = \tables\orders::STATUS_NO_ERROR;

            $statusID = $errorStatuses->getStatusID($status);

            self::updateOrderStatus([
                'app' => $app,
                'array' => array_keys($processed),
                'statusID' => $statusID,
                'field' => 'isError',
            ]);
        }

        // removing orders with empty products table
        foreach ($orderBatches as $batchKey => $scanOrderNumbers) {

            $orderKeys = array_keys($scanOrderNumbers);

            foreach ($orderKeys as $orderKey) {
                if (isset($app->missingProducts[$orderKey])) {
                    unset($orderBatches[$batchKey][$orderKey]);
                }
            }
        }

        foreach ($orderBatches as $batchKey => $scanOrderNumbers) {
            if (! $scanOrderNumbers) {
                unset($orderBatches[$batchKey]);
            }
        }

        return $orderBatches;
    }

    /*
    ****************************************************************************
    */

    static function insertOrUpdateOrder($params)
    {
        $app = $params['app'];
        $formCount = $params['formCount'];
        $batches = getDefault($params['batches']);

        $statuses = new \tables\statuses\orders($app);
        $wavePicks = new \tables\wavePicks($app);
        $truckOrderWaves = new \tables\truckOrderWaves($app);

        $orderBatches = [];

        $checkBoxes = array_flip($app->checkBoxes);

        if ($app->checkType == self::CHECK_IN) {
            $orderBatches = self::insertCheckIn($app,   $formCount, $batches, $checkBoxes);
        } else {

            $errorStatuses = new \tables\statuses\enoughInventory($app);

            self::insertCheckOut($app, $formCount, $checkBoxes);
            $orderBatches = self::updateCheckIn($app, $errorStatuses, $orderBatches);
        }

        $ordersToProcess = [];

        $successOrders = array_keys($app->success);

        foreach ($successOrders as $orderNumber) {
            if (! $app->closedOrders[$orderNumber]) {
                $ordersToProcess[] = $orderNumber;
            }
        }

        $status = $app->checkType == self::CHECK_IN ?
                \tables\orders::STATUS_ENTRY_CHECK_IN :
                \tables\orders::STATUS_ENTRY_CHECK_OUT;

        $statusID = $statuses->getStatusID($status);

        if ($ordersToProcess) {
            self::updateOrderStatus([
                'app' => $app,
                'array' => $ordersToProcess,
                'statusID' => $statusID,
            ]);
        }

        $truckOrders = array_keys($app->truckOrders);

        $result = $wavePicks->processOrderProducts([
            'orderBatches' => $orderBatches,
            'products' => $app->products,
            'orderProducts' => $app->orderProducts,
            'shortageProducts' => $app->shortageProducts,
            'isTruckOrder' => $truckOrders ? FALSE : NULL,
        ]);

        if (! $result['status']) {
            self::$errors = array_merge(self::$errors, $result['errors']);
        }

        if ($truckOrders) {

            $truckOrderWaves->submitMixedCartons($truckOrders);

            $result = $wavePicks->processOrderProducts([
                'orderBatches' => $orderBatches,
                'products' => $app->products,
                'orderProducts' => $app->orderProducts,
                'shortageProducts' => $app->shortageProducts,
                'isTruckOrder' => TRUE,
            ]);

            if (! $result['status']) {
                self::$errors = array_merge(self::$errors, $result['errors']);
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function insertDefaultBatches($app)
    {
        $orderBatches = new \tables\orderBatches($app);
        $dealSites = new \tables\dealSites($app);

        $dealSiteID = $dealSites->getWholesaleID();

        $app->beginTransaction();

        foreach ($app->vendorsArray as $vendor => $values) {

            $orderBatches->insertDefaultBatch($vendor, $dealSiteID);

            if (is_array($values)) {
                // one vendor has several orders
                $app->orderKeys = array_flip($values);
            } else {
                // one vendor has only one order
                $app->orderKeys[$values] = 0;
            }
        }

        $app->commit();
    }

    /*
    ****************************************************************************
    */

    static function checkScanOrderValid($app, $scanOrderNumber)
    {
        $sql = 'SELECT  CONCAT(
                            LPAD(userID, 4, 0),
                            assignNumber
                        ) AS barcode
                FROM    NewOrderLabel
                WHERE   CONCAT(
                            LPAD(userID, 4, 0),
                            assignNumber
                        ) = ?';

        $result = $app->queryResult($sql, [$scanOrderNumber]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    static function insertNewOrderQuery($app, $insertFields)
    {
        $fields = implode(', ', $insertFields);

        $qMarks = $app->getQMarkString($insertFields);

        $sql = 'INSERT INTO neworder (
                   ' . $fields . ',
                       order_batch
                    ) VALUES (' . $qMarks . ', ?)';

        return $sql;
    }

    /*
    ****************************************************************************
    */

    static function processInsertFields($app, $insertFields, $page, $checkBoxes)
    {
        $param = [];

        foreach($insertFields as $field) {
            $param[] = self::getNewValue([
                'app' => $app,
                'page' => $page,
                'field' => $field,
                'checkBoxes' => $checkBoxes,
            ]);
        }

        return $param;
    }

    /*
    ****************************************************************************
    */

    static function processPostData(&$app)
    {
        $orderProducts = $app->post['orderProducts'];

        unset($app->post['orderProducts']);

        $orderProducts = json_decode($orderProducts, TRUE);

        if (! isset($orderProducts['upc'])) {
            return;
        }

        foreach ($orderProducts as $key => $value) {
            $app->post[$key] = $value;
        }
    }

    /*
    ****************************************************************************
    */

    static function submitAddOrEditOrders($app)
    {
        $result = [
            'status' => FALSE,
            'msg' => 'Fail'
        ];

        self::init($app);

        self::processPostData($app);

        $app->checkType = $app->post['typeOrder'];

        $app->checkType == 'Check-Out' ?  1 : 0;

        $updateOrders = [];

        $orders = new \tables\orders($app);

        $scanNumbers = $app->postVar('scanOrderNumber', 'getDef', []);

        if ($app->checkType == 'Check-Out') {

            $orderIDs = json_decode($app->post['orderIDs'], 'array');

            $updateOrders = $orders->getOrderNumbersByID($orderIDs);

            $app->onlineOrders = $orders->getOnlineOrderNumbers($updateOrders);

            $app->dbValues = self::getOrderInfo($app, $updateOrders);

            $scanNumbers = array_column($app->dbValues, 'scanOrderNumber');

            self::checkClosedOrders($app, $orders, $updateOrders);
        }

        $user = new \tables\users($app);
        $vendors = new \tables\vendors($app);
        $orderType = new \tables\orders\orderTypes($app);
        $truckOrderWaves = new \tables\truckOrderWaves($app);
        $workOrderHeaders = new \tables\workOrders\workOrderHeaders($app);

        $app->user = $app->dcPerson = $user->get();
        $app->vendor = $vendors->get();
        $app->commodity = $orders->selectCommodity();
        $app->locationtable = $orders->selectShipFrom();
        $app->orderType = $orderType->get();

        $combineFields = array_merge(
                $app->dbFields, $app->checkBoxes, $app->radio, $app->label
        );

        $app->inputFields = array_unique($combineFields);
        $app->radioNCheck = array_merge($app->radio, $app->checkBoxes);

        $orderNumbers = $app->post['scanOrderNumber'];
        self::getOrdersStatusData($app, $orders, $orderNumbers);

        $app->workOrderNumbres =
                $workOrderHeaders->getByScanOrderNumbers($orderNumbers);

        $repeat = count($orderNumbers);

        $app->duplicate = $repeat - 1;

        //assign values to $app->inputValues
        for ($page = 0; $page < $repeat; $page++) {
            self::getValues($app, $page);
        }

        $truckOrdes = getDefault($orderNumbers, [0]);

        $app->truckOrders =
                $truckOrderWaves->getExistingTruckOrders($orderNumbers);

        self::formSubmit($app, $orders, $orderNumbers, $updateOrders);

        return self::processResponses($app);
    }

    /*
    ****************************************************************************
    */

    static function processResponses($app)
    {
        $result = [];

        if (isset($app->success) && $app->success) {
            $result['status'] = TRUE;
            $result['msg'] = self::getMessageSuccess($app);

        } else {
            $result = self::getResponseFail($app);
        }

        return $result;
    }

    /*
    ****************************************************************************
    */

    static function getMessageSuccess($app)
    {
        $result = '';

        foreach ($app->success as $orderNumber => $success) {
            if (getDefault($app->closedOrders[$orderNumber])) {

                $result .= 'Order # '.$orderNumber.' can not be modified<br>'
                        . 'This order has already been Order Processed <br>';

                continue;
            }

            if ($app->isOrderImport) {
                $action = 'import';
            } else {
                $action = $app->checkType == 'Check-In' ? 'creat' : 'updat';
            }

            $prefix = $success ? '' : 'Error '.$action.'ing ';
            $suffix = $success ? ' was successfully ' . $action . 'ed' : '';
            $result .= $prefix . 'Order # ' . $orderNumber . $suffix;

            if (isset($app->shortageProducts[$orderNumber])) {

                $result .= '<br>This order has been saved as an Error Order '
                        . 'due to lack of inventory';
            }

            $result .= '<br>';
        }

        return $result;
    }

    /*
    ****************************************************************************
    */

    static function getResponseFail($app)
    {
        $result = [
            'status' => FALSE,
            'msg' => ''
        ];

        if (isset($app->noShippingLane) && $app->noShippingLane) {
            return [
                'status' => FALSE,
                'msg' => self::$errors
            ];
        }

        if ($app->checkType == 'Check-In') {
            // products section is not mundatory in Order Check In page
            if ( ! $app->duplicateNumber && ! $app->missingValues
                && ! $app->missingMandatoryValues && ! $app->integerOnly
                && ! $app->nonUTF && ! $app->isOnHold
                && ! $app->productErrors
                && ! $app->reprintWavePick
                && ! $app->splitProducts
                && ! $app->duplicateClientOrderNumber
                && ($app->checkType == 'Check-In' || ! $app->missingProducts)
                ) {
                    return [
                        'status' => FALSE,
                        'code' => 1,
                        'vendorsArray' => $app->vendorsArray,
                        'nextBatchID' => $app->nextBatchID
                    ];
                }
        }

        for ($i = 0; $i <= $app->duplicate; $i++) {
            $params = [
                [
                    'errorFields' => $app->missingValues,
                    'fieldCaptions' => $app->checkFieldsInDB,
                    'formCount' => $i,
                    'errorMessage' => $app->errorMessages['checkFieldsInDB'],
                ],
                [
                    'errorFields' => $app->missingValues,
                    'fieldCaptions' => $app->checkFields,
                    'formCount' => $i,
                    'errorMessage' => $app->errorMessages['checkFields'],
                ],
                [
                    'errorFields' => $app->missingMandatoryValues,
                    'fieldCaptions' => $app->checkAllFields,
                    'formCount' => $i,
                    'errorMessage' => $app->errorMessages['checkAllFields'],
                ],
                [
                    'errorFields' => $app->nonUTF,
                    'fieldCaptions' => $app->nonUTFCheck,
                    'formCount' => $i,
                    'errorMessage' => $app->errorMessages['nonUTFCheck'],
                ],
                [
                    'errorFields' => $app->missingValues,
                    'fieldCaptions' => $app->checkFieldsInDB,
                    'formCount' => $i,
                    'errorMessage' => $app->errorMessages['checkFieldsInDB'],
                ],
                [
                    'errorFields' => $app->duplicateNumber,
                    'fieldCaptions' => $app->duplicateOrderNumbers,
                    'formCount' => $i,
                    'errorMessage' => $app->errorMessages['duplicateOrderNumbers'],
                ],
                [
                    'errorFields' => $app->duplicateClientOrderNumber,
                    'fieldCaptions' => $app->duplicateClientOrderNumbers,
                    'formCount' => $i,
                    'errorMessage' => $app->errorMessages['duplicateOrderNumbers'],
                ]
            ];

            self::errorOutput($params);

            $result['code'] = 2;
        }

        if ($app->splitProducts) {
            self::$errors = $app->splitProducts;
            $result['code'] = 3;
        }

        $result['msg'] = self::$errors;

        return $result;
    }

    /*
    ****************************************************************************
    */

    static function errorOutput($params)
    {
        foreach ($params as $data) {
            $errorFields = $data['errorFields'];
            $fieldCaptions = $data['fieldCaptions'];
            $formCount = $data['formCount'];
            $errorMessage = $data['errorMessage'];

            if ($errorFields) {
                foreach($fieldCaptions as $field => $caption) {
                    if (isset($errorFields[$field][$formCount])) {
                        self::$errors[$formCount][$field] = $caption . $errorMessage;
                    }
                }
            }
        }

        return self::$errors;
    }

    /*
    ****************************************************************************
    */

    static function checkDuplicateClientOrderNumber($app)
    {
        $vendorIDs = $clientOrderNumbers = [];

        for ($i = 0; $i <= $app->duplicate; $i++) {
            if (in_array($app->post['vendor'][$i], $vendorIDs)
                && in_array($app->post['clientordernumber'][$i], $clientOrderNumbers)) {
                $app->duplicateClientOrderNumber['clientOrderNumber'][$i] = TRUE;
            }

            $vendorIDs[] = $app->post['vendor'][$i];
            $clientOrderNumbers[] = $app->post['clientordernumber'][$i];
        }

    }

    /*
    ****************************************************************************
    */
}