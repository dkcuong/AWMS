<?php

namespace tables\onlineOrders;

use tables\locations;
use tables\statuses;

class onlineOrder
{
    public $isImport;

    private $app;
    private $importData;
    private $tableOnlineOrder;
    
    /*
    ****************************************************************************
    */

    public function __construct($params)
    {
        $this->app = $params['app'];
        $this->vendorID = $this->app->post['vendorID'];
        $this->isImport = $params['method'] == 'import';
        $this->dealSiteID = $this->app->post['dealSiteID'];
        $this->importData = $params['importData'];
        $this->tableOnlineOrder = $params['tableOnlineOrder'];
    }

    /*
    ****************************************************************************
    */

    public function findImportError()
    {
        if (! $this->isImport) {
            return;
        }
  
        $import = $this->importData;
        unset($import[1]);

        foreach ($import as $rowIndex => $rowData) {
            if (! array_filter($rowData)) {
                // skip the 1st and blank rows
                continue;
            }
            
            $this->tableOnlineOrder->checkRefID($rowData, $rowIndex, $this->isImport);
        }

        return $this->tableOnlineOrder->errors ? TRUE : FALSE;
    }

    /*
    ****************************************************************************
    */

    public function parseFields($fieldNames)
    {
        $fieldNames = array_map(function($field){
            $field = strToLower(trim($field));
            $field = str_replace([' ', '/'], '_', $field);
            switch ($field) {
                case 'shipping_first_name':
                    $field = 'first_name';
                    break;
                case 'shipping_last_name':
                    $field = 'last_name';
                    break;
                case 'order_id':
                    $field = 'clientordernumber';
                    break;
                default:
                    break;
            }
            return $field;
        }, $fieldNames);

        return $fieldNames;
    }

    /*
    ****************************************************************************
    */

    private function getOnlineOrderMissingField()
    {
        return [
            'shipment_tracking_id' => NULL,
            'shipment_sent_on' => NULL ,
            'shipment_cost' => NULL
        ];
    }

    /*
    ****************************************************************************
    */

    public function formatImportData($importData)
    {
        $data = [];

        $fieldNames = array_shift($importData);
        $header = $this->parseFields($fieldNames);
        $missingFields = $this->getOnlineOrderMissingField();

        foreach ($importData as $rowIndex => $rowData ) {
            if (! array_filter($rowData)) {
                // skip the 1-st and blank rows
                continue;
            }
            $dataItems = [];
            foreach ($rowData as $key => $item) {
                $field = $header[$key];
                $dataItems[$field] = $item;
                //$data[$rowIndex][$field] = $item;
            }
            $dataItems = array_merge($dataItems, $missingFields);

            $data[$rowIndex] = $dataItems;

        }

        return $data;
    }

    /*
    ****************************************************************************
    */

    public function createOnlineOrder()
    {
        $query = new onlineOrderQuery($this->app, $this->tableOnlineOrder);
        $importData = $this->formatImportData($this->importData);
        $nextLabelBatch = $this->tableOnlineOrder->getNextID('label_batches');
        $nextLabelID = $this->tableOnlineOrder->getNextID('neworderlabel');
        $batchNumber = $this->tableOnlineOrder->getNextID('order_batches');
        $status = $this->getOrderStatus();
        $location = $this->getVendorLocation();
        $obj = $this->tableOnlineOrder;

        $this->app->beginTransaction();
        $prevRefID = $orderNumber = NULL;

        foreach ($importData as $rowData) {

            $orderNumber = $prevRefID == $rowData['reference_id'] ? $orderNumber : 
                 $obj->createOnlineOrders($nextLabelBatch++, $nextLabelID++);
            
            $prevRefID = $rowData['reference_id'];
            
            $orderData = $rowData;
            $orderData['clientOrder'] = $rowData['clientordernumber'];
            $orderData['orderNumber'] = $orderNumber;
            $orderData['location'] = $location;
            $orderData['batchNumber'] = $batchNumber;
            $orderData['isErr'] = $status;

            $query->makeNewOrder($orderData);

            $rowData['scan_seldat_order_number'] = $orderNumber;
            $rowData['shipping_first_name'] = $rowData['first_name'];
            $rowData['shipping_last_name'] = $rowData['last_name'];

            $query->makeOnlineOrder($rowData);
        }
        $this->app->commit();

        // Create the batch order that has been assigned to these online orders
        $this->app->batches->create($this->vendorID, $this->dealSiteID);
    }

    /*
    ****************************************************************************
    */

    public function getVendorLocation()
    {
        $locations = new locations($this->app);
        $locInfo = $locations->getLocationfromVendor([$this->vendorID]);
        $location = getDefault($locInfo[$this->vendorID]['locationID'], NULL);

        return $location;
    }

    /*
    ****************************************************************************
    */

    public function getOrderStatus()
    {
        $statuses = new statuses($this->app);
        $status = $statuses->getStatusID('ENIN');
        return $status;
    }

    /*
    ****************************************************************************
    */

}