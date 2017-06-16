<?php

namespace tables;

class onlineOrderExports extends _default
{
    public $ajaxModel = 'onlineOrderExports';
        
    public $primaryKey = 'o.id';
    
    public $fields = [
        'scanordernumber' => [
            'display' => 'Order Number',
            'csvSkipExport' => TRUE,
            'noEdit' => TRUE,
        ],
        'batch_order' => [
            'select' => 'order_batch',
            'display' => 'Batch Order',
            'required' => TRUE,
            'isPositive' => TRUE,
            'noEdit' => TRUE,
        ],
        'order_id' => [
            'select' => 'exportOrderID',
            'display' => 'Order ID',
            'required' => TRUE,
            'isPositive' => TRUE,
            'noEdit' => TRUE,
            'exportFunction' => 'getExportLabelNo',
        ],
        'package_reference' => [
            'select' => 'reference_id',
            'display' => 'Package Reference',
            'required' => TRUE,
            'lengthLimit' => 50,
            'noEdit' => TRUE,
        ],
        'tracking' => [
            'select' => 'shipment_tracking_id',
            'display' => 'Tracking',
            'lengthLimit' => 20,
            'csvSkipExport' => TRUE,
            'noEdit' => TRUE,
        ],
        'from_company' => [
            'select' => 'c.companyName',
            'display' => 'From Company',
            'required' => TRUE,
            'lengthLimit' => 50,
        ],
        'from_name' => [
            'display' => 'From Name',
            'required' => TRUE,
            'lengthLimit' => 50,
        ],
        'from_address_1' => [
            'display' => 'From Address 1',
            'required' => TRUE,
            'lengthLimit' => 50,
        ],
        'from_address_2' => [
            'display' => 'From Address 2',
            'lengthLimit' => 50,
        ],
        'from_city' => [
            'display' => 'From City',
            'required' => TRUE,
            'lengthLimit' => 50,
        ],
        'from_state' => [
            'display' => 'From State',
            'required' => TRUE,
            'lengthLimit' => 2,
        ],
        'from_postal' => [
            'display' => 'From Postal',
            'required' => TRUE,
            'lengthLimit' => 10,
        ],
        'from_country' => [
            'select' => '"US"',
            'display' => 'From Country',
            'required' => TRUE,
            'lengthLimit' => 50,
        ],
        'from_phone' => [
            'display' => 'From Phone',
            'required' => TRUE,
            'lengthLimit' => 20,
        ],
        'from_email' => [
            'display' => 'From Email',
            'lengthLimit' => 50,
        ],
        'from_notify_on_shipment' => [
            'select' => 'IF(from_notify_on_shipment, "Yes", "No")',
            'display' => 'From Notify On Shipment',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'from_notify_on_shipment',
            'updateOverwrite' => TRUE,
            'isBoolean' => TRUE,
            'validation' => 'booleanInput',
            'validationArray' => TRUE,
        ],
        'from_notify_on_exception' => [
            'select' => 'IF(from_notify_on_exception, "Yes", "No")',
            'display' => 'From Notify On Exception',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'from_notify_on_exception',
            'updateOverwrite' => TRUE,
            'isBoolean' => TRUE,
            'validation' => 'booleanInput',
            'validationArray' => TRUE,
        ],
        'from_notify_on_delivery' => [
            'select' => 'IF(from_notify_on_delivery, "Yes", "No")',
            'display' => 'From Notify On Delivery',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'from_notify_on_delivery',
            'updateOverwrite' => TRUE,            
            'isBoolean' => TRUE,
            'validation' => 'booleanInput',
            'validationArray' => TRUE,
        ],
        'labelNo' => [
            'display' => 'Label #',
            'isNum' => 3,
            'csvSkipExport' => TRUE,
        ],
        'to_company' => [
            'select' => 'n.first_name',
            'display' => 'To Company',
            'required' => TRUE,
            'lengthLimit' => 50,
        ],
        'to_name' => [
            'select' => 'n.last_name',
            'display' => 'To Name',
            'required' => TRUE,
            'lengthLimit' => 50,
        ],
        'to_address_1' => [
            'display' => 'To Address 1',
            'required' => TRUE,
            'lengthLimit' => 50,
        ],
        'to_address_2' => [
            'display' => 'To Address 2',
            'lengthLimit' => 50,
        ],
        'to_address_3' => [
            'display' => 'To Address 3',
            'lengthLimit' => 50,
        ],
        'to_city' => [
            'display' => 'To City',
            'required' => TRUE,
            'lengthLimit' => 50,
        ],
        'to_state' => [
            'display' => 'To State',
            'required' => TRUE,
            'lengthLimit' => 2,
        ],
        'to_postal' => [
            'display' => 'To Postal',
            'required' => TRUE,
            'lengthLimit' => 10,
        ],
        'to_country' => [
            'display' => 'To Country',
            'required' => TRUE,
            'lengthLimit' => 50,
        ],
        'to_phone' => [
            'display' => 'To Phone',
            'required' => TRUE,
            'lengthLimit' => 20,
        ],
        'to_email' => [
            'display' => 'To Email',
            'lengthLimit' => 50,
        ],
        'to_notify_on_shipment' => [
            'select' => 'IF(to_notify_on_shipment, "Yes", "No")',
            'display' => 'To Notify On Shipment',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'to_notify_on_shipment',
            'updateOverwrite' => TRUE,            
            'isBoolean' => TRUE,
            'validation' => 'booleanInput',
            'validationArray' => TRUE,
        ],
        'to_notify_on_exception' => [
            'select' => 'IF(to_notify_on_exception, "Yes", "No")',
            'display' => 'To Notify On Exception',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'to_notify_on_exception',
            'updateOverwrite' => TRUE,            
            'isBoolean' => TRUE,
            'validation' => 'booleanInput',
            'validationArray' => TRUE,
        ],
        'to_notify_on_delivery' => [
            'select' => 'IF(to_notify_on_delivery, "Yes", "No")',
            'display' => 'To Notify On Delivery',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'to_notify_on_delivery',
            'updateOverwrite' => TRUE,            
            'isBoolean' => TRUE,
            'validation' => 'booleanInput',
            'validationArray' => TRUE,
        ],
        'signature' => [            
            'select' => 'sg.displayName',
            'display' => 'Signature',
            'searcherDD' => 'onlineOrders\exportsSignatures',
            'ddField' => 'sg.displayName',
            'update' => 'signatureID',
            'updateOverwrite' => TRUE,
            'validation' => 'signature',
            'validationArray' => TRUE,
            'lengthLimit' => 50,
        ],
        'saturday_delivery' => [
            'select' => 'IF(saturday_delivery, "Yes", "No")',
            'display' => 'Saturday Delivery',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'saturday_delivery',
            'updateOverwrite' => TRUE,            
            'isBoolean' => TRUE,
            'validation' => 'booleanInput',
            'validationArray' => TRUE,
        ],
        'reference_1' => [
            'display' => 'Reference 1',
            'lengthLimit' => 20,
        ],
        'reference_2' => [
            'display' => 'Reference 2',
            'lengthLimit' => 20,
        ],
        'provider' => [
            'select' => 'pr.displayName',
            'display' => 'Provider',
            'searcherDD' => 'onlineOrders\exportsProviders',
            'ddField' => 'pr.displayName',
            'update' => 'o.providerID',
            'updateOverwrite' => TRUE,
            'required' => TRUE,
            'validation' => 'provider',
            'validationArray' => TRUE,
            'lengthLimit' => 20,
        ],
        'package_type' => [
            'select' => 'pc.shortName',
            'display' => 'Package Type',
            'searcherDD' => 'onlineOrders\exportsPackages',
            'ddField' => 'CONCAT_WS(" - ", pr.displayName, pc.shortName, pc.displayName)',
            'update' => 'packageID',
            'updateOverwrite' => TRUE,
            'csvExportSkipTrailSpace' => TRUE,
            'required' => TRUE,
            'validation' => 'packageType',
            'validationArray' => TRUE,
            'lengthLimit' => 50,
        ],
        'service' => [
            'select' => 'sr.shortName',
            'display' => 'Service',
            'searcherDD' => 'onlineOrders\exportsServices',
            'ddField' => 'CONCAT_WS(" - ", pr.displayName, sr.shortName, sr.displayName)',
            'update' => 'serviceID',
            'updateOverwrite' => TRUE,
            'csvExportSkipTrailSpace' => TRUE,
            'required' => TRUE,
            'validation' => 'service',
            'validationArray' => TRUE,
            'lengthLimit' => 50,
        ],
        'bill_to' => [
            'select' => 'bl.displayName',
            'display' => 'Bill To',
            'searcherDD' => 'onlineOrders\exportsBillTo',
            'ddField' => 'bl.displayName',
            'update' => 'billToID',
            'updateOverwrite' => TRUE,
            'validation' => 'billTo',
            'validationArray' => TRUE,
            'lengthLimit' => 50,
        ],
        '3rd_party_acc_num' => [
            'select' => 'third_party_acc_num',
            'display' => '3rd Party ACC Num',
            'lengthLimit' => 20,
        ],
        '3rd_party_postal_code' => [
            'select' => 'third_party_postal_code',
            'display' => '3rd Party Postal Code',
            'lengthLimit' => 10,
        ],
        '3rd_party_country_code' => [
            'select' => 'third_party_country_code',
            'display' => '3rd Party Country Code',
            'lengthLimit' => 50,
        ],
        'package_weight' => [
            'display' => 'Package Weight LB',
            'required' => TRUE,
            'isNum' => 10,
            'isDecimal' => 1,
            'isPositive' => TRUE,
        ],
        'package_length' => [
            'display' => 'Package Length',
            'isNum' => 10,
            'isDecimal' => 2,
            'isPositive' => TRUE,
        ],
        'package_width' => [
            'display' => 'Package Width',
            'isNum' => 10,
            'isDecimal' => 2,
            'isPositive' => TRUE,
        ],
        'package_height' => [
            'display' => 'Package Height',
            'isNum' => 10,
            'isDecimal' => 2,
            'isPositive' => TRUE,
        ],
        'package_insured_value' => [
            'display' => 'Package Insured Value',
            'isNum' => 10,
            'isDecimal' => 2,
        ],
        'can_be_merged' => [
            'select' => 'IF(can_be_merged, "Yes", "No")',
            'display' => 'Can be merged',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'can_be_merged',
            'updateOverwrite' => TRUE,            
            'isBoolean' => TRUE,
            'validation' => 'booleanInput',
            'validationArray' => TRUE,
        ],
    ];

