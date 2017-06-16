/*
********************************************************************************
* GLOBAL VARIABLES
********************************************************************************
*/

// variables defined in _default.js

needCloseConfirm = false;

// This variable can be referenced anywhere
var aLengthMenu = [
        [20, 50, 100, 200],
        [20, 50, 100, 200]
    ];
/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/

funcStack.cycleCount = function () {

    createReportBy();

    $('#filterByCustomer, #filterByLocation, #filterBySKU')
            .change(createReportBy);

    $('#btnAddNewRow').on('click', setWidthFormAddNewRow);

    $('input[name="displayBy"]').change(filterDatatable);

    if (jsVars['isCreateCycle']) {
        getCustomer();
    }

    $('#warehouse-input').change(getCustomer);

    locationAutocomplete();

    $('#adjust, #adjust-bottom').on('click', function(){
        defaultConfirmDialog('Are you sure to save data?', 'saveCycle');
    });

    addCycleItemLocationAutoCompleted();

    // add cycle items autocompleted
    addCycleItemAutoCompleted();

    $('#sku-input').on('change', checkSelectWarehouse);

    $('.list-button .selectAll').click(function (e) {
        e.preventDefault();
        $('.printSelect').prop('checked', true);
    });

    $('.list-button .deselectAll').click(function (e) {
        e.preventDefault();
        $('.printSelect').prop('checked', false);
    });

    $('#datepicker').datepicker({dateFormat: 'yy-mm-dd'});

    $('#add-new-SKU').click(function () {
        $('#btnAddNewRow').click();
    });

    $('#create-cycle').on('click', createCycleCount);

    $('.button-action, #add-new-SKU, #adjust-bottom, #adjust')
    .click(function (e) {
        e.preventDefault();
    });

    searchSKUAutoComplete();

    $('#search-sku').click(processSearchSKU);

    $('#create-report-form')
        .on('input', '#search-sku-input', function() {
            inputSKUEvent(this.value, $(this));
        })
        .on('mousemove', '.x', function(event) {
            mouseMoveEvent(event, this);
        })
        .on('touchstart click', '.onX', function(event) {
            clearSearchField(event, $(this))
        });

};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

function createReportBy() {
    var input = $('input[name="filterBy"]:checked').val();
    switch (input) {
        case 'LC':
            $('#customer-input').prop('disabled', true);
            $('#sku-input').prop('disabled', true);
            $('#location-input-from').prop('disabled', false);
            $('#location-input-to').prop('disabled', false);

            $('#sku-input').val(null);
            break;
        case 'CS':
            $('#location-input-from').val(null);
            $('#location-input-to').val(null);
            $('#sku-input').val(null);

            $('#customer-input').prop('disabled', false);
            $('#sku-input').prop('disabled', true);
            $('#location-input-from').prop('disabled', true);
            $('#location-input-to').prop('disabled', true);
            break;
        case 'SK':
            $('#location-input-from').val(null);
            $('#location-input-to').val(null);

            $('#customer-input').prop('disabled', true);
            $('#sku-input').prop('disabled', false);
            $('#location-input-from').prop('disabled', true);
            $('#location-input-to').prop('disabled', true);
            break;
        default:
            $('#filterByCustomer').attr('checked', true);

            $('#customer-input').prop('disabled', false);
            $('#sku-input').prop('disabled', true);
            $('#location-input-from').prop('disabled', true);
            $('#location-input-to').prop('disabled', true);
            break;
    }
}

/*
********************************************************************************
*/

function setWidthFormAddNewRow() {
    $('.ui-dialog').attr('aria-describedby', 'formAddNewRow')
            .css('width', 'auto');
}

/*
********************************************************************************
*/

