<?php

namespace tables\orders;

class masterOrders extends \tables\_default
{


    public $ajaxModel = 'masterOrders';

    public $fields = [];

    public $table = '';

    static $primaryKey = 'scanOrderNumber';

    function __construct($primaryKey = 'scanOrderNumber')
    {
        self::$primaryKey = $primaryKey;
        parent::__construct();
    }

    /*
    ****************************************************************************
    */

    function errorOutput($data)
    {
        $errorFields = $data['errorFields'];
        $fieldCaptions = $data['fieldCaptions'];
        $formCount = $data['formCount'];
        $errorMessage = $data['errorMessage'];

        if ($errorFields) {
            foreach($fieldCaptions as $field => $caption) {
                if (isset($errorFields[$field][$formCount])) { ?>

                    <font color="red">
                        <?php echo $caption . $errorMessage; ?>
                    </font>
                    <br>

                <?php }
            }
        }
    }

    /*
    ****************************************************************************
    */

    function productErrorOutput($checkType, $params, $scanOrderNumber)
    {
        $splitProducts = $params['splitProducts'];
        $productErrors = $params['productErrors'];
        $missingProducts = $params['missingProducts'];
        $postOrderNumber = $params['postOrderNumber'];
        $missingMandatoryValues = $params['missingMandatoryValues'];
        $reprintWavePick = $params['reprintWavePick'];

        if (isset($splitProducts[$scanOrderNumber])) {
            echo $splitProducts[$scanOrderNumber];
        }

        if (isset($productErrors[$scanOrderNumber])) {
            foreach ($productErrors[$scanOrderNumber] as $error) { ?>
                <font color="red"><?php echo $error; ?></font><br><?php
            }
        }

        if ($checkType == 'Check-Out') {
            if (isset($missingProducts[$scanOrderNumber])) { ?>
                <font color="red">Product data are missing!</font><br><?php
            } else {
                $missingData = FALSE;

                if (isset($postOrderNumber)) {
                    $orderKeys = array_flip($postOrderNumber);
                    $page = $orderKeys[$scanOrderNumber];

                    $missingData = isset($missingMandatoryValues['pickid'][$page])
                        || isset($missingMandatoryValues['numberofcarton'][$page])
                        || isset($missingMandatoryValues['numberofpiece'][$page]);
                }

                if (isset($reprintWavePick[$scanOrderNumber])
                    || $missingData) { ?>

                    <font color="red">
                        <?php echo 'Create Pick Tickets for Order # ' . $scanOrderNumber; ?>
                    </font><br><?php
                }
            }
        }
    }


    /*
    ****************************************************************************
    */

    function getProductTotals($params, $i)
    {
        $sku = $quantity = $cartonCount = 0;
        $totalPieces = $totalCartons = 0;


        if (isset($params['sku'][$i])) {
            $sku = $params['sku'];
            $quantity = $params['quantity'];
            $cartonCount = $params['cartonCount'];
            $rowAmount = count($sku[$i]);
            for ($count = 0; $count < $rowAmount; $count++) {
                $totalPieces += $quantity[$i][$count];
                $totalCartons += $cartonCount[$i][$count];
            }
        }

        return [
            'totalPieces' => $totalPieces,
            'totalCartons' => $totalCartons,
        ];
    }

    /*
    ****************************************************************************
    */

