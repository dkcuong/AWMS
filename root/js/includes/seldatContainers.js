/*
********************************************************************************
* SCAN CONTAINER JS
********************************************************************************
*/

// variables defined in _default.js

var needCloseConfirm = true,
    tableInputClasses = jsVars['tableInputClasses'],
    editContainer = jsVars['editContainer'],
    tableCells = jsVars['tableCells'],
    measurements = jsVars['measurements'],
    rowAutoIncrementID = 0;

var upcTableKey = {
    styleNo: 4
};

// Global row index
var rowID = 0,

    upcRowID,

    newUPCDialog,

    dialogDivs = [
        'lookUpUPC',
        'createUPC',
        'selectCategory',
        'upcCreated',
        'createUPCButton',
        'upcCreated'
    ],

    dialogDisplays = {},

    autoSave,

    inputControl,

    skipCloseConfirm = false,

    checkingCell = false;

window.parent.needSetHeight = false;

window.onbeforeunload = function () {

    var inactiveTestSession = $('#testSetter', parent.document).hasClass('hidden');

    if (! skipCloseConfirm && ! jsVars['skipCloseConfirm'] && inactiveTestSession) {
        return 'You are about to leave this page - data you have entered may ' +
            'not be saved.';
    } else {
        skipCloseConfirm = false;
    }
};

/*
********************************************************************************
* ON LOAD FUNCTIONS
********************************************************************************
*/

funcStack.scanContainer = function () {

    autoSave.start();

    $('.message').click(function () {
        skipCloseConfirm = true;
    });

    // When a upc has been updated, search for the inventory info
    $('.upc').bind('change', checkUPC);

    $(document).tooltip();

    $('#newUPCHeader').prop('title', 'If you do not know the UPC of the style '
        + 'you are entering, click the adjacent button to search for the UPC. \n'
        + 'If you can not find the UPC, you will have the option to select an '
        + 'original Seldat UPC for this style.');

    $('#infoIcon').addClass('ui-icon').addClass('ui-icon-info');

    $('#scanContainerTable').on('mousedown', '.addButtons', preventWarningPopup);

    $('#scanContainerTable').on('click', '.addButtons', newUPC);

    $('#scanContainerTable').on('click', '.removeRowButtons', removeRowData);

    $('#scanContainerTable').on('click', '.insertRowButtons', insertRow);

    rowAutoIncrementID =  $('#scanContainerTable tr').length - 1;

    newUPCDialog = $('#dialog-form').dialog({
        autoOpen: false,
        modal: true
    });

    $('#generateBarcode').click(generateBarcode);

    $('#selectCategory').show();

    $('#categoryUPC').change(createUPC);

    saveDialog();

    $('#submitForm').click(submitCheck);

    $(document).keydown(backSpace);

    $('#clearContainer').click(autoSave.clear);

    $('.prefix').blur(submitClientPO);

    inputControl.start();

    $('#scanContainerTable').on('focusout', '[data-post]', checkCellValue);

    $('#measureID').on('change', unitMeasurementChange);

    unitMeasurementChange();

    $('#addRow').click(addRows);
    $('#removeRow').click(removeRows);

    $('#scanContainerTable').on('keyup', 'input', changeColumn);

    autoSetHeight('mainDisplay');

    $(document).contents().on('click', '#addRow, #removeRow, #addButton,' +
    '#removeButton, .addRemoveDescription', function () {
        autoSetHeight('mainDisplay');
    });

    bindChangeDimension();

    $('#downloadBadUpcs').click(downloadBadUpcsSubmit);

    $('#receiving').blur(checkReceiving);

    containerPaste.init();

    $.widget('custom.mcautocomplete', $.ui.autocomplete, {
        _renderItem: function (ul, item) {
            var text = '';

            $.each(this.options.columns, function (index, column) {
                var color = index % 2 == 0 ? 'blue' : 'black';
                var prefix = column.description;
                var field = item[column.valueField] ? item[column.valueField]
                    : 'N/A';

                text += '<span style="color: ' + color + '">&nbsp' + prefix
                    + field + '&nbsp</span>';
            });

            var $anchor = $('<a></a>')
                .addClass('mcacAnchor')
                .html(text);

            var result = $('<li></li>')
                .data('ui-autocomplete-item', item)
                .append($anchor)
                .appendTo(ul);

            return result;
        }
    });

    receivingAutocomplete();
};

