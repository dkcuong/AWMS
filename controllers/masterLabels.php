<?php

class controller extends template
{
    /*
    ****************************************************************************
    */

    function listMasterLabelsController()
    {
        $print = getDefault($this->get['type']) == 'print';

        $table = $print ? new tables\printMasterLabels($this)
            :  new tables\masterLabels($this);

        $fields = array_keys($table->fields);

        $fieldKeys = array_flip($fields);

        $firstSortColumn = $print ? $fieldKeys['batchNumber'] :
            $fieldKeys['container'];

        $secondSortColumn = $fieldKeys['setDate'];

        $linkField = $print ? 'batchNumber' : 'barcode';

        $method = $print ? 'printMasterLabels' : 'masterLabels';

        $this->jsVars['columnNumbers'][$linkField] = $fieldKeys[$linkField];

        $this->jsVars['urls']['print'] = makeLink('masterLabels', 'print');

        $this->modelName = getClass($table);

        $ajax = new datatables\ajax($this);

        $table->commonMysqlFilter('oneMonth', $this, $ajax);

        $customDT = [
            'order' => [
                $firstSortColumn => 'desc',
                $secondSortColumn => 'asc'
            ],
            'bFilter' => FALSE,
        ];

        $dtStructure = $ajax->output($table, $customDT);

        $this->jsVars['dataTables'][$method] = $dtStructure->params;

        new datatables\searcher($table);
    }

    /*
    ****************************************************************************
    */

    function printMasterLabelsController()
    {
        $cartons = new \tables\inventory\cartons($this);

        $target = isset($this->get['batch']) ? 'batchID' : 'barcode';
        
        $ucc128 = $cartons->fields['ucc128']['select'];

        $inactive = \tables\inventory\cartons::STATUS_INACTIVE;
        $shipped = \tables\inventory\cartons::STATUS_SHIPPED;

        $sql = 'SELECT    ca.id,
                          ' . $ucc128 . ' AS ucc128,
                          p.sku,
                          prefix,
                          upc,
                          color,
                          size,
                          batchID,
                          uom,
                          vendorID,
                          cartonID,
                          b.upcID,
                          locID,
                          plate,
                          l.displayName AS location,
                          co.name AS container,
                          co.recNum AS containerRecNum
                FROM      masterlabel ma
                JOIN      inventory_batches b ON b.id = ma.batchNumber
                JOIN      inventory_cartons ca ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      locations l ON l.id = ca.locID
                JOIN      upcs p ON p.id = b.upcID
                JOIN      statuses s ON s.id = ca.statusID
                WHERE     ' . $target . ' = ?
                AND       s.shortName NOT IN (?, ?)
                AND       NOT isSplit
                AND       NOT unSplit
                ';

        $param = isset($this->get['batch']) ? $this->get['batch'] : 
            $this->get['label'];

        $results = $this->queryResults($sql, [$param, $inactive, $shipped]);

        if ($results) {
            labels\create::forCartons([
                'db' => $this,
                'labels' => $results,
            ]);
        } else {
            die('No Barcodes Found');
        }
    }

    /*
    ****************************************************************************
    */
}