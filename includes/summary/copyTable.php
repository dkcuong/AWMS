<?php

namespace summary;

class copyTable extends model
{
    public $handle;
    
    const TIME_SELECT = FALSE;

    static $fieldTemplates = [
        'inventory_cartons_test' => [
            'id' => TRUE, 
            'batchID' => TRUE, 
            'cartonID' => TRUE, 
            'uom' => TRUE,
            'plate' => 'opt',
            'locID' => 'opt',
            'mLocID' => 'opt',
            'orderUserID' => 'opt',
            'orderID' => 'opt',
            'statusID' => TRUE,
            'mStatusID' => TRUE,
            'isSplit' => TRUE,
            'unSplit' => TRUE,
            'vendorCartonID' => 'opt',
            'rackDate' => TRUE,
        ],
        'ctn_sum_mk' => [
            'carton_id' => TRUE,
            'batch_id' => TRUE,
            'last_active' => TRUE,
            'uom' => TRUE,
        ],
        'ctn_sum_shp' => [
            'carton_id' => TRUE,
            'last_active' => TRUE,
            'batch_id' => TRUE,
            'cust_id' => TRUE,
            'vol' => TRUE,
            'uom' => TRUE,
        ],
        'ctn_sum_rec_dt' => [
            'carton_id' => TRUE,
            'rcv_dt' => TRUE,
        ],
        'ctn_sum_cntr_custs' => [
            'rcv_nbr' => TRUE,
            'cust_id' => TRUE,
        ],
        'ctn_sum_batch' => [
            'id' => TRUE,
            'recNum' => TRUE,
            'upcID' => TRUE,
            'prefix' => TRUE,
            'suffix' => TRUE,
            'height' => TRUE,
            'width' => TRUE,
            'length' => TRUE,
            'weight' => TRUE,
            'eachHeight' => TRUE,
            'eachWidth' => TRUE,
            'eachLength' => TRUE,
            'eachWeight' => TRUE,
            'initialCount' => TRUE,
        ]
    ];
    
    /*
    ****************************************************************************
    */

    static function init($summary=NULL)
    {
        $self = new static();
        
        if ($summary) {
            $self->db = $summary->getProp('db');
            $self->path = $summary->getProp('path');
        }

        return $self;
    }
        
    /*
    ****************************************************************************
    */
    
    function setFile($params)
    {
        if ($this->file) {
            return;
        }
        
        $file = $this->path.'/'.$params['csvFile'].'.csv';
        $this->file = str_replace('\\', '/', $file);
    }
        
    /*
    ****************************************************************************
    */
    
    function standard($params)
    {
        isset($params['timeMessage']) ? $this->timer() : NULL;
        
        $this->setFile($params);

        file_exists($this->file) ? unlink($this->file) : NULL;

        $this->db->runQuery('
            '.$params['sql'].'
            INTO OUTFILE "'.$this->file.'"
            FIELDS TERMINATED BY ","
            LINES TERMINATED BY "\n"
        ');

        isset($params['timeMessage']) ? 
            $this->timer($params['timeMessage'].': Getting') : NULL;
            
        $this->loadData($params);
        
        return [];
    }
    
    /*
    ****************************************************************************
    */

    function put($row)
    {
        foreach ($row as &$cell) {
            $cell = str_replace('\\', '\\\\', $cell);
        }

        fputcsv($this->handle, $row);
    }
    
    /*
    ****************************************************************************
    */
    
    static function getTemplate($name)
    {
        return array_keys(self::$fieldTemplates[$name]);
    }
    
    /*
    ****************************************************************************
    */
    
    function loadData($params)
    {
        isset($params['timeMessage']) ? $this->timer() : NULL;

        $this->setFile($params);

        $sql = 'TRUNCATE '.$params['targetTable'];
        getDefault($params['truncate']) ? $this->db->runQuery($sql) : NULL;

        $replace = isset($params['replace']) ? 'REPLACE' : NULL;
        
        // When using fputcsv the file is created on the code server and not 
        // the DB server
        $localFile = getDefault($params['local']) ? 'LOCAL' : NULL;
		
        $templateClause = self::addTemplate($params);

        $sql = 'LOAD DATA '.$localFile.' INFILE "'.$this->file.'"
                '.$replace.'
                INTO TABLE '.$params['targetTable'].'
                FIELDS TERMINATED BY "," 
                LINES TERMINATED BY "\n"
                '.$templateClause.'
                ';
        
        
        $this->db->runQuery($sql);
        
        isset($params['timeMessage']) ? 
            $this->timer($params['timeMessage'].': Writing') : NULL;
    }
    
    /*
    ****************************************************************************
    */
    
    static function addTemplate($params)
    {
        $nullSets = $fieldsDisplay = [];
        
        $targetTable = isset($params['otherTemplate']) ? 
            $params['otherTemplate'] : $params['targetTable'];
        
        $fields = isset($params['fields']) ? 
            $params['fields'] : self::$fieldTemplates[$targetTable];

        $fields or die('No Field Template for table '.$targetTable);
        foreach ($fields as $field => $status) {
            
            $fieldsDisplay[] = $status === 'opt' ? '@'.$field : $field;
            
            if ($status === 'opt') {
                $nullSets[] = $field.' = nullif(@'.$field.',"")';
            }
        }

        $sql = $fieldsDisplay ? 
            '('.implode(', ', $fieldsDisplay).')'.PHP_EOL : NULL;
        
        $sql .= $nullSets ? 'SET '.implode(', ', $nullSets) : NULL;
        
        return $sql;
    }    
}