    public $table = 'online_orders_exports o
        JOIN      online_orders_exports_orders eo ON eo.exportOrderID = o.id
        JOIN      online_orders oo ON oo.id = eo.onlineOrderID
        JOIN      neworder n ON n.scanordernumber = oo.SCAN_SELDAT_ORDER_NUMBER
        JOIN      order_batches b ON b.id = n.order_batch
        JOIN      vendors v ON v.id = b.vendorID
        JOIN      warehouses w ON w.id = v.warehouseID
        JOIN      company_address c ON c.id = w.locationID
        LEFT JOIN online_orders_exports_providers pr ON pr.id = o.providerID
        LEFT JOIN online_orders_exports_packages pc ON pc.id = o.packageID
        LEFT JOIN online_orders_exports_services sr ON sr.id = o.serviceID
        LEFT JOIN online_orders_exports_signatures sg ON sg.id = o.signatureID
        LEFT JOIN online_orders_exports_bill_to bl ON bl.id = o.billToID
        ';
    
    public $groupBy = 'o.id';
    
    public $badRows = [];

    public $errorFile = [
        'missingOrderID' => [
            'captionSuffix' => 'with Order ID(s) that are not present in DB'
        ],
    ];
    
    public $csvExportHandle = 'csvExportHandle';

    public $exportErrors = [];

