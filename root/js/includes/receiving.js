/*
********************************************************************************
* GLOBAL VARIABLES
********************************************************************************
*/

// variables defined in _default.js

needCloseConfirm = false;

// This variable can be referenced anywhere
var recNum,
    finisher = {},
    dimensions = [],
    $blankTable,
    locationChecker = {};

var coloring = {
    titleClicked: ''
};

/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/

funcStack.receiving = function () {

    $('#submitLabor').click(function () {
        $.ajax({
            type: 'post',
            url: jsVars['urls']['updateLabor'],
            dataType: 'json',
            data: {
                type: 'rc',
                recNum: recNum,
                rushAmount: $('#labor').val(),
                otAmount: $('#otLabor').val()
            },
            success: function (response) {
                var rushLabor = parseFloat(response.rushAmount).toFixed(2);
                var otLabor = parseFloat(response.otAmount).toFixed(2);

                $('#labor').val(rushLabor);
                $('#otLabor').val(otLabor);
                alert('Labor Amount Updated');
            }
        });
    });

    $('#updateContainer').click(createRCLog);

    $(document).on('keypress','.cartonCount', charcheck);

    $(document).on('keyup', '.cartonCount', updateRCTotal);

    if (typeof jsVars['urls'] !== 'undefined'
    &&  typeof jsVars['urls']['getContainerNames'] !== 'undefined'
    ) {
        $('#container').autocomplete({
            source: jsVars['urls']['getContainerNames']
        });
    }

    $('body').on('focus', '.locInputs', autocompleteLocations);

    $('#completeRCLog').click(completeRCLog);

    $('#saveRCLog').click(function () {
        saveRC('updateRCLogPrint', printRCLog);
    });

    $('#savePlates').click(savePlates);

    $('#saveCartonLabels').click(saveCartonLabels);

    $blankTable = $('#rcLog').clone().removeClass('rcLogs');

    $('#saveRCLabel').click(function () {
        saveRC('updateRCLabel', displayRCLabel);
    });

    if (jsVars['container']) {
        createRCLog();
    }

    $(document).on('focusout', '.locInputs', locationChecker.check);

    $(document).on('click', '#cancelButton', finisher.cancelRequest);

    $(document).on('change', 'input', locationChecker.resetWaringMsg);
    $(document).on('click', 'button', locationChecker.resetWaringMsg);

    $('#redirectToCreate').click(redirectToCreate);

    $('.backToReceiving').click(redirectBack);

    receivingAutocomplete();

    $('#submitUpdate').click(checkContainerUpdate);

    $(document).on('click', '.deleteReceiving', confirmDelete);

    if (typeof jsVars['multiselect'] !== 'undefined') {

        addMultiselectFilter();

        $('.dataTables_scrollHead th').click(function() {
            coloring.titleClicked = $(this).html();
        });
    }

    if (typeof jsVars['backgroundColors'] !== 'undefined') {

        addBackgroundColorClasses(jsVars['backgroundColors']);
    }
};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

function autocompleteLocations()
{
    if ($(this).data('ui-autocomplete') === 'undefined') {
        return;
    }

    var autocompleteSource = jsVars['urls']['getLocationNames']
            +'&recNum='+recNum;

    $(this).autocomplete({
        source: autocompleteSource
    });
}

/*
********************************************************************************
*/

function createRCLog()
{
    var containerName = $('#container').val().trim();
    locationChecker.resetWaringMsg();

    if (! containerName) {
        var message = 'Please provide container number';
        defaultAlertDialog(message);
        return false;
    }
    $.ajax({
        url: jsVars['urls']['getContainerInfoForRC'],
        data: {
            container: containerName
        },
        dataType: 'json',
        success: createRCLogResponse
    });
}

/*
********************************************************************************
*/

