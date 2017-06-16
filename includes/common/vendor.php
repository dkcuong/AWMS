<?php

namespace common;

class vendor
{
 
    /*
    ****************************************************************************
    */
    
    static function getStringConditionForClientByVendor($app) 
    {
        $result = 1;

        $arrVendorID = \users\groups::commonClientLookUp($app);
        
        if ($arrVendorID) {
            $arrVendorID = array_keys($arrVendorID);
            
            $result = 'v.id IN(' .implode(',', $arrVendorID). ')';
        } else {
            $result = 0;
        }
        return $result;
    }

    /*
    ****************************************************************************
    */

    static function addConditionByVendor($app, &$model) 
    {
        $isClient = \access::isClient($app);
        
        if ($isClient) {
            $clause = getDefault($model->where, 1);

            $strCondition = self::getStringConditionForClientByVendor($app);

            $model->where = $clause .' AND '. $strCondition;
        }
    }
    
    /*
    ****************************************************************************
    */
    
    static function createTable($app) 
    {
        $table = new \tables\customer\customerContact($app);

        $app->jsVars['urls']['customContacts'] = $table->getAjaxSource();
        
        $app->modelName = getClass($table);        
                
        // Export Datatalbe
        $ajax = new \datatables\ajax($app);

        $ajax->output($table, [
            'ajaxPost' => TRUE,
            'order' => [0 => 'desc'],
        ]);
            
        $editable = new \datatables\editable($table);

        $editable->canAddRows();
        
        $app->costsLink = makeLink('costs', 'clients');
    }
    
    /*
    ****************************************************************************
    */
    
}