dtMods['cycleCount'] = {

    fnRowCallback: function (row, rowValues) {

        var cycleID = jsVars['columnNumbers']['cycleID'],
            statusesColumn = jsVars['columnNumbers']['statuses'],
            actionColumn = jsVars['columnNumbers']['viewCycle'],
            actionDelColumn = jsVars['columnNumbers']['actionDelete'],
            dueDateColumn = jsVars['columnNumbers']['dueDate'],
            clientColumn = jsVars['columnNumbers']['client'],
            reportNameColumn = jsVars['columnNumbers']['reportName'],
            reportNameColumn = jsVars['columnNumbers']['reportName'],
            detailURL = jsVars['urls']['viewCycleDetail'],
            deleteURL = jsVars['urls']['deleteCycleCount'],
            auditURL = jsVars['urls']['auditCycle'];

        // Custom Action column
        var cycleIndex = parseInt(rowValues[cycleID]),
            reportName = rowValues[reportNameColumn],
            viewCycleUrl = styleColor = '';

        var tdActionDel = $('td', row).eq(actionDelColumn);
        tdActionDel.html('');

        if (rowValues[statusesColumn] == jsVars['cycleStatus']['AS']
            && ! jsVars['isStaffUser']
        ) {
            $('<button>')
                .prop('type', 'buton')
                .html('Delete')
                .addClass('clDeleteCycleCount')
                .attr('value', rowValues[cycleID])
                .attr('onClick', 'deleteCycleCount(this)')
                .prependTo(tdActionDel);
        }

        if (rowValues[statusesColumn] == jsVars['cycleStatus']['DL']) {
            $('td', row).eq(statusesColumn).addClass('red');
        }

        // Add print PDF button
        var printPDFUrl = httpBuildQuery(jsVars['urls']['printCyclePDF'], {
           cycleID: cycleIndex
        });

        var printButton =
                '<a href="' + printPDFUrl + '" target="_blank">Print</a>';
        $('td', row).eq(actionColumn).html(printButton);


        if (rowValues[statusesColumn] == jsVars['cycleStatus']['AS']
        || rowValues[statusesColumn] == jsVars['cycleStatus']['RC']) {

            viewCycleUrl = httpBuildQuery(detailURL, {
                cycleID: cycleIndex,
                'editable': 'display'
            });
            styleColor = 'color: #cd0a0a';
        } else {
            viewCycleUrl = httpBuildQuery(detailURL, {
                cycleID: cycleIndex
            });
            styleColor = 'color: blue';
        }

        var editAnchor = getHTMLLink({
            link: viewCycleUrl,
            title: ' View',
            getObject: true,
            attributes: {
                class: 'cycleDetail',
                style: styleColor,
                'cycle-id': cycleIndex
            }
        });

        $('td', row).eq(actionColumn).append(editAnchor.prop('outerHTML'));

        if ((rowValues[statusesColumn] == jsVars['cycleStatus']['CC']
        || rowValues[statusesColumn] == jsVars['cycleStatus']['RC'])
        && ! jsVars['isStaffUser']) {

            var auditLink = httpBuildQuery(auditURL, {
                cycleID: cycleIndex
            });

            var auditCycle = getHTMLLink({
                link: auditLink,
                title: ' Adjust',
                getObject: true,
                attributes: {
                    class: 'auditCycle',
                    style: 'color: red',
                    'cycle-id': cycleIndex
                }
            });

            $('td', row).eq(actionColumn).append(auditCycle.prop('outerHTML'));
        }

        // Custom Due date column
        var dueDateValue = rowValues[dueDateColumn],
            currentDay = jsVars['currentDate'];

        if (dueDateValue < currentDay
        && (rowValues[statusesColumn] != jsVars['cycleStatus']['CP']
        && rowValues[statusesColumn] != jsVars['cycleStatus']['DL'])) {

            $('td', row).eq(dueDateColumn).addClass('red');
        }

        // Custom Report name column
        if (rowValues[statusesColumn] == jsVars['cycleStatus']['AS']
        || rowValues[statusesColumn] == jsVars['cycleStatus']['RC']) {
            var viewReportUrl = httpBuildQuery(detailURL, {
                cycleID: cycleIndex,
                editable: 'display'
            });
        } else {
            var viewReportUrl = httpBuildQuery(detailURL, {
                cycleID: cycleIndex
            });
        }

        var editAnchor = getHTMLLink({
            link: viewReportUrl,
            title: reportName,
            getObject: true,
            attributes: {
                class: 'cycleDetail',
                style: 'color: blue',
                'cycle-id': cycleIndex
            }
        });

        $('td', row).eq(reportNameColumn).html(editAnchor.prop('outerHTML'));

        $('td', row).eq(clientColumn).html(rowValues[clientColumn]
	            .replace(/,/g, '<br>'));
    }
};

