<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function listAddPlatesController()
    {
        $labels = new tables\plates($this);

        $ajax = new datatables\ajax($this);

        $ajax->output($labels, [
            'order' => [0 => 'desc']
        ]);

        $users = new tables\users($this);

        new common\labelMaker($this, $labels, $users);
    }

    /*
    ****************************************************************************
    */

    function searchPlatesController()
    {
        $table = new tables\plates\tallies($this);

        $ajax = new datatables\ajax($this);

        $ajax->output($table, [
            'order' => [0 => 'desc']
        ]);

        new datatables\searcher($table);
    }

    /*
    ****************************************************************************
    */

    function displayPlatesController()
    {
        $getTerm = getDefault($this->get['term']);

        $printByPlate = isset($this->get['term']);

        if ($printByPlate) {
            $term = getDefault($this->post['term'], $getTerm);
        } else {

            $cartons = new \tables\inventory\cartons($this);

            $getOrderNumber = getDefault($this->get['order']);
            $orderNumber = getDefault($this->post['order'], $getOrderNumber);

            $order = is_array($orderNumber) ? $orderNumber 
                            : explode(',', $orderNumber);

            $term = $cartons->getProcessedPlatesByOrder($order);
        }

        $getLevel = getDefault($this->get['level']);
        $level = getDefault($this->post['level'], $getLevel);

        $displayLevel = $level ? $level : 'carton';

        $getSearch = getDefault($this->get['search']);
        $search = getDefault($this->post['search'], $getSearch);

        if (! $search || ! $term) {
            die('No plates found');
        }

        $labels = new labels\licensePlates();

        if ($printByPlate) {
            $term = json_decode($term);
        }

        $displayTerm = is_array($term) ? 'Mulitiple' : $term;

        $printAll = getDefault($this->post['printAll'], NULL);

        $labels->addLicensePlate([
            'db' => $this,
            'term' => $term,
            'search' => $search,
            'level'  => $displayLevel,
            'printAll' => $printAll,
            'fileName' => 'License_Plate_'.$search.'_'.$displayTerm,
        ]);
    }

    /*
    ****************************************************************************
    */

    function recordSheetsPlatesController()
    {
        $vendors = new tables\vendors($this);

        $this->vendorDD = $vendors->getDropdown('CONCAT(w.shortName, "_", vendorName)');

        $this->includeJS['js/jQuery/blocker.js'] = TRUE;

        $this->jsVars['urls']['addPalletSheets'] =
            customJSONLink('appJson', 'addPalletSheets');
    }

    /*
    ****************************************************************************
    */

    function displayLabelPlatesController()
    {
        $plate = getDefault($this->get['plate']);

        if (! $plate) {
            return;
        }

        barcodephp\create::display([
            'code' => 'BCGcode128',
            'filetype' => 'JPEG',
            'dpi' => 72,
            'scale' => 1,
            'rotation' => 0,
            'fontFamily' => 'Arial.ttf',
            'fontSize' => 12,
            'text' => $plate,
            'noText' => TRUE,
        ]);
    }

    /*
    ****************************************************************************
    */

}
