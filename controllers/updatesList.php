<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{
    function modifyUpdatesListController()
    {
        $type = getDefault($this->get['type']);

        $selectSize = 3;

        $versionObject = new \tables\releaseVersion($this);
        $subject =  $versionObject->get();
        $firstKey = key($subject);

        switch ($type) {
            case 'listFeature':
                $table = new \tables\updatesList($this);
                break;
            case 'releaseVersion':
                $table = new \tables\releaseVersion($this);
                break;
            default:
                die;
        }

        $this->modelName = getClass($table);

        $dtOptions = [
            'bFilter' => FALSE
        ];

        $ajax = new datatables\ajax($this);

        $ajax->output($table, $dtOptions);

        $searcher = new datatables\searcher($table);

        $editable = new datatables\editable($table);

        $searcher->createMultiSelectTable([
            'size' => $selectSize,
            'title' => 'Release Version',
            'idName' => 'versionID',
            'trigger' => TRUE,
            'subject' => $subject,
            'selected' => [$firstKey => TRUE],
            'fieldName' => 'versionName',
            'searchField' => 'versionName',
        ]);

        $this->addRows = isset($table->customInsert) ? $table->customInsert :
            NULL;

        if (isset($table->customAddRows)) {
            $table->customAddRows();
        } else if (isset($table->customInsert)){
            $editable->canAddRows();
        }

        $this->jsVars['urls']['updatesList'] =
            makeLink('updatesList', 'modify');

    }

    /*
    ****************************************************************************
    */

}