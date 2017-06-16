var orderCheckOutParam = null,
    shipOnlineOrders = null,

onlineOrdersDescription = onlineOrdersDescription();

funcStack.scannersMain = function () {

    $('#scans').keyup(updateRowCountDisplay);

    $('#rescan').click(emptyScans);

    $('#scanSubmit').click(submitScans);

    // Old scanner needs to be move into new one later
    var $autoScansOld = $('#autoScansOld');

    $autoScansOld.keyup(autoSubmit);

    if ($autoScansOld.length) {
        $autoScansOld.focus();
    }

    var $autoScans = $('#autoScans');

    automated.storeTextarea($autoScans);
    $autoScans.keyup(automated.autoSubmit);

    if ($autoScans.length) {
        $autoScans.focus();
    }

    if ($('#scans').length) {
        $('#scans').focus();
    }

    if ($('#onlyOnScanner').length) {
        $('#scanner').css({
            'margin-top': '0',
            'width': '275px'
        });
        $('#scanner').css('box-shadow','none');
    }

    $('#shippingCheckOut').submit(function () {

        var success = true;
        var inputBack = 'white';

        $.each(jsVars['shippingInfo'], function (name) {

            var maxLength = jsVars['shippingInfo'][name]['length'];

            $('input.'+name).each(function (dontNeed, input) {

                var inputLength = $(input).val().length;

                success = inputLength > maxLength ? false : success;
                inputBack = inputLength > maxLength ? 'pink' : 'white';
                $(input).css('background', inputBack).css('border-width', 1);
            });
        });

        return success;
    });

    if (jsVars['noPlateInShipOut']) {
        $('.failedMessage').html('No valid plate scanned').show();
    }

    $('body').on('click', '#workOrderVerifyQuantity',
            workOrderVerifyQuantity);

    $('.changeSelect').change(warehouseEnableInterchange);

    $('.confirmSubmit').click(clickOnce);

    $('.descriptionType').click(onlineOrdersDescription.hideShowDescription);

    $('.useUPC').change(changeUPCScan);

    $('.printAllPlate').click(function () {
            $('#platesDetail').submit();
    });

    shipOnlineOrders = {
        params: null,
        dialog: $('#passwordDialog').dialog({
            title: 'Password',
            autoOpen: false,
            width: 220,
            height: 80,
            modal: true
        })
    };

    $('#shipPassword').keyup(shipOnlineOrdersPasswordCheck);

    $('.confirmSelectClientOrder').click(checkSelectedClientOrDerNumber);

    var warehouseID = $('option:selected', '#vendor').attr('data-warehouse-id');
    $('#warehouse').val(warehouseID);

    $('#vendor').change(function(){
      var warehouseID = $('option:selected', this).attr('data-warehouse-id');
      $('#warehouse').val(warehouseID);
   });
};

/*
********************************************************************************
*/

dtMods['inactive'] = {

    fnServerParams: function(aoData) {
        aoData = addControllerSearch(aoData, 'inactive');
    }
};

/*
********************************************************************************
*/

dtMods['styleLocations'] = {

    fnServerParams: function(aoData) {
        aoData = addControllerSearch(aoData, 'styleLocations');
    }
};

/*
********************************************************************************
*/

dtMods['styleUOMs'] = {

    fnServerParams: function(aoData) {
        aoData = addControllerSearch(aoData, 'styleUOMs');
    }
};

/*
********************************************************************************
*/

dtMods['noMezzanine'] = {

    fnServerParams: function(aoData) {
        aoData = addControllerSearch(aoData, 'noMezzanine');
    }
};

/*
********************************************************************************
*/

function submitScans(event)
{
    event.preventDefault();

    defaultConfirmDialog(null, 'submitScannerForm', null, $('#scans'));
}

/*
********************************************************************************
*/

function updateRowCountDisplay()
{
    var newlineCount = $(this).val().split("\n").filter(function (value) {
        return value;
    }).length;

    $('#scanCount span').text(newlineCount);
}

/*
********************************************************************************
*/

function submitScannerForm()
{
    //check if all cartons are scanned correctly in Order Processing Check Out
    var isWorkOrderCheckIn = $('.workOrderCheckInSubmit').length,
        isWorkOrderCheckOut = $('.workOrderCheckOutSubmit').length;

    var scans = $('#scans').val();

    if (isWorkOrderCheckIn || isWorkOrderCheckOut) {

        $.blockUI({
            message: 'Checking Submitted Orders. Do NOT Close This Window.'
        });

        var url = isWorkOrderCheckIn ? jsVars['urls']['workOrderCheckInVerify'] :
            jsVars['urls']['workOrderCheckOutVerify'];

        $.ajax({
            type: 'post',
            url: url,
            dataType: 'json',
            data: {
                scans: scans
            },
            success: function (response) {
                $.blockUI.defaults.onUnblock =
                    workOrderVerifyAjaxSuccess(response, isWorkOrderCheckIn);
                $.unblockUI();
             },
            error: ajaxError
        });
    } else {
        submitClause('#scannerForm');
    }
}

