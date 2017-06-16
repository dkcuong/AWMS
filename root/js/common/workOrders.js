/*
********************************************************************************
* SHARING WORK ORDERS FUNCTIONALITY                                            *
********************************************************************************
*/

function workOrdersLabor()
{
    var self = {

        editWorkOrderNumber: function (data) {

            $.ajax({
                url: jsVars['urls']['getOrderDataByWorkOrderNumber'],
                dataType: 'json',
                type: 'post',
                data: {
                    data: data
                },
                success: function(response) {

                    if (! data.isCheckOut) {

                        var length = Object.keys(response).length;

                        for (var count = 0; count < length; count++) {

                            var scanOrderNumber = data.scanOrderNumbers[count];

                            response[scanOrderNumber].workOrderNumber =
                                data.workOrderNumbers[count];
                        }
                    }

                    self.editWorkOrderNumberExecute({
                        isDialog: false,
                        response: response
                    });
                }
            });
        },

        //**************************************************************************

        editWorkOrderNumberExecute: function (data) {

            $.ajax({
                url: jsVars['urls']['getClientLabor'],
                data: {
                    data: data.response
                },
                dataType: 'json',
                success: function(response) {
                    if (! Object.keys(response).length) {
                        return false;
                    }

                    if (! Object.keys(response.tables).length) {

                        var message = 'This customer does not have work ' +
                                'order charge codes assigned';

                        defaultAlertDialog(message);

                        return false;
                    } else if (data.isDialog && response.hours.cat == 'a') {

                        var message = 'Work Order # <strong>' + data.workOrderNumber
                                    + '</strong> was already checked out.<br>'
                                    + 'Use Work Order Check-Out page instead.';

                        defaultAlertDialog(message);

                        return false;
                    }

                    $div = $('.woDialog').eq(0);

                    $parent = $div.parent();

                    for (var count = 0; count < Object.keys(data.response).length; count++) {

                        var key = Object.keys(data.response)[count];

                        var currentData = data.response[key];

                        var vendorID = currentData.vendorID,
                            workOrderNumber = currentData.workOrderNumber;

                        if (count) {

                            var $currentDiv = $('.woDialog').eq(0).clone();

                            $currentDiv.appendTo($parent);
                        } else {
                            $currentDiv = $div;
                        }

                        self.workOrderHeader({
                            div: $currentDiv,
                            data: currentData,
                            header: response.header[workOrderNumber],
                            rushHours: response.hours.rushAmt,
                            otHours: response.hours.otAmt
                        });

                        if (response.tables.hasOwnProperty(vendorID)) {
                            self.workOrderLaborTables(response.tables[vendorID],
                                response.values[workOrderNumber], $currentDiv);
                        } else {
                            $('.workOrderLaborContainer', $currentDiv).hide();
                        }
                    }

                    var buttonClass = data.isDialog ? 'woSubmitDialog' : 'woSubmit',
                        isDialog = data.isDialog ? 1 : 0;

                    if (! $('.woSubmitDialog', $currentDiv).length) {
                        $('<button>')
                            .addClass(buttonClass)
                            .attr('data-is-dialog', isDialog)
                            .html('Submit')
                            .on('click', self.submitWorkOrder)
                            .appendTo($currentDiv);
                    }

                    $('.datepicker').datepicker({'dateFormat': 'yy-mm-dd'});

                    data.isDialog && woDialog.dialog('open');
                }
            });
        },

        //**************************************************************************

        workOrderHeader: function (params) {

            var $div = params.div,
                header = params.header,
                data = params.data,
                rushHours = params.rushHours,
                otHours = params.otHours;

            $('.datepicker', $div).val('');

            var isHeader = typeof header !== 'undefined';

            var requestDate = isHeader ? header.rqst_dt : '',
                completeDate = isHeader ? header.comp_dt : '',
                clientWorkOrderNumber = isHeader ? header.client_wo_num : '',
                relatedToCustomer = isHeader ? header.rlt_to_cust: '',
                requestBy = isHeader ? header.rqst_by : '',
                rushHours  = isHeader ? rushHours : '',
                otHours  = isHeader ? otHours : '',
                details = isHeader ? header.wo_dtl : '';

            $('.woDialogWorkOrderNumber', $div).text(data.workOrderNumber);
            $('.woDialogLocation', $div).text(data.location);
            $('.woDialogClient', $div).text(data.vendor);
            $('.woDialogClient', $div).attr('data-vendor', data.vendorID);
            $('.woDialogRequestDate', $div).val(requestDate);
            $('.woDialogCompleteDate', $div).val(completeDate);
            $('.woDialogClientWorkOrderNumber', $div).val(clientWorkOrderNumber);
            $('.woRelatedToCustomer', $div).prop('checked', relatedToCustomer == 1);
            $('.woDialogOrderNumber', $div).text(data.scanOrderNumber);
            $('.woDialogShipDate', $div).text(data.shipDate);
            $('.woDialogRequestBy', $div).val(requestBy);
            $('.woDialogUser', $div).text(data.userName);
            $('.woDialogWorkingHours', $div).val(rushHours);
            $('.woDialogOtWorkingHours', $div).val(otHours);
            $('.woDialogDetails', $div).val(details);
        },

        //**************************************************************************

        workOrderLaborTables: function (tables, values, $div) {

            $('.workOrderLabor', $div).map(function () {
                // loop through labor tables
                var category = $(this).attr('data-code');

                $(this).show();

                if (tables.hasOwnProperty(category)) {

                    $('.quantityContainer', $(this)).map(function () {
                        // loop through rows within a labor table
                        var labor = $(this).attr('data-code');

                        $(this).show();

                        var $quantity = $('.woQty', $(this)),
                            $checkBox = $('.checkBoxes', $(this));

                        $quantity.val('');
                        $checkBox.prop('checked', false);

                        if (tables[category].hasOwnProperty(labor)) {

                            var chargeCode = tables[category][labor].id;

                            var value = typeof values !== 'undefined'
                                     && values.hasOwnProperty(chargeCode)
                                     && values[chargeCode] ? values[chargeCode] : '';

                            $quantity.val(value);
                            $checkBox.prop('checked', value != '');
                        } else {
                            $(this).hide();
                        }
                    });
                } else {
                    $(this).hide();
                }
            });
        },

        //**************************************************************************

        submitWorkOrder: function () {

            var isDialog = $(this).attr('data-is-dialog') == 1;

            var data = {
                    actual: jsVars['isCheckOut'],
                    workOrderNumbers: {}
                };

            $('.woDialog').map(function () {

                var $dialog = $(this),
                    message = [],
                    labor = false;;

                var workOrderNumber = $('.woDialogWorkOrderNumber', $dialog).text();

                data.workOrderNumbers[workOrderNumber] = {
                    relatedToCustomer: $('.woRelatedToCustomer', $dialog).is(':checked') ? 1 : 0,
                    shipDate: $('.woDialogShipDate', $dialog).text(),
                    workOrderDetails: $('.woDialogDetails', $dialog).val(),
                    scanOrderNumber: $('.woDialogOrderNumber', $dialog).text(),
                    labor: {}
                };

                $('.mandatory', $dialog).map(function () {

                    var id = $(this).attr('data-class');
                        value = $(this).val(),
                        variable = $(this).attr('data-name');

                    value.trim() ||
                        message.push($('.' + id + 'Title', $dialog).text() + ' is a mandatory value');

                    data.workOrderNumbers[workOrderNumber][variable] = value;
                });

                $('.headerErrors, .laborErrors, .detailsErrors', $dialog).hide();

                if (message.length) {
                    $('.headerErrors', $dialog)
                        .html(message.join('<br>'))
                        .show();
                }

                message = [];

                $('.quantityContainer', $dialog).map(function () {

                    var checked = $('.checkBoxes', $(this)).prop('checked'),
                        text = $('.titleNames', $(this)).html(),
                        inputValue = $('.woQty', $(this)).val(),
                        chargeCodeID = $('.woQty', $(this)).attr('data-id');

                    var value = inputValue == parseInt(inputValue) ?
                        Math.max(0, inputValue) : 0;

                    if (inputValue && value <= 0) {
                        message.push(text + ' - labor must be greater than zero');
                    } else if (checked && value <= 0) {
                        message.push(text + ' - labor is checked but no value entered');
                    } else if (! checked && value) {
                        message.push(text + ' - value is entered but no labor is checked');
                    } else if (value) {
                        data.workOrderNumbers[workOrderNumber].labor[chargeCodeID] = value;
                    }

                    labor = labor || value > 0;
                });

                if (! labor && jsVars['isCheckOut']) {
                    message.push('Pick at least one labor');
                }

                if (message.length) {
                    $('.laborErrors', $dialog)
                        .html(message.join('<br>'))
                        .show();
                }

                if (! $('.woDialogDetails', $dialog).val() && jsVars['isCheckOut']) {
                    $('.detailsErrors', $dialog)
                        .html('Fill in work details')
                        .show();
                }
            });

            if (! $('.errors:visible').length) {

                var workOrderNumbers = Object.keys(data.workOrderNumbers);

                $.blockUI({
                    message: 'Submitting. Do NOT Close This Window.'
                });

                $.ajax({
                    url: jsVars['urls']['submitWorkOrder'],
                    type: 'post',
                    data: {
                        data: data
                    },
                    dataType: 'json',
                    success: function(response) {
                        $.blockUI.defaults.onUnblock =
                            self.submitAction(workOrderNumbers, true, isDialog);
                    },
                    error: function (response) {
                        $.blockUI.defaults.onUnblock =
                            self.submitAction(workOrderNumbers, false, isDialog);
                    }
                });
            }
        },

        //**************************************************************************

        submitAction: function (workOrderNumbers, isSuccess, isDialog) {

            if (isDialog) {
                if (! isSuccess) {

                    var message = 'Error submitting Work Order # '
                                + workOrderNumbers[0];

                    defaultAlertDialog(message);
                }

                woDialog.dialog('close');
            } else {

                $('.woDialog').hide();

                var message = [];

                var action = jsVars['isCheckOut'] ? 'updated' : 'created',
                    color = isSuccess ? '#090' : '#f00';

                if (isSuccess) {
                    $.map(workOrderNumbers, function (workOrderNumber) {
                        message.push('Work Order # ' + workOrderNumber
                                   + ' was successfully ' + action);
                    });
                } else {
                    $.map(workOrderNumbers, function (workOrderNumber) {
                        message.push('Error submitting Work Order # '
                                   + workOrderNumber);
                    });
                }

                $('<font>')
                    .attr('size', 5)
                    .attr('color', color)
                    .html(message.join('<br>'))
                    .appendTo('body');
            }

            $.unblockUI();
        }
    };

    return self;
};

/*
********************************************************************************
*/