/*
********************************************************************************
*/

containerPaste = {

    newLine: '\n',
    message: {
        'notEnoughRows': 'Current row in table is not enough. Please add {x} more.'
    },

    init: function() {
        var self = containerPaste;
        var table = $('#scanContainerTable');

        $('input', table).bind('paste', function(e) {
            var element = $(this);
            var data = self.getCopiedData(e);
            // Current rows in table is enough to paste
            var rowIndex = containerPaste.getRowIndex(element);
            var isEnoughRow = self.checkEnoughRow(data, rowIndex);

            if (isEnoughRow) {
                self.paste(element, data);
            }

            return false;
        });
    },

    paste: function(element, data) {
        var columnIndex = containerPaste.getColumnIndex(element);
        var rowIndex = containerPaste.getRowIndex(element);

        $.each(data, function(index, value) {
            var pasteIndex = rowIndex -1 + index;
            var rowElement = $('#scanContainerTable .batchRows').eq(pasteIndex);
            var columnElement = $('td', rowElement).eq(columnIndex);
            $('input', columnElement).val(value);
        });
    },

    getColumnIndex: function(element) {
        return element.parent('td').index();
    },

    getRowIndex: function(element) {
        return element.closest('tr').index();
    },

    getCopiedData: function(e) {
        var copied = e.originalEvent.clipboardData.getData('Text');
        var data = copied.split(this.newLine);
        //remove last array element if it empty in some copy case
        return containerPaste.removeEmptyElement(data);
    },

    removeEmptyElement: function(data) {
        var last = data[data.length - 1];

        if (! last) {
            data.pop();
        }
        return data;
    },

    checkEnoughRow: function(data, rowIndex) {
        var numRows = $('#scanContainerTable .batchRows').length;
        var copiedRows = data.length;
        var availableRow = numRows - rowIndex + 1;

        if (availableRow < copiedRows) {

            var needRow = copiedRows - availableRow;
            defaultAlertDialog(
                    this.message.notEnoughRows.replace('{x}', needRow));
            return false;
        }

        return true;
    }
};

/*
********************************************************************************
*/

function noCubes()
{
    var row = $(this).parent().parent();

    var values = {};

    jsVars['actualDimensions'].map(function (name) {
        values[name] = $('.'+name, row).val();
    });

    if (values['length'] !== values['width']
    || values['length'] !== values['height']
    ) {
        return;
    }

    var message = 'Cartons cannot have cubed dimensions';
    defaultAlertDialog(message);
}

/*
********************************************************************************
*/

function popEaches()
{
    var row = $(this).parent().parent();
    var haveAll = true;

    jsVars['needForEachValues'].map(function (name) {
        var dimensionValue =  $('.'+name, row).val();
        haveAll = parseInt(dimensionValue) ? haveAll : false;
    });

    if (! haveAll) {
        return;
    }

    var uom = $('.uom', row).val();
    $.each(jsVars['eachMeasurements'], function (measurement, eachName) {
        var divisor = 1;
        switch (measurement) {
            case 'height':
            case 'weight':
                divisor = uom;
        }

        var eachValue = $('.'+measurement, row).val() / divisor;
        var rounded = Math.round(eachValue * 10) / 10;
        $('.'+eachName, row).val(rounded);
    });
}

/*
********************************************************************************
*/

inputControl = {

    target: '.batchRows input',

    rowInputs: null,

    start: function () {

        this.rowInputs = $('.batchRows').eq(0).find('input').length;

        $(this.target).keyup(this.moveFocusDown);
    },

    moveFocusDown: function (event) {

        var batchInputs = inputControl.target;

        var isDownKey = event.keyCode == 40;

        if (! isDownKey) {
            return;
        }

        var index = $(batchInputs).index(this);

        var inputCount = $(batchInputs).length;

        index += inputControl.rowInputs;

        index = index > inputCount ? index % inputCount + 1 : index;

        var input = $(batchInputs).eq(index);

        input.focus();
    }
};

