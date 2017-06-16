<?php

/*
********************************************************************************
* MODEL                                                                        *
********************************************************************************
*/

use \common\seldatContainers;

class model extends base
{
    public $acutualDimensions = ['length', 'width', 'height'];

    public $needForEachValues = ['uom', 'length', 'width', 'height', 'weight'];

    public $eachMeasurements = [
        'width' => 'eachWidth',
        'length' => 'eachLength',
        'height' => 'eachHeight',
        'weight' => 'eachWeight',
    ];

    public $containerValues = [];

    public $modify = FALSE;

    public $tableCells = [];

    public $editContainer = NULL;

    public $modifyRows = FALSE;

    public $modifyBatches = NULL;

    public $recNum = NULL;

    public $fileSubmitted = FALSE;

    public $allVendors = [];

    /*
    ****************************************************************************
    */

    function modelGetContainerInfo($editContainer)
    {
        $ajax = new datatables\ajax($this);
        $batches = new tables\inventory\batches($this);

        $ajax->addControllerSearchParams([
            'values' => [$editContainer],
            'field' => 'recNum'
        ]);

        $this->includeJS['js/datatables/editables.js'] = TRUE;

        $output = $ajax->output($batches, [
            'bFilter' => FALSE
        ]);

        $this->prevStyle = reset($output->params['data']);
    }

    /*
    ****************************************************************************
    */

    function getDBValues()
    {
        $this->modifyBatches = NULL;
        $this->modifyRows = -1;

        $cartons = new tables\inventory\cartons($this);

        $results = $cartons->getInventoryData($this->editContainer);

        if ($results) {
            foreach ($results as $result) {

                $this->modifyRows++;

                $this->modifyBatches[] = $result['batchnumber'];

                $this->restoreFormData([
                    'result' => $result,
                    'cartonsClass' => $cartons
                ]);
            }

            $this->modifyRows++;
        }
    }

    /*
    ****************************************************************************
    */

    function getNextRecNum()
    {
        $maxRecNumber = $this->getFieldMaxValue('inventory_containers', 'recNum');

        $isError = $maxRecNumber > 0 && $maxRecNumber < 10000001;

        $error = $isError ? 'Invalid Receiving Number' : FALSE;
        $recNum = $isError ?  FALSE : $maxRecNumber + 1;

        return [
            'error' => $error,
            'recNum' => $recNum,
        ];
    }

    /*
    ****************************************************************************
    */

    function restoreFormData($data)
    {
        $result = $data['result'];
        $cartons = $data['cartonsClass'];

        $isMetric = $this->post['measurementSystem'] == 'Metric';

        $line = $this->modifyRows;

        foreach (seldatContainers::$tableCells as $field => $cellInfo) {
            if ($field == 'rowNo'
            || $field == 'newUPC'
            || $field == 'categoryUPC'
            || $field == 'tableFunc'
            ) {
                continue;
            }

            $dimension = getDefault($cellInfo['dimension']);

            if ($isMetric && $dimension) {

                $isEach = getdefault($cellInfo['each']);
                $dimensionLimits = $cartons->measurements[$dimension];

                $divider = $dimensionLimits['convert'];
                $limits = $dimensionLimits['metric'];

                $minValue = $isEach ? 0.1 : $limits['min'];
                $maxValue = $limits['max'];

                $convertValue = round($result[$field] / $divider, 1);

                $this->post['tableData'][$line][$field] =
                        max($minValue, min($maxValue, $convertValue));
            } else {
                $this->post['tableData'][$line][$field] = $result[$field];
            }
        }
    }

    /*
    ****************************************************************************
    */

    function getMeasureAbbr($cellInfo, $measurementSystem)
    {
        if (isset($cellInfo['dimension'])) {
            if ($cellInfo['dimension'] == 'weight') {
                return $measurementSystem == 'Metric' ? '(KG)' : '(LBS)';
            } else {
                return $measurementSystem == 'Metric' ? '(CM)' : '(IN)';
            }
        }
    }

    /*
    ****************************************************************************
    */