/*
*******************************************************************************
*/

dtMods['cycleCountAudit'] = {
    aLengthMenu: aLengthMenu,
    fnRowCallback: customAuditTable,
    fnServerParams: function (aoData) {
        addControllerSearch(aoData, jsVars['searcher']['modelName']);
    }
};

/*
********************************************************************************
*/

dtMods['cycleCountAuditNonSizeColor'] = {
    aLengthMenu: aLengthMenu,
    fnRowCallback: customAuditTable,
    fnServerParams: function (aoData) {
        addControllerSearch(aoData, jsVars['searcher']['modelName']);
    }
};

/*
********************************************************************************
*/

dtMods['cycleCountDetail'] = {
    aLengthMenu: aLengthMenu,
    fnRowCallback: customCSSDatatable,
    fnServerParams: function (aoData) {
        addControllerSearch(aoData, jsVars['searcher']['modelName']);
    }
};

/*
********************************************************************************
*/

dtMods['cycleCountDetailNonSizeColor'] = {
    aLengthMenu: aLengthMenu,
    fnRowCallback: customCSSDatatable,
    fnServerParams: function (aoData) {
        addControllerSearch(aoData, jsVars['searcher']['modelName']);
    }
};

/*
********************************************************************************
*/

function customCSSDatatable(row, rowValues) {

    var systemQtyColumn = jsVars['columnNumbers']['systemQty'],
        actualQtyColumn = jsVars['columnNumbers']['actualQty'],
        systemLocColumn = jsVars['columnNumbers']['systemLoc'],
        actualLocColumn = jsVars['columnNumbers']['actualLoc'],
        totalPieceColumn = jsVars['columnNumbers']['totalPiece'],
        statusColumn = jsVars['columnNumbers']['cycleStatus'];

    var systemQty = jsVars['uomByCarton'] ? rowValues[systemQtyColumn] :
            rowValues[totalPieceColumn],
        actualQty = rowValues[actualQtyColumn],
        systemLoc = rowValues[systemLocColumn],
        actualLoc = rowValues[actualLocColumn],
        status = rowValues[statusColumn];

    // Custom location column

    if (systemQty != actualQty && actualQty) {

        $('td', row).eq(actualQtyColumn).css('color', 'red');
    }

    $('td', row).eq(actualQtyColumn).addClass('actQty');

    if (systemLoc != actualLoc && actualLoc) {

        $('td', row).eq(actualLocColumn).css('color', 'red');
    }

    // Disable input value
    var arrStatusCanEdit = [
        jsVars['countItemStatus']['NW'],
        jsVars['countItemStatus']['RC']
    ];

    if ($.inArray(status, arrStatusCanEdit) === -1)
    {
        $('.canEdit', row).removeClass('canEdit');
        var overflow = $('<div>').attr('id', 'overflow');

        $(row).append(overflow);
    }

    $('.canEdit.actQty', row).focusin(function() {
        var data = {
            status : status,
            row : row,
            oldValue : $('input',row).val()
        };

        $('.canEdit.actQty', row).keypress(data, kepPressCanEditRow);
    });
}

/*
********************************************************************************
*/

function customAuditTable(nRow, rowValues) {

    var id = jsVars['columnNumbers']['id'],
        systemQty = jsVars['columnNumbers']['systemQty'],
        actualQty = jsVars['columnNumbers']['actualQty'],
        systemLoc = jsVars['columnNumbers']['systemLoc'],
        actualLoc = jsVars['columnNumbers']['actualLoc'],
        status = jsVars['columnNumbers']['status'];

    var $td = $('td', nRow).eq(id);
    $td.html('');

    var arrStatusCheckbox = [
        jsVars['countItemStatus']['NA'],
        jsVars['countItemStatus']['OP']
    ];

    if ($.inArray(rowValues[status].trim(), arrStatusCheckbox) > -1) {
        $('<input>')
            .prop('type', 'checkbox')
            .addClass('printSelect countItemCheck')
            .attr('value', rowValues[id])

            .prependTo($td);
    }

    if (rowValues[systemQty] != rowValues[actualQty]) {
        $('td', nRow).eq(actualQty).addClass('txtRed');
    }

    if (rowValues[systemLoc] != rowValues[actualLoc]) {
        $('td', nRow).eq(actualLoc).addClass('txtRed');
    }

    if (rowValues[status].trim() == jsVars['cycleStatus']['OP'] ) {
        $('td', nRow).eq(status).addClass('txtRed');
    }

}