/*
********************************************************************************
*/

function submitClause(element)
{
    $(element).submit();
}

/*
********************************************************************************
*/

function emptyScans(event)
{
    event.preventDefault();

    defaultConfirmDialog(null, 'emptyTextArea', null, $('#scans'));
}

/*
********************************************************************************
*/

function emptyTextArea()
{
    $('textarea').val('');
}

/*
********************************************************************************
*/

automated = {

    processing: false,

    textarea: null,

    startAJAX: function () {
        this.processing = true;
        $('#processingDiv').show();
    },

    endAJAX: function () {
        this.processing = false;
        $('#processingDiv').hide();
    },

    newLineCheck: function (event) {
        var newLinesInScan = automated.textarea.val().indexOf("\n") !== -1;
        var isClick = typeof event !== 'undefined';

        if (isClick) {
            return event.which === 13 || newLinesInScan;
        } else {
            return newLinesInScan;
        }
    },

    storeTextarea:function (textarea) {
        automated.textarea = textarea;
    },

    autoSubmit: function (event) {

        var newLine = automated.newLineCheck(event);

        if (! newLine || automated.processing) {
            return;
        }

        $('.failedMessage, .showsuccessMessage, #errorDiv').hide();

        var scanVal = $('textarea').val();

        $('textarea').val('');

        var request = jsVars['request'];

        automated.startAJAX();

        $.ajax({
            url: jsVars['urls']['automatedScanner'],
            type: 'post',
            data: {
                scans: scanVal,
                request: request
            },
            dataType: 'json',
            success: function (response) {
                var nextTarget = response['next'] || jsVars['scannerTitles'][request];
                $('#needToScan').html(nextTarget);

                if (response['complete']) {
                    if (response['customMessage']) {
                        $('.showsuccessMessage').html(response['customMessage']);
                    }

                    $('.showsuccessMessage').show();
                }

                if (response['error']) {
                    $('.failedMessage').html(response['error']).show();
                }

                automated.endAJAX();

                // Call again if there are new lines in the textarea
                automated.autoSubmit();
            },
            error: function (response) {
                $('#errorDiv').html(response.responseText);
                $('#errorDiv').show();
            }
        });
    }

};

/*
********************************************************************************
*/

function workOrderVerifyAjaxSuccess(response, isCheckIn)
{
    if (response.errors && response.errors.length) {

        var message = response.errors.join('<br>');

        $('#scans').val('');

        $('.failedMessage')
            .html(message)
            .show();
    } else {
        workOrderConfirm(response, isCheckIn);
    }
}

/*
********************************************************************************
*/

function workOrderConfirm(param, isCheckIn)
{
    var workOrders = [],
        orderNumbers = [];

    $.each(param.workOrders, function (workOrder, orderNumber) {

        workOrders.push(workOrder);

        isCheckIn && orderNumbers.push(orderNumber);
    });

    var $confirmForm = $('<form>')
        .attr('method', 'POST');

    var $confirmTable = $('<table>')
        .attr('id', 'confirm');

    tableHeader($confirmTable, 'Verify Work Order Quantities', 2);

    $tr = $('<tr>');

    $('<td>')
        .html('Enter Work Order Quantity')
        .appendTo($tr);

    var $td = $('<td>')
        .appendTo($tr);

    var $input = $('<input>')
        .attr('type', 'text')
        .attr('id', 'quantityEntered')
        .attr('data-quantity-scanned', workOrders.length)
        .attr('data-work-orders', workOrders.join(','))
        .attr('value', '')
        .appendTo($td);

    isCheckIn && $input.attr('data-order-numbers', orderNumbers.join(','));

    $tr.appendTo($confirmTable);

    $tr = $('<tr>');

    $('<td>').appendTo($tr);

    var $td = $('<td>').appendTo($tr);

    $('<input>')
        .attr('type', 'submit')
        .attr('name', 'submit')
        .attr('id', 'workOrderVerifyQuantity')
        .attr('data-is-check-in', isCheckIn)
        .attr('value', 'Submit')
        .appendTo($td);

    $tr.appendTo($confirmTable);
    $confirmTable.appendTo($confirmForm);

    $('body').on('click', '#workOrderVerifyQuantity',
            workOrderVerifyQuantity);

    $('#scannerForm').replaceWith($confirmForm);
}