function saveCartonLabels()
{
    if (! checkContainer()) {
        return;
    }

    $.ajax({
        url: jsVars['urls']['getPrintUccLabelsFile'],
        data: {
            recNum: recNum
        },
        dataType: 'json',
        success: function(response)
        {
            var andOr = $('<input>')
                    .prop('type', 'hidden')
                    .prop('name', 'andOrs[]')
                    .val('and')
                    .addClass('addedInputs');
            var searchType = $('<input>')
                    .prop('type', 'hidden')
                    .prop('name', 'searchTypes[]')
                    .val('containerRecNum')
                    .addClass('addedInputs');
            var searchValue = $('<input>')
                    .prop('type', 'hidden')
                    .prop('name', 'searchValues[]')
                    .prop('value', recNum)
                    .addClass('addedInputs');

            if (response) {
                var uccLabelDir = $('<input>')
                        .prop('type', 'hidden')
                        .prop('name', 'uccLabelDir')
                        .prop('value', jsVars['uccLabelDir'])
                        .addClass('addedInputs');
                var uccLabelFile = $('<input>')
                        .prop('type', 'hidden')
                        .prop('name', 'uccLabelFile')
                        .prop('value', response)
                        .addClass('addedInputs');
            }

            $('.addedInputs').remove();

            $('#printLabels')
                .append(andOr)
                .append(searchType)
                .append(searchValue)
                .append(uccLabelDir)
                .append(uccLabelFile)
                .submit();
        }
    });
}

/*
********************************************************************************
*/

function savePlates()
{
    if (! checkContainer()) {
        return;
    }

    var urlEncodeContainer = encodeURIComponent(recNum);

    window.open(jsVars['urls']['printPlates']+urlEncodeContainer, '_blank');
}

/*
********************************************************************************
*/

function saveRC(link, action)
{
    if (! checkContainer()) {
        return;
    }

    $.ajax({
        url: jsVars['urls'][link],
        data: {
            recNum: recNum
        },
        dataType: 'json',
        success: function () {
            action();
            checkReadyToComplete();
        }
    });
}

/*
********************************************************************************
*/

function printRCLog()
{
    window.print();
}

/*
********************************************************************************
*/

function displayRCLabel()
{
    window.open(jsVars['urls']['labelReceiving'] + recNum, '_blank');
}

/*
********************************************************************************
*/

function checkReadyToComplete(tally)
{
    tally = tally || {};

    $.ajax({
        url: jsVars['urls']['readyToComplete'],
        data: {
            recNum: recNum
        },
        dataType: 'json',
        success: function (resonse) {

            if (resonse.errors) {
                var message = resonse.errors.join('<br>');
                $('#alertMessages').html(message).addClass('alert alert-error');
            }

            resonse.ready
                ? $('#completeRCLog, .cartonCount, .locInputs').show()
                : $('#completeRCLog, .cartonCount, .locInputs').hide();

            if (resonse.ready && ! $.isEmptyObject(tally)) {
                // Tally is locked if found
                $('.locInputs, .cartonCount').prop('readonly', true);

                var batches = classToArray('.batchID', 'text');
                var upcs = classToArray('.upc', 'text');

                var columns = [];
                $.each(batches, function (index, batch) {
                    var upc = upcs[index];
                    if (typeof columns[upc] == 'undefined') {
                        columns[upc] = [];
                    }
                    columns[upc][batch] = index;
                });

                var columnPalletCounts = [];

                $.each(tally, function (dontNeed, row) {
                    var batch = row['batchID'];
                    var upc = row['upc'];
                    var location = row['location'];
                    var cartonCount = row['cartonCount'];

                    // Get the column
                    var column = columns[upc][batch];

                    columnPalletCounts[column] =
                        typeof columnPalletCounts[column] == 'undefined' ?
                        0 : columnPalletCounts[column] + 1;

                    // Get the page ID
                    var pageID = Math.floor(column / 4);

                    var rowOffset = column % 4;
                    var pageOffset = pageID * 80;
                    var columnOffset = columnPalletCounts[column] * 4;

                    var finalOffset = rowOffset + pageOffset + columnOffset;

                    $('.locInputs').eq(finalOffset).val(location);
                    $('.cartonCount').eq(finalOffset).val(cartonCount);
                });

                updateAllRCTotals();
            }
        }
    });
}