/*
 ********************************************************************************
 */

function filterDatatable() {
    var input = $('input[name="displayBy"]:checked').val(),
        oTable = $('#' + jsVars['searcher']['modelName']).dataTable();

    switch (input) {
        case 'discrepancies':
            oTable.fnFilter('Open');
            break;
        case 'accepted':
            oTable.fnFilter('Accepted');
            break;
        case 'recount':
            oTable.fnFilter('Recount');
            break;
        default:
            oTable.fnFilter('');
            return;
    }
}

/*
********************************************************************************
*/

function getCustomer() {
    var warehouseID = $('#warehouse-input').val();
    var byCustomer = $('#filterByCustomer:checked').length;

    if (byCustomer) {
        $.ajax({
            type: 'post',
            url: jsVars['urls']['getCustomerByWarehouseID'],
            data: {
                warehouseID: warehouseID
            },
            dataType: 'json',
            success: function (response) {

                $('.by-customer').html(response);
            }
        });
    }
}

/*
********************************************************************************
*/

function callRecount()
{
    var arrChecked = [];

    $('.countItemCheck:checked').map(function () {
        arrChecked.push($(this).val());
    });

    if (! arrChecked.length) {
        defaultAlertDialog('Please select item from list');
        return;
    }

    var params = {
        countItemIds: arrChecked
    };

    defaultConfirmDialog('Are you sure to Recount?', 'fnRecount', params);
}

/*
********************************************************************************
*/

function fnRecount(params) {

    var countItemIds = params.countItemIds;

    $.ajax({
        url: jsVars['urls']['recountCountItems'],
        data: {
            columnId: -1,
            countItems: countItemIds,
            value: ''
        },
        type: 'POST',
        beforeSend: function () {
            beforeSendAjaxDatatable();
        },
        error: responseAjaxDatatable,
        success: function (result) {
            $.unblockUI();
            responseAjaxDatatable();

            result = $.parseJSON(result);

            defaultAlertDialog(result.msg);
            // Update the datatable
            var modelName = jsVars['searcher']['modelName'];

            var tableAPI = dataTables[modelName].api();
            tableAPI.clear().draw();
        }
    });
}

/*
******************************************************************************
*/

function callAccept()
{
    var arrChecked = [];

    $('.countItemCheck:checked').each(function () {
        arrChecked.push(this.value);
    });

    if (! arrChecked.length) {
        defaultAlertDialog('Please select item from list');
        return false;
    }

    var params = {
        countItemIds: arrChecked
    };

    defaultConfirmDialog('Are you sure to Adjust?', 'fnAccept', params);
}

/*
******************************************************************************
*/

function fnAccept(params)
{
    var countItemIds = params.countItemIds;

    $.ajax({
        url: jsVars['urls']['acceptCountItems'],
        data: {
            columnId: -1,
            countItems: countItemIds,
            value: ''
        },
        type: 'POST',
        beforeSend: function () {
            beforeSendAjaxDatatable();
        },
        error: responseAjaxDatatable,
        success: function (result) {
            responseAjaxDatatable();

            result = $.parseJSON(result);

            defaultAlertDialog(result.msg);

            // Update the datatable
            var modelName = jsVars['searcher']['modelName'];

            var tableAPI = dataTables[modelName].api();
            tableAPI.clear().draw();
        }
    });
}

/*
********************************************************************************
*/

function locationAutocomplete() {
    $('#location-input-from, #location-input-to').autocomplete({
        source: function (request, response) {
            var url = jsVars['urls']['getCustomerNameByWarehouseID'];
            var params = {
                warehouseID: checkSelectWarehouse(),
                term: request.term
            };

            loadInfoFromAjax(url, response, params);
        }
    });
}

/*
********************************************************************************
*/