    function displayTableData($row)
    {
        foreach ($this->tableCells as $key => $values) {
           $rowID = ' rowid="row-' . $row . '"';
           switch ($key) {
               case 'rowNo':
                   $value = getDefault($this->post['tableData'][$row]['categoryUPC'],
                           NULL); ?>

               <td class="firstCol">

                   <span class="idxCtn"><?php echo $row + 1; ?></span>

                   <input type="hidden" class="categoryUPC"
                          name="categoryUPC" value="<?php echo $value; ?>"
                          data-row-index="<?php echo $row; ?>" data-post>
               </td><?php

                   break;
               case 'categoryUPC':
                   break;
               case 'tableFunc':
                   //user can not remove incase modify container
                   $disable = $this->modifyRows > $row ?
                   ' disabled="disabled" ' .
                   'style="background-color: #DDD !important;"' : '';
                   ?>
               <td>
                   <input type="button"
                          class="removeRowButtons ui-icon ui-icon-trash"
                          value=""
                          <?php echo $rowID . $disable ?> />
                   <input type="button"
                          class="insertRowButtons ui-icon ui-icon-plus"
                          value=""
                          <?php echo $rowID . $disable ?> />
               </td>
                   <?php
                   break;
               case 'newUPC': ?>

               <td class="newUPC">
                   <input type="button" class="addButtons" value="Add">
               </td><?php

                   break;
               default:
                   $value = getDefault($this->post['tableData'][$row][$key], NULL);
                   // make "# CARTON" cell not editable when modifying a row
                   $styling = $key == 'carton' && $this->modifyRows > $row ?
                           'style="background-color: #DDD !important; '
                          .'color: #777" readonly' : NULL;
                   $rel = empty($values['inputRel']) ? NULL :
                       $values['inputRel']; ?>
               <td>
                   <input type="text" class="<?php echo $key ?>"
                          rel="<?php echo $rel ?>"
                          size="<?php echo $values['size']; ?>"
                          name="<?php echo $key; ?>" <?php echo $styling; ?>
                          value="<?php echo htmlspecialchars($value); ?>" data-post
                          data-row-index="<?php echo $row; ?>">
               </td><?php
                   break;
           }
        }
    }

    /*
    ****************************************************************************
    */

    function setUrlScanContainer()
    {
        $this->jsVars['urls']['updateStyleRows'] =
                customJSONLink('appJSON', 'updateStyleRows');
        $this->jsVars['urls']['autoSaveContainer'] =
                customJSONLink('appJSON', 'autoSaveContainer');
        $this->jsVars['urls']['seldatUPC'] =
                customJsonLink('appJSON', 'seldatUPC');
        $this->jsVars['urls']['checkSeldatUPC'] =
                customJSONLink('appJSON', 'checkSeldatUPC');
        $this->jsVars['urls']['duplicateUPC'] =
                customJSONLink('appJSON', 'duplicateUPC');
        $this->jsVars['urls']['generateBarcode'] =
                makeLink('seldatContainers', 'barcode');
        $this->jsVars['urls']['addCategoryUPC'] =
                makeLink('appJSON', 'addCategoryUPC');
        $this->jsVars['urls']['checkScanContainerCell'] =
                makeLink('appJSON', 'checkScanContainerCell');
        $this->jsVars['urls']['getReceivingNumber'] =
            customJSONLink('appJSON', 'getReceivingNumber');
        $this->jsVars['urls']['createReceiving'] =
            makeLink('receiving', 'create');
    }

    /*
    ****************************************************************************
    */

    function setIncludeJsScanContainer()
    {
        $this->includeJS['custom/js/common/formToArray.js'] = TRUE;
        $this->includeJS['js/jQuery/blocker.js'] = TRUE;

        $this->jsVars['skipCloseConfirm'] = FALSE;
        $this->jsVars['container'] = FALSE;
        $this->jsVars['eachMeasurements'] = $this->eachMeasurements;
        $this->jsVars['actualDimensions'] = $this->acutualDimensions;
        $this->jsVars['needForEachValues'] = $this->needForEachValues;
    }

    /*
    ****************************************************************************
    */

    function setValueDefaultScanContainer($useSessionRows = FALSE)
    {
        $upcs = new tables\inventory\upcs($this);
        $users = new tables\users($this);
        $measures = new tables\inventory\measure($this);

        $this->jsVars['modifyBatches'] = $this->modifyBatches;
        $this->jsVars['modifyRows'] = $this->modifyRows;

        $this->listCategoryUPCs = $upcs->listCategoryUPC();

        $this->userResults = $users->get();
        $this->accessUserID = access::getUserID();

        $this->accessUser = $this->userResults[$this->accessUserID]['lastFirst'];

        $this->measureDD = $measures->getDropdown();

        $this->post = getDefault($this->post, []);

        $this->vendorID = getDefault($this->post['vendorID'], NULL);
        $this->measureID = getDefault($this->post['measureID'], NULL);
        $this->measurementSystem = getDefault($this->post['measurementSystem'], NULL);
        $this->setAutoDate = getDefault($this->post['setAutoDate'],
                date('m/d/Y h:i:s a', time()));
        $this->receivingNumber = seldatContainers::getFieldNextValue('recNum',
                'inventory_containers', $this);

        $tableData = getDefault($this->post['tableData'], []);

        $this->setrow = $useSessionRows ? count($tableData) - 1 : 0;
        $this->setrow = max($this->setrow, $this->modifyRows - 1, 0);
    }