/*
********************************************************************************
*/

function createRCLogResponse(response)
{
    $('.laterButtons').hide();

    var batches = response.batches;
    var tally = response.tally;

    if (! batches) {
        $('#laborDiv').hide();

        var message = 'Container Not Found';

        return defaultAlertDialog(message);
    } else {
        $.ajax({
            url: jsVars['urls']['getLabor'],
            dataType: 'json',
            data: {recNum: response.recNum},
            success: function (response) {
                var rushLabor = parseFloat(response.rushAmt).toFixed(2);
                var otLabor = parseFloat(response.otAmt).toFixed(2);

                $('#labor').val(rushLabor);
                $('#otLabor').val(otLabor);
            }
        });

        $('#laborDiv').show();

    }

    $('.rcLogs').remove();

    $newTable = $blankTable.clone().addClass('rcLogs');
    $('.palletSheetDIV').append($newTable);

    var containerName = $('#container').val();

    $('#saveRCLog, #saveRCLabel').show();

    var batchCount = batches.length;
    var sheetCount = Math.ceil(batchCount/4);

    $('.containerButtons').show();

    for (sheetIndex=1; sheetIndex<sheetCount; sheetIndex++) {
        var $sheetCopy = $('#rcLog').clone().removeAttr('id')
                .addClass('cloneLogs');

        $('.palletSheetDIV').append($sheetCopy);
    }

    var dateInfo;
    var vendorName;
    var locked;

    $.each(batches, function (countStyles, row) {
        locked = row['locked'];
        recNum = row['recNum'];
        showName = row['name'];
        dateInfo = row['setDate'];
        vendorName = row['vendorName'];

        if (locked == 1) {
            dimensions = [];
        } else {
            dimensions = [
                'width',
                'length',
                'height'
            ];
        }

        $.each(row, function (field, value) {
            if (field == 'crossDock' && value) {
                $('.description', '.rcLogs').eq(countStyles).css('font-weight', 'bold');
                $('.description', '.rcLogs').eq(countStyles).css('color', 'red');
            }
            $('.'+field, '.rcLogs').eq(countStyles).html(value);
        });

        // Add the batchID to the dimensions to reference later if they are
        // edited
        $.each(dimensions, function (dontNeed, dimension) {

            var dimCell = $('.'+dimension, '.rcLogs').eq(countStyles);
            var prevValue = $(dimCell).html();

            $(dimCell).editable(function (value) {

                var curClass = $(this).attr('class');
                var curIndex = $('.'+curClass).index($(this));
                batchID = $('.batchID').eq(curIndex).html();

                $.ajax({
                    url: jsVars['urls']['updateBatchDimension'],
                    type: 'post',
                    dataType: 'json',
                    data: {
                        value: value,
                        batchID: batchID,
                        target: dimension
                    },
                    success: function (response) {
                        $(dimCell).html(response);
                    },
                    error: function (error){

                        defaultAlertDialog(error.responseText);
                        $(dimCell).html(prevValue);
                    }
                });
            });
        });
    });

    $('.containerName').html('Name: '+ containerName);
    $('.date').html('Date: '+dateInfo);
    $('.vendorName').html('Vendor Name: '+vendorName);

    var batchError = null;

    $('.style').each(function (index, style) {

        var sku = $('.sku').eq(index).html();
        var batchID = $('.batchID').eq(index).html();

        if (! batchID && sku && ! batchError) {
            batchError = 'Error updating container consistency.<br>Please, '
                +'open a new tab or window input container name and click '
                +'"Update Container" button.';
        }

        var styleString = 'Style - '+ (index+1);
        $(style).html(styleString);
    });

    batchError && defaultAlertDialog(batchError);

    var isLocked = locked == 1;

    if (isLocked) {
        $('#saveCartonLabels, #savePlates').show();
    }

    checkReadyToComplete(tally);

    $('#completeRCLog').prop('disabled', isLocked);

    $('#container').attr('container', containerName);
}

