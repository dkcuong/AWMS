<?php

namespace datatables;

class customClauses
{
    
    /*
    ****************************************************************************
    */

    static function callback($params)
    {
        $index = $params['index'];
        $searchType = $params['searchType'];
        $clauseGroups = $params['clauseGroups'];
        
        switch ($searchType) {
            case 'ucc128':
                return [
                    'andOrs' => 'and',
                    'searchTypes' => 'co.vendorID',
                    'searchValues' => 
                        substr($clauseGroups['searchValues'][$index], 0, 5 ),
                ];
                default:
                    die('Custom Clause Not Found');
        }
        
    }

}