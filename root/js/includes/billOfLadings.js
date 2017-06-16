/**
 * Created by rober on 26/01/2016.
 */
/*
 ********************************************************************************
 * ORDERS JS
 ********************************************************************************
 */

// variables defined in _default.js

needCloseConfirm = false;
var rowAutoIncrementID = 0;
var orderNumber = [],
    duplicateOrders = [];
    firstVendorID = '';


// This variable can be referenced anywhere

var productTable = {

    headerHeigth: 2,

    columns: [
        'item',
        'rowNo',
        'ordernumber',
        'customerordernumber',
        'pkgs',
        'cartonUnit',
        'weight',
        'countPlates',
        'deptid',
        'clientpickticket',
        'clientordernumber',
        'additionalshipperinformation',
        '#',
        '#'
    ]
};

funcStack.billOfLadings = function () {
    $('.generateOption').click(submitOptionOrderNumberList);
    $('.generateBillOfLadinglabel').click(generateBillOfLadingLabel);
    autocompleteScanOrderNumber();
    $('tbody', $('.productTable')).on( 'click', 'tr', checkOrders);
    setShipFrom();

    //Recreate bill of lading barcode image
    var code = $('.bollabel').val();
    if (code) {
        barcode(code);
    }

    $('#submit').click(function () {
        setFun('Submit');
    });

    // Scroll to element is missing
    if (jsVars['isAddShipment']) {
        scrollToMissingField();
    }
};

/*
********************************************************************************
*/

function getShipFromInfo(params)
{
    var index = $('.generateOption').index(this);
    $.ajax({
        url: jsVars['urls']['getShipFromInfo'],
        dataType: 'json',
        data: {
            params: params
        },
        success: function(response) {
            if (response) {
                $('.shipfromnameSpan').eq(index).html(response.companyName);
                $('.shipfromaddressSpan').eq(index).html(response.address);
                $('.shipfromcitySpan').eq(index).html(response.city);

                $('.shipfromid').eq(index).val(response.id);
                $('.shipfromname').eq(index).val(response.companyName);
                $('.shipfromaddress').eq(index).val(response.address);
                $('.shipfromcity').eq(index).val(response.city);
            } else {
                defaultAlertDialog('Not Found Shipping From');
            }
        }
    });
}

/*
********************************************************************************
*/

function setShipFrom() {
    var vendorID = $('#vendorID').val();
    if (! vendorID) {

        $('.shipfromnameSpan').html();
        $('.shipfromaddressSpan').html();
        $('.shipfromcitySpan').html();

        $('#shipfromid').val();
        $('.shipfromname').val();
        $('.shipfromaddress').val();
        $('.shipfromcity').val();
    }
}

/*
********************************************************************************
*/