/*
********************************************************************************
*/

function charcheck(event)
{
    var keyCode = event.which;
    return keyCode >= 48 && keyCode <= 57 || keyCode === 8 ? true : false;
}

/*
********************************************************************************
*/

function completeRCLog()
{
    if (! checkContainer()) {
        return;
    }

    var errInfo = '',
        confInfo = '',
        usedMessage = '',
        batchError = false,
        locations = [],
        locationsArray = [],
        nCartonValue = 0,
        nLocationValue = 0;

    var value = $('.locInputs').val();

    $('.locInputs').map(function() {
        if (value) {
            var duplicateLocation = checkDuplicateLocations($(this), locationsArray);
            usedMessage += duplicateLocation.message;
        }

        var location = $(this).val();

        location ? nLocationValue++ : '';

        if (location && !~$.inArray(location, locations)) {
            locations.push(location);
        }
    });

    $('.receivedCartons').each(function() {
        nCartonValue += $(this).text() ? parseInt($(this).text()) : 0;
    });

    var nValues = nLocationValue + nCartonValue;

    if ( ! nValues) {
        defaultAlertDialog('Please enter the Carton Count and Location!');
        return false;

    } else {
        if (! nLocationValue) {
             defaultAlertDialog('Please enter the Location!');
            return false;
        }

        if (! nCartonValue) {
             defaultAlertDialog('Please enter the Carton Count!');
            return false;
        }
    }

    $('.sku').each(function(index){

        var sku = $(this).text();

        if (sku) {

            var batchID = $('.batchID').eq(index).html();

            if (! batchID && sku && ! batchError) {
                batchError = true;
            }

            var masterCarton = $('.initialCount').eq(index).text()
                ? $('.initialCount').eq(index).text() : '0';

            var totalCartonsReceived = $('.receivedCartons').eq(index).text()
                ? $('.receivedCartons').eq(index).text() : '0';

            var message = 'Style "'+sku+'" has '
                         +masterCarton+' in MASTER CARTON and '
                         +totalCartonsReceived+' in TOTAL CARTONS RECVD <br>';

            var intMasterCarton = parseInt(masterCarton);
            var intTotalCartonsReceived = parseInt(totalCartonsReceived);

            if (intMasterCarton < intTotalCartonsReceived) {
                errInfo += errInfo ? '<br>' : '';
                errInfo += message;
            } else if (intMasterCarton > intTotalCartonsReceived) {
                confInfo += confInfo ? '<br>' : '';
                confInfo += message;

            }
        }
    });

    if(usedMessage) {
        confInfo = confInfo + '<br>' + usedMessage;
    }

    if (batchError) {
        errInfo += errInfo ? '<br>' : '';
        errInfo += 'Error checking container consistency.<br>Please, open a new tab '
            +'or window input container name and click "Update Container" button.';
    }

    var param = {
        errInfo: errInfo
    };

    $.ajax({
        url: jsVars['urls']['checkLocationCycleCount'],
        type: 'get',
        dataType: 'json',
        data: {
            recNum: recNum,
            locations: locations
        },
        success: function (response) {
            if (response.errors) {
                defaultAlertDialog(response.errors.join('<br>'));
            } else {
                confInfo ?
                    defaultConfirmDialog(confInfo, 'checkSubmitLocations', param) :
                    checkSubmitLocations(param);
            }
        }
    });
}

/*
********************************************************************************
*/