function checkSelectWarehouse() {
    var warehouseID = parseInt($('#warehouse-input').val());

    if (! warehouseID) {
        var message = 'Please select warehouse!';
        defaultAlertDialog(message);
    }
    return warehouseID;
}

/*
********************************************************************************
*/

function saveCycle() {

    var cycleID = jsVars['cycleID'];

    $.ajax({
        url: jsVars['urls']['saveCycle'],
        dataType: 'json',
        type: 'post',
        data: {
            cycleID: cycleID
        },
        beforeSend: function () {
            $('.dataTables_processing').show();
        },
        error: responseAjaxDatatable,
        success: function (response) {

            $('#dataTables_processing').hide();

            var cycleStatus = jsVars['cycleStatusArray'][response.cycleCountID];

            if (response.invalidCountItem) {
                $('#notification').html('<div id="warningMessage">'
                    + response.invalidCountItem + '</div>');
            }

            $('#cycle-status').val(cycleStatus);

            $('#btnAddNewRow, #add-new-SKU, #adjust, #adjust-bottom')
                .css('display', 'none');

            defaultAlertDialog('Save data successful!');

            var modelName = jsVars['searcher']['modelName'],
                tableAPI = dataTables[modelName].api();

            tableAPI.clear().draw();
        }
    });
}

/*
********************************************************************************
*/

function addCycleItemLocationAutoCompleted() {
    $('#act_loc').autocomplete({
        source: function (request, response) {

            var url = jsVars['urls']['getCustomerNameByWarehouseID'];
            var params = {
                warehouseID: jsVars['warehouseID'],
                term: request.term
            };

            loadInfoFromAjax(url, response, params);
        }
    });
}

/*
********************************************************************************
*/

function addCycleItemAutoCompleted() {

    // SKU auto completed
    addCycleItemSKUAutoCompleted();

    addCycleItemBySKUAutoCompleted();

    // Size auto completed
    addCycleItemStyleAutoCompleted('size');

    // Color auto completed
    addCycleItemStyleAutoCompleted('color');
}

/*
********************************************************************************
*/