    public $exportSumFields = [
        'package_weight',
        'package_length',
        'package_width',
        'package_height',
        'package_insured_value'        
    ];

    public $skipLabelCheck = [
        'scanordernumber',
        'batch_order',
        'order_id',
        'package_reference',
        'tracking',
        'labelNo'
    ];

    /*
    ****************************************************************************
    */

    function getCarrierExports($orderIDs=[], $assoc=FALSE, $carrier='UPS')
    {   
        $qMarks = $this->app->getQMarkString($orderIDs);
        $clause = $orderIDs ? 'onlineOrderID IN ('.$qMarks.')' : 1;
        $selectFields = $this->getSelectFields();

        $sql = 'SELECT   ' . $selectFields . '
                FROM     ' . $this->table . '
                WHERE    ' . $clause;

        $result = $assoc ? $this->queryResults($sql, $orderIDs) :
            $this->app->ajaxQueryResults($sql, $orderIDs);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function checkOrderExport($orderIDs)
    {
        $qMark = $this->app->getQMarkString($orderIDs);
        
        $sql = 'SELECT onlineOrderID
                FROM   online_orders_exports_orders
                WHERE  onlineOrderID IN ('.$qMark.')';

        $result = $this->app->queryResults($sql, $orderIDs);

        return $result;
    }
    
    /*
    ****************************************************************************
    */
    
    function checkExportTable($data)
    {
        $tableData = $data['tableData'];
        $exportsSignatures = $data['exportsSignatures'];
        $exportsProviders = $data['exportsProviders'];
        $exportsPackages = $data['exportsPackages'];
        $exportsServices = $data['exportsServices'];
        $exportsBillTo = $data['exportsBillTo'];
        
        $signatureValues = $exportsSignatures->getActiveValues('Yes', 'displayName');
        $providerValues = $exportsProviders->getActiveValues('Yes', 'displayName');
        $billToValues = $exportsBillTo->getActiveValues('Yes', 'displayName');
        $packageValues = $exportsPackages->getActiveValues();
        $serviceValues = $exportsServices->getActiveValues();
        
        $params = [
            'signatureKeys' => array_flip($signatureValues),
            'providerKeys' => array_flip($providerValues),
            'billToKeys' => array_flip($billToValues),
            'packageKeys' => $this->getUniqueType($packageValues),
            'serviceKeys' => $this->getUniqueType($serviceValues)
        ];

        $titleKeys = array_keys($this->fields);

        $lableValues = [];

        $labelKey = array_search('labelNo', $titleKeys);

        $skipCheckFields = array_merge($this->skipLabelCheck, $this->exportSumFields);

        $skipCheckKeys = array_flip($skipCheckFields);

        $fieldKeys = array_keys($this->fields);

        $fieldNames = array_flip($fieldKeys);

        $skipFields = array_intersect_key($fieldNames, $skipCheckKeys);

        $skipKeys = array_flip($skipFields);

        foreach ($tableData as $row => $rowData) {

            $provider = NULL;

            $label = $rowData[$labelKey] == 'Click to edit' ? 0 : 
                    $rowData[$labelKey];

            $checkRow = array_diff_key($rowData, $skipKeys);
            
            if (isset($lableValues[$label])) {
                
                $diff = array_diff($checkRow, $lableValues[$label]);

                $this->checkMismatchValues([
                    'diff' => $diff, 
                    'fieldKeys' => $fieldKeys, 
                    'label' => $label,
                    'row' => $row
                ]);

            } else {
                $lableValues[$label] = $checkRow;
            }
            
            foreach ($titleKeys as $key => $value) {

                if (isset($this->fields[$value]['csvSkipExport'])) {
                    continue;
                }

                $text = $rowData[$key] == 'Click to edit' ? NULL : $rowData[$key];
                
                $provider = $value == 'provider' ? $text : $provider;                

                $params['value'] = $value;
                $params['text'] = $text;
                $params['row'] = $row;
                $params['provider'] = $provider;

                $this->checkCellValue($params);
            }
        }

        return $this->exportErrors;
    }
    
    /*
    ************************************************************************
    */    
    
    function checkMismatchValues($data)
    {        
        $diff = $data['diff'];
        $fieldKeys = $data['fieldKeys'];
        $label = $data['label'];
        $row = $data['row'];

        $errorFields = array_intersect_key($fieldKeys, $diff);

        $errorFieldKeys = array_flip($errorFields);

        $errorFieldData = array_intersect_key($this->fields, $errorFieldKeys);

        foreach ($errorFieldData as $value) {
            $this->exportErrors[] = $value['display'] . ' at row ' . $row
                    . ' for Label # ' . $label . ' mismatch previous value for'
                    . ' the label';
        }
    }

    /*
    ************************************************************************
    */    

    function checkCellValue($data)
    {        
        $signatureKeys = $data['signatureKeys'];
        $providerKeys = $data['providerKeys'];
        $billToKeys = $data['billToKeys'];
        $packageKeys = $data['packageKeys'];
        $serviceKeys = $data['serviceKeys'];
        $value = $data['value'];
        $text = $data['text'];
        $row = $data['row'];
        $provider = $data['provider'];

        $caption = $this->fields[$value]['display'];
        
        switch ($value) {
            case 'provider':
                if ($text && ! isset($providerKeys[$text])) {
                    $this->exportErrors[] = 'Provider at row ' . $row 
                            . ' is invalid';
                }

                $provider = $text;

                break;
            case 'package':
                if ($text && ! isset($packageKeys[$provider][$text])) {
                    $this->exportErrors[] = 'Package at row ' . $row 
                            . ' is invalid';
                }

                break;
            case 'service':
                if ($text && ! isset($serviceKeys[$provider][$text])) {
                    $this->exportErrors[] = 'Service at row ' . $row 
                            . ' is invalid';
                }

                break;
            case 'signature':
                if ($text && ! isset($signatureKeys[$text])) {
                    $this->exportErrors[] = 'Signature at row ' . $row 
                            . ' is invalid';
                }

                break;
            case 'bill_to':
                if ($text && ! isset($billToKeys[$text])) {
                    $this->exportErrors[] = 'Bill To at row ' . $row 
                            . ' is invalid';
                }

                break;
            case 'from_country':
            case 'to_country':

                $upperText = strtoupper($text);

                if ($upperText != 'US') {
                    $this->exportErrors[] = $caption . ' at row ' . $row 
                            . ' does not equal to "US". Check country code';
                }

                break;
            default:
                break;
        }
    }
    
    /*
    ************************************************************************
    */    
    
    function getUniqueType($values)
    {
        $typeValues = [];
        
        foreach ($values as $value) {
            
            $provider = $value['providerID'];
            $code = $value['shortName'];
            
            $typeValues[$provider][$code] = $value['displayName'];
        }
        
        return $typeValues;
     }
    
    /*
    ************************************************************************
    */    
    
    function booleanInput($input)
    {
        $values = [
            'yes', 
            'no',
        ];

        $data = $this->acceptedValues($values, $input);

        return $data;
    }
    
    /*
    ****************************************************************************
    */
 
    function provider($input)
    {
        $this->provider = strtoupper($input['input']);

        $result = $input ? $this->acceptedValues($this->providers, $input) :
            FALSE;

        return $result;
    }
    
    /*
    ****************************************************************************
    */
 
    function signature($input)
    {
        $result = $input ? $this->acceptedValues($this->signatures, $input) :
            FALSE;

        return $result;
    }    
    
    /*
    ****************************************************************************
    */
 
    function packageType($input)
    {
        $allValues = $values = [];
        
        foreach ($this->packages as $provider => $packages) {
            if (strtoupper($provider) == $this->provider) {
                $values = $packages;
            }            
            // do not use array_merge because it changes keys;            
            $allValues += $packages;
        }
        
        if (! $values) {
            $values = $allValues;
        }
        
        $result = $this->acceptedValues($values, $input);

        return $result;
    }
   
    /*
    ****************************************************************************
    */
 
    function service($input)
    {
        $allValues = $values = [];
        
        foreach ($this->services as $provider => $services) {
            if (strtoupper($provider) == $this->provider) {
                $values = $services;
            }            
            // do not use array_merge because it changes keys;            
            $allValues += $services;
        }
        
        if (! $values) {
            $values = $allValues;
        }

        $result = $this->acceptedValues($values, $input);

        return $result;
    }
   
    /*
    ****************************************************************************
    */

    function billTo($input)
    {
        $result = $input ? $this->acceptedValues($this->billTo, $input) :
            FALSE;

        return $result;
    }
   
    /*
    ****************************************************************************
    */

    function acceptedValues($values, $data)
    {
        $input = trim($data['input']);
        $valid = FALSE;
        $descriptions = NULL;
        // checking if any key is not numeric. Associative array assumes descriptions
        $isDescription = (bool)count(array_filter(array_keys($values), 'is_string'));

        foreach ($values as $key => $value) {           
            $compare = $isDescription ? $key : $value;

            $key = $isDescription ? $key . ' - ' : NULL;
            $valid = $valid ? $valid : \strtolower($compare) === \strtolower($input);
            $descriptions .= '<br>' . $key . $value;
        }
        
        if (! $valid) {
            return '<br><strong>Accepted values:</strong>' . $descriptions;
        }
        return FALSE;
    }
   
    /*
    ****************************************************************************
    */

    function valueKeys($values, $field='displayName')
    {
        foreach ($values as $key => $value) {
            
            $fieldValue = strtolower($value[$field]);
            
            $valueKeys[$fieldValue] = $key;
        }
        
        return $valueKeys;
    }

    /*
    ****************************************************************************
    */    
    
    function insertFile()
    {       
        $this->errors = $this->badRows = $submittedOrderIDs = [];
        $this->provider = NULL;
        $this->thirdParty = [
            '3rd_party_acc_num' => 0,
            '3rd_party_postal_code' => 0,
            '3rd_party_country_code' => 0,
        ];

        $exportsSignatures = new \tables\onlineOrders\exportsSignatures($this->app);
        $exportsProviders = new \tables\onlineOrders\exportsProviders($this->app);
        $exportsPackages = new \tables\onlineOrders\exportsPackages($this->app);
        $exportsServices = new \tables\onlineOrders\exportsServices($this->app);
        $exportsBillTo = new \tables\onlineOrders\exportsBillTo($this->app);
        
        $signatureValues = $exportsSignatures->getActiveValues('Yes');
        $providerValues = $exportsProviders->getActiveValues('Yes');
        $packageValues = $exportsPackages->getActiveValues();
        $serviceValues = $exportsServices->getActiveValues();
        $billToValues = $exportsBillTo->getActiveValues('Yes');

        $signatures = $this->valueKeys($signatureValues);
        $providers = $this->valueKeys($providerValues);
        $packages = $this->valueKeys($packageValues, 'shortName');
        $services = $this->valueKeys($serviceValues, 'shortName');
        $billTos = $this->valueKeys($billToValues);

        $this->signatures = array_column($signatureValues, 'displayName');
        $this->providers = array_column($providerValues, 'displayName');
        $this->packages = $this->getUniqueType($packageValues);
        $this->services = $this->getUniqueType($serviceValues);
        $this->billTo = array_column($billToValues, 'displayName');
        
        $this->ignoredIndexes = $this->inputNames = [];

        foreach ($this->importData as $rowIndex => $rowData) {
            if ($rowIndex == 1) {
                
                $result = $this->handleTitleRow($rowData);
                
                $rowData = $result['rowData'];
                $sets = $result['sets'];
                
                if ($this->errors) {
                    return;
                }

                $sql = 'UPDATE online_orders_exports 
                        SET ' . implode(',', $sets) . ' 
                        WHERE id = ?';

                $updateSql = 'UPDATE online_orders oo
                              JOIN   online_orders_exports_orders oe 
                                  ON oe.onlineOrderID = oo.id
                              SET    shipment_tracking_id = ?
                              WHERE  exportOrderID = ?';

                unset($rowData[$this->orderBatchKey]);

                $rowData[$this->packageReferenceKey] = 'reference_id';
                $rowData[$this->thirdPartyAccNumKey] = 'third_party_acc_num';
                $rowData[$this->thirdPartyPostalCodeKey] = 'third_party_postal_code';
                $rowData[$this->thirdPartyCountryCodeKey] = 'third_party_country_code';

                $failSQL = 'INSERT INTO online_orders_fails_update (
                                ' . implode(',', $rowData) . '
                            ) VALUES (
                                ' . $this->app->getQMarkString($rowData) . '
                            )';
                continue;
            }

            // No blank rows
            if (! \array_filter($rowData)) {
                $this->emptyRows[$rowIndex] = TRUE;
                continue;
            }

            if (! getDefault($rowData[$this->orderIDKey])) {
                $this->errors['missingReqs'][$rowIndex][] = 'Order ID';
            } else {
                $submittedOrderIDs[] = $rowData[$this->orderIDKey];
            }
        }

        if ($this->errors) {
            return;
        }

        $importedOrderData = $this->getImportedOrderData($submittedOrderIDs);

        $indices = array_column($importedOrderData, 'index');

        $existingOrderIDs = array_unique($indices);

        $missingOrders = array_diff($submittedOrderIDs, $existingOrderIDs);

        $orderTotals = $this->getOrderTotals($importedOrderData);

        foreach ($this->importData as $rowIndex => $rowData) {
            if ($rowIndex == 1 || isset($this->emptyRows[$rowIndex])) {
                // skip first row
                continue;
            }

            $results = \excel\importer::checkCellErrors([
                'model' => $this,
                'rowData' => $rowData,
                'rowIndex' => $rowIndex,
            ]);
            
            $errors = $results['errors'];
            $rowData = $results['rowData'];

        }

        if ($missingOrders) {
            $errors = TRUE;

            $this->errors['missingOrderID'] = array_flip($missingOrders);
        }

        $this->app->beginTransaction();

        foreach ($this->importData as $rowIndex => $rowData) {
            if ($rowIndex == 1 || isset($this->emptyRows[$rowIndex])) {
                // skip first row
                continue;
            }

            foreach ($rowData as $key => $input) {
                if (isset($this->booleanIndexes[$key])) {
                    $rowData[$key] = strtolower($input) == 'yes' ? 1 : 0;
                }
                
                if (getDefault($this->numeric[$key])) {
                    $decimalPart = getDefault($this->decimal[$key], 0);
                    
                    if ($decimalPart) {
                        $decimal = $decimalPart + 1;
                    }
                    
                    $integerPart = $this->numeric[$key] - $decimal;
                    $max = pow(10, $integerPart) - 1 / pow(10, $decimalPart);

                    $rowData[$key] = max(0, min($rowData[$key], $max));
                }
            }

            unset($rowData[$this->orderBatchKey]);

            if (! $errors) {
                
                $label = $rowData[$this->orderIDKey];
                $tracking = $rowData[$this->trackingKey];
                
                unset($rowData[$this->orderIDKey]);
                unset($rowData[$this->packageReferenceKey]);
                unset($rowData[$this->trackingKey]);
                
                $signature = trim(strtolower($rowData[$this->signatureKey]));
                $rowData[$this->signatureKey] = $signatures[$signature];
                
                $provider = trim(strtolower($rowData[$this->providerKey]));
                $rowData[$this->providerKey] = $providers[$provider];

                $package = trim(strtolower($rowData[$this->packageKey]));
                if (isset($packages[$package])) {
                    $rowData[$this->packageKey] = $packages[$package];                    
                }

                $service = trim(strtolower($rowData[$this->serviceKey]));
                if (isset($services[$service])) {
                    $rowData[$this->serviceKey] = $services[$service];
                }
                                
                $billTo = trim(strtolower($rowData[$this->billToKey]));
                
                if (! $billTo) {
                    $billTo = 'none required';
                }

                $rowData[$this->billToKey] = $billTos[$billTo];

                $this->updateExportTable([
                    'sql' => $sql,
                    'updateSql' => $updateSql,
                    'tracking' => $tracking,
                    'rowData' => $rowData,
                    'orderTotals' => $orderTotals,
                    'label' => $label,
                    'importedOrderData' => $importedOrderData
                ]);
                                
            } else {
                $params = array_values($rowData);
                $this->app->runQuery($failSQL, $params);
            }
        }

        $this->app->commit();

        if (! isset($rowData)) {
            // the file has proper extension, but actually is not an Excel file
            $this->errors['wrongType'] = TRUE;
            return;
        }
    }