function checkSubmitLocations(param)
{
    var errInfo = param.errInfo,
        cellCount = 0,
        carton = 0;

    $('.cartonCount, .locInputs').each(function() {
        var pageIncrement = Math.floor(cellCount / 160);
        var skuNo = Math.floor((cellCount % 8) / 2) + pageIncrement*4 + 1;
        var row = Math.floor(cellCount / 8) - pageIncrement*20 + 1;

        if (cellCount % 2 === 0) {
            carton = $(this).val();

            var badCarton = ! $.isNumeric(carton)
                        ||  Math.floor(carton) != carton
                        || carton < 0;

            if (carton && badCarton) {
                errInfo += errInfo ? '<br>' : '';
                errInfo += 'Style-'+skuNo+', row # '+row+': '
                        +'Carton Count - only positive integer values are allowed';
            }
        } else {

            var location = $(this).val().trim();

            if (carton || location) {

                var sku = $('.sku').eq(skuNo - 1).text();

                if (sku) {
                    if (! carton) {
                        errInfo += errInfo ? '<br>' : '';
                        errInfo += 'Style "'+sku+'", row # '+row
                                +': Carton Count is missing';
                    } else if (! location) {
                        errInfo += errInfo ? '<br>' : '';
                        errInfo += 'Style "'+sku+'", row # '+row
                                +': Location Name is missing';
                    }
                } else {
                    errInfo += errInfo ? '<br>' : '';
                    errInfo += 'Style-'+skuNo+', row # '+row
                            +': Style is not defined, no input is allowed';
                }
            }
        }

        cellCount++;
    });

    if (errInfo) {
        defaultAlertDialog(errInfo);
    } else {

        var message = 'You are about to lock this RC Log.<br>'
                    + 'Once this log has been submitted it can not '
                    + 'be modified.';

        defaultConfirmDialog(message, 'finisher.completeRCLogExecute');
    }
}

/*
********************************************************************************
*/

finisher = {

    ajax: null,

    completeRCLogExecute: function () {

        var $completeBlockMessage = $('<div>')
                .html('Submitting RC Log. Do NOT Close This Window');

        $.blockUI({message: $completeBlockMessage});

        var $cancelButton = $('<button>').attr('id', 'cancelButton')
                .html('Click Here To Cancel');

        $completeBlockMessage.after($cancelButton);

        var cartons = classToArray('.cartonCount');
        var locations = classToArray('.locInputs');

        finisher.ajax = $.ajax({
            url: jsVars['urls']['completeRCLog'],
            type: 'post',
            data: {
                recNum: recNum,
                container: $('#container').val(),
                rowCount: jsVars['palletRows'],
                styles: classToArray('.sku', 'text'),
                upcs: classToArray('.upc', 'text'),
                batches: classToArray('.batchID', 'text'),
                uoms: classToArray('.uom', 'text'),
                locations: JSON.stringify(locations),
                cartons: JSON.stringify(cartons)
            },
            dataType: 'json',
            success: finisher.submitSuccess
        });
    },

    cancelRequest: function () {

        finisher.ajax.abort();

        finisher.removeBlocker();

        defaultAlertDialog('RC Log Completion Cancelled');
    },

    removeBlocker: function (response) {
        $.blockUI.defaults.onUnblock = rcLogAjaxSuccess(response);
        $.unblockUI();
    },

    submitSuccess: function (response) {

        if (response.errors) {

            var errInfo = '',
                errors = response.errors;

            if (errors.invalidLocations) {
                $.map(errors.invalidLocations, function(location) {
                    errInfo += '<br>Location Name <strong>' + location
                             + '</strong> is invalid';
                });
            }

            if (errors.wrongWarehouse) {
                $.map(errors.wrongWarehouse, function(location, wrongWarehouses) {
                    errInfo += '<br>Location Name <strong>' + location
                             + '</strong>' + wrongWarehouseMessage(wrongWarehouses);
                });
            }

            if (errors.message) {
                errInfo += errors.message;
            }
        }

        if (errInfo) {

            errInfo = 'Error Completing RC Log:<br>' + errInfo;

            defaultAlertDialog(errInfo);
        }

        var success = ! errInfo;

        finisher.removeBlocker(success);
    }
};