    /*
    ****************************************************************************
    */

    function setValueDefaultImportContainer()
    {
        $this->jsVars['urls']['checkSeldatUPC'] =
                customJSONLink('appJSON', 'checkImportSeldatUPC');
        $this->jsVars['urls']['checkScanContainerCell'] =
                makeLink('appJSON', 'checkImportContainerCell');
        $this->jsVars['urls']['importSeldatContainers'] =
                makeLink('seldatContainers', 'import');
        $this->jsVars['urls']['downloadBadUpcs'] =
                makeLink('seldatContainers', 'import', 'downLoadBadUpcs/1');

        $this->jsVars['modifyRows'] = FALSE;
        $this->modifyRows = FALSE;
        $this->modifyBatches = $this->recNum = NULL;

        $this->data = [];
        $this->isBadUpcs = FALSE;
    }

    /*
    ****************************************************************************
    */

    function generalContainerInfo()
    {?>
        <table id="containerInfo">
            <tr>
                <?php $containerValues = $this->containerValues;?>
                <td class="red">Receiving</td>
                <td>
                    <?php if ($containerValues) {?>
                    <input type="text" name="receiving" id="receiving"
                               value="<?php echo $containerValues['receiving']; ?>"
                               readonly placeholder=" (autocomplete)" data-post>
                    <?php } else {?>
                        <input type="text" name="receiving" id="receiving"
                           placeholder=" (autocomplete)" data-post>
                    <?php } ?>
                </td>
                <td class="red">Measurement System</td>
                <td> <?php
                    if ($containerValues) {
                        $measurementSystem = $containerValues['measurementSystem']; ?>
                        <input value="<?php echo $measurementSystem; ?>"
                               id="measurementSystem" name="measurementSystem"
                               readonly data-post>
                        <input value="<?php echo $containerValues['measureID']; ?>"
                               id="measureID" name="measureID" hidden data-post>
                    <?php } else { ?>
                        <select id="measureID" name="measureID" data-post>
                            <?php foreach ($this->measureDD as $id => $option) {
                                // US-Imperial is a default measurement system
                                $measurementSystem = $this->measureID ?
                                    $this->measurementSystem : 'US-Imperial';

                                if ($this->measureID) {
                                    $selected = $id == $this->measureID ? 'selected' :
                                        NULL;
                                } else {
                                    $selected = $option == 'US-Imperial' ? 'selected' :
                                        NULL;
                                } ?>

                                <option <?php echo $selected; ?> value="<?php echo $id;
                                ?>"><?php echo $option; ?></option>
                            <?php } ?>
                        </select>
                    <?php }?>
                </td>
            </tr>
            <tr>
                <td>Client Name:</td>
                <td id="client-name">

                    <?php echo array_key_exists('vendorName', $containerValues) ?
                            $containerValues['vendorName'] : NULL; ?>

                </td>
                <td>Reference #</td>
                <td id="reference-number">

                    <?php echo array_key_exists('ref', $containerValues) ?
                            $containerValues['ref'] : NULL; ?>

                </td>
            </tr>
            <tr>
                <td class="red">Container #</td>
                <td><?php

                    if ($containerValues) { ?>
                        <input id="container" name="container"
                               value="<?php echo $containerValues['container']; ?>"
                               readonly data-post>
                    <?php } else {
                        $container = getDefault($this->post['container'], NULL);

                        $readonly = isset($this->modifyBatches)
                            ? 'readonly ' : NULL; ?>
                        <input id="container" name="container" type="text"
                               value="<?php echo trim($container); ?>"
                               style="width: 170px;" maxlength=32
                            <?php echo $readonly; ?> data-post>
                    <?php } ?>

                </td><?php
                $userID = $containerValues ? $containerValues['userID'] :
                    $this->accessUserID; ?>

                <input id="userID" name="userID" type="hidden"
                       value="<?php echo $userID; ?>" data-post>
            </tr>
        </table><?php
    }

    /*
    ****************************************************************************
    */

}