    /*
    ****************************************************************************
    */

     function handleTitleRow($rowData)
    {
        $sets = [];

        foreach ($rowData as $key => $display) {
            $submittedDisplay = strToLower(trim($display));
            // replace spaces with underscorse for fields
            $display = str_replace([' ', '/'], '_', $submittedDisplay);

            switch ($display) {
                case 'batch_order':
                    $this->orderBatchKey = $key;
                    break;
                case 'order_id':
                    $this->orderIDKey = $key;
                    break;
                case 'package_reference':
                    $this->packageReferenceKey = $key;
                    break;
                case 'tracking':
                    $this->trackingKey = $key;
                    break;
                case 'signature':
                    $this->signatureKey = $key;
                    $sets[] = 'signatureID = ?';
                    break;
                case 'provider':
                    $this->providerKey = $key;
                    $sets[] = 'providerID = ?';
                    break;
                case 'package_type':
                    $this->packageKey = $key;
                    $sets[] = 'packageID = ?';
                    break;
                case 'service':
                    $this->serviceKey = $key;
                    $sets[] = 'serviceID = ?';
                    break;
                case 'bill_to':
                    $this->billToKey = $key;
                    $sets[] = 'billToID = ?';
                    break;
                case '3rd_party_acc_num':
                    $this->thirdPartyAccNumKey = $key;
                    $sets[] = 'third_party_acc_num = ?';
                    break;
                case '3rd_party_postal_code':
                    $this->thirdPartyPostalCodeKey = $key;
                    $sets[] = 'third_party_postal_code = ?';
                    break;
                case '3rd_party_country_code':
                    $this->thirdPartyCountryCodeKey = $key;
                    $sets[] = 'third_party_country_code = ?';
                    break;
                case 'package_weight':
                    $this->packageWeightKey = $key;
                    $sets[] = $display.' = ?';
                    break;
                case 'package_length':
                    $this->packageLengthKey = $key;
                    $sets[] = $display.' = ?';
                    break;
                case 'package_width':
                    $this->packageWidthKey = $key;
                    $sets[] = $display.' = ?';
                    break;
                case 'package_height':
                    $this->packageHeightKey = $key;
                    $sets[] = $display.' = ?';
                    break;
                case 'package_insured_value':
                    $this->packageInsuredValueKey = $key;
                    $sets[] = $display.' = ?';
                    break;
                default:
                    $sets[] = $display.' = ?';
                    break;
            }

            $rowData[$key] = trim($display);

            if (isset($this->fields[$display]['isDecimal'])) {
                $this->decimal[$key] = $this->fields[$display]['isDecimal'];
            }
            
            if (isset($this->fields[$display]['isNum'])) {
                $this->numeric[$key] = $this->fields[$display]['isNum'];
            }

            \excel\importer::indexArrayFill([
                'model' => $this,
                'display' => $display,
                'key' => $key,
                'rowData' => $rowData,
            ]);

            if (isset($this->thirdParty[$display])) {
                $this->thirdParty[$display] = $key;
            }         
        }

        \excel\importer::checkTableErrors($this);

        return [
            'rowData' => $rowData,
            'sets' => $sets,
         ];
    }
    