/*
********************************************************************************
*/

function rcLogAjaxSuccess(response)
{
    if (response) {

        $('#saveCartonLabels, #savePlates').show();

        $('.locInputs, .cartonCount').prop('readonly', true);

        $('#completeRCLog').prop('disabled', true);

        // Remove editablity from dimensions
        $('.height, .width, .length').unbind('click.editable');

        defaultAlertDialog('RC Log has been completed.');
    }
}

/*
********************************************************************************
*/

function updateRCTotal()
{
    var currentCell = $(this);
    caculateTotals(currentCell);
}

/*
********************************************************************************
*/

function updateAllRCTotals()
{
    var cartonInputCounts = $('.cartonCount').length;

    var inputsPerPage = jsVars['palletRows'] * 4;

    for (pageCount=0; pageCount<cartonInputCounts; pageCount+=inputsPerPage) {

        for (i=0; i<4; i++) {
            var inputID = pageCount + i;
            var firstInput = $('.cartonCount').eq(inputID);
            caculateTotals(firstInput);
        }
    }
}

/*
********************************************************************************
*/

function caculateTotals(currentCell)
{
    var indexCell = $('.cartonCount').index(currentCell);
    var indexCol = indexCell % 4;
    var indexPage = Math.floor(indexCell / 80);
    var index = indexPage * 4 + indexCol;
    var sum = 0;

    for (var i=0; i<jsVars['palletRows']; i++) {
       var indexInList = indexPage * 80 + indexCol + i * 4;
       var currentValue = $('.cartonCount').eq(indexInList).val();
       sum += Number(currentValue);
    }

    $('.receivedCartons').eq(index).html(sum);
    var uomInfo = $('.uom').eq(index).text();
    var totalUnits = sum*uomInfo;
    $('.units').eq(index).html(totalUnits);
}

/*
********************************************************************************
*/

alertDialogChecker = {

    showAlertAssigned : true,
    showAlertWrongWarehouse : true,
    showAlertInvalid : true,
    showAlertDuplicate : true,

    setStatusDialog: function (typeDialog) {
        if ($(document).find("input#notShowAgain").is(':checked')) {
            alertDialogChecker[typeDialog] = false;
        }
    }
};

/*
********************************************************************************
*/

locationChecker = {

    cell: null,

    location: null,

    cachedLocs: {},

    check: function () {

        if (! this.value) {
            return;
        }

        var storedResponse = locationChecker.cachedLocs[this.value];

        if (storedResponse) {
            locationChecker.alert(storedResponse);
            return;
        }

        locationChecker.cell = this;

        $.ajax({
            url: jsVars['urls']['checkRCLogLocations'],
            type: 'post',
            data: {
                recNum: recNum,
                locations: this.value
            },
            dataType: 'json',
            success: locationChecker.alert
        });
    },

    alert: function (response) {

        // Store the results to reduce redundant AJAX requests
        locationChecker.cachedLocs[locationChecker.cell.value] = {
            usedLocations: response.usedLocations,
            wrongWarehouse: response.wrongWarehouse
        };

        var message = msgDuplicate = null;

        if (response.usedLocations !== false) {
            message = 'Location ' + response.usedLocations +
                    ' has already been ' + 'used in other inventory in system.';
        } else if (response.wrongWarehouse !== false) {
            $.each(response.wrongWarehouse, function(location, values) {

                message = 'Location <strong>' + location + '</strong>';
                message += wrongWarehouseMessage(values);

            });
        } else if (response.validLocations === false) {
            message = 'Location is invalid';
        }

        msgDuplicate = checkDuplicateLocations($(locationChecker.cell),
                        locationsArray = false);

        if (msgDuplicate) {
            message = '<br>' + message;
            msgDuplicate = '<br>' + msgDuplicate;
        }

        if (message) {
            message =  '<strong>Warning!</strong> ' + message + msgDuplicate;
            $('#alertMessages').html(message).addClass('alert alert-warning');
        }
    },

    resetWaringMsg: function() {

        $('#alertMessages').removeClass().html('');
    }
};