function setFun(val)
{
    $('#buttonFlag').val(val);
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

function addRow(param)
{
    $.each(param, function (key, data){
        var duplicate = checkOrderExist(data);
        if (! duplicate) {
            orderNumber.push(data.ordernumber);
            var $row = getRow(getRowIdx(), data);
            $('.productTable').append($row);
        }
    });
    if (duplicateOrders.length > 1) {
        defaultAlertDialog('Order duplicate!');
    }
}

/*
********************************************************************************
*/

function checkOrders()
{
    var index = [],
        orderNumberIndex = [],
        checkedBool = $(this).hasClass('selected');

    $('.addRemove', this).prop('checked', ! checkedBool);

    $(this).toggleClass('selected');

    $('.productTable tbody tr').each(function(row) {
        if ($(this).hasClass('selected') && row >= productTable.headerHeigth) {
            index.push(row);

            var orderIndex  = $('input', this).attr('data-val');
            orderNumberIndex.push(orderIndex);
        }
    });
}

/*
********************************************************************************
*/

function getRow(idx, data)
{
    var rowID = 'row-' + idx;
    var $row = $('<tr>')
        .addClass('batchRows' )
        .attr('align', 'center')
        .attr('id', rowID);
    $.each(productTable.columns, function (field, value) {
        switch(value) {
            case 'item':
                var $cell = $('<td>')
                .addClass('firstCol');
                $('<input>')
                    .attr('type', 'checkbox')
                    .attr('class', 'addRemove')
                    .attr('name', 'scanOrderNumbers[]')
                    .attr('value', data.ordernumber)
                    .attr('data-val', data.ordernumber)
                    .attr('data-post', data.ordernumber)
                    .appendTo($cell);
                break;
            case 'rowNo':
                var $cell = $('<td>');
                $('<span>').addClass('idxCtn')
                    .appendTo($cell)
                    .html(idx + 1);

                break;
            case '#':
                var $cell = $('<td>');
                break;
            default:
                var $cell = $('<td>')
                    .addClass('firstCol');
                $('<span>')
                    .appendTo($cell)
                    .html(data[value]);
                break;
        }
        $row.append($cell);
    });

    return $row;
}

/*
********************************************************************************
*/

function checkOrderExist(data)
{
    if (orderNumber.length > 0) {
        for (i = 0; i <= orderNumber.length; i++) {
            if (data.ordernumber == orderNumber[i]) {
                duplicateOrders.push(data.ordernumber);
                return true;
            }
        }
    }
    return false;
}

/*
********************************************************************************
*/

function generateBillOfLadingLabel(event)
{
    event.preventDefault();

    $.ajax({
        url: jsVars['urls']['getNewLabel'],
        dataType: 'json',
        data: {
            type: 'bill'
        },
        success: function(response) {
            updateBOLLabel(response);
            barcode(response);
        }
    });
}

/*
********************************************************************************
*/

function barcode(barcode)
{
    var img = jsVars['urls']['displayBOLabel']+'/'+barcode;

    $('.barcode').empty();

    var $barcodeImg = barcode ?
        $('<img>', {
            src: img
        }).bind('load', function () {
            updateBOLLabel(barcode);
        }) :
        $('<span>').html('Reserved for barcode');

    $('.barcode').append($barcodeImg);
}

/*
********************************************************************************
*/

function updateBOLLabel(response)
{
    $('.bollabel').val(response);

    if ($('.bollabelSpan').length) {
        $('.bollabelSpan').html(response);
    }

    $('.barcodeFooter').html(response);
}

/*
********************************************************************************
*/

function autocompleteScanOrderNumber()
{
    $('#ordernumber').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: jsVars['urls']['getAutocompleteOrderNumber'],
                dataType: 'json',
                data: {
                    term: request.term
                },
                success: function(data) {
                    if (data.length > 0) {
                        response(data);
                    }
                }
            });
        }
    });
}

/*
********************************************************************************
*/

function submitOptionOrderNumberList()
{
    var vendorID = $('#vendorID').val();

    if (firstVendorID && firstVendorID != vendorID) {
        defaultAlertDialog('You submitted two orders from 2 different clients');
        return;
    }
    var createdOrderDay = $('#createdOrderDay').val();
    var scanordernumber = $('#ordernumber').val();
    var message = '';
    var params = {
        vendorID: vendorID,
        createdOrderDay: createdOrderDay,
        scanordernumber: scanordernumber
    };
    if (! vendorID){
        message = 'Select a Client';
        defaultAlertDialog(message);
        return;
    }

    if ((scanordernumber || createdOrderDay)) {
        addOrderInfoExecute(params);
    } else {
        message = 'Select Create Order Day(s) or Input Scan Order Number';
        defaultAlertDialog(message);
        return;
    }
}

/*
********************************************************************************
*/

function addOrderInfoExecute(params)
{
    $.ajax({
        url: jsVars['urls']['getOrderInfo'],
        dataType: 'json',
        data: {
            params: params
        },
        success: function(response) {
            if (response.errors) {
                firstVendorID = '';
                defaultAlertDialog(response.errors);
            } else {

                getShipFromInfo(params);

                addRow(response.results);

                firstVendorID = firstVendorID ? firstVendorID : params.vendorID;
            }
        }
    });
}

/*
********************************************************************************
*/

function generateNewManualBOL()
{
    var orderNumberAdjust = $('#ordernumbersAdjust').val();
    if (! orderNumberAdjust.length) {
        var message = 'Need input an Adjust Order #';
        defaultAlertDialog(message, $(this));

        return false;
    }

    $.ajax({
        type: 'post',
        url: jsVars['urls']['generateManualBOL'],
        data: {
            orderNumbers: orderNumberAdjust,
        },
        dataType: 'json',
        success: function (response) {
            $('#displayMessage').css('display', 'inline-flex');

            if (response.errors) {
                var message = response.errors.join('<br>');
                $('#displayMessage').html(message);
                $('#displayMessage').addClass('failedMessage');

                return false;
            } else {
                var message = 'Bill Of Lading Label has been added successfully: <strong>' + response.bolLabel + '</strong> for Order#: ' + response.orderNumbers.join(', ');
                $('#displayMessage').html(message);

                $('#displayMessage').addClass('successMessage');
                var tableAPI = dataTables['billOfLadings'].api();
                tableAPI.clear().draw();
            }

        }
    });
}