    /*
    ****************************************************************************
    */

    function getImportedOrderData($exportIDs)
    {
        if (! $exportIDs) {
             return [];
        }

        $labelLength = pow(10, $this->fields['labelNo']['isNum']);

        $clause = 'order_batch * ' . $labelLength . ' + labelNo';

        $qMarks = $this->app->getQMarkString($exportIDs);

        $sql = 'SELECT    o.id, ' . $clause . ' AS `index`,
                          ' . implode(',', $this->exportSumFields) . '
                FROM      online_orders_exports o
                JOIN      online_orders_exports_orders eo ON eo.exportOrderID = o.id
                JOIN      online_orders oo ON oo.id = eo.onlineOrderID 
                JOIN      neworder n ON n.scanordernumber = oo.SCAN_SELDAT_ORDER_NUMBER
                WHERE     ' . $clause . ' IN (' . $qMarks . ')
                ORDER BY  order_batch,
                          labelNo';

        $results = $this->app->queryResults($sql, $exportIDs);

        return $results;
    }
    
    /*
    ****************************************************************************
    */

    function importSuccess()
    { ?> 
        <br>
        <div style="border: 1px #9d9 solid; background: #e9ffe9;" class="blockDisplay">
            Your file has been imported successfully!
        </div>
                
        <?php
    }
    