/*
********************************************************************************
*/

autoSave = {

    cycle: null,

    start: function () {

        if (! jsVars['runAutosave']) {
            return;
        }

        var inactiveTestSession = $('#testSetter', parent.document).hasClass('hidden');

        if (inactiveTestSession) {
            this.cycle = setInterval(this.update, 10000);
        }
    },

    update: function () {

        $.ajax({
            url: jsVars['urls']['autoSaveContainer'],
            type: 'post',
            data: {
                autoSaveContainer: formToArray($('[data-post]'))
            },
            dataType: 'json'
        });
    },

    clear: function (event) {

        event.preventDefault();

        $.ajax({
            url: jsVars['urls']['autoSaveContainer'],
            type: 'post',
            data: {
                clearAutoSave: true
            },
            dataType: 'json',
            success: function () {
                window.location = '';
                return false;
            }
        });
    }
};

/*
********************************************************************************
*/

dtMods['upcs'] = {

    // Add the carrier links
    fnRowCallback: function(nRow, row) {

        var upcCol = 0;
        var categoryCol = 1;

        $('td', nRow).eq(upcCol)
               .addClass('selectUPC')
               .attr('data-upc', row[upcCol])
               .attr('data-cat', row[categoryCol])
               .html('Select ' + row[upcCol])
               .bind('click', selectUPC);
    }
};

/*
********************************************************************************
*/

dtMods['batches'] = {

    fnServerParams: function(aoData) {
        aoData = addControllerSearch(aoData, 'batches');
    }
};


/*
********************************************************************************
*/

function selectUPC()
{
    // Set the upc in the container form
    var upcValue = $(this).attr('data-upc');
    var categoryValue = $(this).attr('data-cat');

    $('.upc').eq(upcRowID).val(upcValue);
    $('.categoryUPC').eq(upcRowID).val(categoryValue);

    // Close the dialog
    newUPCDialog.dialog('close');
    updateStyleRow(upcRowID, upcValue);

}

/*
********************************************************************************
*/

function saveDialog()
{
    $.each(dialogDivs, function (index, divName) {
        dialogDisplays[index] = $('#' + divName).css('display');
    });
}

/*
********************************************************************************
*/

function resetDialog()
{
    $.each(dialogDivs, function (index, divName) {
        $('#' + divName).css('display', dialogDisplays[index]);
    });

    $('#categoryUPC option:selected').prop('selected', false);
    $('#categoryUPC option:first').prop('selected', 'selected');
    $("#alertMessages").removeClass().html('');
}

/*
********************************************************************************
*/

function createUPC()
{
    var catUPC = $("#categoryUPC").val();

    if (parseInt(catUPC) === -1) {
        return false;
    }

    $.ajax({
        type: 'post',
        url: jsVars['urls']['seldatUPC'],
        data: {request: 'newUPC'},
        dataType: 'json',
        success: function (response) {

            var messageType = response ? 'success' : 'error',
                message = response ? 'New UPC: ' + response :
                    'The system is out of UPCs';

            if (response) {

                $('#lookUpUPC, #createUPC').hide(500);
                $('.upc').eq(upcRowID).val(response);
                $('.categoryUPC').eq(upcRowID).val(catUPC);

                updateStyleRow(upcRowID, response);
            }

            alertMessage(message, messageType);

            newUPCDialog.dialog('close');
        }
    });
}

/*
********************************************************************************
*/

function preventWarningPopup()
{
    checkingCell = true;
}

/*
********************************************************************************
*/

function getStyleNo(upcRowID)
{
    var rowElement = $('#scanContainerTable .batchRows').eq(upcRowID);
    var colElement = $('td', rowElement).eq(upcTableKey.styleNo);
    var styleNo = $('input', colElement).val();
    return styleNo;
}

/*
********************************************************************************
*/

