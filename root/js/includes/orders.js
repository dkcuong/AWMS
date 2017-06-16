/*
********************************************************************************
* ORDERS JS
********************************************************************************
*/

var woDialog = null,
    noUccPropcessing = noUccPropcessing();

var keyCodes = {
    tab: 9,
    upArrow : 38,
    downArrow : 40
};

if (jsVars.hasOwnProperty('includeWorkOrderLabor')) {
    var workOrdersLabor = workOrdersLabor();
}

funcStack.orders_control = function () {
    jsVars['requestMethod'] == 'searchStatuses'
        ? searcher.useExternalParams() : null;
};

/*
********************************************************************************
*/

funcStack.orders = function () {

    $('.submitLabor').click(submitLabor);

    $('.skipCloseConfirm').click(function () {
        skipCloseConfirm = true;
    });

    if (typeof jsVars['multiSelect'] !== 'undefined') {
        searcher.useExternalParams();
    }

    jsVars['requestMethod'] == 'search' ? searcher.useExternalParams() : null;

    $('.productTable').on('keydown', '.inputCell', productCellKeyDown);

    $.widget('custom.mcautocomplete', $.ui.autocomplete, {
        _renderItem: function (ul, item) {
            var text = '';

            $.each(this.options.columns, function (index, column) {
                var color = index % 2 == 0 ? 'blue' : 'black';
                var prefix = index == 4 ? 'Units: ' : '';
                var field = column.valueField ? column.valueField : 'NA';

                text += '<span style="color: '+color+'">&nbsp'+prefix
                    +item[field]+'&nbsp</span>';
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

    $('.displayPickTicket').click(displayPickTicket);

    $('.displayWavePick').click(displayWavePick);

    $('.clearWavePick').click(clearWavePick);

    $('.datepicker').each(function (index, datePicker) {
        datePicker.id = 'datepicker'+index;
    });

    $('.datepicker').change(function () {
        if ($(this).hasClass('cancelDates')) {
            var formID = $('.cancelDates').index(this);
            checkDateField(formID);
        }
    });

    $('.datepicker').datepicker({'dateFormat': 'yy-mm-dd'});

    $('.datepicker').keydown(function () {
        return false;
    });

    $('.generateScanOrderNumber').click(generateScanOrderNumber);

    $('#submitForm').click(processSubmitForm);

    $('#duplicateButton').click(cloneSingleOrder);

    $('form').submit(function () {
        if ($(this).attr('id') == 'submitForm'){

            var submitForm = true;

            $('.cancelDates').each(function (index) {

                var goodDates = checkDateField(index);

                submitForm = goodDates ? submitForm : false;
            });

            return submitForm;
        }
    });

    $('#print').click(pagePrint);

    $('#orderForm').keypress(function(event) {
        if (event.which == 13 ) {

            var target = event.target;
            var tag = target.tagName;

            if (tag == 'INPUT') {
                return false;
            }
        }
    });

    productDescription();

    $('input[type=radio]').click(radioClick);

    $('input[type=text]').blur(inputBlur);

    //add batch in order check in
    addOrderBatch();

    $('.scanOrderNumber').each(function(index) {
        var code = $(this).val();
        barcode(index, code);
    });

    $('.vendor').change(updateShippingFrom);

    $('.changeBatch').click(changeBatch);

    $('.releaseCanceledOrder').click(releaseCanceledOrder);

    $('#messageAlertDialg').on('click', '.splitCartons', splitCartons);

    $('.splitCartons').click(splitCartons);

    $('.printSplitLabels').click(printReport);

    $('.printVerificationList').click(printReport);

    $('.printUCCLabels').click(printReport);

    $('.productTable').on('keyup', '.quantity', addRowByEnter);

    splitDialog = $('#splitDialog').dialog({
        title: 'Split cartons',
        autoOpen: false,
        width: 450,
        modal: true
    });

    woDialog = $('.woDialog').dialog({
        title: 'Work Orders',
        autoOpen: false,
        width: 950,
        modal: true
    });

    $('#printBOL').click(printBOL);

    upcDescription.description();

    $('.productTable').on('change', '.upcDescription', upcDescriptionChange);

    $('.quantity').on('blur', productQuantityBlur);

    $('#selectAll').click(function() {
        $('.printSelect').prop('checked', true);
    });

    $('#deselectAll').click(function() {
        $('.printSelect').prop('checked', false);
    });

    $('.regular, .truck').change(changeOrderCategory);

    $('.downloadImportOrderTemplate').click(downloadImportOrderTemplate);

    $('.downloadTruckOrderTemplate').click(downloadTruckOrderTemplate);

    $('#truckImportSubmit').click(importTruckOrder);

    $('#orderImportSubmit').click(orderImportSubmit);

    $('#emptyTruckOrder').click(emptyTruckOrder);

    if (jsVars['isOrderCheckInOut']) {
        scrollToMissingField();
    }

    commentDialog = $('#commentForm').dialog({
        autoOpen: false,
        width: 350,
        modal: true,
        resizable: false,
        position: ['center',20],
        buttons: {
            "OK": orderNote.submitNotes,
            "Cancel": function() {
                $(this).dialog('close');
            }
        }
    });

    if (jsVars['isClient']) {
            $('#orders').on('click', '.note', function() {
                var orderNum = $(this).attr('data-order-num');
                var notes = $(this).attr('data-notes');

            commentDialog.dialog('open');

            $('#commentNote').val(notes);
            $('#commentNote').attr('data-order-num', orderNum);

            return false;
        });
    }

    $( document ).tooltip();

    $('.editWorkOrderNumber').click(editWorkOrderNumber);
    $('.addSkuLocation').click(noUccPropcessing.addSkuLocation);
    $('#picking').click(noUccPropcessing.picking);
    $('.addRemoveSkuLocation').click(noUccPropcessing.addRemoveSkuLocation);
    $('.pickingPiecesPicked').change(noUccPropcessing.addRemoveCycleCount);
    $('.pickingActualLocation').change(noUccPropcessing.addRemoveCycleCount);

    $('.viewPickTicket').click(noUccPropcessing.viewPickTicket);

    $(".btnAddNewRow").click(addNewRows);

    new updateShippedCartons();
};

// variables defined in _default.js

needCloseConfirm = true;

// This variable can be referenced anywhere

var skipCloseConfirm = false;

var labelFields = [
    'shiptolabel',
    'eri',
    'UFlabels',
    'client',
    'seldat',
    '3rdparty',
    'collect'
];
var selectedClient = false;
var currentDescription = {
    object: null,
    text: ''
};

var productTable = {

    headerHeigth: 4,

    columns: [
        null,
        '#',
        'upcID',
        'cartonCount',
        'sku',
        'size',
        'color',
        'upc',
        'uom',
        'quantity',
        'cartonLocation',
        'prefix',
        'suffix',
        'available',
        'volume',
        'weight'
    ]
};

var splitDialog = null;

var wavePickData = {
    quantity: {
        total: 0,
        className: 'numberofpiece',
        totalCellClass: 'totalPieces'
    },
    cartonCount: {
        total: 0,
        className: 'numberofcarton',
        totalCellClass: 'totalCartons'
    },
    volume: {
        total: 0,
        className: 'totalVolume'
    },
    weight: {
        total: 0,
        className: 'totalWeight'
    }
};

var upcDescription = {

    fields: ['uom', 'cartonLocation', 'prefix', 'suffix'],

    rowValues: function (index, row, purgeDropdown) {

        var rowValues = {};

        $.map(upcDescription.fields, function(field) {

            var selectedText = [],
                currentField = 'select.'+field,
                selectedOption = 'option:selected',
                $table = $('.productTable').eq(index);

            var $select = $(currentField, $table);

            var $selectedRow = $select.eq(row);

            if (field == 'uom') {

                $(selectedOption, $selectedRow).map(function() {

                    var text = $(this).text();

                    text = text.trim();

                    if (text > 0) {
                        selectedText.push(text);
                    }
                });

                if (! selectedText.length) {
                    selectedText.push('ANY '+field.toUpperCase());
                }
            } else {
                selectedText = $(selectedOption, $select).eq(row).text();
            }

            // set dropdowns values to "ANY LOCATION/PREFIX/SUFFIX" if this is
            // requested by purgeDropdown variable or empty value is sent (note:
            // suffix dropdown accepts empty value as this field is not mandatory)
            var updateDropdown = purgeDropdown || ! selectedText && field != 'suffix';

            rowValues[field] = updateDropdown ? dropdownDefaultValue(field) :
                selectedText;
        });

        return rowValues;
    },

    description: function () {

        var tableCount = $('.productTable').length;

        for (var index = 0; index < tableCount; index++) {

            var upcIDs = [],
                currentUpcID = 'input.upcID',
                $table = $('.productTable').eq(index);

            $(currentUpcID, $table).each(function() {

                var upcID = $(this).val();

                upcIDs.push(upcID);
            });

            upcDescription.display({
                tableIndex: index,
                upcIDs: upcIDs
            });
        }
    },

    displayRowDropdowns: function (param) {

        var index = param.tableIndex,
            row = param.row,
            purgeDropdown = param.purgeDropdown,
            upcID = param.upcID,
            pieces = 0;

        var $table = $('.productTable').eq(index),
            rowValues = upcDescription.rowValues(index, row, purgeDropdown);

        param.type = 'table';

        $.map(upcDescription.fields, function(field) {
            if (field == 'uom' && Array.isArray(rowValues[field])) {
                // adding prerequested uoms
                $.map(rowValues[field], function (uom) {
                    if (! ~$.inArray(uom, param.response.uom[param.upcID])) {
                        param.response.uom[param.upcID].push(uom);
                    }
                });
                // sort numbers in an array in ascending order
                param.response.uom[param.upcID].sort(function (a, b) {
                    return b - a;
                });
            }

            var selectedField = 'select.' + field,
                $currentDropdown = $(selectedField, $table).eq(row),
                $dropdown = createDropdown(param, field)
                    .clone()
                    .val(rowValues[field]);

            $dropdown.css('width', '99%');

            var attr = $table.attr('data-closed-order');

            if (typeof attr !== typeof undefined && attr !== false) {
                $dropdown.css('display', 'none');
            }

            $currentDropdown.replaceWith($dropdown);
        });

        var rowUOM = rowValues.uom,
            rowLoc = rowValues.cartonLocation,
            rowPrefix = rowValues.prefix,
            rowSuffix = rowValues.suffix;

        $.map(param.response.results, function(result) {
            // db field's null value is an equivalent of empty value
            var dbUOM = result.uom,
                dbLocation = result.cartonLocation,
                dbPrefix = result.prefix,
                dbSuffix = result.suffix === null ? '' : result.suffix;

            var sameUPC = result.upcID == upcID,
                sameUOM = rowUOM == 'ANY UOM' || ~$.inArray(dbUOM, rowUOM),
                sameLocation = rowLoc == 'ANY LOCATION' || rowLoc == dbLocation,
                samePrefix = rowPrefix == 'ANY PREFIX' || rowPrefix == dbPrefix,
                sameSuffix = rowSuffix == 'ANY SUFFIX' || rowSuffix == dbSuffix;

            if (sameUPC && sameUOM && sameLocation && samePrefix && sameSuffix) {
                pieces += parseInt(result.available);
            }
        });

        var span = 'span.available';

        $(span, $table).eq(row).html(pieces);
        $('input.available', $table).eq(row).val(pieces);
    },

    display: function(param) {

        var index = param.tableIndex,
            upcIDs = param.upcIDs,
            row = param.row,
            purgeDropdown = param.purgeDropdown;

        var order = $('.scanOrderNumber').eq(index).val(),
            vendorID = $('.vendor').eq(index).val();

        $.ajax({
            type: 'post',
            url: jsVars['urls']['getUPCDescription'],
            data: {
                orderNumber: order,
                vendorID: vendorID,
                upcIDs: upcIDs,
                processed: jsVars['processedOrders'][order]
            },
            dataType: 'json',
            success: function(response) {

                var param = {
                    response: response,
                    tableIndex: index,
                    row: row,
                    purgeDropdown: purgeDropdown
                };

                var $table = $('.productTable').eq(index),
                    currentUpcID = 'input.upcID';

                var $upcs = $(currentUpcID, $table);

                if (typeof row === 'undefined') {
                    // process all rows
                    $upcs.each(function() {

                        row = $upcs.index(this);

                        param.row = row;
                        param.upcID = $upcs.eq(row).val();

                        upcDescription.displayRowDropdowns(param);
                    });
                } else {

                    param.upcID = upcIDs[0];

                    if (purgeDropdown) {
                        // empty dropdown box if a different UPC was selected
                        $.map(upcDescription.fields, function(field) {
                            $('select.' + field, $table).eq(row).empty();
                        });
                    }

                    upcDescription.displayRowDropdowns(param);
                }
            }
        });
    }
};

window.onbeforeunload = function () {

    var inactiveTestSession = $('#testSetter', parent.document).hasClass('hidden');

    if (jsVars['requestMethod'] == 'addOrEdit'
    && ! jsVars['skipCloseConfirm'] && inactiveTestSession) {

        if (! skipCloseConfirm) {
            return 'You are about to leave this page - data you have entered '
                + 'may not be saved.';
        } else {
            skipCloseConfirm = false;
        }
    }
};

/*
********************************************************************************
*/

function noUccPropcessing()
{
    var self = {

        addRemoveSkuLocation: function() {

            $(this).html() == '+' ? self.addSkuLocation($(this)) :
                self.removeSkuLocation($(this));
        },

        addSkuLocation: function($button) {

            var index = $button.attr('data-table-index');

            var $table = $('.pickingTable').eq(index),
                $currentRow = $button.parent().parent(),
                $newRow = $('<tr>')
                    .appendTo($table);

            if ($('tr', $table).length % 2 == 0) {
                $newRow.addClass('oddRows');
            }

            var count = 0,
                cycleCountCells = [
                    'cycleCountAssignedToCell',
                    'cycleCountReportNameCell',
                    'cycleCountDueDateCell'
                ];

            $.each(jsVars['pickingTableColumnClasses'], function (cellClass, inputClass) {

                var $td = $('td', $currentRow).eq(count).addClass(cellClass);

                var $newCell = $('<td>').appendTo($newRow);

                if (~$.inArray(cellClass, cycleCountCells)) {
                    $newCell.addClass(cellClass);
                }

                switch (count) {
                    case 0:

                        $button.clone().appendTo($newCell);

                        break;
                    case 1:
                    case 2:
                    case 3:

                        $('<input>')
                            .addClass(inputClass)
                            .addClass('pickingAutocomplete')
                            .html('')
                            .appendTo($td);

                        break;
                    case 4:

                        $('<input>')
                            .addClass(inputClass)
                            .addClass('pickingAutocomplete')
                            .attr('name', 'upc[]')
                            .attr('data-post', '')
                            .html('')
                            .appendTo($td);

                        $('<input>')
                            .addClass('pickingUPCID')
                            .addClass('pickingAutocomplete')
                            .attr('type', 'hidden')
                            .attr('name', 'upcID[]')
                            .attr('data-post', '')
                            .html('')
                            .appendTo($td);

                        break;
                    case 7:

                        $('<input>')
                            .addClass(inputClass)
                            .attr('type', 'number')
                            .attr('min', '1')
                            .attr('max', '99999999')
                            .attr('name', 'quantity[]')
                            .attr('data-post', '')
                            .html('')
                            .appendTo($td);

                        break;
                    case 8:

                        $('<select>')
                            .addClass(inputClass)
                            .attr('name', 'cartonLocation[]')
                            .attr('data-post', '')
                            .html('')
                            .appendTo($td);

                        break;
                    default:
                        break;
                }

                count++;
            });

            $button.html('-');

            $($newRow, $table).on('click', '.addRemoveSkuLocation', self.addRemoveSkuLocation);

            self.descriptionAutocomplete(index);

            $('.pickingAutocomplete').focus(function() {
                currentDescription.object = $(this);
                currentDescription.text = $(this).val();
            });

            var $cycleCountCells = $('.cycleCountAssignedToCell, '
                + '.cycleCountReportNameCell, .cycleCountDueDateCell', $table);

            $('.cycleCountAssignedTo', $table).is(':visible') ?
                $cycleCountCells.show() : $cycleCountCells.hide();

            $('.pickingAutocomplete').blur(function() {
                if (currentDescription.text != $(this).val()) {
                    $(this).val(currentDescription.text);
                }
            });
        },

        removeSkuLocation: function($button) {

            var $table = $button.parent().parent().parent();

            $button.parent().parent().remove();

            $('tr', $table).removeClass('oddRows');

            var count = 0;

            $('tr', $table).map(function () {
                if (count++ % 2) {
                    $(this).addClass('oddRows');
                }
            });
        },

        descriptionAutocomplete: function (index) {

            var $table = $('.pickingTable').eq(index);

            var vendorID = $table.attr('data-vendor'),
                orderNumber = $table.attr('data-order-number'),
                warehouseType = $table.attr('data-warehouse-type');

            var mezzanineClause = warehouseType == 'mezzanine' ?
                'isMezzanine' : 'NOT isMezzanine';

            $('.pickingAutocomplete').mcautocomplete({
                columns: [{
                    valueField: 'sku'
                }, {
                    valueField: 'size'
                }, {
                    valueField: 'color'
                }, {
                    valueField: 'upc'
                }, {
                    valueField: 'totalUnits'
                }],
                minLength: 1,
                source: function(request, response) {
                    $.ajax({
                        url: jsVars['urls']['getProductInfo'],
                        dataType: 'json',
                        data: {
                            term : request.term,
                            clientID: vendorID,
                            mezzanineClause: mezzanineClause
                        },
                        success: function(data) {
                            if (data.length > 0) {
                                response(data);
                            } else {
                                // restoring initial text if autocomplete returns no text
                                var initialText = currentDescription.text;
                                currentDescription.object.val(initialText);
                            }
                        }
                    });
                },
                select: function(event, ui) {

                    var $tr = $(this).parent().parent();

                    $('.pickingSKU', $tr).val(ui.item.sku);
                    $('.pickingColor', $tr).val(ui.item.color);
                    $('.pickingSize', $tr).val(ui.item.size);
                    $('.pickingUPC', $tr).val(ui.item.upc);
                    $('.pickingUPCID', $tr).val(ui.item.upcID);

                    $.ajax({
                        type: 'post',
                        url: jsVars['urls']['getUPCDescription'],
                        data: {
                            orderNumber: orderNumber,
                            vendorID: vendorID,
                            upcIDs: [ui.item.upcID],
                            mezzanineClause: mezzanineClause
                        },
                        dataType: 'json',
                        success: function(response) {

                            var $select = $('.pickingActualLocation', $tr),
                                key = Object.keys(response.cartonLocation)[0];

                            $select.empty();

                            $.map(response.cartonLocation[key], function (location) {
                                $('<option>')
                                    .text(location)
                                    .appendTo($select);
                            });
                        }
                    });

                    currentDescription.object = $(this);
                    currentDescription.text = $(this).val();

                    return false;
                }
            });
        },

        picking: function () {

            var params = {},
                messages = [];

            $('.pickingTable').map(function () {

                var isClosed = $(this).attr('data-active') == 'closed',
                    count = 1,
                    orderNumber = $(this).attr('data-order-number'),
                    vendorID = $(this).attr('data-vendor'),
                    warehouseType = $(this).attr('data-warehouse-type');

                if (isClosed) {
                    return;
                } else {
                    if (! $('.pickingPiecesPicked', $(this)).length) {
                        messages.push('Product table for Order # ' + orderNumber
                            + ' can not be empty');
                    }
                }

                if (! params.hasOwnProperty(orderNumber)) {
                    params[orderNumber] = {};
                    params[orderNumber].data = {};
                }

                params[orderNumber].data[warehouseType] = {};
                params[orderNumber].vendorID = vendorID;

                $('.pickingPiecesPicked', $(this)).map(function () {

                    var value = $(this).val();

                    var quantity = value == parseInt(value) ? Math.max(0, value) : 0;

                    if (quantity < 1) {
                        messages.push('Enter Pieces Picked at row ' + count
                            + ' for Order # ' + orderNumber);
                    }

                    var $tr = $(this).parent().parent();


                    var cycleCount = $('.cycleCountAssignedTo', $tr).is(':visible'),
                        emptyCycleCount = ! $('.cycleCountAssignedTo', $tr).val()
                            || ! $('.cycleCountReportName', $tr).val()
                            || ! $('.cycleCountDueDate', $tr).val();

                    if (cycleCount && emptyCycleCount) {
                        messages.push('Enter Cycle Count data at row ' + count
                            + ' for Order # ' + orderNumber);
                    }

                    params[orderNumber].data[warehouseType][count] =
                        formToArray($('[data-post]', $(this).parent().parent()));

                    count++;
                });
            });

            if (messages.length) {

                defaultAlertDialog(messages.join('<br>'));

                return;
            }

            $.blockUI({
                message: 'Processing cartons. Do NOT Close This Window.'
            });

            $.ajax({
                type: 'post',
                url: jsVars['urls']['picking'],
                data: params,
                dataType: 'json',
                success: function(response) {
                    self.pickingAjaxSuccess(response);
                }
            });
        },

        pickingAjaxSuccess: function (response, orderNumbers) {

            $.unblockUI();

            if (response.errors.length) {

                defaultAlertDialog(response.errors.join('<br>'));

                return false;
            }

            var $resultTable = $('.pickingResultTable');

            $.each(response.processInventory, function (orderNumber, inventory) {

                var link = self.viewPickTicket(orderNumber);

                var $anchor = getHTMLLink({
                    link: link,
                    attributes: {
                        target: '_blank'
                    },
                    title: 'Print'
                });

                var $tr = $('<tr>');

                $('<td>')
                    .html(orderNumber)
                    .appendTo($tr);

                $('<td>')
                    .html(jsVars['pickingStatus'])
                    .appendTo($tr);

                $('<td>')
                    .html($anchor)
                    .appendTo($tr);

                var $uccLabels = $('.pickedUccLabels').eq(0).clone().show(),
                    uccList = inventory.UCCs.join(',');

                if (! inventory.isPrintUCCEDI) {
                    $('.printPickedUCCLabels', $uccLabels)[0].setAttribute('disabled', true);
                    $('.printPickedUCCLabels', $uccLabels).addClass('disabled');
                }

                $('.isFromEDI', $uccLabels).val(inventory.isFromEDI);

                $('.orderNumber', $uccLabels).val(orderNumber);

                $('.pickedUccs', $uccLabels).val(uccList);

                $('<td>')
                    .append($uccLabels)
                    .appendTo($tr);

                var $splitLabels = $('.pickedSplitLabels').eq(0).clone().show(),
                    uccList = '';

                if (response.parentUCCs.hasOwnProperty(orderNumber)) {
                    uccList = response.parentUCCs[orderNumber].join(',');
                } else {
                    $('.printPickedSplitLabels', $splitLabels).hide();
                    $('.noSplitLabels', $splitLabels).show();
                }

                $('.pickedSplitUccs', $splitLabels).val(uccList);

                $('<td>')
                    .append($splitLabels)
                    .appendTo($tr);

                $tr.appendTo($resultTable);
            });

            $('.printPickedUCCLabels').click(self.pickedUccLabels);
            $('.printPickedSplitLabels').click(self.pickedSplitLabels);

            $('#pickingContainer').hide();

            $resultTable.show();

            $.unblockUI();
        },

        pickedSplitLabels: function () {
            $(this).parent().submit();
        },

        pickedUccLabels: function () {
            $(this).parent().submit();
        },

        viewPickTicket: function (orderNumber) {

            var wavePickType = typeof orderNumber !== 'object' ? 'picked' :
                $(this).attr('data-wave-pick-type');

            orderNumber = typeof orderNumber !== 'object' ? orderNumber :
                $(this).parent().attr('data-order-number');

            var link = httpBuildQuery(jsVars['urls']['displayWavePicks'], {
                wavePickType: wavePickType,
                order: orderNumber
            });

            window.open(link, '_blank');

            return link;
        },

        addRemoveCycleCount: function () {

            var $tr = $(this).parent().parent();

            var $table = $tr.parent();

            var pickingPiece = $('.pickingPieceQuantity', $tr).val(),
                primeLocation = $('.pickingPrimeLocation', $tr).val(),
                $cycleCountCells = $('.cycleCountAssignedToCell, '
                    + '.cycleCountReportNameCell, .cycleCountDueDateCell', $table);
            $cycleCountInputs = $('.cycleCountAssignedTo, '
                + '.cycleCountReportName, .cycleCountDueDate', $tr),
                piecesPicked = $('.pickingPiecesPicked', $tr).val(),
                actualLocation = $('.pickingActualLocation', $tr).val();

            if (parseInt(pickingPiece) > parseInt(piecesPicked)
            || primeLocation != actualLocation) {

                $cycleCountInputs.show();
                $cycleCountCells.show();

            } else {

                $cycleCountInputs.hide();

                if (! $('.cycleCountAssignedTo', $table).is(':visible')) {
                    $cycleCountCells.hide();
                }
            }
        }
    };

    return self;
};

/*
********************************************************************************
*/

var orderNote = {

    addLinks: function (nRow, row, index) {
        var columns = jsVars['columnNumbers'];

        var noteNumColumn = columns.clientNotes;
        var noteValue = row[noteNumColumn] ? row[noteNumColumn] : '';
        var orderNumColumn = columns.scanOrderNumber;
        var orderValue = row[orderNumColumn];

        var titleHead = row[noteNumColumn] ? 'View Notes' : 'Add Notes';

        if (! jsVars['isClient']) {
            var titleHead = row[noteNumColumn] ? 'View Notes' : '';
        }

        var $anchor = $('<a></a>')
                .addClass('note')
                .attr('title',noteValue)
                .attr('data-order-num',orderValue)
                .attr('data-notes',noteValue)
                .html(titleHead);

        $('td', nRow).eq(noteNumColumn).html($anchor);
    },

    submitNotes : function() {
        var orderNum = $('#commentNote', this).attr('data-order-num');
        var comment = $('#commentNote', this).val();

        $.ajax({
            type: 'post',
            url: jsVars['urls']['addOrderClientNotes'],
            dataType: 'json',
            data: {
                orderNum: orderNum,
                notes: comment
            },
            success: function (response) {
                if (response) {
                    var modelName = jsVars['modelName'];
                    dataTables[modelName].fnDraw();
                }
            }
        });

        $(this).dialog('close');
    }
};


/*
********************************************************************************
*/

dtMods['orders'] = {

    fnRowCallback: function(nRow, row, index) {

        var columnNumbers = jsVars['columnNumbers'];

        var parentCol = columnNumbers['scanOrderNumber'],
            printBOLCol = columnNumbers['printBOL'],
            statusIDCol = columnNumbers['statusID'];

        var orderNumber = row[parentCol];

        if (orderNumber == null) {
            return;
        }

        var $td = $('td', nRow).eq(printBOLCol),
            printBoL = $($td, nRow).html();

        var cellText = printBoL ? 'Print BOL' : 'No BOL';

        $td.html(cellText);

        if (printBoL) {
            $('<input>')
                .prop('type', 'checkbox')
                .addClass('printSelect')
                .attr('data-order-number', row[parentCol])
                .html('Print BOL')
                .prependTo($td);
        }

        orderNote.addLinks(nRow, row, index);

        if (~$.inArray(row[statusIDCol], jsVars['processedOrdersStatuses'])) {

            var labelPrintLink = getHTMLLink({
                link: httpBuildQuery(jsVars['urls']['displayProcessedLabels'], {
                    order: orderNumber
                }),
                attributes: {
                    target: '_blank'
                },
                title: 'UCC Labels'
            });

            var platePrintLink = getHTMLLink({
                link: httpBuildQuery(jsVars['urls']['displayProcessedPlates'], {
                    search: 'plates',
                    order: orderNumber,
                    level: 'order'
                }),
                attributes: {
                    target: '_blank'
                },
                title: 'License Plates'
            });

            $('td', nRow).eq(parentCol)
                .html('Print ' + labelPrintLink + ' / ' + platePrintLink
                    + ' for ' + orderNumber);
        }
    }
};

/*
*******************************************************************************
*/

dtMods['truckOrderWaves'] = {

    fnServerParams: function(aoData) {
        aoData = addControllerSearch(aoData, 'truckOrderWaves');
    }
};

/*
*******************************************************************************
*/

function checkDateField(formID)
{
    var dStartDate = $('.shipDates:eq('+formID+')').val();
    var dCancelDate = $('.cancelDates:eq('+formID+')').val();

    if (! dStartDate || ! dCancelDate) {

        var message = 'Cancel date and start ship date are required';

        defaultAlertDialog(message);

        return false;
    }

    if (dStartDate > dCancelDate) {

        var message = 'Cancel date can not be before start ship date';

        defaultAlertDialog(message);

        return false;
    }

    return true;
}

/*
********************************************************************************
*/

function submitOrderForm(val)
{
    $('#buttonFlag').val(val);
}

/*
********************************************************************************
*/

function radioClick()
{
    var currentField = $(this).val();

    if (~$.inArray(currentField, labelFields)) {

        var inputField = $(this).parent().nextAll().eq(1).children();

        inputField.focus();
    }
}

/*
********************************************************************************
*/

function inputBlur()
{
    var $radio = $(this).parent().prevAll().eq(1).children();

    var radioName = $radio.val();

    var isMonitored = ~$.inArray(radioName, labelFields),
        radioChecked = $radio.is(':checked');

    if (isMonitored && radioChecked) {

        var input = $(this).val();

        if (! input) {

            var fieldName = $(this).parent().prev().text();

            var checkName = fieldName.substring(0, 1);

            if (checkName == '*') {
                fieldName = fieldName.substring(1);
            }

            var message = '"'+fieldName+'" is checked but not filled!';

            defaultAlertDialog(message);

            return false;
        }
    }
}

/*
********************************************************************************
*/

function addProductDescription($button)
{
    $button.html('-');

    var tableIndex = $button.attr('data-table-index'),
        rowOriginal = parseInt($button.attr('data-row-index'));

    var rowIndex = rowOriginal + productTable.headerHeigth,
        colIndex = 0,
        $table = $('.productTable').eq(tableIndex);

    var rowTd = 'tr:eq('+rowIndex+') td';

    if ((rowOriginal + 1) % 5 === 0) {
        $($button).closest('tr').addClass('fifthMarked');
    }

    $(rowTd, $table).each(function() {
        var cellName = productTable.columns[colIndex];

        if (cellName == '#') {
            $(this).attr('align', 'center')
                .addClass('rowIndex')
                .attr('data-table-index', tableIndex)
                .attr('data-row-index', rowOriginal)
                .attr('data-post', '')
                .html(rowOriginal + 1);
        } else if (cellName !== null) {
            // adding inputs and(or) spans to a current td-tag

            productTableDataRow({
                tdCell: $(this),
                cellName: cellName,
                tableIndex: tableIndex,
                rowIndex: rowOriginal
            });
        }

        colIndex++;
    });

    productTableLastRow($table);

    productDescription();
}

/*
********************************************************************************
*/

function productDescription()
{
    var $productDescription = $('.productDescription'),
        $addRemoveDescription = $('.addRemoveDescription');

    $productDescription.focus(function() {

        var tableIndex = $(this).attr('data-table-index');

        selectedClient = $('.vendor').eq(tableIndex).find(':selected')
            .attr('value');

        currentDescription.object = $(this);
        currentDescription.text = $(this).val();
    });

    productDescriptionAutocomplete();

    // unbind to avoid duplicate addRemoveProductDescription function execution
    $addRemoveDescription.unbind('click', addRemoveProductDescription);
    $addRemoveDescription.bind('click', addRemoveProductDescription);

    pasteProduct.init();
}

/*
********************************************************************************
*/

function productDescriptionAutocomplete()
{
    $('.productDescription').mcautocomplete({
        columns: [{
            valueField: 'sku'
        }, {
            valueField: 'size'
        }, {
            valueField: 'color'
        }, {
            valueField: 'upc'
        }, {
            valueField: 'totalUnits'
        }],
        minLength: 1,
        source: function(request, response) {
            if (selectedClient) {
                $.ajax({
                    url: jsVars['urls']['getProductInfo'],
                    dataType: 'json',
                    data: {
                        term : request.term,
                        clientID: selectedClient
                    },
                    success: function(data) {
                        if (data.length > 0) {
                            response(data);
                        } else {
                            // restoring initial text if autocomplete returns no text
                            var initialText = currentDescription.text;
                            currentDescription.object.val(initialText);
                        }
                    }
                });
            } else {

                var message = 'Select a Client Vendor Name';

                defaultAlertDialog(message);
            }
        },
        select: function(event, ui) {

            var tableIndex = $(this).attr('data-table-index');
            var row = parseInt($(this).attr('data-row-index'));
            var rowIndex = row + productTable.headerHeigth;
            var currentCell = currentDescription.object.attr('name');

            currentCell = currentCell.substr(0, currentCell.indexOf('['));

            var $table = $('.productTable').eq(tableIndex);
            var cell = 'tr:eq('+rowIndex+') .inputCell';
            var currentQty =
                $('.inputCell.quantity', $('tr:eq('+rowIndex+')', $table)).val();

            $(cell, $table).each(function() {

                var name = $(this).attr('name');
                name = name.substr(0, name.indexOf('['));

                var text = ui.item[name];

                if (currentCell == name) {
                    currentDescription.text = text;
                }

                if (currentQty && name == 'quantity') {
                    return true;
                }

                $(this).val(text);
            });

            var upcID = ui.item.upcID;

            var upcIDInput = 'input.upcID:eq('+row+')',
                upcIDSpan = 'span.upcID:eq('+row+')';
            // trigger empty drop down boxes if a different UPC was selected
            var purgeDropdown = upcID != $(upcIDInput, $table).val();

            $(upcIDInput, $table).val(upcID);
            $(upcIDSpan, $table).html(upcID);

            upcDescription.display({
                tableIndex: tableIndex,
                upcIDs: [upcID],
                row: row,
                purgeDropdown: purgeDropdown
            });

            return false;
        }
    }).bind('focus', function(){
        $(this).mcautocomplete("search");
    });
}

/*
********************************************************************************
*/

function addRemoveProductDescription(event)
{
    event.preventDefault();

    var tableIndex = $(this).attr('data-table-index');
    var row = parseInt($(this).attr('data-row-index'));
    var rowIndex = row + productTable.headerHeigth;

    if ($(this).text() == '+') {

        addProductDescription($(this));

        $('.sku[data-table-index="'+tableIndex+'"][data-row-index="'+row+'"]').focus();
    } else {

        var $table = $('.productTable').eq(tableIndex);

        $('tr', $table).eq(rowIndex).remove();

        $('.rowIndex[data-table-index='+tableIndex+']').each(function(index){
            $(this).html(index + 1);
        });

        productCartonsRecalculate(tableIndex);
        productQuantityRecalculate(tableIndex, true);
        oddRowHighLight(tableIndex);
    }
    // return false - is needed to prevent from jumping to the top of the page
    return false;
}

/*
********************************************************************************
*/

function displayPickTicket(event)
{
    event.preventDefault();

    var truckRows = $('#truckOrderWaves').dataTable().fnSettings().fnRecordsTotal(),
        param = {
            removeTruckOrderData: false,
            index: $('.displayPickTicket').index(this)
        };

    if (truckRows > 0 && $('#orderCategoryDiv > .regular').is(':checked')) {

        var message = '"Regular Order" is selected but the order has active '
            + 'Mixed Items Cartons. Creating a Pick Ticket will remove '
            + 'these cartons. If you would like to include Mixed Items '
            + 'Cartons to the Pick Ticket click "Cancel" button now then '
            + 'select "ECOMM Truck" and try to create a Pick Ticket again.';

        param.removeTruckOrderData = true;

        defaultConfirmDialog(message, 'runWavePickCheck', param);
    } else {
        runWavePickCheck(param);
    }
}

/*
********************************************************************************
*/

function runWavePickCheck(param)
{
    if (param.removeTruckOrderData) {
        emptyTruckOrder();
    }

    checkWavePick({
        index: param.index,
        param: false
    });
}

/*
********************************************************************************
*/

function emptyTruckOrder()
{
    if (! $('#truckOrderWaves').dataTable().fnSettings().fnRecordsTotal()) {
        return;
    }

    var tableIndex = 0;

    var order = $('.scanOrderNumber').eq(tableIndex).val();

    $.ajax({
        type: 'post',
        url: jsVars['urls']['emptyTruckOrder'],
        data: {
            orderNumber: order
        },
        dataType: 'json',
        success: function() {

            $('#truckOrderWaves').DataTable().ajax.reload();

            $('#truckOrderImportResults').empty().hide();

            emptyNumberOfCartonsTitle(tableIndex);
        }
    });
}

/*
********************************************************************************
*/

function checkWavePick(param)
{
    var index = param.index;
    var functionParam = param.param;

    var order = $('.scanOrderNumber').eq(index).val(),
        children = typeof param.children === 'undefined' ? [] : param.children;
    isDisabled = $('.clearWavePick').eq(index).is(':disabled');

    var productData = isDisabled ? [] : getProductTableData(index);

    $.blockUI({
        message: 'Creating Pick Ticket. Do NOT Close This Window.'
    });

    $.ajax({
        type: 'post',
        url: jsVars['urls']['createPickTicket'],
        dataType: 'json',
        data: {
            orderNumber: order,
            tableData: productData,
            children: children
        },
        success: function(response) {
            $.blockUI.defaults.onUnblock =
                createPickTicketAjaxSuccess(response, index, order, functionParam);
            $.unblockUI();
        }
    });
}

/*
********************************************************************************
*/

function createPickTicketAjaxSuccess(response, index, order, functionParam)
{
    if (response.batch) {

        $('span.pickid').eq(index).html(response.batch);
        $('input.pickid').eq(index).val(response.batch);

        if (functionParam === false) {
            // just display Wave Pick
            displayWavePickExecute({
                type: 'order',
                number: order
            });
        } else {
            if (index == 0) {

                var fn = functionParam.fn;

                if (fn in window) {
                    // call submitted as a parameter function
                    window[fn](functionParam);
                }
            } else {
                // use recursive call to check previous order
                checkWavePick({
                    index: index - 1,
                    param: functionParam
                });
            }
        }

        if (! jsVars['processedOrders'][order]) {
            // update Product table only if the order is not processed
            updateProductTable(order, index);
        }
    } else {
        // handling errors
        if (response.error && response.error.length > 0) {
            // errors other than split cartons
            var message = 'Error Creating Wave Pick for Order #'+order
                +':<br>';

            $.map(response.error, function(value) {
                message += '<br>'+value;
            });

            defaultAlertDialog(message);
        } else {
            // split cartons error
            $('#splitDialogTable tr:gt(0)').remove();

            $('#splitDialog .processing').hide();
            $('#splitDialog .splitCartons').show();

            $.each(response.splitProducts, function (orderNumber, upcs) {
                $.each(upcs, function (upc, upcData) {
                    $.map(upcData, function (cartons) {
                        var $tr = $('<tr>');

                        var $td = $('<td>')
                            .html(upc);

                        $tr.append($td);

                        $td = $('<td>')
                            .addClass('ucc')
                            .html(cartons.ucc128);

                        $tr.append($td);

                        $td = $('<td>')
                            .addClass('uomA')
                            .attr('align', 'center')
                            .html(cartons.portionOne);

                        $tr.append($td);

                        $td = $('<td>')
                            .addClass('uomB')
                            .attr('align', 'center')
                            .html(cartons.portionTwo);

                        $tr.append($td);

                        $('#splitDialogTable').append($tr);
                    });
                });

                $('#splitDialog .splitCartons')
                    .attr('data-order-number', orderNumber)
                    .attr('data-print-pick-ticket', 1);
            });

            splitDialog.dialog('open');
        }
    }
}

/*
********************************************************************************
*/

function clearWavePick(event)
{
    event.preventDefault();

    var tableIndex = $('.clearWavePick').index(this);

    var order = $('.scanOrderNumber').eq(tableIndex).val();

    var message = 'You are about to clear Wave Pick for order # '+order+'.<br>'
        +'Once it has been cleared it cannot be restored.';

    var param = {
        order: order,
        tableIndex: tableIndex
    };

    defaultConfirmDialog(message, 'clearWavePickExecute', param);
}

/*
********************************************************************************
*/

function clearWavePickExecute(param)
{
    var order = param['order'],
        tableIndex = param['tableIndex'];

    $.ajax({
        type: 'post',
        url: jsVars['urls']['clearWavePick'],
        dataType: 'json',
        data: {
            orderNumber: order
        },
        success: function() {

            $('input.pickid').eq(tableIndex).val(null);
            $('span.pickid').eq(tableIndex).html('Not Created');

            $.map(wavePickData, function (fieldData) {

                var caption = fieldData.className == 'numberofpiece' ?
                    'Not Stated' : 'Not Estimated';

                $('input.'+fieldData.className).eq(tableIndex).val(null);
                $('span.'+fieldData.className).eq(tableIndex).html(caption);
            });

            $('.productTable:eq('+tableIndex+') .addRemoveDescription')
                .bind('click', addRemoveProductDescription);

            var message = 'Wave Pick for order # '+order
                +' was successfully cleared';

            defaultAlertDialog(message);
        }
    });
}

/*
********************************************************************************
*/

function updateScanNumber(index, response, isBind)
{
    $('.scanOrderNumber').eq(index).val(response);

    if ($('.scanordernumberSpan').length) {
        $('.scanordernumberSpan').eq(index).html(response);
    }

    $('.barcodeFooter').eq(index).html(response);

    if (! isBind) {
        // use isBind to avoid dublicate code execution

        var userID = response.substring(0, 4),
            assignNumber = response.substring(4);

        var searchParams = {
            userID: parseInt(userID),
            assignNumber: parseInt(assignNumber)
        };

        jsVars['dataTables']['truckOrderWaves']['searchParams'] = [];

        $.each(searchParams, function (type, value) {
            jsVars['dataTables']['truckOrderWaves']['searchParams'].push({
                andOrs: ['AND'],
                searchTypes: [type],
                searchValues: [value],
                compareOperator: ['exact']
            });
        });
    }
}

/*
********************************************************************************
*/

function generateScanOrderNumber(event)
{
    event.preventDefault();

    var index = $('.generateScanOrderNumber').index(this);

    $.ajax({
        url: jsVars['urls']['getNewLabel'],
        dataType: 'json',
        success: function(response) {

            var isBind = false;

            updateScanNumber(index, response, isBind);
            barcode(index, response);

            if ($('.orderNumberBatchDisplay').length) {
                // update of Make Additional Batch table is required since its
                // values are used as a key in storing orders data to DB
                $('.orderNumberBatchDisplay').eq(index).html(response);
                $('.orderNumberBatchInput').eq(index).val(response);
            }
        }
    });
}

/*
********************************************************************************
*/

function generateWorkOrderNumber($button)
{
    $.ajax({
        url: jsVars['urls']['getNewLabel'],
        dataType: 'json',
        data: {
            type: 'work'
        },
        success: function(response) {
            $button
                .attr('data-work-order-id', '')
                .attr('data-work-order', response)
                .text('Edit Work Order Number ' + response);

            var index = $('.editWorkOrderNumber').index(this);

            editWorkOrderNumberExecute(response, index);
        }
    });
}

/*
********************************************************************************
*/

function barcode(index, barcode)
{
    var img = jsVars['urls']['displayScanNumber']+'/'+barcode;

    $('.barcode').eq(index).empty();

    var $barcodeImg = barcode ?
        $('<img>', {
            src: img
        }).bind('load', function () {

            var isBind = true;

            updateScanNumber(index, barcode, isBind);
        }) :
        $('<span>').html('Reserved for barcode');

    $('.barcode').eq(index).append($barcodeImg);
}

/*
********************************************************************************
*/

function pagePrint(event)
{
    event.preventDefault();

    var data = {};

    var count = 0;

    $('.singleOrder').map(function () {
        data[count++] = formToArray($('[data-post]', $(this)));
    });

    var stringifiedData = JSON.stringify(data);

    $('#printPageData').val(stringifiedData);

    $('#orderImportResults').hide();

    $('#printPage').submit();
}

/*
********************************************************************************
*/

function addOrderBatch(obj)
{
    var index = $('.addBatch').index(obj);
    var $batchNumber = $('.batchNumber').eq(index);

    if ($(obj).html() == 'Make an Additional Batch') {

        $(obj).html('Revert Back');

        $.ajax({
            type: 'get',
            url: jsVars['urls']['insertOrderBatch'],
            dataType: 'json',
            data: {
                vendor: $batchNumber.attr('vendor')
            },
            success: function (response) {
                var newBatch = response[1];

                $batchNumber.html(newBatch);
                $('.orderBatch').eq(index).val(newBatch);
            }
        });
    } else {
        var initialValue = $batchNumber.attr('initialValue');

        $(obj).html('Make an Additional Batch');
        $batchNumber.html(initialValue);
        $('.orderBatch').eq(index).val(initialValue);
    }

    return false;
}

/*
********************************************************************************
*/

function updateProductTable(order, tableIndex)
{
    $.ajax({
        type: 'get',
        url: jsVars['urls']['getWavePickData'],
        dataType: 'json',
        data: {
            orderNumber: order,
            processed: jsVars['processedOrders'][order]
        },
        success: function(response) {

            var $table = $('.productTable').eq(tableIndex);
            var headerHeigth = productTable.headerHeigth - 1;

            $table.find('tr:gt('+headerHeigth+')').remove();

            var rowIndex = 0;

            $.map(wavePickData, function(value) {
                // resetting column totals to zero
                value.total = 0;
            });

            $.map(response.data, function(results) {

                $.each(results, function(field, value) {
                    if (typeof wavePickData[field] !== 'undefined') {
                        wavePickData[field].total += parseFloat(value);
                    }
                });

                if (parseInt(results.isMezzanine)) {
                    // do not display Mezzanine cartons Truck Orders may have
                    return;
                }

                var rowNumber = rowIndex + 1,
                    upcID = results.upcID;

                var $cell = productTableAddRemoveButton('-', tableIndex);

                var $index = $('<td>')
                    .addClass('center rowIndex')
                    .attr('data-table-index', tableIndex)
                    .attr('data-row-index', rowIndex)
                    .attr('data-post', '')
                    .html(rowNumber);

                var $row = $('<tr>');

                $row.append($cell);
                $row.append($index);

                $.each(results, function(field, value) {
                    if (field == 'isMezzanine') {
                        // do not display Mezzanine cartons Truck Orders may have
                        return;
                    }

                    var $td = productTableDataRow({
                        tdCell: $('<td>'),
                        cellName: field,
                        cellValue: value,
                        upcID: upcID,
                        tableIndex: tableIndex,
                        rowIndex: rowIndex
                    });

                    $row.append($td);
                });

                $table.append($row);

                upcDescription.display({
                    tableIndex: tableIndex,
                    upcIDs: [upcID],
                    row: rowIndex
                });

                rowIndex++;
            });

            $.each(wavePickData, function(field, values) {

                var value = values.total;

                if (field === 'volume' || field === 'weight') {

                    value = parseFloat(value.toFixed(3));

                    value = Math.max(0.1, value);

                }

                $('input.' + values.className).eq(tableIndex).val(value);
                $('span.' + values.className).eq(tableIndex).html(value);

                if (typeof values.totalCellClass !== 'undefined') {
                    $('.' + values.totalCellClass).eq(tableIndex).html(value);
                }
            });

            productTableLastRow($table);

            $('.addRemoveDescription', $table)
                .bind('click', addRemoveProductDescription);

            productDescriptionAutocomplete();
        }
    });
}

/*
********************************************************************************
*/

function productTableLastRow($table)
{
    var tableIndex = $('.productTable').index($table);

    var $firstCell = productTableAddRemoveButton('+', tableIndex);

    var $row = $('<tr>')
        .attr('data-table-index', tableIndex);

    $.map(productTable.columns, function (name) {

        var cell = name ? '<td>' : $firstCell;

        $row.append(cell);
    });

    $table.append($row);

    oddRowHighLight(tableIndex);
}

/*
********************************************************************************
*/

function productTableDataRow(param)
{
    var $td = param.tdCell,
        cellName = param.cellName,
        cellValue = typeof param.cellValue == 'undefined' ? null : param.cellValue,
        tableIndex = param.tableIndex,
        row = param.rowIndex,
        upcID = param.upcID,
        classAttr = 'inputCell';

    var inputColumns = ['quantity', 'sku', 'size', 'color', 'upc'],
        $cellSpan = null,
        $cellInput = null;

    if (~$.inArray(cellName, inputColumns)) {
        classAttr += cellName == 'quantity'
            ? ' productQuantity' : ' productDescription';
        classAttr += ' ' + cellName;

        $cellInput = $('<input>')
            .attr('type', 'text')
            .addClass(classAttr)
            .attr('name', cellName + '[' + tableIndex + '][]')
            .attr('data-table-index', tableIndex)
            .attr('data-post', '')
            .val(cellValue);

        if (cellName == 'quantity') {
            $cellInput.attr('maxLength', '10');

            $('.quantity').on('blur', productQuantityBlur);
        }
    } else {
        $td.attr('align', 'center');

        if (~$.inArray(cellName, upcDescription.fields)) {

            var param = {
                row: row,
                tableIndex: tableIndex,
                upcID: upcID
            };

            param.response = cellValue === null ? '' : cellValue;

            param.type = 'row';

            createDropdown(param, cellName)
                .hide()
                .appendTo($td);
        } else {
            $cellSpan = $('<span>')
                .addClass('extraInfo')
                .addClass(cellName)
                .html(cellValue);

            $cellInput = $('<input>')
                .attr('type', 'hidden')
                .addClass(cellName + ' productDescription')
                .attr('name', cellName + '[' + tableIndex + '][]')
                .attr('data-table-index', tableIndex)
                .attr('data-post', '')
                .val(cellValue);
        }
    }

    $td.append($cellSpan).append($cellInput);

    pasteProduct.init();

    return $td;
}

/*
********************************************************************************
*/

function productTableAddRemoveButton(caption, tableIndex)
{
    var $table = $('.productTable').eq(tableIndex);

    var rowIndex = $('tr', $table).length - productTable.headerHeigth;

    var $button = $('<button>')
        .addClass('addRemoveDescription')
        .attr('data-table-index', tableIndex)
        .attr('data-row-index', rowIndex)
        .attr('data-col-index', 0)
        .attr('data-post', '')
        .html(caption);

    var $cell = $('<td>')
        .addClass('addRemove')
        .append($button);

    return $cell;
}

/*
********************************************************************************
*/

function productQuantityBlur(event)
{
    var demand = $(this).val();
    var quantity = demand == parseInt(demand) ? Math.max(0, demand) : 0;

    if (quantity < 1) {
        event.preventDefault();
        var message = 'Require units';
        defaultAlertDialog(message, $(this));
    }

    $(this).val(quantity);

    var tableIndex = $(this).attr('data-table-index'),
        row = $(this).attr('data-row-index');

    productQuantityRecalculate(tableIndex);

    checkAvailableInventory(tableIndex, row);
}

/*
********************************************************************************
*/

function productQuantityRecalculate(tableIndex, rowRemoved)
{
    var totalPieces = 0,
        $table = $('.productTable').eq(tableIndex);

    $('.quantity', $table).each(function() {

        var rawValue = $(this).val();
        var quantity = rawValue == parseInt(rawValue) ? Math.max(0, rawValue) : 0;

        totalPieces += quantity;
    });

    totalPieces = totalPieces || null;

    $('.totalPieces', $table).html(totalPieces);

    $('input.numberofpiece').eq(tableIndex).val(totalPieces);

    totalPieces = totalPieces || 'Not Stated';

    $('span.numberofpiece').eq(tableIndex).html(totalPieces);

    if (typeof rowRemoved != 'undefined' && totalPieces != 'Not Stated') {

        var totalCartons = $('.totalCartons').eq(tableIndex).html();
        // put carton quantity if a row was deleted from the table and there are cartons in remainig rows
        $('input.numberofcarton').eq(tableIndex).val(totalCartons);
        $('span.numberofcarton').eq(tableIndex).html(totalCartons);
    } else {
        emptyNumberOfCartonsTitle(tableIndex);
    }
}

/*
********************************************************************************
*/

function productCartonsRecalculate(tableIndex)
{
    var totalCartons = 0,
        $table = $('.productTable').eq(tableIndex);

    $('span.cartonCount', $table).each(function() {

        var rawValue = $(this).html();
        var quantity = rawValue == parseInt(rawValue) ? Math.max(0, rawValue) : 0;

        totalCartons += quantity;
    });

    totalCartons = totalCartons || null;

    $('.totalCartons').eq(tableIndex).html(totalCartons);
}

/*
********************************************************************************
*/

function oddRowHighLight(tableIndex)
{
    var $table = $('.productTable').eq(tableIndex);

    $('tr', $table).slice(productTable.headerHeigth).each(function(index, trObj) {
        var colIndex = 1;

        $('td', $(this)).children().each(function () {

            $(this).attr('data-row-index', index);
            $(this).attr('data-col-index', colIndex++);

            if ($(this).hasClass('uom')) {
                $(this).attr('name', 'uom['+tableIndex+']['+index+'][]');
            }
        });

        $(this).find('td >button').each(function () {
            $(this).attr('data-row-index', index);
        });

        // highlighting product table odd rows with grey colour
        if (index % 2) {
            $(this).removeClass('oddRows');
        } else {
            $(this).addClass('oddRows');
        }

        var hasInput = $(trObj).find('input').length;

        if ((index + 1) % 5 === 0 && hasInput) {
            $(this).addClass('fifthMarked');
        } else {
            $(this).removeClass('fifthMarked');
        }
    });
}

/*
********************************************************************************
*/

function productCellKeyDown(event)
{
    var keyCode = event.keyCode || event.which;
    var arrowKeys = [keyCodes.tab, keyCodes.upArrow, keyCodes.downArrow];
    var tableIndex = $(this).attr('data-table-index');
    var $newCell = null;
    var $table = $('.productTable').eq(tableIndex);


    if (~$.inArray(keyCode, arrowKeys)) {
        event.preventDefault();
        var currentCell = $(this).closest('td');
        var $cells = $('.inputCell', $table);
        // cell index within current table
        var cellIndex = $cells.index(this),
        rowAmount = $('.rowIndex', $table).length, nextRow = '', rowShift = 1,
        rowIndex = $(this).closest('tr').index() - productTable.headerHeigth,
        lengthAutoComplete = $('.ui-autocomplete.ui-widget:visible').length;

        if (keyCode == keyCodes.tab) {
            //Tab

            cellIndex += event.shiftKey ? -1 : 1;
            cellIndex = cellIndex >= $cells.length ? 0 : cellIndex;

            $newCell = $cells.eq(cellIndex);

        } else {

            if (keyCode == keyCodes.upArrow) {
                rowIndex = (rowIndex -rowShift) < 0 ?
                rowAmount -rowShift : rowIndex - rowShift;
            } else {
                rowIndex = (rowIndex + rowShift) >= rowAmount ?
                    0 : rowIndex + rowShift;
            }

            rowIndex += productTable.headerHeigth;
            nextRow = $('tr', $table).eq(rowIndex);

            $newCell = nextRow.find('td:eq(' +
                currentCell.index() + ')').find('input');
        }

        if ($newCell && lengthAutoComplete == 0) {
            $newCell.focus();
        }

    } else {
        return;
    }

}

/*
********************************************************************************
*/

function updateShippingFrom()
{
    var index = $('.vendor').index(this);
    var vendorID = $('.vendor').eq(index).val();

    $('#importVendorID').val(vendorID);

    $.ajax({
        type: 'post',
        url: jsVars['urls']['getShippingFrom'],
        data: {
            vendorID: vendorID
        },
        dataType: 'json',
        success: function(response) {

            var text = response == 'noLocationID' ? '' : response;

            $('.location').eq(index).val(text);
        }
    });
}

/*
********************************************************************************
*/

function changeBatch(event)
{
    event.preventDefault();

    var result = validateWavePickOrders();

    if (result.error) {
        var message = result.error;

        if (result.closedOrders.length !== 0) {

            message += '<br>Closed orders:';

            $.map(result.closedOrders, function (value) {
                message += '<br>'+value;
            });
        }

        defaultAlertDialog(message);
    } else {

        var confInfo = 'You are about to create a new Batch and a new Wave Pick'
            +' for the following orders:<br>';

        $.map(result.orders, function (value) {
            confInfo += '<br>'+value;
        });

        confInfo += '<br><br>This will cancel all of the Pick Tickets that were'
            +' previously associated with these orders.<br>Proceed?';

        var updateParam = {
            vendorID: result.vendorID,
            orders: result.orders,
            fn: 'changeBatchExecute'
        };

        var param = {
            index: $('.scanOrderNumber').length - 1,
            param: updateParam
        };

        defaultConfirmDialog(confInfo, 'checkWavePick', param);
    }
}

/*
********************************************************************************
*/

function changeBatchExecute(param)
{
    $.ajax({
        type: 'get',
        url: jsVars['urls']['changOrdersBatch'],
        data: {
            vendorID: param.vendorID,
            orderNumbers: param.orders
        },
        dataType: 'json',
        success: function(response) {
            if (response) {

                $('.productTable').each(function (tableIndex) {

                    var totalCartons = $('.totalCartons').eq(tableIndex).html();

                    var inputText = totalCartons;
                    var spanText = totalCartons;

                    if (! totalCartons) {
                        inputText = null;
                        spanText = 'Not Estimated';
                    }

                    $('input.numberofcarton').eq(tableIndex).val(inputText);
                    $('span.numberofcarton').eq(tableIndex).html(spanText);

                    var totalPieces = $('.totalPieces').eq(tableIndex).html();

                    var piecesInputText = totalPieces;
                    var piecesSpanText = totalPieces;
                    var pickIDInputText = response;
                    var pickIDSpanText = response;

                    if (! totalPieces) {
                        piecesInputText = null;
                        piecesSpanText = 'Not Stated';
                        pickIDInputText = null;
                        pickIDSpanText = 'Not Created';
                    }

                    $('input.numberofpiece').eq(tableIndex).val(piecesInputText);
                    $('span.numberofpiece').eq(tableIndex).html(piecesSpanText);

                    $('input.pickid').eq(tableIndex).val(pickIDInputText);
                    $('span.pickid').eq(tableIndex).html(pickIDSpanText);
                });

                var confInfo = 'A new batch # '+response+' was assigned to the '
                    +'following orders:<br>';

                $.map(param.orders, function (value) {
                    confInfo += '<br>'+value;
                });

                confInfo += '<br><br>Would you like to print the Wave Pick?';

                param = {
                    type: 'batch',
                    number: response
                };

                defaultConfirmDialog(confInfo, 'displayWavePickExecute', param);
            } else {
                var message = 'Error changing order batch';

                defaultAlertDialog(message);
            }
        }
    });
}

/*
********************************************************************************
*/

function displayWavePick(event)
{
    event.preventDefault();

    var index = $('.displayWavePick').index(this);
    var wavePick = $('span.pickid').eq(index).html();

    if (wavePick == parseInt(wavePick)) {

        displayWavePickExecute({
            type: 'batch',
            number: wavePick
        });

    } else {

        var message = 'There is no Wave Pick assigned to this order.<br>'
            +'Create a Pick Ticket first';

        defaultAlertDialog(message);
    }
}

/*
********************************************************************************
*/

function displayWavePickExecute(param)
{
    var link = jsVars['urls']['displayWavePicks'],
        params = {};

    params[param.type] = param.number;

    var tabLink = httpBuildQuery(link, params);

    newTab(tabLink);
}

/*
********************************************************************************
*/

function validateWavePickOrders()
{
    var vendorID = $('.vendor').eq(0).val();
    var validClients = true;
    var orders = [];
    var closedOrders = [];
    var error = [];

    $('.vendor').each(function(index) {

        if (vendorID != $(this).val()) {
            validClients = false;
        }

        var scanOrderNumber = $('.scanOrderNumber').eq(index).val();

        if (scanOrderNumber) {
            orders.push(scanOrderNumber);

            if ($('.clearWavePick').eq(index).is(':disabled')) {
                closedOrders.push(scanOrderNumber);
            }
        }
    });

    if (orders.length === 0) {
        error.push('No valid Scan Order Numbers were found');
        orders = false;
    }

    if (! validClients) {
        error.push('Scan Orders Numbers have different clients');
        vendorID = false;
    }

    if (closedOrders.length !== 0) {
        error.push('Some Scan Order Numbers have passed Order Processing Check Out phase');
        orders = false;
    }

    if (error.length === 0) {
        error = false;
    }

    return {
        error: error,
        vendorID: vendorID,
        orders: orders,
        closedOrders: closedOrders
    };
}

/*
********************************************************************************
*/

function getProductTableData(index)
{
    var productData = [];

    var $table = $('.productTable').eq(index);

    $('input.upc', $table).each(function() {

        var row = $('input.upc', $table).index(this);

        var rowValues = upcDescription.rowValues(index, row);

        productData[row] = {};

        productData[row].upc = $(this).val();
        productData[row].quantity = $('input.quantity', $table).eq(row).val();

        if (!~$.inArray('ANY UOM', rowValues.uom)) {

            productData[row].uom = [];

            $.map(rowValues.uom, function(uom) {
                productData[row].uom.push(uom);
            });
        }

        if (rowValues.cartonLocation != 'ANY LOCATION' ) {
            productData[row].cartonLocation = rowValues.cartonLocation;
        }

        if (rowValues.prefix != 'ANY PREFIX' ) {
            productData[row].prefix = rowValues.prefix;
        }

        if (rowValues.suffix != 'ANY SUFFIX' ) {
            productData[row].suffix = rowValues.suffix;
        }
    });

    return productData;
}

/*
********************************************************************************
*/

function splitCartons(event)
{
    event.preventDefault();

    var splitIndex = $('.splitCartons').index(this);
    var splitData = getSplitTableData(splitIndex);

    if (splitData.length == 0) {

        var message = 'Error getting Split Table data';

        defaultAlertDialog(message);

        return;
    }

    var printPickTicket = parseInt($(this).attr('data-print-pick-ticket'));

    if (printPickTicket) {

        var order = $(this).attr('data-order-number');
        var tableIndex = false;

        $('.scanOrderNumber').each(function(index) {
            if ($(this).val() == order) {
                return tableIndex = index;
            };
        });

        if (tableIndex === false) {

            var message = 'Error getting index for Order Number # '+order;

            defaultAlertDialog(message);

            return;
        }
    }

    $(this).hide();
    $(this).siblings('.processing').show();

    $.blockUI({
        message: 'Splitting Cartons. Do NOT Close This Window.'
    });

    $.ajax({
        type: 'post',
        url: jsVars['urls']['splitOrderCartons'],
        data: {
            tableData: splitData
        },
        dataType: 'json',
        success: function(response) {
            $.blockUI.defaults.onUnblock =
                splitAjaxSuccess(response, printPickTicket, tableIndex, splitIndex);
            messageDialog.dialog('close');
            $.unblockUI();
        }
    });


}

/*
********************************************************************************
*/

function splitAjaxSuccess(response, printPickTicket, tableIndex, splitIndex)
{
    if (response.error.length) {

        var message = 'Error splitting cartons:';

        $.each(response.error, function(index) {
            message += '<br>'+response.error[index];
        });

        $('.splitCartonsDiv').eq(splitIndex).remove();
        $('.splitDivBreak').eq(splitIndex).remove();

        defaultAlertDialog(message);
    } else {
        if (printPickTicket) {

            // passing cartons that were split at last Create Pick Ticket attempt
            var children = typeof response.children === 'undefined' ? false :
                response.children;

            // check out
            checkWavePick({
                index: tableIndex,
                children: children,
                param: false
            });

            $('#splitDialog').dialog('close');
        } else {
            // check in
            $('#splitUCCs').empty();

            $.map(response.cartonsToSplit, function (value) {
                var $input = $('<input>')
                    .attr('name', 'uccs[]')
                    .attr('type', 'hidden')
                    .val(value);

                $input.appendTo($('#splitUCCs'));
            });

            $('.splitCartonsDiv').eq(splitIndex).remove();
            $('#splitUCCs').submit();
            $('#splitUCCs').empty();

            $('.splitDivBreak').eq(splitIndex).remove();
        }
    }
}

/*
********************************************************************************
*/

function getSplitTableData(splitIndex)
{
    var splitData = {};
    var $splitTable = $('.splitTable').eq(splitIndex);

    $('tr', $splitTable).each(function(rowIndex) {
        if (rowIndex > 0) {

            var index = rowIndex - 1;
            var ucc = $('.ucc', $splitTable).eq(index).html(),
                uomA = $('.uomA', $splitTable).eq(index).html(),
                uomB = $('.uomB', $splitTable).eq(index).html();

            splitData[ucc] = [uomA, uomB];
        }
    });

    return splitData;
}

/*
********************************************************************************
*/

function addRowByEnter(event)
{
    var buttonIndexTable = $(event.target).attr('data-table-index');
    var buttonIndexRow = $(event.target).attr('data-row-index');

    buttonIndexRow++;

    var totalRow = $('.addRemoveDescription[data-table-index='+buttonIndexTable+']')
        .length;

    totalRow--;

    var isLastRow = totalRow == buttonIndexRow ? true : false;

    if (event.which == 13 && isLastRow ) {
        $('.addRemoveDescription[data-table-index='+buttonIndexTable
            +'][data-row-index='+buttonIndexRow+']').trigger('click');
    }
}

/*
********************************************************************************
*/

function printReport(event)
{
    event.preventDefault();

    if ($(this).hasClass('printVerificationList')) {
        var printReport = 'printVerificationList';
    } else if ($(this).hasClass('printSplitLabels')) {
        var printReport = 'printSplitLabels';
    } else if ($(this).hasClass('printUCCLabels')) {
        var printReport = 'printUCCLabels';
    } else {
        return;
    }

    var index = parseInt($('.'+printReport).index(this) / 2);
    var type = $(this).attr('data-print-type');
    var number = false;

    if (type == 'order') {
        number = $('.scanOrderNumber').eq(index).val();
    } else if (type == 'batch') {
        number = $('span.pickid').eq(index).html();

        if (number != parseInt(number)) {
            var message = 'Pick Ticket is not created. Create Pick Ticket First';
            defaultAlertDialog(message);

            return;
        }
    }

    if (! number) {
        return;
    }

    var link = jsVars['urls'][printReport];
    buttons = ['printVerificationList', 'printSplitLabels', 'printUCCLabels'],
        params = {};

    params[type] = number.trim();

    if (~$.inArray(printReport, buttons)) {
        if (printReport == 'printVerificationList') {
            params.printType = 'verificationList';
        } else if (printReport == 'printUCCLabels') {
            params.printType = 'uccLabels';
        }

        var tabLink = httpBuildQuery(link, params);

        newTab(tabLink);
    }
}

/*
********************************************************************************
*/

function printBOL(event)
{
    event.preventDefault();

    var orders = '';

    $('.printSelect:checked').each(function() {
        orders += ','+$(this).attr('data-order-number');
    });

    if (orders) {
        $('#ladingOrders').val(orders);
        $('#lading').submit();
    }
}

/*
********************************************************************************
*/

function removeOrder()
{
    var clickedOrder = $('.removeOrder').index(this);

    $('.singleOrder').eq(clickedOrder + 1).remove();

    if (! $('.removeOrder').length) {
        $('#duplicateButton').show();
        $('#duplicateAmount').show();
        $('#duplicateAmount').val(0);
        $('#orderImport, #orderCategoryDiv').show();
    }
}

/*
********************************************************************************
*/

function createDropdown(param, field)
{
    var response = param.response,
        type = param.type,
        upcID = param.upcID,
        row = param.row,
        tableIndex = param.tableIndex;

    var nameSuffix = field == 'uom' ? '['+row+'][]' : '[]';

    var $select = $('<select>')
        .addClass('upcDescription '+field)
        .attr('name', field+'['+tableIndex+']'+nameSuffix)
        .attr('data-table-index', tableIndex)
        .attr('data-row-index', row)
        .attr('data-post', field)
        .attr('data-name', field);

    if (field == 'uom') {
        $select
            .attr('multiple', 'multiple')
            .attr('size', '4');
    }

    var caption = dropdownDefaultValue(field);

    $('<option>')
        .html(caption)
        .appendTo($select);

    if (typeof response !== 'undefined') {
        if (type == 'table') {

            $.each(response[field], function(key, values) {
                if (key == upcID) {
                    // loop dropdown values
                    createOptions(values, $select);
                }
            });

        } else {
            if (typeof response === 'object') {
                createOptions(response, $select);
            } else {
                // data is passed as a single value
                $('<option>')
                    .html(response)
                    .appendTo($select);
            }

            $select.val(response);
        }
    }

    return $select;
}

/*
********************************************************************************
*/

function createOptions(values, $select)
{
    $.map(values, function(value) {
        $('<option>', {
            value: value
        })
        .html(value)
        .appendTo($select);
    });
}

/*
********************************************************************************
*/

function upcDescriptionChange()
{
    var name = $(this).attr('data-name'),
        index = $(this).attr('data-table-index'),
        row = $(this).attr('data-row-index');

    var $table =  $('.productTable').eq(index);

    var orderNumber = $('.scanOrderNumber').eq(index).val(),
        vendorID = $('.vendor').eq(index).val(),
        upcID = $('input.upcID', $table).eq(row).val();

    var data = {
        orderNumber: orderNumber,
        vendorID: vendorID,
        upcIDs: [upcID],
        name: name,
        tableIndex: index,
        row: row,
        isRecursive: false
    };

    $.map(upcDescription.fields, function(field) {

        var value = $('select.'+field, $table).eq(row).val(),
            caption = dropdownDefaultValue(field);

        var isStatedUOM = field == 'uom' && !~$.inArray(caption, value),
            isStatedDescription = field != 'uom' && value != caption;

        if ((isStatedUOM || isStatedDescription) && value !== null) {

            data[field] = value;
        }
    });

    upcDescriptionChangeExecute(data);
}

/*
********************************************************************************
*/

function upcDescriptionChangeExecute(data)
{
    var name = data.name,
        index = data.tableIndex,
        row = data.row,
        orderNumber = data.orderNumber,
        isRecursive = data.isRecursive;

    data.processed = jsVars['processedOrders'][orderNumber];

    $.ajax({
        type: 'post',
        url: jsVars['urls']['getUPCDescription'],
        data: data,
        dataType: 'json',
        success: function(response) {

            var rowValues = upcDescription.rowValues(index, row),
                $table = $('.productTable').eq(index);

            var piecesData = getRowPieces(rowValues, data, response);

            var pieces = piecesData.pieces;

            if (! pieces) {
                // current set of location/prefix/suffix does not have cartons.
                // Set all dropdowns to "ANY ..." except for the changed one
                $.map(upcDescription.fields, function(field) {
                    var caption = dropdownDefaultValue(field);

                    if (name == field) {
                        if (rowValues[field] == caption) {
                            delete data[field];
                        } else {
                            // remove the field from a list of filter fields
                            data[field] = rowValues[field];
                        }
                    } else {
                        $('select.'+field, $table).eq(row).val(caption);
                        // remove the field from a list of filter fields
                        delete data[field];
                    }
                });

                if (! isRecursive) {
                    data.isRecursive = true;
                    upcDescriptionChangeExecute(data);
                    return;
                }

                pieces = piecesData.totalPieces;
            }

            $('span.available', $table).eq(row).html(pieces);
            $('input.available', $table).eq(row).val(pieces);

            checkAvailableInventory(index, row);
        }
    });
}

/*
********************************************************************************
*/

function getRowPieces(rowValues, data, response)
{
    var name = data.name,
        pieces = 0,
        totalPieces = 0,
        rowUOM = rowValues.uom,
        rowLoc = rowValues.cartonLocation,
        rowPrefix = rowValues.prefix,
        rowSuffix = rowValues.suffix;

    $.map(response.data, function(values) {

        var available = parseInt(values.available);

        var equalData = name == 'uom'
            ? $(values[name]).not(data[name]).length === 0
            : values[name] == data[name];

        if (typeof data[name] === 'undefined' || equalData) {
            // db field's null value is an equivalent of empty value
            var dbUOM = values.uom,
                dbLocation = values.cartonLocation,
                dbPrefix = values.prefix,
                dbSuffix = values.suffix === null ? '' : values.suffix;

            var sameUOM = rowUOM == 'ANY UOM' || $(dbUOM).not(rowUOM).length === 0,
                sameLocation = rowLoc == 'ANY LOCATION' || dbLocation == rowLoc,
                samePrefix = rowPrefix == 'ANY PREFIX' || dbPrefix == rowPrefix,
                sameSuffix = rowSuffix == 'ANY SUFFIX' || dbSuffix == rowSuffix;

            if (name == 'uom' && sameLocation && samePrefix && sameSuffix
            || name == 'cartonLocation' && sameUOM && samePrefix && sameSuffix
            || name == 'prefix' && sameUOM && sameLocation && sameSuffix
            || name == 'suffix' && sameUOM && sameLocation && samePrefix) {

                pieces += available;
            }
        }

        // count all available pieces for the case if the current set of
        // location/prefix/suffix will not have cartons
        totalPieces += available;
    });

    return {
        'pieces': pieces,
        'totalPieces': totalPieces
    };
}

/*
********************************************************************************
*/

function checkAvailableInventory(index, row)
{
    var $table = $('.productTable').eq(index);

    var demand = $('.productQuantity', $table).eq(row).val(),
        upc = $('.upc', $table).eq(row).val(),
        pieces = $('span.available', $table).eq(row).html();

    var intDemand = parseInt(demand),
        intPieces = parseInt(pieces);

    if (intPieces < intDemand) {

        $('td[data-row-index="' + row + '"]', $table).parent()
            .addClass('availableInventory');

        $('.productQuantity', $table).eq(row).addClass('redColor');
    } else {
        $('td[data-row-index="' + row + '"]', $table).parent()
            .removeClass('availableInventory');

        $('.productQuantity', $table).eq(row).removeClass('redColor');
    }
}

/*
********************************************************************************
*/

function dropdownDefaultValue(field)
{
    var caption = field == 'cartonLocation' ? 'LOCATION' : field.toUpperCase();

    return 'ANY ' + caption;
}

/*
********************************************************************************
*/

function releaseCanceledOrder(event)
{
    event.preventDefault();

    var $this = $(this);
    var index = $this.attr('data-table-index');
    var order = $('.scanOrderNumber').eq(index).val();

    $.ajax({
        type: 'get',
        url: jsVars['urls']['releaseCanceledOrder'],
        dataType: 'json',
        data: {
            orderNumber: order
        },
        success: function() {

            $this.remove();

            var $orderForm = $('.singleOrder').eq(index);
            var spanList = '';

            $('.displayPickTicket').eq(index).html('Create Pick Ticket');
            $('.disabledButton', $orderForm)
                .prop('disabled', false)
                .removeClass('disabledButton');

            $.map(jsVars['restoreCanceledOrder'].preserveSpan, function(field) {
                spanList += spanList ? ', .' + field : '.' + field;
            });

            $('.inputCell:not(' + spanList + ')', $orderForm).prop('type', 'text');

            $.map(jsVars['restoreCanceledOrder'].restoreMenu, function(field) {

                $('select.' + field, $orderForm)
                    .css('visibility', '')
                    .css('width', '99%');
            });

            $('.removable', $orderForm).remove();
            $(':radio[data-post], :checkbox[data-post], .ordernotes, .specialinstruction', $orderForm)
                .removeAttr('hidden');
            $('.addRemoveDescription, .available, .upcDescription ', $orderForm)
                .css('display', '');

            $table = $('.productTable').eq(index);

            $table.removeAttr('data-closed-order');

            productTableLastRow($table);

            productDescription();

            jsVars['processedOrders'][order] = false;
        }
    });
}

/*
********************************************************************************
*/

function changeOrderCategory()
{
    var $this = $(this);

    var className = $this.attr('class');
    var index = $('.' + className).index($this),
        tableCaption = $this.attr('data-table-caption');

    $('.orderProductsTableCaption').eq(index).html(tableCaption);

    $('#truckOrderImportResults').empty();

    if (className == 'truck') {

        $('#truckOrderData, #truckOrderImport, #truckOrderImportResults').show();

        $('#truckOrderWaves').DataTable().ajax.reload();
    } else {
        $('#truckOrderData, #truckOrderImport, #truckOrderImportResults').hide();
    }

    if (jsVars['checkType'] == 'Check-In') {
        className == 'truck' ? $('#duplicateButton, #duplicateAmount').hide() :
            $('#duplicateButton, #duplicateAmount').show();
    }
}

/*
********************************************************************************
*/

function downloadImportTemplate(type)
{
    var link = jsVars['urls'][type],
        params = {};

    var tabLink = httpBuildQuery(link, params);

    newTab(tabLink);
}

/*
********************************************************************************
*/

function downloadImportOrderTemplate()
{
    event.preventDefault();

    downloadImportTemplate('downloadImportOrderTemplate');
}

/*
********************************************************************************
*/

function downloadTruckOrderTemplate()
{
    event.preventDefault();

    downloadImportTemplate('downloadTruckOrderTemplate');
}

/*
********************************************************************************
*/

function importTruckOrder(event)
{
    event.preventDefault();

    var message = [];

    $('.scanOrderNumber').eq(0).val() || message.push('Fill in Scan Order Number');
    $('.vendor').eq(0).val() || message.push('Select a Client Vendor Name');
    $(this).siblings('#truckImportFile').val() || message.push('Choose a File');

    if (message.length) {

        defaultAlertDialog(message.join('<br>'));

        return false;
    }

    submitOrderForm('importTruckOrder');

    skipCloseConfirm = false;

    $('#orderImportResults').hide();

    $('#orderForm').attr('enctype', 'multipart/form-data').submit();
    $('#orderForm').removeAttr('enctype');

    skipCloseConfirm = true;
}

/*
********************************************************************************
*/

function orderImportSubmit(event)
{
    event.preventDefault();

    submitOrderForm('importOrder');

    var message = [];

    $('.vendor').eq(0).val() || message.push('Select a Client Vendor Name');
    $(this).siblings('#orderImportFile').val() || message.push('Choose a File');

    if (message.length) {

        defaultAlertDialog(message.join('<br>'));

        return false;
    }

    skipCloseConfirm = false;

    $('#orderForm').attr('enctype', 'multipart/form-data').submit();
    $('#orderForm').removeAttr('enctype');

    skipCloseConfirm = true;
}

/*
********************************************************************************
*/

function emptyNumberOfCartonsTitle(tableIndex)
{
    $('input.numberofcarton').eq(tableIndex).val('0');
    $('span.numberofcarton').eq(tableIndex).html('Not Estimated');
}

/*
********************************************************************************
*/

function processSubmitForm()
{
    $('#batchTable').remove();

    return submitOrder();
}

/*
********************************************************************************
*/

function submitOrder()
{
    var truckRows = $('#truckOrderWaves').dataTable().fnSettings().fnRecordsTotal(),
        truckOrder = $('#orderCategoryDiv > input:checked').val();

    if (truckRows && truckOrder == 'regularOrder') {
        emptyNumberOfCartonsTitle(0);
    }

    var params = {}, orderProducts = {}, page = 0;

    tableBatch = formToArray($('#batchTable [data-post]'));

    if (typeof tableBatch['tableData'] != 'undefined') {
        var tableData = tableBatch.tableData;

        $.each(tableData, function(key, batches) {

            $.each(batches, function(index, value){
                if (typeof params[index] != 'undefined') {
                    params[index].push(value);
                } else {
                    params[index] = [value];
                }
            });

        });
    }

    $('.singleOrder').map(function (index) {

        var $this = $(this);

        dataPost = formToArray($('[data-post]', $this));

        hiddenPost = formToArray($(':hidden', $this));


        $.each(dataPost, function(key, data){
            //process product info
            if (key == 'tableData') {
                processProductData(orderProducts, data, index);
            } else {
                params[key] = addNewValueOnArray(params[key], index, data);
            }

        });

        page++;
    });

    params.typeOrder = jsVars['checkType'];
    params.duplicate = $('#duplicateAmount').val();
    params.orderProducts = JSON.stringify(orderProducts);

    if (jsVars['checkType'] == 'Check-Out') {
        params.orderIDs = jsVars['orderIDs'];
    }

    $.blockUI({
        message: 'Submitting Order. Do NOT Close This Window.'
    });

    $.ajax({
        type: 'post',
        url: jsVars['urls']['submitAddOrEditOrders'],
        dataType: 'json',
        data: params,
        success: function(response) {
            processResponeOrder(response);
        }
    });

    $('#orderImportResults').hide();
}

function addNewValueOnArray(arr, key, value)
{
    if (typeof arr == 'undefined') {
        arr = [];
    }

    for (var i = 0; i < key; i++) {
        if (typeof arr[i] == 'undefined') {
            arr[i] = null;
        }
    }

    arr[key] = value;

    return arr;
}

/*
********************************************************************************
*/

function refreshForm()
{
    $('.msgError').remove();
    $('#orderForm .missField').removeClass('missField');
}

/*
********************************************************************************
*/

function processResponeOrder(data)
{
    var $divMsg = $('<div>');

    $.unblockUI();

    refreshForm();

    if (data.status === true) {
        $divMsg.addClass('success').html(data.msg);
        $('body').html('').append($divMsg);

    } else {

        switch(data.code) {
            //missing field : type 1: checkfield inDB, 2 check fields
            case 1:
                defaultAlertDialog(getBatchOrderTable(data));
                break;
            //missingMandatoryValues
            case 2:
                displayErrors(data.msg);
                break;

            //Split carton
            case 3:
                displaySplitCartons(data.msg);
                break;
            default:
                defaultAlertDialog(data.msg);
                break;
        }
    }
}

/*
********************************************************************************
*/

function getBatchOrderTable(data)
{
    var $vendorsArray = data.vendorsArray,
        $nextBatchID = data.nextBatchID;

    var $batchTable = $('<table/>', {
        border: 1,
        id: 'batchTable'
    }).append(
        $('<tr>').append([
            $('<td>').html('Vendor'),
            $('<td>').html('Order'),
            $('<td>').html('Batch'),
            $('<td>')
        ])
    );

    var i = 0;

    $.each($vendorsArray, function($vendor, $orderNumbers) {
        var $rows = $orderNumbers.length;

        var $tr = $('<tr>');
        var $tdVendor = $('<td>', {
            rowspan: $rows,
            text: $vendor
        });

        $tr.append($tdVendor);

        $.each($orderNumbers, function($index, $orderNumber) {

            if ($index) {
                $tr = $('<tr>');
            }

            var $td1 = $('<td>', {
                class: 'orderNumberBatchDisplay',
                text: $orderNumber
            });

            var $span = $('<span>', {
                class: 'batchNumber',
                vendor: $vendor,
                initialValue: $nextBatchID
            }).html($nextBatchID);

            var $td2 = $('<td>').append($span);

            var $orderBatch = $('<input>', {
                type: 'hidden',
                name: 'order_batch[]',
                class: 'orderBatch',
                value: $nextBatchID,
                'data-post': '',
                'data-row-index': i
            });

            var $batchVendor = $('<input>', {
                type: 'hidden',
                name: 'batch_vendor[]',
                class: 'orderBatch',
                'data-row-index': i,
                value: $vendor,
                'data-post': ''
            });

            var $batchOrderNumber = $('<input>', {
                type: 'hidden',
                name: 'batch_orderNumber[]',
                class: 'orderNumberBatchInput',
                value: $orderNumber,
                'data-post': '',
                'data-row-index': i
            });

            var $addBatchButton = $('<button>', {
                class: 'addBatch',
                text: 'Make an Additional Batch',
                onClick: 'return addOrderBatch(this);'
            });

            var $td3 = $('<td>').append($addBatchButton);

            $tr.append([$td1, $td2, $orderBatch, $batchVendor, $batchOrderNumber, $td3]);
            $batchTable.append($tr);
            i++;
        });

        ++$nextBatchID;
    });

    var $continueButton = $('<input>', {
        id: 'continueSubmit',
        value: 'Continue',
        type: 'button',
        onClick: 'return submitOrder();'
    });

    var $divBatchTable = $('<div>', {
        id: 'infoBatch'
    });

    $divBatchTable.append([$batchTable, $continueButton]);

    return $divBatchTable;
}

/*
********************************************************************************
*/

function processProductData(params, data, page)
{
    $.each(data, function(index, productInfo) {
        $.each(productInfo, function(key, value) {

            if (typeof params[key] != 'undefined' &&
                typeof params[key][page] != 'undefined') {
                params[key][page].push(value);
            } else {

                if (page == 0) {
                    params[key] = [];
                }

                params[key][page] = [value];
            }
        });
    });
}

/*
********************************************************************************
*/

function editWorkOrderNumber()
{
    event.preventDefault();

    if (! $(this).is('button')) {
        return false;
    }

    var workOrderNumber = $(this).attr('data-work-order'),
        index = $('.editWorkOrderNumber').index(this);

    if (! workOrderNumber) {
        var message = [];

        if (! $('.vendor option:selected').eq(index).val()) {
            message.push('Select a Client Vendor Name');
        }

        if (! $('.scanOrderNumber').eq(index).val()) {
            message.push('Generate Scan Order Number');
        }

        if (! $('.shipDates').eq(index).val()) {
            message.push('Input Start Ship Date');
        }

        if (! $('.location option:selected').eq(index).val()) {
            message.push('Select Shipping From Location');
        }

        if (message.length) {
            defaultAlertDialog(message.join('<br>'));
        } else {
            generateWorkOrderNumber($(this));
        }
    } else {
        editWorkOrderNumberExecute(workOrderNumber, index);
    }
}

/*
********************************************************************************
*/

function editWorkOrderNumberExecute(workOrderNumber, index)
{
    var data = {
        isDialog: true,
        response: {}
    };

    data.response[workOrderNumber] = {
        workOrderNumber: workOrderNumber,
        vendorID: $('.vendor option:selected').eq(index).val(),
        vendor: $('.vendor option:selected').eq(index).text(),
        userName: $('.userid option:selected').eq(index).text(),
        location: $('.location option:selected').eq(index).text(),
        scanOrderNumber: $('.scanOrderNumber').eq(index).val(),
        shipDate: $('.shipDates').eq(index).val()
    };

    workOrdersLabor.editWorkOrderNumberExecute(data);
}

/*
********************************************************************************
*/

function updateShippedCartons()
{
    var self = this;

    self.orderNum = null;
    self.orderCartons = {};
    self.checksOn = false;

    //**************************************************************************

    self.init = function () {
        $(document).on('click', '.orderDetails', self.clickOrderDetails);
    };

    //**************************************************************************

    self.shippingReport = $('#shippingReport').DataTable({
        data: jsVars.data,
        columns: [{
            title: 'Time Entered',
            className: 'noWrap'
        }, {
            title: 'Customer',
            className: 'noWrap'
        }, {
            title: 'Wave Pick'
        }, {
            title: 'Seldat Order Number'
        }, {
            title: 'Wave Pick Qty'
        }, {
            title: 'Shipped Carton Qty'
        }],
        fnRowCallback: function (nRow, aData) {
            var link = $('<span>').text(aData[3]).addClass('orderDetails');
            $('td', nRow).eq(3).html(link);
        }
    });

    //**************************************************************************

    self.orderCartons = $('#orderCartons').DataTable({
        paging: false,
        aaSorting: [],
        columns: [{
            title: 'Select',
            data: null,
            className: 'centered'
        }, {
            title: 'Customer',
            data: 'vendor',
            className: 'noWrap'
        }, {
            title: 'Seldat Order Number',
            data: 'scanOrderNumber',
            className: 'noWrap'
        }, {
            title: 'UCC',
            data: 'ucc'
        }, {
            title: 'Carton Status',
            data: 'status'
        }, {
            title: 'Manual Status',
            data: 'mStatus'
        }, {
            title: 'Wave Pick',
            data: 'isPick',
            className: 'noWrap'
        }, {
            title: 'Order Shipped',
            data: 'isShipped',
            className: 'noWrap'
        }],
        fnRowCallback: function (nRow, aData) {

            var checkBox = $('<input>').attr('type', 'checkbox')
                .attr('name', 'cartons['+aData.cartonID+']')
                .addClass('ctnStsChecks');
            $('td', nRow).eq(0).html(checkBox);
        }
    });

    //**************************************************************************

    $('#toggleChecks').click(function () {

        self.checksOn = ! self.checksOn;

        var ctnStsChecks = $('.ctnStsChecks');

        $.map(ctnStsChecks, function (checkbox) {
            $(checkbox).prop('checked', self.checksOn);
        });

        return false;
    });

    //**************************************************************************

    self.clickOrderDetails = function (order) {

        self.checksOn = false;

        self.orderNum = typeof order === 'string' ? order : $(this).text();

        $.ajax({
            url: jsVars.urls.getTables,
            dataType: 'json',
            data: {order: self.orderNum},
            success: self.clickOrderDetailsSuccess
        });
    };

    //**************************************************************************

    self.clickOrderDetailsSuccess = function (response) {

        self.orderCartons.clear().rows.add(response.cartons).draw();

        var waveFound = ! response.wavePick === false;

        $('#wavePickSpan').toggle(waveFound);
        response.wavePick ? $('#wavePick').text(response.wavePick) : null;

        $('#orderCartonsDialog').dialog({
            title: 'Edit Order Carton Status',
            width: '75%',
            modal: true
        });
    };

    //**************************************************************************

    $('#cacnelWave').click(function () {
        $.ajax({
            type: 'post',
            url: jsVars.urls.updateShippedCartons,
            dataType: 'json',
            data: {
                cancelWave: true,
                order: self.orderNum
            },
            success: self.clickOrderDetails
        });
        return false;
    });

    //**************************************************************************

    $('#updateAllCartons').click(function () {
        $.ajax({
            type: 'post',
            url: jsVars.urls.updateShippedCartons,
            dataType: 'json',
            data: {
                target: 'order',
                order: self.orderNum
            },
            success: self.clickOrderDetails
        });
        return false;
    });

    //**************************************************************************

    $('#updateCartons').click(function () {

        var data = $('#updateForm').serializeArray();

        data.push({
            name: 'order',
            value: self.orderNum
        });
        data.push({
            name: 'target',
            value: 'cartons'
        });

        $.ajax({
            type: 'post',
            url: jsVars.urls.updateShippedCartons,
            dataType: 'json',
            data: data,
            success: self.clickOrderDetails
        });
        return false;
    });

    //**************************************************************************

    return self.init();
}

/*
********************************************************************************
*/

function isInt(number)
{
    return (typeof number === "number") && Math.floor(number) === number;
}

/*
********************************************************************************
*/

function cloneSingleOrder()
{
    var $qtyDateDiv = $('.datepicker').length;

    refreshForm();

    var $duplicateCount = parseInt($('#duplicateAmount').val());

    if ( ! (isInt($duplicateCount) && $duplicateCount)) {
        return;
    }

    $('#orderImport, #orderCategoryDiv').hide();

    var $breakPage = $('<div>', {class: 'pageBreak'}),
        $newLine = $('<br>');

    $checkedOriginals =  $('#orderNumber1 :checked');

    for (var $i = 0; $i < $duplicateCount; $i ++) {
        var $original = $('#orderNumber1').clone(true, true);

        $($original).attr('id', 'orderNumber' + ($i + 2));
        $('input.quantity', $original).attr('data-table-index', $i + 1);
        $('.msgError', $original).remove();
        $('.barcodeFooter', $original).html('');
        $('[name="customerordernumber[]"]', $original).val('');
        $('.scanOrderNumber', $original).val('');
        $('.scanordernumberSpan', $original).html('');
        $('#pageIndex', $original).html($i + 2);

        $original.insertBefore('#listActionButton');

        $('#listActionButton').before([$breakPage, $newLine]);

    }

    $('.singleOrder').each(function($index, $orderDiv) {

        if ($index) {
            $('[data-post]', $orderDiv).each(function() {

                var $name = $(this).attr('name');
                if (typeof $name !== 'undefined') {
                    if( $name.indexOf('[0]') > -1) {
                        $name = $name.substring(0, $name.indexOf('['));
                        $(this).attr('name', $name + '[' + $index + ']');
                    }
                }
            });

            var $spanReserved = $('<span>').html('Reserved for barcode') ,
                $br = $('<br>');

            var $spanBarcode = $('span.barcode', $orderDiv).empty();

            $($spanBarcode).html([$spanReserved, $br]);

            var $removeOrderButton = $('<input>', {
                type: 'button',
                value: 'Remove Order',
                name: 'removeOrder',
                class: 'removeOrder',
                media: 'print'
            });

            $('.textAreas', $orderDiv).append([$removeOrderButton]);
            $('.removeOrder', $orderDiv).click(removeOrder);

            $('.vendor', $orderDiv)
                .change(updateShippingFrom)
                .val($('#orderNumber1 .vendor').val())
                .trigger('change');

            $('.type', $orderDiv).val($('#orderNumber1 .type').val());
            $('.dcUserID', $orderDiv).val($('#orderNumber1 .dcUserID').val());

            $('.datepicker',$orderDiv).removeClass('hasDatepicker');

            $('.datepicker', $orderDiv).each(function ($dateIndex, $datePicker) {
                $datePicker.id = 'datepicker' + $qtyDateDiv + $dateIndex + 1;

                $qtyDateDiv++;
            });

            $('.datepicker', $orderDiv).change(function () {
                if ($(this).hasClass('cancelDates')) {
                    var $formID = $('.cancelDates').index(this);
                    checkDateField($formID);
                }
            });

            $('.datepicker', $orderDiv).datepicker({'dateFormat': 'yy-mm-dd'});

            $('.datepicker', $orderDiv).keydown(function () {
                return false;
            });

            var addRemoveDescriptionDiv = $('.addRemoveDescription', $orderDiv);
            addRemoveDescriptionDiv.attr('data-table-index', $index);

            addRemoveDescriptionDiv.unbind('click', addRemoveProductDescription);
            addRemoveDescriptionDiv.bind('click', addRemoveProductDescription);
        }

    });

    $.map($checkedOriginals, function(checkedOriginal) {
        if ($(checkedOriginal).attr('type') == 'radio') {
            var value = $(checkedOriginal).attr('value');
            $(':radio[value="' + value + '"]').prop('checked', true);
        }
    });

    $('#duplicateButton, #duplicateAmount, #orderImport, #orderImportResults, #orderCategoryDiv')
        .hide();
    $('#duplicateAmount').val(0);
}

/*
********************************************************************************
*/

function submitLabor(event) {

    var index = $('.submitLabor').index(this);

    var scanNumber = $('.barcodeFooter').eq(index).text().trim();

    scanNumber.length ?
        $.ajax({
            type: 'post',
            url: jsVars['urls']['updateLabor'],
            data: {
                actual: jsVars['isCheckOut'],
                type: 'op',
                scanNumber: scanNumber,
                rushAmount: $('.labor').eq(index).val(),
                otAmount: $('.otLabor').eq(index).val()
            },
            dataType: 'json',
            success: function (response) {

                var rushLabor = parseFloat(response.rushAmount).toFixed(2);
                var otLabor = parseFloat(response.otAmount).toFixed(2);

                $('.labor').eq(index).val(rushLabor);
                $('.otLabor').eq(index).val(otLabor);

                alert('Order Labor Amount has been updated.');
            }
        }) :
        alert('This order does not have a Scan Order Number yet.');

    return false;
}

/*
********************************************************************************
*/

function displaySplitCartons(splitMsg)
{
    var $result = '';

    $.each(splitMsg, function(index, $msg) {
        $result += $msg;
    });

    defaultAlertDialog($result);
}

/*
********************************************************************************
*/

function displayErrors(errors)
{
    $.each(errors, function(index, $orderMessage) {
        var $orderNumberID = 'orderNumber' + (parseInt(index) + 1);
        var $orderNumberIDDiv = $('#' + $orderNumberID);

        var $msgErrors = '';

        $.each($orderMessage, function($name, $message){

            var name1 = $name + '[]';
            var name2 = $name + '[' + index + ']';

            $('[name = "' + name1 + '"], [name = "' + name2 + '"]',
                $orderNumberIDDiv).parent().parent()
                .addClass('missField');

            if ($name === 'cartonLabelsColNoCheck') {
                $('.cartonLabelsColNoCheck', $orderNumberIDDiv)
                    .addClass('missField');
            }

            if ($name === 'PickListColNoCheck') {
                $('.picklistColumn', $orderNumberIDDiv)
                    .addClass('missField');
            }

            if ($name === 'clientOrderNumber') {
                $('[name = "' + $name.toLowerCase() + '[]"]', $orderNumberIDDiv)
                    .parent().parent().addClass('missField');
            }

            $msgErrors += $message + '<br>';
        });

        var $divError = $('<div>', {
            class: 'msgError',
            html: $msgErrors
        });

        $('#' + $orderNumberID).prepend($divError);
    });
}

/*
********************************************************************************
*/

function addNewRows()
{
    var index = $('.btnAddNewRow').index(this);

    var amount = $(this).next('.addRowAmount').val();

    amount = parseInt(amount);

    if (! isInt(amount)) {
        return false;
    }

    var $table = $('.productTable').eq(index);

    for (var $i = 0; $i < amount; $i++) {
        $('.addRemoveDescription:contains("+")', $table).trigger('click');
    }

    return false;
}

/*
********************************************************************************
*/

pasteProduct = {

    newLine: '\n',
    message: {
        'notEnoughRows': 'Current row in table is not enough. Please add {x} more.'
    },

    init: function() {
        var self = pasteProduct;
        var table = $('.productTable');

        $('input.inputCell', table).bind('paste', function(e) {
            var element = $(this),
                data = self.getCopiedData(e),
                rowIndex = pasteProduct.getRowIndex(element),
                isEnoughRow = self.checkEnoughRow(data, rowIndex);

            if (isEnoughRow) {
                self.paste(element, data);
            }

            return false;
        });
    },

    paste: function(element, data) {
        var columnIndex = pasteProduct.getColumnIndex(element);
        var rowIndex = pasteProduct.getRowIndex(element);

        $.each(data, function(index, value) {
            var pasteIndex = rowIndex + index;
            var rowElement = $('.productTable tr').eq(pasteIndex);
            var columnElement = $('td', rowElement).eq(columnIndex);
            $('input', columnElement).val(value).addClass('pastedData');
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
        return pasteProduct.removeEmptyElement(data);
    },

    removeEmptyElement: function(data) {
        var last = data[data.length - 1];

        if (! last) {
            data.pop();
        }
        return data;
    },

    checkEnoughRow: function(data, rowIndex) {
        var availableRow =
                $('.productTable tr:gt(' + rowIndex + ')').length,
            copiedRows = data.length;

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