    function displayTextArea($data, $params)
    {
        $page = $params['page'];
        $title = $params['title'];
        $field = getDefault($params['field'], NULL);
        $mandatory = getDefault($params['mandatory'], 'Check-Out');
        $textAreaClass = getDefault($params['textAreaClass'], NULL);
        $maxLength = isset($params['maxLength'])
            ? ' maxlength="' . $params['maxLength'] . '" ' : NULL;
        $width = isset($params['width'])
        ? ' style="width:'. $params['width'] .'px;"' : NULL;
        $checkType = $data['checkType'];
        $inputValues = $data['inputvalues'];
        $closedOrders = $data['closedOrders'];
        $order = $inputValues[self::$primaryKey][$page];
        $missField = getDefault($params['tdClass'], NULL);?>

        <table border="1">
        <tr>
            <td valign="top" class="<?php echo $missField; ?>"><?php

                echo $mandatory == $checkType
                    ? '<span class="red">*</span> ' : NULL;
                echo $title; ?>

                <br>

                <?php

                $textAreaAttributes = 'rows="8" cols="57" '
                        . 'class="' . $textAreaClass
                        . '" name="' . $field . '[]" ' . $maxLength . 'data-post' . $width;
                if (getDefault($closedOrders[$order])) { ?>
                    <textarea <?php echo $textAreaAttributes; ?> hidden><?php
                        echo $inputValues[$field][$page]; ?></textarea>
                    <textarea rows="8" cols="57" class="removable <?php
                              echo $textAreaClass; ?>" disabled
                              style="background-color: #fff; font-weight: bold;"><?php
                        echo $inputValues[$field][$page]; ?></textarea><?php
                } else { ?>
                    <textarea <?php echo $textAreaAttributes; ?>><?php
                        echo $inputValues[$field][$page]; ?></textarea><?php
                } ?>
            </td>
        </tr>
        </table><?php
    }


    /*
    ****************************************************************************
    */

    function productRows($data, $page, $disabled)
    {
        if (isset($data['postData']['sku'][$page])) {
            $sku = $data['postData']['sku'];
            $rowAmount = count($sku[$page]);

            for ($count = 0; $count < $rowAmount; $count++) {

                $oddRowsClass = $count % 2 ? NULL : 'oddRows'; ?>

            <tr class="<?php echo $oddRowsClass; ?>">
                <td class="addRemove">

                    <?php

                    $style = $disabled ? 'style="display: none;"' : NULL; ?>

                    <button class="addRemoveDescription" <?php echo $style;?>
                            name="removeDescription_<?php echo $rowAmount; ?>"
                            data-table-index="<?php echo $page; ?>"
                            data-row-index="<?php echo $count; ?>"
                            data-col-index="0">-</button>
                </td>
                <td class="center rowIndex" data-table-index="<?php echo $page;?>"
                    data-row-index="<?php echo $count;?>" data-post>
                    <?php echo $count+1; ?>
                </td>

                <?php

                self::productCell($data, [
                    'page' => $page,
                    'row' => $count,
                    'col' => 1,
                    'field' => 'upcID',
                    'spanClass' => 'upcID',
                    'disabled' => $disabled,
                ]);

                self::productCell($data, [
                    'page' => $page,
                    'row' => $count,
                    'col' => 2,
                    'field' => 'cartonCount',
                    'spanClass' => 'cartonCount',
                    'disabled' => $disabled,
                ]);

                self::productCell($data, [
                    'page' => $page,
                    'row' => $count,
                    'col' => 3,
                    'field' => 'sku',
                    'disabled' => $disabled,
                ]);

                self::productCell($data, [
                    'page' => $page,
                    'row' => $count,
                    'col' => 4,
                    'field' => 'size',
                    'disabled' => $disabled,
                ]);

                self::productCell($data, [
                    'page' => $page,
                    'row' => $count,
                    'col' => 5,
                    'field' => 'color',
                    'disabled' => $disabled,
                ]);

                self::productCell($data, [
                    'page' => $page,
                    'row' => $count,
                    'col' => 6,
                    'field' => 'upc',
                    'disabled' => $disabled,
                ]);

                self::productCell($data, [
                    'page' => $page,
                    'row' => $count,
                    'col' => 7,
                    'field' => 'uom',
                    'inputClass' => 'upcDescription',
                    'disabled' => $disabled,
                ]);

                self::productCell($data, [
                    'page' => $page,
                    'row' => $count,
                    'col' => 8,
                    'field' => 'quantity',
                    'inputClass' => 'productQuantity',
                    'maxLength' => 10,
                    'disabled' => $disabled,
                ]);

                self::productCell($data, [
                    'page' => $page,
                    'row' => $count,
                    'col' => 9,
                    'field' => 'cartonLocation',
                    'inputClass' => 'upcDescription',
                    'disabled' => $disabled,
                ]);

                self::productCell($data, [
                    'page' => $page,
                    'row' => $count,
                    'col' => 10,
                    'field' => 'prefix',
                    'inputClass' => 'upcDescription',
                    'disabled' => $disabled,
                ]);

                self::productCell($data, [
                    'page' => $page,
                    'row' => $count,
                    'col' => 11,
                    'field' => 'suffix',
                    'inputClass' => 'upcDescription',
                    'disabled' => $disabled,
                ]);

                self::productCell($data, [
                    'page' => $page,
                    'row' => $count,
                    'col' => 12,
                    'field' => 'available',
                    'spanClass' => 'available',
                    'disabled' => $disabled,
                ]);

                $this->productCell($data, [
                    'page' => $page,
                    'row' => $count,
                    'col' => 13,
                    'field' => 'volume',
                    'spanClass' => 'volume',
                    'disabled' => $disabled,
                ]);

                $this->productCell($data, [
                    'page' => $page,
                    'row' => $count,
                    'col' => 14,
                    'field' => 'weight',
                    'spanClass' => 'weight',
                    'disabled' => $disabled,
                ]); ?>
            </tr><?php
            }

            return $rowAmount;
        }
        return 0;
    }