function newUPC()
{
    upcRowID = $('.addButtons').index(this);
    var table = dataTables['upcs'].DataTable();
    var keyword = getStyleNo(upcRowID);

    $('#requestUPC').show();

    // If row has styleNo available, the search of upc table will search by
    // styleNo
    if (keyword) {
        table.search(keyword).draw();
    }

    var windowWidth = $(window).width() * 0.8;

    resetDialog();

    var buttonPosition = $(this).position().top;

    newUPCDialog.dialog({
        width: windowWidth
    }).dialog('option', 'position', [
        'center',
        buttonPosition - 200
    ]).dialog('open');

    newUPCDialog.scrollTop(0);

    dataTables['upcs'].fnAdjustColumnSizing();
}

/*
********************************************************************************
*/

function checkUPC()
{
    var upc = this.value;

    if (upc.length < 11) {
        return;
    }

    var cellRow = $(this).last().parent().parent();

    rowID = $('.batchRows').index(cellRow);

    updateStyleRow(rowID, upc);
}

/*
********************************************************************************
*/

function updateStyleRow(rowID, upc)
{
    $.ajax({
        // Needs to be turned into a jsVar
        url: jsVars['urls']['updateStyleRows'],
        type: 'post',
        data: {
            upc: upc
        },
        dataType: 'json',
        success: function (styleInfos) {

            var measure = getMeasurementSystem(true);

            $.each(styleInfos, function (name, value) {

                var dimension = tableCells[name]['dimension'];

                if (measure == 'metric' && typeof dimension !== 'undefined') {

                    var metricValue = value / measurements[dimension]['convert'];

                    var convertedValue = metricValue.toFixed(1);

                    value = parseFloat(convertedValue);

                }

                $('.' + name).eq(rowID).val(value);
            });

            checkingCell = false;
        }
    });
}

/*
********************************************************************************
*/

function submitCheck(event)
{
    event.preventDefault();

    var params = formToArray($('[data-post]'));

    params.measurementSystem = getMeasurementSystem();
    params.modify = jsVars['modify'];
    params.modifyRows = jsVars['modifyRows'];
    params.modifyBatches = jsVars['modifyBatches'];
    params.editContainer = editContainer;

    $.blockUI({
        message: 'Creating Container. Do NOT Close This Window.'
    });

    $.ajax({
        type: 'post',
        url: jsVars['urls']['checkSeldatUPC'],
        data: params,
        dataType: 'json',
        success: submitContainerAjaxSuccess
    });
}

/*
********************************************************************************
*/

function submitContainerAjaxSuccess(response)
{
    if (response['errors']) {

        var message = '';

        $.map(response['errors'], function(value) {
            message += '<br><strong>'+value.field+'</strong> '+value.error;
        });

        defaultAlertDialog(message);
    } else {
        if (editContainer && ! jsVars['modify']) {
            location.reload();
        } else {

            $('#scanContainerForm').hide();
            $('#importFile').hide();
            var messageClass = '';

            var message = jsVars['modifyRows'] === false ? 'Container Created' :
                'Container Modified';

            if (response['submitErrors'].length) {
                messageClass = 'failedMessage';

                $.map(response['submitErrors'], function (value) {
                    message += '<br>'+value.error;
                });
            } else if (response['rejectUOM'].length) {
                messageClass = 'warningMessage';

                $.map(response['rejectUOM'], function(value) {
                    message += '<br>'+value;
                });
            } else {
                messageClass = 'successMessage';

                message += jsVars['modifyRows'] === false
                    ? '<br>Receiving Number '+response['recNum'] : '';
            }

            $('#resultMessage')
                .html(message)
                .addClass(messageClass)
                .show();

            $('#generateBarcode').show();

            skipCloseConfirm = true;
        }
    }

    $.unblockUI();
}

/*
********************************************************************************
*/

function backSpace(event)
{
    if (event.which === 8 && !$(event.target).is('input, textarea')) {
        event.preventDefault();
    }
}

/*
********************************************************************************
*/

function submitClientPO()
{
    var clientNo = $(this).val();

    clientNo = clientNo.toUpperCase();

    var text = ['NA', 'N/A', 'N\A'];

    if (~$.inArray(clientNo, text)) {
        var message = 'You have entered NA for Client PO.';

        defaultAlertDialog(message);
    }
}