    /*
    ************************************************************************
    */
    
    function getExportLabelNo($data)
    {
        $titleKeys = array_keys($this->fields);
        
        $batchKey = array_search('batch_order', $titleKeys);
        $labelKey = array_search('labelNo', $titleKeys);
        
        $label = getDefault($data[$labelKey], 0);

        $labelLength = pow(10, $this->fields['labelNo']['isNum']);
        
        return $data[$batchKey] * $labelLength + $label;
    }
    
    /*
    ************************************************************************
    */
    
    function csvExportHandle($data)
    {
        $titleKeys = array_keys($this->fields);

        foreach ($this->exportSumFields as $field) {
            $rowKeys[] = array_search($field, $titleKeys);
        }
        
        $labelKey = array_search('labelNo', $titleKeys);
        
        $return = [];
        
        foreach ($data as $row) {
            
            $labelKey = array_search('labelNo', $titleKeys);
            
            $label = $row[$labelKey];

            if (! isset($return[$label])) {

                $return[$label] = $row;
                
                continue;
            }

            foreach ($rowKeys as $key) {
                $return[$label][$key] += $row[$key];
            }
        }

        return $return;
    }

    /*
    ************************************************************************
    */

    function getOrderTotals($orderData)
    {
        $totals = [];
        
        foreach ($orderData as $orderID => $values) {

            $index = $values['index'];
            
            $totals[$index]['orderIDs'][] = $orderID;

            foreach ($this->exportSumFields as $field) {
                $totals[$index]['measure'][$field] = 
                        getDefault($totals[$index]['measure'][$field], 0) + 
                        $values[$field];
                $totals[$index]['orderCount'] = 
                        getDefault($totals[$index]['orderCount'], 0) + 1;
            }
        }

        return $totals;
    }