    /*
    ****************************************************************************
    */

    function productCell($data, $params)
    {
        $page = $params['page'];
        $row = $params['row'];
        $col = $params['col'];
        $field = $params['field'];
        $inputClass = getDefault($params['inputClass'], 'productDescription');
        $maxLength = isset($params['maxLength'])
            ? ' maxlength="' . $params['maxLength'] . '" ' : NULL;
        $disabled = $params['disabled'];

        $spanClass = NULL;
        $type = 'text';
        $inputValues = $data['inputvalues'];
        $closedOrders = $data['closedOrders'];
        $postData = $data['postData'];

        if ($field == 'upcID' || $field == 'cartonCount' || $field == 'available') {
            // cells that are readonly in any case
            $spanClass = 'extraInfo ';
            $type = 'hidden';
        } else {
            $inputClass .= ' inputCell';
        }

        $order = $inputValues['scanOrderNumber'][$page];

        $type = getDefault($closedOrders[$order]) ? 'hidden' : $type;

        $spanClass .= $field; ?>

        <td align="center"><?php

        $value = getDefault($postData[$field][$page][$row], NULL);

        if ($type == 'hidden') {

            $inputColumns = ['sku', 'size', 'color', 'upc', 'uom', 'quantity',
                'cartonLocation', 'prefix', 'suffix'];

            $spanClass .= in_array($field, $inputColumns) ? ' removable' : NULL;

            if (is_array($value)) {
                reset($value);
                $key = key($value);
                $value = $value[$key];
            }

            $style = $field == 'available' && $disabled
                    ? 'style="display: none;"' : NULL; ?>

            <span class="<?php echo $spanClass; ?>" <?php echo $style; ?>>
                <?php echo $value; ?></span><?php
        }

        if (isset($postData[$field])) {

            $attr = ' name="' . $field . '[' . $page . '][]" data-post'
                . ' data-table-index="' . $page . '"'
                . ' data-row-index="' . $row . '"';

            if (in_array($field, ['uom', 'cartonLocation', 'prefix', 'suffix'])) {

                $displayNone = $disabled ? ' display: none;' : NULL;
                $style = 'width: 99%;' . $displayNone;

                $multiple = $field == 'uom' ? 'multiple="multiple"' : NULL; ?>

                <select class="upcDescription <?php echo $field; ?>"
                        style="<?php echo $style; ?>"
                        <?php echo $multiple; ?> <?php echo $attr; ?>
                        data-name="<?php echo $field; ?>"><?php

                $value = is_array($value) ? $value : [$value];

                foreach ($value as $rowValue) { ?>

                <option selected="selected"><?php echo $rowValue; ?></option>

                <?php } ?>

                </select><?php

            } else { ?>
                <input type="<?php echo $type; ?>" <?php echo $maxLength; ?>
                       class="<?php echo $field . ' ' . $inputClass; ?>"
                       <?php echo $attr; ?>
                       data-col-index="<?php echo $col; ?>"
                       value="<?php echo $value; ?>"> <?php
            }
        } ?>
        </td><?php
    }

    /*
    ****************************************************************************
    */