/*
********************************************************************************
*/

function removeRows(event)
{
    event.preventDefault();

    var removeRowAmount = $('#removeRowAmount').val();
    var rowsToRemove = removeRowAmount == parseInt(removeRowAmount)
            ? Math.max(0, removeRowAmount) : 0;

    if (rowsToRemove > 0) {
        var rowAmount = $('.upc').length;
        var modifyRows = $('#scanContainerTable').attr('modifyRows');

        rowsToRemove = Math.min(rowAmount, rowsToRemove);

        if (modifyRows > 0) {
            // in "Modify Container" page previously submitted rows can not be removed
            var activeRows = rowAmount - modifyRows;

            rowsToRemove = Math.min(activeRows, rowsToRemove);
        }

        if (rowsToRemove > 0) {
            $('#scanContainerTable tr').slice(-rowsToRemove).remove();
        }

        if (! $('.upc').length) {
            addRow(1);
        }

        var rowAmount = $('.upc').length;

        $('#setrow').val(rowAmount - 1);
    } else {
        var message = 'Only Positive Numeric Values are Allowed';

        defaultAlertDialog(message, $('#removeRowAmount'));
    }

    $('#removeRowAmount').val('');
}

/*
********************************************************************************
*/

function addRows(event)
{
    event.preventDefault();

    var addRowAmount = $('#addRowAmount').val();
    var rowsToAdd = addRowAmount == parseInt(addRowAmount) ?
        Math.max(0, addRowAmount) : 0;

    if (rowsToAdd > 0) {
        if (rowsToAdd > 100) {
            var message = 'You can add no more than 100 rows at once.<br>'
                + 'Only 100 rows were added.';

            defaultAlertDialog(message, $('#addRowAmount'));

            rowsToAdd = 100;
        }

        addRow(rowsToAdd);

        var rowAmount = $('.upc').length;

        $('#setrow').val(rowAmount - 1);
    } else {

        var message = 'Only Positive Numeric Values are Allowed';

        defaultAlertDialog(message, $('#addRowAmount'));
    }

    $('#addRowAmount').val('');
}

/*
********************************************************************************
*/

function addRow(rowsToAdd)
{
    var rowAmount = $('.upc').length, idx = 0;
    var rowIdx = 0;
    for (idx = rowAmount; idx < rowAmount + rowsToAdd; idx++) {
        rowIdx = getRowIdx();

        var $row = getRow(rowIdx);

        $('#scanContainerTable').append($row);
    }

    $('.upc').bind('change', checkUPC);

    updateScanContainerTable();
}

/*
********************************************************************************
*/

function addCell(data)
{
    var cellName = data.cellName,
        cellClass = data.cellClass,
        cellSize = data.cellSize,
        idx = data.idx,
        inputRel = data.inputRel;

    var $input = $('<input>')
        .attr('type', 'text')
        .attr('name', cellName)
        .attr('data-post', '')
        .attr('data-row-index', idx);

    if (typeof cellClass !== 'undefined') {
        $input.addClass(cellClass);
    }

    if (typeof inputRel !== 'undefined') {
        $input.attr('rel', inputRel);
    }

    if (typeof cellSize !== 'undefined') {
        $input.attr('size', cellSize);
    }

    return $('<td>').append($input);
}

/*
********************************************************************************
*/

function generateBarcode(event)
{
    event.preventDefault();

    location.href = jsVars['urls']['generateBarcode'] + '/container/'
        + $(this).attr('data-container');
}

/*
********************************************************************************
*/

function unitMeasurementChange()
{
    var measure = getMeasurementSystem(true);

    var dimensionUnit = ' ('+measurements['width'][measure]['unit'].toUpperCase()+')';
    var weightUnit = ' ('+measurements['weight'][measure]['unit'].toUpperCase()+')';

    $('.unitDimensions').html(dimensionUnit);
    $('.unitWeight').html(weightUnit);
}

/*
********************************************************************************
*/