/*
********************************************************************************
*/

function checkContainer()
{
    var updatedContainerName = $('#container').attr('container');
    var inputContainerName = $('#container').val();

    if (updatedContainerName && updatedContainerName != inputContainerName) {
        var message = 'Container Name has been changed. Update Container first.';
        defaultAlertDialog(message);
        return false;
    }
    return true;
}

/*
********************************************************************************
*/

function checkDuplicateLocations($cell, locationsArray)
{
    var oldIndex = $('.locInputs').index($cell);
    var oldLocation = $cell.val();
    var result = '';

    if(! locationsArray) {
        $('.locInputs').each(function(index) {
            var currentLocation = $(this).val();
            if (currentLocation && index != oldIndex
                    && currentLocation == oldLocation) {

                result = 'You already assigned location '
                        + oldLocation
                        + ' to other pallet.';
                return;
            }
        });

        return result;

    } else {

        $('.locInputs').each(function(index) {
            var currentLocation = $(this).val();

            if (currentLocation
            && index != oldIndex
            && currentLocation == oldLocation
            && $.inArray(currentLocation, locationsArray) == -1
            ) {

                result = 'You already assigned location ' + oldLocation
                        +' to other pallet(s).\n';
                locationsArray.push(oldLocation);

                return;
            }
        });
    }

    return {
        message: result,
        locationsArray: locationsArray
    };
}

/*
********************************************************************************
*/

function wrongWarehouseMessage(array)
{
    var errInfo = ' belongs to ';

    if (array.length == 1) {
        errInfo += array[0]+' warehouse';
    } else {
        errInfo += 'different warehouses:';
        $.each(array, function(index, value) {
            errInfo += '<br>'+value;
        });
    }

    return errInfo;
}

/*
********************************************************************************
*/

function classToArray(selector, type, stringify)
{
    type = type || 'val';
    var values = [];
    $(selector).each(function (dontNeed, element) {
        var value = type == 'val' ? $(element).val() : $(element).text();
        values.push(value);
    });

    return stringify ? JSON.stringify(values) : values;
}

/*
********************************************************************************
*/

function redirectToCreate()
{
    var createUrl = jsVars['urls']['createReceiving'];
    location.href = createUrl;
}

/*
********************************************************************************
*/

function redirectBack()
{
    var backUrl = jsVars['urls']['display'];
    location.href = backUrl;
}

/*
********************************************************************************
*/

dtMods['receiving'] = {

    fnRowCallback: function(row, rowValues) {

        var receivingColumn = jsVars['columnNumbers']['receiving'],
            statusesColumn = jsVars['columnNumbers']['statuses'],
            actionColumn = jsVars['columnNumbers']['action'],
            link = jsVars['urls']['updateReceiving'];

        var receivingNumber = parseInt(rowValues[receivingColumn]),
            status = rowValues[statusesColumn];

        if (status == 'New' || status == 'Receipted') {

            var editLink = httpBuildQuery(link, {
                receivingID: receivingNumber
            });

            var $editAnchor = getHTMLLink({
                link: editLink,
                title: 'Edit',
                getObject: true
            });

            if (status == 'New') {

                var deleteAnchorHTML = getHTMLLink({
                    title: 'Delete',
                    attributes: {
                        class: 'deleteReceiving',
                        style: 'color: red',
                        'receiving-id': receivingNumber
                    }
                });

                $('td', row).eq(actionColumn).html($editAnchor.prop('outerHTML')
                    + ' / ' + deleteAnchorHTML);
            } else {
                $('td', row).eq(actionColumn).html('').append($editAnchor);
            }
        } else {
            var viewLink = httpBuildQuery(link, {
                view: receivingNumber
            });

            var $viewAnchor = getHTMLLink({
                link: viewLink,
                title: 'View',
                getObject: true
            });

            $('td', row).eq(actionColumn).html('').append($viewAnchor);
        }
    }
};