    function createMenuBox($data, $params)
    {
        $page = $params['page'];
        $array = $params['array'];
        $field = $params['field'];
        $index = $params['index'];
        $inputColSpan = isset($params['inputColSpan'])
            ? ' colspan ="' . $params['inputColSpan'] . '" ' : NULL;
        $preset = getDefault($params['preset']);
        $disabled = getDefault($params['disabled'], NULL);
        $emptyOption = getDefault($params['emptyOption']);
        $menu = $data['menu'];
        $checkType = $data['checkType'];
        $inputValues = $data['inputvalues'];

        $firstOption = TRUE;
        $value = NULL;

        if (! $preset) {

            $keys = array_keys($array);

            // if default value is not defined get it from "menu" array
            foreach ($keys as $id) {
                if (isset($menu[$field][$id][$page])) {
                    $preset = $id;
                    break;
                }
            }
        }

        $order = $inputValues[self::$primaryKey][$page];

        $isClosed = getDefault($data['closedOrders'][$order]);

        $hidden = $isClosed || $disabled === TRUE || $disabled === $checkType ?
            ' visibility: hidden; width: 0px' : NULL;

        self::displayInputTitle($params, $checkType); ?>

        <td <?php echo $inputColSpan; ?>>

        <select name="<?php echo $field; ?>[]" class="<?php echo $field; ?>"
                style="width: 99%;<?php echo $hidden; ?>" data-post><?php

        if ($emptyOption) { ?>

            <option value="">Select</option>

        <?php }

        foreach ($array as $id => $row) {

            $selected = NULL;

            if ($firstOption) {
                // if no default value is defined - make the first option selected
                if (! $emptyOption) {
                    $preset = $preset ? $preset : $id;
                }
                $firstOption = FALSE;
            }

            if ($preset == $id) {
                $value = $row[$index];
                $selected = 'selected';
            } ?>

            <option value="<?php echo $id; ?>" <?php echo $selected; ?>><?php
                echo $row[$index]; ?></option><?php

        } ?>

        </select><?php

        if ($hidden) {

            $spanClass = in_array($field, $this->restoreCanceledOrder['restoreMenu']) ?
                    'class="removable"' : NULL; ?>

            <span style="font-weight: bold;" <?php echo $spanClass; ?>><?php
                echo $value; ?>
            </span><?php

        } ?>

        </td>

        <?php
    }

    /*
    ****************************************************************************
    */

    function createInputBox($data, $params)
    {
        $field = getDefault($params['field'], NULL);
        $firstTitle = getDefault($params['firstTitle'], NULL);
        $radioField = getDefault($params['radioField'], NULL);
        $checkField = getDefault($params['checkField'], NULL);
        $inputColSpan = isset($params['inputColSpan'])
            ? ' colspan ="' . $params['inputColSpan'] . '" ' : NULL;
        $tdClass = isset($params['tdClass'])
            ? ' class="' . $params['tdClass'] . '" ' : NULL;
        $rightToLeft = getDefault($params['rightToLeft']);
        $inputSingleCell = getDefault($params['inputSingleCell']);
        $checkType = getDefault($data['checkType']); ?>

        <tr>

        <?php

        $radioField && self::displayCheckRadio('radio', $data, $params);
        $checkField && self::displayCheckRadio('checkbox', $data, $params);

        if (! $rightToLeft) {
            // 1-st goes title and then input tag
            $firstTitle && self::displayInputTitle($params, $checkType, $firstTitle);

            self::displayInputTitle($params, $checkType);
        }

        if ($field) {

            echo $inputSingleCell ? '<br>' : '<td ' . $inputColSpan . $tdClass .'>';

            self::displayInput($data, $params);

            echo $inputSingleCell ? NULL : '</td>';
        }

        $rightToLeft && self::displayInputTitle($params, $checkType); ?>

        </tr>

        <?php
    }

    /*
    ****************************************************************************
    */