/*
********************************************************************************
*/

function workOrderVerifyQuantity(event)
{
    event.preventDefault();

    var $table = $('<table>'),
        $quantityInput = $('#quantityEntered'),
        isCheckIn = $(this).attr('data-is-check-in');

    var quantityScanned = $quantityInput.attr('data-quantity-scanned'),
        quantityEntered = $quantityInput.val(),
        workOrders = $quantityInput.attr('data-work-orders');

    if (isCheckIn) {
        var orderNumbers = $quantityInput.attr('data-order-numbers');
    }

    if (quantityScanned == quantityEntered) {

        var $workOrdersForm = $('<form>')
            .attr('method', 'POST')
            .attr('action', jsVars['urls']['workOrders']);

        var $div = $('<div>')
            .css('display', 'none')
            .appendTo($workOrdersForm);

        $('<input>')
            .attr('name', 'workOrders')
            .val(workOrders)
            .appendTo($div);

        if (isCheckIn) {
            $('<input>')
                .attr('name', 'orderNumbers')
                .val(orderNumbers)
                .appendTo($div);
        }

        $workOrdersForm.submit();
    } else {

        var $table = $('<table>').attr('id', 'rejected');

        tableHeader($table, 'You have entered incorrect Work Order quantity', 2);

        tableRow($table, ['Quantity Scanned', 'Quantity Entered']);
        tableRow($table, [quantityScanned, quantityEntered]);

        $('#confirm').replaceWith($table);
    }
}

/*
********************************************************************************
*/

function tableHeader($table, displayTitle, colspan)
{
    var $tr = $('<tr>');

    var $td = $('<td>')
        .attr('colspan', colspan);

    $('<b>')
        .html(displayTitle)
        .appendTo($td);

    $td.appendTo($tr);
    $tr.appendTo($table);
}

/*
********************************************************************************
*/

function tableRow($table, cells)
{
    var $tr = $('<tr>');

    $.each(cells, function (key, text) {
        $('<td>')
            .html(text)
            .appendTo($tr);
    });

    $tr.appendTo($table);
}

/*
********************************************************************************
*/

function onScannerButtons(emptyCellCount, $table)
{
    var $tr = $('<tr>');

    var $td = $('<td>');

    $('<a>')
        .attr('href', jsVars['urls']['goBack'])
        .html('Go Back')
        .appendTo($td);

    $td.appendTo($tr);

    for (var i=0; i<emptyCellCount; i++) {
        $('<td>')
            .appendTo($tr);
    }

    $td = $('<td>')
        .attr('align', 'right');

    $('<a>')
        .attr('href', jsVars['urls']['logout'])
        .html('Log Out')
        .appendTo($td);

    $td.appendTo($tr);

    $tr.appendTo($table);
}

/*
********************************************************************************
*/

function warehouseEnableInterchange()
{
    var disabled = $(this).val() != 'locID';

    $('#warehouse').prop('disabled', disabled);
}

/*
********************************************************************************
*/

function autoSubmit(event)
{
    var newLine = event.which == 13 || this.value.indexOf("\n") != -1;

    if (newLine) {

        $('.failedMessage').hide();
        $('.showsuccessMessage').hide();

        var scanVal = $('textarea').val(),
            useUPC = $('#useUPC').is(':checked');

        if (! scanVal || scanVal == Array(scanVal.length + 1).join("\n")) {
            return;
        }

        $('textarea').val('');

        var params = {
            scans: scanVal,
            useUPC: useUPC,
            useTracking: $('#useTrackingID').is(':checked') && useUPC
        };

        if (useUPC) {
            shipOnlineOrdersExecute(params);
        } else {

            shipOnlineOrders.params = params;

            $('#shipPassword').val('');

            shipOnlineOrders.dialog.dialog('open');
        }
    }
}

/*
********************************************************************************
*/

function shipOnlineOrdersExecute(params)
{
    $.ajax({
        url: jsVars['urls']['checkOut'],
        type: 'post',
        data: params,
        async: false,
        dataType: 'json',
        success: function (response) {
            var nextTarget = response['next'] || 'Packing Slip';
            $('#needToScan').html(nextTarget);

            response.error ? onlineOrdersDescription.empty() :
                onlineOrdersDescription.display(response);

            var $successMsg = $('.showsuccessMessage'),
                $orderShippedMsg = $('.orderShipped');

            response['complete'] ? $successMsg.show() : $successMsg.hide();
            response['complete'] && response['orderShipped'] ?
                $orderShippedMsg.show() : $orderShippedMsg.hide();

            var process = nextTarget != 'Packing Slip';

            $('.useTracking').prop('disabled', process || $('#noUPC').is(':checked'));
            $('.useUPC').prop('disabled', process);

            if (response['error']) {
                $('.failedMessage').html(response['error']).show();
            }
        }
    });
}

