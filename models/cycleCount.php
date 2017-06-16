<?php

/*
********************************************************************************
* MODEL                                                                        *
********************************************************************************
*/

class model extends base
{
    public $defaultShowData = 100;
    public $errors = [];
    public $success = [];

    /*
    ****************************************************************************
    */

    function customAddRows($app)
    {

        if (! $this->activateEdit()) {
            // Set default values for editable add row HTML
            $this->searcherAddRowButton = '';
            $this->searcherAddRowFormHTML = '';

            return;
        }

        $type = trim(strtolower($this->data['type']));
        ob_start(); ?>

        <form id="formAddNewRow" action="#" title="Add

            <?php echo $app->displaySingle; ?>">

            <div id="addRowNotice"></div>
            <input type="hidden" name="cycleID"
                   value="<?php echo $this->cycleID ?>">
            <input type="hidden" name="hasSizeColor"
                   value="<?php echo $this->data['bySizeColor'] ?>">
            <input type="hidden" id="warehouse-id" name="warehouseID"
                   value="<?php echo $this->data['whs_id'] ?>">
            <input type="hidden" id="cycle-count-by-uom" name="cycleCountByOUM"
                   value="<?php echo $this->data['cycle_count_by_uom'] ?>">
            <input type="hidden" name="cycleType"
                   value="<?php echo $this->data['type']?>">
            <input type="hidden" name="cycleDate"
                   value="<?php echo $this->data['created']?>">
            <table>
                <?php if ($type == 'cs') {?>

                    <td colspan="2">
                        <input type="hidden" name="customer" id="customer-input"
                               value="<?php echo $this->data['vnd_id']; ?>"/>
                    </td>

                    <?php

                } else {
                    $warehouseID = getDefault($this->data['whs_id']);?>

                    <td colspan="2">
                        <?php echo $this->vendor->getVendorDropdown($warehouseID)?>
                    </td>

                <?php }

                $this->formAddNewSKU($app, $type); ?>

            </table>
        </form>

        <?php $this->searcherAddRowFormHTML = ob_get_clean();

        ob_start(); ?>

        <a href="#" id="btnAddNewRow" class="add_row">
            Add <?php echo $app->displaySingle;?>
        </a>

        <?php $this->searcherAddRowButton = ob_get_clean();

    }

    /*
    ****************************************************************************
    */

    function activateEdit()
    {
        $editable = isset($this->get['editable']);

        $admin = \access::required([
            'app'          => $this,
            'terminal'     => false,
            'accessLevels' => 'admin',
        ]);

        return $admin && $editable ? true : false;
    }

    /*
    ****************************************************************************
    */

    function getDropdown($field, $info)
    {
        // If searcherDD is set to TRUE use searcherDD values
        if ($info['searcherDD'] === true) {
            return $this->dropdowns[$field] =
                    $this->jsVars['searcherDDs'][$field];
        }

        $modelName = 'tables\\' . $info['searcherDD'];
        $ddField = getDefault($info['ddField'], 'displayName');

        if (isset($this->dropdowns[$field])) {
            return $this->dropdowns[$field];
        }

        $model = new $modelName($this);

        $this->dropdowns[$field] = $model->getDropdown($ddField);

        if (getDefault($info['canEmptyFieldValue'])) {
            array_unshift($this->dropdowns[$field], 'Empty Value');
        }

        return $this->dropdowns[$field];
    }

    /*
    ****************************************************************************
    */

    function addClickEventOnDatatable($model)
    {
        $dtName = $model->dtName;
        $columns = $this->jsVars['dataTables'][$dtName]['columns'];

        foreach ($columns as $index => $info) {
            if (isset($info['class'])) {
                $this->jsVars['editables'][$dtName]['aoColumns']
                        [$index]['event'] = 'click';
            }
        }
    }

    /*
    ****************************************************************************
    */

    function setDefaultForAutocompleteLinkCycle($app, $model)
    {
        $index = 0;
        foreach ($model->fields as $info) {
            if (isset($info['autocompleteLink'])) {
                $this->jsVars['urls']['autocomplete'][$index] =
                    $info['autocompleteLink'] . 'warehouseID=' .
                    $app->data['whs_id'] . '&';
            }
            $index++;
        }
    }

    /*
    ****************************************************************************
    */

    private function formAddNewSKU($app, $type)
    {
        $rel = 0;
        foreach ($app->fields as $field => $info) {
            if (isset($info['insertDefaultValue'])) { ?>

                <input type="hidden" rel="<?php echo $rel++; ?>">

                <?php continue;
            }

            if (isset($info['noEdit']) && ! isset($info['canAdd'])) { ?>

                <input type="hidden" rel="<?php echo $rel++; ?>"
                       name="<?php echo $field; ?>" value="0">

                <?php continue;
            }

            if (isset($info['allowNull'])) { ?>

                <input type="hidden" rel="<?php echo $rel++; ?>"
                       name="<?php echo $field; ?>" value="">

                <?php continue;
            } ?>

            <tr>
                <td class="noWrap"><?php echo $info['display']; ?></td>

                <?php $required = isset($info['optional']) ?
                    NULL : 'class="required"';
                $autocomplete = isset($info['autocomplete']) ?
                        'placeholder="(autocomplete)"' : NULL;

                if (isset($info['searcherDD'])) {
                    $dropdown = $this->getDropdown($field, $info); ?>

                    <td><select rel="<?php echo $rel++; ?>"
                                name="<?php echo $field; ?>">

                    <?php foreach ($dropdown as $value => $display) { ?>

                        <option value="<?php echo $value; ?>">
                            <?php echo $display; ?>
                        </option>

                    <?php } ?>

                        </select>
                    </td>

                <?php } elseif (isset($info['inputNumber'])) { ?>

                    <td><input rel="<?php echo $rel++; ?>" id="<?php echo $field; ?>"
                               name="<?php echo $field; ?>" type="number"
                               min="1" max="99999999" <?php echo $required; ?>>
                    </td>

                <?php } else { ?>

                    <td>
                        <input type="text" id="<?php echo $field; ?>"
                             name="<?php echo $field; ?>" rel="<?php echo $rel++; ?>"
                             <?php echo $autocomplete . $required; ?>>
                    </td>

                <?php } ?>

            </tr>

            <?php
        }
    }

    /*
    ****************************************************************************
    */
    
    public function setRoleUser()
    {
        $userLevel =  appConfig::getSetting('accessLevels', 'user');
        $myLevel = access::getUserInfoValue('level');

        $this->isStaffUser = $userLevel == $myLevel;
    }

    /*
    ****************************************************************************
    */
}