    function displayInput($data, $params)
    {
        $page = $params['page'];
        $field = getDefault($params['field'], NULL);
        $spanClassList = getDefault($params['spanClass']);
        $inputClassList = getDefault($params['inputClass']);
        $disabled = getDefault($params['disabled'], NULL);
        $maxLength = isset($params['maxLength'])
            ? ' maxlength="' . $params['maxLength'] . '" ' : NULL;
        $isFieldOrder = getDefault($params['isFieldOrder'], TRUE);

        $checkType = $data['checkType'];
        $inputValues = $data['inputvalues'];

        $order = $inputValues[self::$primaryKey][$page];

        $isClosed = getDefault($data['closedOrders'][$order]);

        $type = $isClosed || $disabled === TRUE || $disabled === $checkType ?
            'hidden' : 'text';
        $value = $spanValue = $isFieldOrder ? $inputValues[$field][$page] :
                getDefault($params['value']);

        $inputClassList .= $type == 'hidden' ? ' inputCell' : NULL;
        $spanClassList .= $type == 'hidden' ? ' spanCell' : NULL;

        if (! $value) {
            switch ($field) {
                case 'pickid':
                    $spanValue = 'Not Created';
                    break;
                case 'numberofpiece':
                    $spanValue = 'Not Stated';
                    break;
                case 'numberofcarton':
                case 'totalVolume':
                case 'totalWeight':
                    $spanValue = 'Not Estimated';
                    break;
            }
        }

        if (! in_array($field, $this->restoreCanceledOrder['preserveSpan'])) {
            $spanClassList .= ' removable';
        }

        $inputClass = $inputClassList ? ' class="' . $inputClassList . '" ' : NULL;
        $spanClass = $spanClassList ? ' class="' . $spanClassList . '" ' : NULL; ?>

        <input type="<?php echo $type; ?>" <?php echo $inputClass . $maxLength; ?>
               <?php if ($isFieldOrder) { ?>
               name="<?php echo $field; ?>[]"
               <?php } ?>
               value="<?php echo $value; ?>"
               data-post><?php

        if ($type == 'hidden') { ?>

            <span style="font-weight: bold;" <?php echo $spanClass; ?>><?php
                echo $spanValue ? $spanValue : '&nbsp'; ?>
            </span><?php
        }
    }

    /*
    ****************************************************************************
    */

    function displayCheckRadio($type, $data, $params)
    {
        $page = $params['page'];
        $radioField = getDefault($params['radioField'], NULL);
        $radioName = getDefault($params['radioName'], NULL);
        $checkField = getDefault($params['checkField'], NULL);
        $checkName = getDefault($params['checkName'], NULL);
        $tdClass = isset($params['tdClass'])
            ? ' class="' . $params['tdClass'] . '" ' : NULL;

        $field = $type == 'radio' ? $radioField : $checkField;
        $name = $type == 'radio' ? $radioName : $checkName;
        $value = $type == 'radio' ? $radioField : 'YES';

        $inputValues = $data['inputvalues'];
        $closedOrders = $data['closedOrders'];
        $order = $inputValues[self::$primaryKey][$page];
        $hidden = getDefault($closedOrders[$order]) ? 'hidden' : NULL; ?>

        <td <?php echo $tdClass; ?>>
            <input type="<?php echo $type; ?>" value="<?php echo $value; ?>"
                   name="<?php echo $name; ?>[<?php echo $page; ?>]" data-post
            <?php echo $inputValues[$field][$page]; ?>
            <?php echo $hidden; ?>><?php

        if ($hidden) { ?>

            <input type="<?php echo $type; ?>" value="<?php echo $value; ?>"
                   class="removable" <?php echo $inputValues[$field][$page]; ?>
                   disabled><?php
        } ?>

        </td><?php
    }

    /*
    ****************************************************************************
    */

    function displayInputTitle($params, $checkType = NULL,  $firstTitle = NULL)
    {
        $title = $params['title'];
        $mandatory = getDefault($params['mandatory'], 'Check-Out');
        $titleColSpan = isset($params['titleColSpan'])
            ? ' colspan ="' . $params['titleColSpan'] . '" ' : NULL;
        $tdClass = isset($params['tdClass'])
            ? ' class="' . $params['tdClass'] . '" ' : NULL;
        $inputSingleCell = getDefault($params['inputSingleCell']);

        if ($firstTitle) {
            $title = $firstTitle;
            $titleColSpan = NULL;
            $inputSingleCell = FALSE;
        } ?>

        <td nowrap="nowrap" <?php echo $titleColSpan; ?><?php echo $tdClass; ?>><?php
        echo $mandatory == $checkType
            ? '<span class="red">*</span> ' : NULL;
        echo $title;
        if (! $inputSingleCell) { ?>
            </td><?php
        }
    }

    /*
    ****************************************************************************
    */

}