function getMeasurementSystem(isShortName)
{
    var measurementSystem = editContainer ?
        $('#measurementSystem').val() : $('#measureID :selected').text();

    if (isShortName) {
        return measurementSystem == 'Metric' ? 'metric' : 'imperial';
    }

    return measurementSystem;
}

/*
********************************************************************************
*/

function autoSetHeight(id)
{
    var tmpHeight = 40;

    var ifrm = window.parent.document.getElementById(id);

    if (ifrm) {

        var heightDocIframe = $(ifrm).contents().find('body').height();

        ifrm.style.height = heightDocIframe + tmpHeight + 'px';

        ifrm.style.visibility = 'visible';
    }
}

/*
********************************************************************************
*/

function checkCellValue(event)
{
    if (checkingCell) {
        // check for another cell is in progress
        return;
    }

    var field = $(this).attr('class');

    $.ajax({
        url: jsVars['urls']['checkScanContainerCell'],
        type: 'post',
        data: {
            field: field,
            value: $(this).val(),
            measurement: getMeasurementSystem(true)
        },
        dataType: 'json',
        success: function (response) {
            // momorize a cell to which a check is in progress
            checkingCell = field;

            var message = '';

            $.map(response, function (errors) {
                message += '<strong>'+errors.field+'</strong> '+errors.error+'<br>';
            });
            alertMessage(message, 'warning');
            //message && defaultAlertDialog(message);
            checkingCell = false;
        }
    });
}

/*
********************************************************************************
*/

function changeColumn(event)
{
    if (event.which === 40) {

        var cellClass = $(this).attr('class');
        var currentRow = $('.' + cellClass).index(this);
        var rowAmount = $('.' + cellClass).length - 1;
        var newColumn = cellClass;
        var newRow = currentRow + 1;

        if (currentRow >= rowAmount) {

            newRow = 0;

            if (~$.inArray(cellClass, tableInputClasses)) {

                var classIndex = tableInputClasses.indexOf(cellClass),
                    lastColumnIndex = tableInputClasses.length - 1;

                classIndex = Math.min(classIndex + 1, lastColumnIndex);

                newColumn = tableInputClasses[classIndex];
            }
        }

        var $newCell = $('.'+newColumn).eq(newRow);

        $newCell.focus();
        $newCell.select();
    }
}

/*
********************************************************************************
*/

function removeRowData()
{
    var removeButton = $(this);
    var rowID = removeButton.attr('rowid');

    $('#' + rowID). remove();

    var rowAmount = $('.upc').length;

    if (rowAmount === 0) {
        addRow(1);
    }

    updateScanContainerTable();
}

/*
********************************************************************************
*/

function insertRow()
{
    var insertButton = $(this);

    var rowID = insertButton.attr('rowid');

    var rowIdx = getRowIdx();

    var $row = getRow(rowIdx);

    $("#" + rowID).after($row);

    updateScanContainerTable();
}

/*
********************************************************************************
*/

function getRow(idx)
{
    var rowID = 'row-' + idx;
    var $row = $('<tr>')
        .addClass('batchRows' )
        .attr('id', rowID);
    $.each(tableCells, function (field, value) {
        switch(field) {
            case 'rowNo':
                var $cell = $('<td>')
                    .addClass('firstCol');
                $('<span>').addClass('idxCtn')
                            .appendTo($cell)
                            .html(idx + 1);
                $('<input>')
                    .attr('type', 'hidden')
                    .attr('name', 'categoryUPC')
                    .attr('class', 'categoryUPC')
                    .attr('data-row-index', idx)
                    .attr('data-post', '')
                    .appendTo($cell);
                break;
            case 'categoryUPC':
                break;
            case 'newUPC':
                var $cell = $('<td>')
                    .addClass('newUPC');

                $('<input>')
                    .attr('type', 'button')
                    .addClass('addButtons')
                    .val('Add')
                    .appendTo($cell);

                break;
            case 'tableFunc':
                var $cell = $('<td>')
                    .addClass('newUPC');

                // button remove row
                $('<input>')
                    .attr('type', 'button')
                    .attr('rowid', rowID)
                    .addClass('removeRowButtons ui-icon ui-icon-trash')
                    .val('')
                    .appendTo($cell);
                // button insert row
                $('<input>')
                    .attr('type', 'button')
                    .attr('rowid', rowID)
                    .addClass('insertRowButtons ui-icon ui-icon-plus')
                    .val('')
                    .appendTo($cell);
                break;
            default:
                var $cell = addCell({
                    cellName: field,
                    cellClass: field,
                    cellSize: value.size,
                    idx: idx,
                    inputRel: value.inputRel
                });
                break;
        }

        $row.append($cell);
    });

    return $row;
}