    /*
    ************************************************************************
    */

    function updateExportTable($data)
    {
        $sql = $data['sql'];
        $updateSql = $data['updateSql'];
        $tracking = $data['tracking'];
        $rowData = $data['rowData'];
        $orderTotals = $data['orderTotals'];
        $label = $data['label'];
        $importedOrderData = $data['importedOrderData'];

        $fields = $this->fields;

        $weightRound = getDefault($fields['package_weight']['isDecimal'], 0);
        $lengthRound = getDefault($fields['package_length']['isDecimal'], 0);
        $widthRound = getDefault($fields['package_width']['isDecimal'], 0);
        $heightRound = getDefault($fields['package_height']['isDecimal'], 0);
        $valueRound = getDefault($fields['package_insured_value']['isDecimal'], 0);

        $weightKey = $this->packageWeightKey;
        $lengthKey = $this->packageLengthKey;
        $widthKey = $this->packageWidthKey;
        $heightKey = $this->packageHeightKey;
        $insuredValueKey = $this->packageInsuredValueKey;

        $remnant['weight'] = $importWeight = $rowData[$weightKey];
        $remnant['length'] = $importLength = $rowData[$lengthKey];
        $remnant['width'] = $importWidth = $rowData[$widthKey];
        $remnant['height'] = $importHeight = $rowData[$heightKey];
        $remnant['insuredValue'] = $importValue = $rowData[$insuredValueKey];
        $remnant['orderCount'] = $orderTotals[$label]['orderCount'];

        foreach ($orderTotals[$label]['orderIDs'] as $orderID) {

            $measure = $orderTotals[$label]['measure'];
            $orderData = $importedOrderData[$orderID];

            if ($remnant['orderCount'] > 1) {

                $originalWeight = $measure['package_weight'];
                $originalLength = $measure['package_length'];
                $originalWidth = $measure['package_width'];
                $originalHeight = $measure['package_height'];
                $originalValue = $measure['package_insured_value'];

                $orderWeight = $orderData['package_weight'];
                $orderLength = $orderData['package_length'];
                $orderWidth = $orderData['package_width'];
                $orderHeight = $orderData['package_height'];
                $orderValue = $orderData['package_insured_value'];

                $rawWeight = ! $originalWeight ? 0 : 
                        $importWeight / $originalWeight * $orderWeight;
                $rawLength = ! $originalLength ? 0 : 
                        $importLength / $originalLength * $orderLength;
                $rawWidth = ! $originalWidth ? 0 : 
                        $importWidth / $originalWidth * $orderWidth;
                $rawHeight = ! $originalHeight ? 0 : 
                        $importHeight / $originalHeight * $orderHeight;
                $rawInsuredValue = ! $originalValue ? 0 : 
                        $importValue / $originalValue * $orderValue;

                $rowData[$weightKey] = round($rawWeight, $weightRound);
                $rowData[$lengthKey] = round($rawLength, $lengthRound);
                $rowData[$widthKey] = round($rawWidth, $widthRound);
                $rowData[$heightKey] = round($rawHeight, $heightRound);
                $rowData[$insuredValueKey] = round($rawInsuredValue, $valueRound);

                $remnant['weight'] -= $rowData[$weightKey];
                $remnant['length'] -= $rowData[$lengthKey];
                $remnant['width'] -= $rowData[$widthKey];
                $remnant['height'] -= $rowData[$heightKey];
                $remnant['insuredValue'] -= $rowData[$insuredValueKey];

                $remnant['orderCount'] --;
                
            } else {                        

                $rowData[$weightKey] = $remnant['weight'];
                $rowData[$lengthKey] = $remnant['length'];
                $rowData[$widthKey] = $remnant['width'];
                $rowData[$heightKey] = $remnant['height'];
                $rowData[$insuredValueKey] = $remnant['insuredValue'];                        
            }

            $params = array_values($rowData);

            $params[] = $orderID;

            $this->app->runQuery($sql, $params);

            // updating shipment_tracking_id field in online_orders table
            $this->app->runQuery($updateSql, [$tracking, $orderID]);
        }

        return $orderTotals;
    }

    /*
    ************************************************************************
    */

}