function addCycleItemSKUAutoCompleted() {
    $('#sku').autocomplete({
        source: function (request, response) {
            var clientID = parseInt($('#customer-input').val());
            $.ajax({
                url: jsVars['urls']['getSKUByClientID'],
                dataType: 'json',
                data: {
                    term: request.term,
                    clientID: clientID
                },
                success: function (data) {
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

function addCycleItemBySKUAutoCompleted() {
    $('#addSKUBySKU').autocomplete({
        source: function (request, response) {
            var warehouseID = parseInt($('#warehouse-id').val());
            $.ajax({
                url: jsVars['urls']['getSKUByWarehouseID'],
                dataType: 'json',
                data: {
                    term: request.term,
                    warehouseID: warehouseID
                },
                success: function (data) {
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

function addCycleItemStyleAutoCompleted(type) {
    $('#' + type).autocomplete({
        source: function (request, response) {

            var clientID = parseInt($('#customer-input').val()),
                url = jsVars['urls']['loadUPCInfoFromAjax'],
                sku = $('#sku').val();

            if (! sku) {
                defaultAlertDialog('Please input #sku field!');
            }

            var params = {
                term: request.term,
                clientID: clientID,
                sku: sku,
                type: type
            };

            loadInfoFromAjax(url, response, params);
        }
    });
}

/*
********************************************************************************
*/

function loadInfoFromAjax(url, response, params) {
    $.ajax({
        url: url,
        dataType: 'json',
        data: params,
        success: function (data) {
            if (data.length > 0) {
                response(data);
            }
        }
    });
}

/*
********************************************************************************
*/

function btnAddNewRow() {
    $.ajax({
        url: jsVars['urls']['addCustomRow'],
        dataType: 'json',
        success: function (data) {
            if (data.length > 0) {
                response(data);
            }
        }
    });
}

/*
********************************************************************************
*/

function beforeSendAjaxDatatable() {
    $.blockUI({
        message: 'Please wait...'
    });
}

/*
********************************************************************************
*/

function responseAjaxDatatable() {
     $.unblockUI();
}

/*
********************************************************************************
*/

function customAutoCompleteFunc(obj, settings, original)
{
    var colIndex = $(original).index();
    var oldValue = newValue = $('input',original).val();

    var warehouseID = $('#warehosue-id').val();

    var message = 'Warehouse does not exist this location';

    $('input', obj).autocomplete({
        source: jsVars['urls']['autocomplete'][colIndex]
    });

    $('input', obj).keypress(function(event) {
        var keyCode = event.keyCode ? event.keyCode : event.which;

        if (keyCode == '13') {
            newValue = $('input', original).val();

            $.ajax({
                url: jsVars['urls']['checkLocationOnWarehouse'],
                type: 'post',
                data: {
                    fieldName: 'l.displayName',
                    fieldValue: newValue,
                    warehouseID: warehouseID
                },
                success: function (result) {
                    data = $.parseJSON(result);
                    if (! data) {
                        $('input', obj).val(oldValue);
                        defaultAlertDialog(message);
                    }
                }
            });
        }
    });
}

/*
********************************************************************************
*/

function createCycleCount()
{
    var data = validateCycleDataInput();

    if (! data) {
        return false;
    }

    $.blockUI({
        message: 'Processing Create Cycle Count. Do NOT Close This Window.'
    });

    $('#notification').html('');

    $.ajax({
        url: jsVars['urls']['createCycleCount'],
        type: 'post',
        data: {
            data: data
        },
        success: createCycleSuccessful
    });
}

/*
********************************************************************************
*/

function validateCycleDataInput() {

    var reportName = $('#report-name').val().trim(),
        description = $('#report-description').val().trim(),
        dueDate = $('#datepicker').val(),
        warehouseID = parseInt($('#warehouse-input').val()),
        assigneeTo = parseInt($('#assigned-input').val()),
        filterBy = $('input[name="filterBy"]:checked').val(),
        customer = parseInt($('#customer-input').val()),
        sku = $('#sku-input').val().trim(),
        locFrom = $('#location-input-from').val().trim(),
        locTo = $('#location-input-to').val().trim(),
        cycleCountByOUM = $('input[name="cycleCountByOUM"]:checked').val(),
        cycleCountByColorSize =
            $('input[name="cycleCountByColorSize"]:checked').val(),
        currentDay = jsVars['currentDate'],
        message = '';

    if (! reportName) {
        message +='- Please input Report Name.<br>';
    }

    if (! dueDate) {
        message +='- Please input Due Date.<br>';
    }

    if (dueDate && dueDate < currentDay) {
        message += '- Due Date need after current date.<br>';
    }

    if (! warehouseID) {
        message +='- Please select Warehouse.<br>';
    }

    if (! assigneeTo) {
        message += '- Please select User to assign.<br>';
    }

    switch (filterBy) {
        case 'LC':
            if (! locFrom) {
                message += '- Please input Location from.<br>';
            }

            if (! locTo) {
                message += '- Please input Location to.<br>';
            }

            break;
        case 'SK':
            if (! sku) {
                message += '- Please input SKU list!<br>';
            }

            break;
        default:
            if (! customer) {
                message += '- Please select Customer!<br>';
            }
            break;
    }

    if (message) {
        defaultAlertDialog(message);
        return false;
    }

    return {
        reportName: reportName,
        description: description,
        dueDate: dueDate,
        warehouseID: warehouseID,
        assigneeTo: assigneeTo,
        filterBy: filterBy,
        customer: customer,
        sku: sku,
        locFrom: locFrom,
        locTo: locTo,
        cycleCountByOUM: cycleCountByOUM,
        cycleCountByColorSize: cycleCountByColorSize
    };
}

/*
********************************************************************************
*/

function createCycleSuccessful(response) {

    $.unblockUI();

    var data = $.parseJSON(response);

    if (data.errors) {

        var errors = '- ' + data.errors.join('<br>- ');

        defaultAlertDialog(errors);
    }

    if (data.warning) {
        var warnings = '- ' + data.warning.join('<br>- ');
        $('#notification').html('<div id="warningMessage">' + warnings
            + '</div>');
    }

    if (data.status === true) {
        $('#sku-input').val(null);
        defaultAlertDialog('Create cycle count successful!');

        var modelName = jsVars['searcher']['modelName'];

        var tableAPI = dataTables[modelName].api();
        tableAPI.clear().draw();
    }
}

/*
********************************************************************************
*/

function deleteCycleCount(obj)
{
    var cycleID = $(obj).attr('value');
    var params = {
        cycleID: cycleID
    };

    defaultConfirmDialog('Are you sure to delete the Cycle count #' + cycleID +
            ' ?', 'runDeleteCycleCount', params);

}

/*
********************************************************************************
*/

function runDeleteCycleCount(params)
{
    var cycleID = params.cycleID;

    $.ajax({
        url: jsVars['urls']['deleteCycleCount'],
        data: {
            cycleID: cycleID
        },
        type: 'POST',
        beforeSend: function () {
            beforeSendAjaxDatatable();
        },
        error: responseAjaxDatatable,
        success: function (result) {
            responseAjaxDatatable();

            result = $.parseJSON(result);

            defaultAlertDialog(result.msg);

            // Update the datatable
            var modelName = jsVars['searcher']['modelName'];

            var tableAPI = dataTables[modelName].api();
            tableAPI.clear().draw();
        }
    });
}

/*
********************************************************************************
*/

function kepPressCanEditRow(event)
{
    var status = event.data.status;
    var row = event.data.row;
    var oldValue = event.data.oldValue;

    var arrCanEdit = [
        jsVars['countItemStatus']['RC'],
        jsVars['countItemStatus']['NW']
    ];

    if ($.inArray(status, arrCanEdit) < 0) {
        return false;
    }

    var keyCode = (event.keyCode ? event.keyCode : event.which);
    var newValue = $('input',row).val();
    //press Enter
    if (keyCode == '13') {
        if (! $.isNumeric(newValue) || newValue < 0){
            defaultAlertDialog(newValue + ' : qty invalid!');
            $('input', row).val(oldValue);
            return false;
        }
    }
}

/*
********************************************************************************
*/

function searchSKUAutoComplete() {

    $('#search-sku-input').autocomplete({

        source: function (request, response) {

            var sku = $('#search-sku-input').val().trim();

            $.ajax({
                url: jsVars['urls']['searchSKUAutoComplete'],
                dataType: 'json',
                data: {
                    sku: sku
                },
                success: function (result) {
                    if (result.length > 0) {
                        response(result);
                    }
                }
            });
        }
    });
}

/*
********************************************************************************
*/

function processSearchSKU() {

    var sku = $('#search-sku-input').val().trim();

    if (! sku) {
        defaultAlertDialog('Please input sku.');
    } else {

        var customSearch = $('.customSearch');

        if (customSearch.length) {
            $('#searchForm .customSearch').remove();
        }

        $.ajax({
            url: jsVars['urls']['processSearchSKU'],
            type: 'post',
            data: {
                sku: sku
            },
            success: processFilterData
        });
    }
}

/*
********************************************************************************
*/

function processFilterData(response) {

    var cycleIDs = $.parseJSON(response);

    $('#searchForm')
        .append($('<input>')
            .attr('name', 'sOperator')
            .attr('class', 'customSearch')
            .attr('type', 'hidden')
            .val('IN'))
        .append($('<input>')
            .attr('name', 'sKey')
            .attr('class', 'customSearch')
            .attr('type', 'hidden')
            .val('cycle_count_id'));

    cycleIDs.forEach(function(id) {
        $('#searchForm')
            .append($('<input>')
                .attr('name', 'sValues[]')
                .attr('class', 'customSearch')
                .attr('type', 'hidden')
                .val(id));
    });

    filterSearch();
}

/*
********************************************************************************
*/

function toggleClass(value) {
    return value ? 'addClass' : 'removeClass';
}

/*
********************************************************************************
*/

function clearSearchField(event, element) {

    event.preventDefault();

    $(element).removeClass('x onX').val('').change();

    $('#searchForm .customSearch').remove();

    filterSearch();
}

/*
********************************************************************************
*/

function mouseMoveEvent(e, element) {

    var isRemove =
        element.offsetWidth - 18 < e.clientX - element.getBoundingClientRect().left;

    $(element)[toggleClass(isRemove)]('onX');

}

/*
********************************************************************************
*/

function inputSKUEvent(value, element) {

    $(element)[toggleClass(value)]('x');

}

/*
********************************************************************************
*/