/*
********************************************************************************
*/

function shipOnlineOrdersPasswordCheck(event)
{
    if (event.which == 13) {

        shipOnlineOrders.dialog.dialog('close');

        $.ajax({
            url: jsVars['urls']['checkOnlineOrdersShipPassword'],
            type: 'post',
            data: {
                password: $(this).val()
            },
            dataType: 'json',
            success: function (response) {
                if (response) {
                    shipOnlineOrdersExecute(shipOnlineOrders.params);
                } else {

                    var message = 'Incorrect password';

                    defaultAlertDialog(message);
                }
            }
        });
    }
}

/*
********************************************************************************
*/

function ajaxError(xhr, textStatus, errorThrown)
{
    var message = '';

    if (xhr.status == 0) {
        message = 'You are offline!\n Please check your network.';
    } else if (xhr.status == 404) {
        message = 'Requested URL not found.';
    } else if (xhr.status == 500) {

        message = xhr.responseText;
        try {
            //Error handling for POST calls
            message = JSON.parse(xhr.responseText);
        } catch (ex) {
            //Error handling for GET calls
            message = xhr.responseText;
        }

    } else if (textStatus == 'parsererror') {
        message = 'Error.\nParsing JSON Request failed.';
    } else if (textStatus == 'timeout') {
        message = 'Request timed out.\nPlease try later';
    } else {
        message = ('Unknown Error.\n' + xhr.responseText);
    }

    if (message != '' && xhr.status != 500) {
        message = message;
    }
    $.unblockUI();
    defaultAlertDialog(message);

}

/*
********************************************************************************
*/

function clickOnce(event)
{
    var clicked = $(this).attr('data-clicked');

    if (clicked) {
        // reject any succeeding click
        event.preventDefault();
    } else {
        $(this).attr('data-clicked', true);
    }
}

/*
********************************************************************************
*/