/*
********************************************************************************
*/

dtMods['receivingContainers'] = {
    fnServerParams: function (aoData) {
        addControllerSearch(aoData, jsVars['searcher']['modelName']);
    }
};

/*
********************************************************************************
*/

dtMods['containerReports'] = {

    fnDrawCallback: function () {
        tableColoring('containerReports');
    },
    fnRowCallback: function (nRow) {
        rowColoring(nRow);
    }
};

/*
********************************************************************************
*/

function receivingAutocomplete()
{
    $('.location').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: jsVars['urls']['getReceivingNumber'],
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

function confirmDelete()
{
    event.preventDefault();

    var receivingID = $(this).attr('receiving-id'),
        message = 'Do you want delete this receiving ' + receivingID + '?';

    defaultConfirmDialog(message, 'deleteReceiving', receivingID);
}

/*
********************************************************************************
*/

function deleteReceiving(receivingID)
{
    $.ajax({
        url: jsVars['urls']['deleteReceiving'],
        dataType: 'json',
        type: 'post',
        data: {
            receivingID: receivingID
        },
        success: function() {
            dataTables['receiving'].fnDraw();
        }
    });
}

/*
********************************************************************************
*/

function checkContainerUpdate(event)
{
    event.preventDefault();

    var receivingNumber = jsVars['recNum'],
        receivingStatus = parseInt($('.receivingStatus').val());

    switch (jsVars['statusArray'][receivingStatus].shortName) {
        case 'FNS' :
            //Check RC Log receiving container
            $.ajax({
                url: jsVars['urls']['checkRCLogContainer'],
                dataType: 'json',
                type: 'post',
                data: {
                    confirm: 'checkRCLog',
                    receivingNumber: receivingNumber,
                    receivingStatus: receivingStatus
                },
                success: continueUpdate
            });
            break;
        case 'RCT' :
            //Check Receipt receiving container
            $.ajax({
                url: jsVars['urls']['checkRCLogContainer'],
                dataType: 'json',
                type: 'post',
                data: {
                    confirm: 'receipt',
                    receivingNumber: receivingNumber,
                    receivingStatus: receivingStatus
                },
                success: function(response){
                    if (! response.status) {
                        continueUpdate(response);
                    } else {
                        var message = 'Please scan container for this Receiving.';
                        defaultAlertDialog(message);
                    }
                }
            });
            break;
        default :
            //Update receiving status
            $.ajax({
                url: jsVars['urls']['updateReceivingStatus'],
                dataType: 'json',
                type: 'post',
                data: {
                    receivingNumber: receivingNumber,
                    receivingStatus: receivingStatus
                },
                success: redirectBack
            });

    }
}

/*
********************************************************************************
*/

function continueUpdate(response)
{
    var data = response.data,
        notRCLog = response.notRCLog,
        status = response.status,
        missQuantity = response.quantity ? response.quantity : '';

    if (notRCLog === true) {
        var message = 'All containers not RC Log. Receiving will change to ' +
            'status Cancel. Continue?';
            data.status = jsVars['cancel'];
        defaultConfirmDialog(message, 'confirmUpdate', data);
        return;
    }
    if (status === true) {
        var message = 'Found missing ' + missQuantity + ' container(s) not RC '
            + 'Log. Do you want continue to complete?';
        defaultConfirmDialog(message, 'confirmUpdate', data);
    } else {
        confirmUpdate(data);
    }
}

/*
********************************************************************************
*/

function confirmUpdate(data)
{
    $.ajax({
        url: jsVars['urls']['confirmUpdateReceivingStatus'],
        dataType: 'json',
        type: 'post',
        data: {
            confirm: 'confirmUpdate',
            data: data
        },
        success: redirectBack
    });
}

/*
 ********************************************************************************
 */