/*
********************************************************************************
*/

function updateScanContainerTable()
{
    /*
     * refresh css after remove or insert row
     */

    var $trs = $('#scanContainerTable tr');
        $trs.removeClass('oddRows')
            .removeClass('fifthMarked');

    $trs.each(function(index, trObj) {
        if (index === 0) {
            return;
        }

        trObj = $(trObj);

        if (index % 2) {
            trObj.addClass('oddRows');

        }

        if (index % 5 === 0) {
            trObj.addClass('fifthMarked');
        }

        trObj.find('td.firstCol')
            .find('span.idxCtn')
            .html(index);

        trObj.find('input[data-row-index]')
            .attr('data-row-index', index - 1);
    });

    autoSetHeight('mainDisplay');

    containerPaste.init();

    bindChangeDimension();
}

/*
********************************************************************************
*/

function getRowIdx()
{
    return rowAutoIncrementID++;
}

/*
********************************************************************************
*/

function alertMessage(message, type)
{
    if (! message) {
        return false;
    }

    var cssClass = 'alert';

    switch (type) {
        case 'success':
            cssClass += ' alert-success';
        break;

        case 'warning':
            cssClass += ' alert-warning';
        break;

        case 'error':
            cssClass += ' alert-error';
        break;
    }

    var $alertMsgDiv =  $('#alertMessages');

    if (! $alertMsgDiv) {
        defaultAlertDialog(message);
        return false;
    }

    $alertMsgDiv.removeClass()
            .html(message)
            .addClass(cssClass);
}

/*
********************************************************************************
*/

function downloadBadUpcsSubmit(event)
{
    event.preventDefault();
    window.location = jsVars['urls']['downloadBadUpcs'];
}

/*
********************************************************************************
*/

function bindChangeDimension()
{
    $('.length, .width, .height').on('change', noCubes);
    $('.uom, .weight, .length, .width, .height').on('change', popEaches);
}

/*
********************************************************************************
*/

function receivingAutocomplete()
{
    $('#receiving').mcautocomplete({
        columns: [
            {
                valueField: 'receivingID',
                description: 'ID: '
            }, {
                valueField: 'ref',
                description: 'Ref: '
            }, {
                valueField: 'description',
                description: 'Des: '
            }
        ],
        minLength: 1,
        source: function(request, response) {
            $.ajax({
                url: jsVars['urls']['getReceivingNumber'],
                dataType: 'json',
                data: {
                    term : request.term
                },
                success: function(data) {
                    if (data.length > 0) {
                        response(data);
                    }
                }
            });
        },
        select: function(event, ui) {

            var receivingID = ui.item['receivingID'];

            $(this).val(receivingID);

            updateReceivingDetails(ui.item);

            return false;
        }
    });
}

/*
********************************************************************************
*/

function updateReceivingDetails(data)
{
    var vendorName = data ? data.vendorName : '';
    var ref = data ? data.ref : '';

    $('#client-name').text(vendorName);
    $('#reference-number').text(ref);

    $('#receiving-info').show();
}

/*
********************************************************************************
*/

function checkReceiving()
{
    $.ajax({
        type: 'get',
        url: jsVars['urls']['checkReceiving'],
        data: {
            receivingID: $(this).val()
        },
        dataType: 'json',
        success: function (response) {

            if (response) {
                updateReceivingDetails(response);
            } else {

                defaultAlertDialog('Inavlid Receiving');

                $(this).val('');

                updateReceivingDetails();
            }
        }
    });
}

/*
********************************************************************************
*/