function onlineOrdersDescription()
{
    var self = {

        empty: function() {

            $('#descriptionTable').empty();
            $('#descriptionType').hide();

            $('#descriptionTable').attr('data-order-number', '');
            $('#descriptionTable').attr('data-tracking-id', '');
        },

        display: function(response) {

            if (! response.useUPC) {
                return false;
            }

            var $description = $('#descriptionTable'),
                cell = '[data-tracking-id="' + response.trackingID + '"] td';

            if (response.orderNumber !== null
             && $description.attr('data-order-number') != response.orderNumber) {

                self.updateTable(response);
            }

            if (! $description.attr('data-tracking-id') && response.trackingID) {

                $('tr td', $description).removeClass('currentTracking');

                $(cell, $description).eq(1)
                    .addClass('currentTracking');
            }

            var $td = $(cell, $description).eq(1);

            var isMinus = $('button', $td.prev()).hasClass('ui-icon-minus'),
                displayType = $('.descriptionType:disabled').eq(0).attr('id');

            if (response.cartonsScanned) {

                var $li = $('[data-upc="' + response.upc + '"]', $td);
                var requested = $li.attr('data-requested');
                var shipped = Math.min(response.cartonsScanned, requested);
                var caption = response.upc + ' - ' + shipped + ' / ' + requested;

                if (shipped == requested) {

                    $li.removeClass('remaining').addClass('shipped');

                    if (displayType == 'shippedInventory' && isMinus) {
                        $li.show();
                    }
                }

                $li.html(caption);
            }

            $('#descriptionType').show();

            isMinus && self.hideShowDescriptionExecute(displayType);
        },

        updateTable: function(response) {

            var trackingID = '',
                $ul = '',
                $description = $('#descriptionTable'),
                caption = 'Order Number # ' + response.orderNumber,
                firstRun = ! $description.attr('data-order-number');

            self.empty();

            $description.attr('data-order-number', response.orderNumber);

            if ($('caption').length) {
                $('caption').html(caption);
            } else {
                $('<caption>')
                    .html(caption)
                    .appendTo($description);
            }

            $.map(response.description, function (values) {
                if (trackingID != values.trackingID) {

                    trackingID = values.trackingID;

                    var $tr = $('<tr>').attr('data-tracking-id', trackingID),
                        $button = $('<button>')
                            .addClass('addRemoveDescription ui-icon ui-icon-minus')
                            .html('-');

                    $('<td>')
                        .addClass('descriptionHideShowCell')
                        .append($button)
                        .appendTo($tr);

                    var $h5 = $('<h5>')
                            .addClass('trackingID')
                            .html(trackingID);

                    $ul = $('<ul>').addClass('trackingUCCs');

                    $('<td>')
                        .append($h5)
                        .append($ul)
                        .appendTo($tr);

                    $tr.appendTo($description);
                }

                var requested = values.requested,
                    shipped = values.shipped;

                var caption = values.upc + ' - ' + shipped + ' / ' + requested,
                    className = requested > shipped + 1 ? 'remaining' : 'shipped';

                $('<li>')
                    .addClass(className)
                    .attr('data-upc', values.upc)
                    .attr('data-requested', requested)
                    .html(caption)
                    .appendTo($ul);
            });

            $description
                .off('click', '.addRemoveDescription', self.addRemoveDescription)
                .on('click', '.addRemoveDescription', self.addRemoveDescription);

            $description.attr('data-tracking-id', '');

            $('#descriptionType').show();

            if (firstRun) {
                self.hideAll();
            }
        },

        hideShowDescription: function(event) {

            event.preventDefault();

            var id = $(this).attr('id');

            self.hideShowDescriptionExecute(id);
        },

        hideShowDescriptionExecute: function(id) {

            switch (id) {
                case 'showInventory':

                    self.showAll();

                    break;
                case 'shippedInventory':

                    self.showShipped();

                    break;
                case 'remainingInventory':

                    self.showRemaining();

                    break;
                case 'hideInventory':

                    self.hideAll();

                    break;
                default:
                    break;
            }
        },

        hideAll: function() {

            $('#descriptionTable').hide();

            $('.descriptionType').prop('disabled', false);
            $('#hideInventory').prop('disabled', true);
        },

        showAll: function() {

            var $description = $('#descriptionTable');

            self.listItemShow('remaining', $description);
            self.listItemShow('shipped', $description);

            $description.show();

            $('.descriptionType').prop('disabled', false);
            $('#showInventory').prop('disabled', true);
        },

        showShipped: function() {

            var $description = $('#descriptionTable');

            $('.remaining', $description).hide();

            self.listItemShow('shipped', $description);

            $description.show();

            $('.descriptionType').prop('disabled', false);
            $('#shippedInventory').prop('disabled', true);
        },

        showRemaining: function() {

            var $description = $('#descriptionTable');

            $('.shipped', $description).hide();

            self.listItemShow('remaining', $description);

            $description.show();

            $('.descriptionType').prop('disabled', false);
            $('#remainingInventory').prop('disabled', true);
        },

        listItemShow: function(className, $description) {

            $('.' + className, $description).map(function () {

                var $td = $(this).parent().parent();

                $('button', $td.prev()).hasClass('ui-icon-minus') ?
                    $(this).show() : $(this).hide();
            });
        },

        addRemoveDescription: function(event) {

            event.preventDefault();

            var $this = $(this);

            var isPlus = $this.hasClass('ui-icon-plus'),
                $tr = $this.parent().parent();

            if (isPlus) {

                $this
                    .removeClass('ui-icon-plus')
                    .addClass('ui-icon-minus');

                var shippedMode = $('#shippedInventory').prop('disabled'),
                    remainingMode = $('#remainingInventory').prop('disabled');

                var allMode = ! remainingMode && ! shippedMode;

                if (remainingMode || allMode) {
                    $('td ul .remaining', $tr).show();
                }

                if (shippedMode || allMode) {
                    $('td ul .shipped', $tr).show();
                }
            } else {

                $this
                    .removeClass('ui-icon-minus')
                    .addClass('ui-icon-plus');

                $('li', $tr).hide();
            }
        }
    };

    return self;
}

/*
********************************************************************************
*/

function changeUPCScan()
{
    var isDisabled = $('#noUPC').is(':checked');

    $('.useTracking', '#instructions').prop('disabled', isDisabled);
}

/*
********************************************************************************
*/

function checkSelectedClientOrDerNumber(event)
{
    var $table = $(this).closest('table'),
        countOrder = $table.attr('count-order'),
        missArray = [];

    for ( var i = 1; i <= countOrder; i++) {
        var $order = $('input[order-index='+i+']', $table);
        if (! $order.is(':checked')) {
            missArray.push($order[0].name);
        }
    }

    if (missArray.length) {
        event.preventDefault();
        var message = 'Please select client order number: ' + missArray.join(', ');
        defaultAlertDialog(message);
    } else {
        $('#confirmSelectClientOrder').submit();
    }
}