/*
********************************************************************************
* GLOBAL VARIABLES
********************************************************************************
*/

// This variable can be referenced anywhere
var offset = 1,
    cartonUOMRow = 8,
    cartonUCCRow = 24,
    cartonStatusRow = 30,
    splitDialog,
    splitter,
    styleInfo = [];

var tallyFields = [
    'style',
    'upc',
    'plateCount',
    'cartonCount'
];

var tallyFieldsDB = [
    'sku',
    'upc',
    'plateCount',
    'cartonCount'
];
var checkedItems = [];

// This variable can be referenced anywhere
var aLengthMenu = [
    [20, 50, 100, 200],
    [20, 50, 100, 200]
];

var summaryReport = {

    makeLinks: function (row, rowValues) {

        var columns = jsVars['columnNumbers'];
        var urls = jsVars['urls'];
        var dataAttr = {};

        $.map(jsVars['fields'], function(field) {

            var columnNo = columns[field];
            var value = rowValues[columnNo];

            switch (field) {
                case 'name' :

                    var recNumColumn = columns.recNum,
                        vendorColumn = columns.vendorID,
                        upcColumn = columns.upc,
                        skuColumn = columns.sku;

                    var vendorID = rowValues[vendorColumn];

                    var containerLink = httpBuildQuery(urls['containers'], {
                        recNum: rowValues[recNumColumn]
                    });

                    var $containerAnchor = getHTMLLink({
                        link: containerLink,
                        title: value,
                        attributes: {
                            target: '_blank'
                        },
                        getObject: true
                    });

                    var $vendorAnchor = getHTMLLink({
                        title: 'Display RC Log',
                        attributes: {
                            class: 'rcLog',
                            'data-name': value
                        },
                        getObject: true
                    });

                    var $upcAnchor = getHTMLLink({
                        title: rowValues[upcColumn],
                        attributes: {
                            class: 'upc',
                            'data-vendor': vendorID
                        },
                        getObject: true
                    });

                    var $skuAnchor = getHTMLLink({
                        title: rowValues[skuColumn],
                        attributes: {
                            class: 'sku',
                            'data-vendor': vendorID
                        },
                        getObject: true
                    });

                    $('td', row).eq(columnNo).html('').append($containerAnchor);

                    if (! jsVars['isClient']) {
                        $('td', row).eq(vendorColumn).html('').append($vendorAnchor);
                    }

                    $('td', row).eq(upcColumn).html('').append($upcAnchor);
                    $('td', row).eq(skuColumn).html('').append($skuAnchor);

                    break;
                case 'uom' :
                    // pad UOM with leding zeros
                    value = '000'.substring(0, 3 - value.length) + value;
                    break;
                case 'suffix' :
                    // pad UOM with leding zeros
                    value = value == null ? '' : value;
                    break;
                default:
                    break;
            }

            dataAttr['data-' + field] = value;
        });

        $.each(jsVars['statuses'], function(field, status) {

            var columnNo = columns[field];
            var value = rowValues[columnNo];

            dataAttr.class = 'status';
            dataAttr['data-status'] = status;

            var $anchor = getHTMLLink({
                title: value,
                attributes: dataAttr,
                getObject: true
            });

            $('td', row).eq(columnNo).html('').append($anchor);
        });
    },

    displayRCLog: function () {

        var container = $(this).attr('data-name');

        $('#name', '#rcLog').val(container);

        $('#rcLog').submit();
    },

    displayStyleLocations: function () {

        var value = $(this).html(),
            field = $(this).attr('class'),
            vendorID = $(this).attr('data-vendor');

        $('#value', '#styleLocations').val(value);
        $('#field', '#styleLocations').val(field);
        $('#vendorID', '#styleLocations').val(vendorID);

        $('#styleLocations').submit();
    },

    displayCartons: function () {

        var container = $(this).attr('data-name'),
            upc = $(this).attr('data-upc'),
            prefix = $(this).attr('data-prefix'),
            suffix = $(this).attr('data-suffix'),
            uom = $(this).attr('data-uom'),
            status = $(this).attr('data-status');

        $('#name', '#cartonsTable').val(container);
        $('#upc').val(upc);
        $('#prefix').val(prefix);
        $('#suffix').val(suffix);
        $('#uom').val(uom);

        switch (status) {
            case 'RK' :
                $('#status').val(status);

                $('#manualStatus').val(status);
                break;
            case 'RS' :
                $('#status').val('RK');
                $('#manualStatus').val(status);
                break;
            default:
                $('#status').val(status);
                break;
        }

        $('#cartonsTable').submit();
    }
};

/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/

if (jsVars.requestPage === 'sccInventory') {

    var sccModel = new scc(jsVars.urls, {
        dtName: 'items',
        dataTables: dataTables,
        tableFields: jsVars['itemsFieldIDs'],
        itemColumns: jsVars.dataTables.items.columns
    });

    funcStack.scc = sccModel.init;
    dtMods.items = {fnRowCallback: sccModel.dtMod};
}

funcStack.cartonLabels = function () {

    if (typeof jsVars['closeTabOnLoad'] !== 'undefined' && jsVars['closeTabOnLoad']) {
        // labels are downloaded in PDF - close window immediately after it is displayed
        window.close();
    }
};

funcStack.inventory = function () {

    if (typeof jsVars['customSearcher'] !== 'undefined' && jsVars['customSearcher']) {

        $('.created-date').datepicker({
            'dateFormat': 'yy-mm-dd'
        });

        $('#warehouse-input').change(getCustomer);

        $('.downloadMezzanineTransferred').click(function() {
            $('#alertMessages').hide();
        });
    }

    typeof jsVars['searcher']['multiID'] === 'undefined' ? null :
        searcher.useExternalParams();

    $('#printButton').click(printButton);

    $('#tallyForm').submit(checkTallyForm);

    if (typeof dataTables !== 'undefined' && $('#cartons').length > 0) {
        $('#cartons').dataTable().fnSettings().aoDrawCallback.push({
            fn: updateLabelButtons
        });
    }

    $('#splitForm').submit(submitSplit);

    if (typeof jsVars['urls'] !== 'undefined'
    &&  typeof jsVars['urls']['getContainerNames'] !== 'undefined'
    ) {
        $('#container').autocomplete({
            source: jsVars['urls']['getContainerNames']
        });
    }

    $('#printLabels').click(function () {
        var container = $('#container').val();
        $('#containerInput').val(container);
        reprintLabels();
    });

    $('.printTallySheet').click(printTallySheet);

    $('#updateContainer').click(updateContainer);

    $('#updateTallySheet').click(updateTallySheet);

    $('#print').click(printPage);

    $('#tallyTable tr').on('change', '.loadUPC', autoLoadUPC);

    $('#createCartons').click(createCartons);

    splitDialog = $('#splitterForm').dialog({
        autoOpen: false,
        width: 350,
        modal: true
    });

    updateLabelButtons(false);

    $('.reprintLabels').click(reprintLabels);

    $('#printSplitLabels').click(printSplitLabels);

    $('#selectAll').click(function() {
        $('.printSelect').prop('checked', true);
    });

    $('#deselectAll').click(function() {
        $('.printSelect').prop('checked', false);
    });

    var $summaryReport = $('#summaryReport');

    $summaryReport.on('click', '.rcLog', summaryReport.displayRCLog);
    $summaryReport.on('click', '.upc, .sku', summaryReport.displayStyleLocations);
    $summaryReport.on('click', '.status', summaryReport.displayCartons);

    if (typeof jsVars['styleHistory'] !== 'undefined'
     || typeof jsVars['available'] !== 'undefined') {

        addMultiselectFilter();
    }

    $("#changeUom").click(changeUomByPlate);

    $("#new_uom").keyup(function(event){
        if(event.keyCode == 13){
            return changeUomByPlate();
        }
    });

    $('.list-button .selectAll').click(function (e) {
        e.preventDefault();
        $('.printSelect').prop('checked', true);
    });

    $('.list-button .deselectAll').click(function (e) {
        e.preventDefault();
        $('.printSelect').prop('checked', false);
    });

};

/*
*******************************************************************************
*/

dtMods['summaryReport'] = {

    fnRowCallback: function(row, rowValues) {

        summaryReport.makeLinks(row, rowValues);
    }
};

dtMods['styleLocations'] = {

    fnServerParams: function(aoData) {
        aoData = addControllerSearch(aoData, 'styleLocations');
    }
};

dtMods['receivingReport'] = {

    fnServerParams: function(aoData) {
        aoData = addControllerSearch(aoData, 'receivingReport');
    }
};

dtMods['licensePlateBatch'] = {
    fnServerParams: function(aoData) {
        aoData = addControllerSearch(aoData, jsVars['searcher']['modelName']);
    },
    fnRowCallback: function (row, rowValues) {

        var licensePlate = jsVars['columnNumbers']['licensePlate'],
            batch = jsVars['columnNumbers']['batch'],
            actionColumn = jsVars['columnNumbers']['action'];

        var batchID = rowValues[actionColumn];

        var editUomLPUrl = httpBuildQuery(jsVars['urls']['getCartonEditByPlate'], {
            licensePlate: rowValues[licensePlate],
            batch: batchID
        });

        var editUomButton =
            '<a href="' + editUomLPUrl + '" >Edit UOM</a>';
        $('td', row).eq(actionColumn).html(editUomButton);
    }
};

dtMods['licensePlateCartons'] = {
    aLengthMenu: aLengthMenu,
    fnServerParams: function(aoData) {
        aoData = addControllerSearch(aoData, jsVars['searcher']['modelName']);
    },
    fnRowCallback: function (row, rowValues) {

        var actionColumn = jsVars['columnNumbers']['action'];

        var editUomButton = '<input class="invId printSelect" type="checkbox" value="' + rowValues[actionColumn] + '" checked />';

        $('td', row).eq(actionColumn).html(editUomButton);
    }
};

/*
********************************************************************************
*/

dtMods['requestChangeStatus'] = {
    fnRowCallback: customDatatable,
    fnServerParams: function (aoData) {
        addControllerSearch(aoData, jsVars['searcher']['modelName']);
    }
};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

function customDatatable(nRow, rowValues) {

    var req_dtl_id = jsVars['columnNumbers']['req_dtl_id'],
        status = jsVars['columnNumbers']['sts'];

    var $td = $('td', nRow).eq(req_dtl_id);
    $td.html('');

    var arrStatusCheckbox = [
        jsVars['requestStatusArray']['P']
    ];

    var stsClass = rowValues[status] == 'Pending' ? 'warning' :
        (rowValues[status] == 'Approved' ? 'success' : 'danger');
    $('td', nRow).eq(status).addClass(stsClass);

    if ($.inArray(rowValues[status].trim(), arrStatusCheckbox) > -1) {
        $('<input>')
            .prop('type', 'checkbox')
            .addClass('reqDtlID')
            .attr('value', rowValues[req_dtl_id])
            .attr('checked', true)

            .prependTo($td);
    }

}

/*
********************************************************************************
*/

function processData(event)
{
    var arrChecked = [];

    $('.reqDtlID:checked').each(function () {
        arrChecked.push(this.value);
    });

    if (! arrChecked.length) {
        defaultAlertDialog('Please select item from list');
        return false;
    }

    var params = {
        type: event.target.value,
        reqDtlIDs: arrChecked
    };

    var msg = event.target.value == 'approve' ? 'Approve' : 'Decline';
    defaultConfirmDialog('Are you sure to ' + msg + '?', 'processRequest', params);

}

/*
********************************************************************************
*/

function processRequest(params)
{
    $.ajax({
        url: jsVars['urls']['processRequest'],
        data: params,
        type: 'POST',
        beforeSend: function () {
            $.blockUI({
                message: 'Please wait...'
            });
        },
        error: $.unblockUI(),
        success: function (res) {
            $.unblockUI();

            defaultAlertDialog($.parseJSON(res));

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

function createTallyPrint(response)
{
    $('#showContainerName').val(null);

    if (! response) {
        return;
    }

    var containerName = $('#container').val();

    $('#showContainerName').text(containerName);

    var $tBody = $('#printTable').find('tbody');
    $('#printTable .additionalRow').remove();

    $.each(response, function (rowID, row) {
        for (i=0; i<3; i++) {
            var $tRow = $('<tr>');
            $tRow.addClass('additionalRow');

            $.each(tallyFieldsDB, function (fieldID, field) {
                var value = typeof row[field] === 'undefined' ?
                    null : row[field];
                $tCell = $('<td>').html(value);
                $tRow.append($tCell);
            });
            $tBody.append($tRow);
        }
    });

    // Print the table
    printButton();
}

/*
********************************************************************************
*/

function printTallySheet(event, funcName)
{
    // Get the container's styles and UPCs
    var callback = funcName ? funcName : createTallyPrint;

    $.ajax({
        url: jsVars['urls']['getContainerInfo'],
        dataType: 'json',
        data: {
            container: $('#container').val()
        },
        success: callback
    });
}

/*
********************************************************************************
*/
function autoLoadStyle(response)
{
    if (! response) {
        var message = 'Invalid Container Number';
        defaultAlertDialog(message);

        location.reload();
        return;
    }

    $('.containerButtons').show();

    $.each(response, function (batchID, row) {
        var style = row['sku'];
        styleInfo[style] = row['upc'];
    });

    var option = '<option value="">Select</option>';

    $.each(response, function(batch, styleNUpc) {
        option += '<option>'+styleNUpc['sku']+'</option>';
    });

    $('#tallyTable tr').each(function(index){
        var select = '<select class="loadUPC styles forScreen" data-column="style"'
                +'data-row="'+(index-1)+'">'+option+'</select>';

        if (index > 0) {
            $(this).children('td').eq(0).empty().html(select
              +'<span class="forPagePrint"></span>');
        }
    });
}

/*
********************************************************************************
*/
function autoLoadUPC()
{
    var style = $(this).val();
    var dataRow = $(this).attr('data-row');
    var upc = styleInfo[style];
    $('.upcs[data-row='+dataRow+']').val(upc);
}


/*
********************************************************************************
*/

function updateContainer()
{
    // First check that there are no data to be overwritten
    var isData = false;

    $('#tallyTable input').each(function () {
        isData = this.value ? true : isData;
    });

    if (isData) {
        var message = 'There is an information in this tally sheet.<br>'
                    + 'Are you sure you want to get tally sheet for this container?';

        defaultConfirmDialog(message, 'updateContainerExecute');
    } else {
        updateContainerExecute();
    }
}

/*
********************************************************************************
*/

function updateContainerExecute()
{
    $('#tallyTable td').each(function (index) {
        if ($(this).children('select').length) {

            var dataRow = index / 4 - 1;

            $(this).html('<input data-row="'+dataRow+'" class="styles" '
                        +'data-column="style" type="text">');

        } else if ($(this).children('input').length) {

            $(this).children('input').val('');
        }
    });

    $.ajax({
        url: jsVars['urls']['getContainerTally'],
        data: {
            container: $('#container').val()
        },
        dataType: 'json',
        success: updateContainerResponse
    });
}

/*
********************************************************************************
*/

function updateTallySheet(param)
{
    if (! $('#container').val()) {
        var message = 'Container Not Found';
        return defaultAlertDialog(message);
    }

    $('input[type=text]').css('background', 'white');

    var tallyRows = getTallyData();

    var forcedGo = typeof param === 'undefined' ? 'NO' : param['forcedGo'];

    $.ajax({
        url: jsVars['urls']['updateTally'],
        type: 'post',
        data: {
            container: $('#container').val(),
            tally: tallyRows,
            forcedGo: forcedGo
        },
        dataType: 'json',
        success: validateTallyUpdate
    });
}

/*
********************************************************************************
*/

function validateTallyUpdate(response)
{
    if (typeof response.badProducts !== 'undefined') {
        $.each(response.badProducts, function(rowID, dontNeed) {
            $('input[data-row='+rowID+'].upcs, input[data-row='+rowID+'].styles')
                .css('background', 'pink');
        });

        var message = response.badProducts.length > 1
            ? 'Some of the Styles and UPCs were not found'
            : 'One of the Styles and UPCs were not found';

        defaultAlertDialog(message);
        return;
    }

    if (typeof response.badTally !== 'undefined') {

        var message = '';
        $.each(response.badTally, function(style, row) {
            message += 'Incorrect carton count for Style <string>'+style+
                      +'</string><br> '+row.passed+' cartons were in tally<br>'
                      + row.actual+' total cartons in container<br>';
        });

        var param = {
            forcedGo: 'YES'
        };

        defaultConfirmDialog(message, 'updateTallySheet', param);
    } else {
        var message = 'Container has been updated';
        defaultAlertDialog(message);
    }
}

/*
********************************************************************************
*/

function getTallyData()
{
    var tallyRows = [];

    // Probably should have use form serialize. but too late
    for (rowID=0; rowID<jsVars['tallyRows']; rowID++) {
        var emtpy = true;

        $('[data-row='+rowID+']').each(function () {
            emtpy = this.value ? false : emtpy;
        });

        if (emtpy) {
            continue;
        }

        $('[data-row='+rowID+']').each(function (fieldID) {
            if (! $(this).val()) {
                $(this).prop('placeholder', 'Missing');
            }
        });

        tallyRows[rowID] = {};

        tallyRows[rowID]['rowNum'] = rowID;
        $.each(tallyFields, function (dontNeed, field) {
            tallyRows[rowID][field] = $('[data-row='+rowID+'].'+field+'s').val();
        });

    }

    return tallyRows;
}

/*
********************************************************************************
*/

function updateLabelButtons(response)
{
    var $tableSettings = $('#cartons').dataTable().fnSettings();

    var fnRecordsTotal = $tableSettings ? $tableSettings.fnRecordsTotal() : null;

    var records = typeof response === 'object' ? response._iRecordsDisplay
            : fnRecordsTotal;

    if (! records) {
        return;
    }

    if (records) {
        $('#printFrom').val(1);
        $('#printTo')
            .val(Math.min(900, records))
            .attr('maxValue', records);
    } else {
        $('#printFrom').val(0);
        $('#printTo')
            .val(0)
            .attr('maxValue', 0);
    }
}

/*
********************************************************************************
*/

function updateContainerResponse(response)
{

    if (! response) {
        //Get the container's styles and UPCs by using printTallySheet method
        //and go to next method
        return printTallySheet(null, autoLoadStyle);
    }

    $('.containerButtons').show();

    for (var rowID=0; rowID<jsVars['tallyRows']; rowID++) {

        if (typeof response[rowID] === 'undefined') {
            continue;
        }

        $.each(tallyFields, function (dontNeed, field) {
            var value = response[rowID][field];
            $('input[data-row='+rowID+'].'+field+'s').val(value);
        });
    }
}

/*
********************************************************************************
*/

function printButton(e)
{
    typeof e == 'undefined' ? false : e.preventDefault();
    $('.doPrint').show();
    window.print();
    $('.doPrint').hide();
}

/*
********************************************************************************
*/


function checkTallyForm()
{
    var noFlag = true;
    $('.plateInputs').each(function () {
        if (this.value != 0 && this.value.length != 8) {
            this.focus;
            noFlag = false;
            return false;
        }
    });

    if (! noFlag) {
        var message = 'License Plates must be eight digits.';
        defaultAlertDialog(message);

    }

    return noFlag;
}

/*
********************************************************************************
*/

dtMods['cartons'] = {

    fnServerParams: function(aoData) {
        aoData = addControllerSearch(aoData, 'cartons');
    },

    fnRowCallback: function(row, rowValues) {
        if (typeof jsVars['splitCartons'] != 'undefined'
        &&  jsVars['splitCartons'] && rowValues[cartonStatusRow] == 'RK'
        ) {

            var splitLink = $('<span>')
                    .addClass('splitCartonsLink')
                    .attr('data-ucc', rowValues[cartonUCCRow])
                    .attr('data-uom', rowValues[cartonUOMRow])
                    .text('Split Carton ' + rowValues[cartonUCCRow])
                    .bind('click', splitter.splitByPieces);

            $('td', row).eq(cartonUCCRow).html(splitLink);
        } else {
            $('td', row).eq(cartonUCCRow).html(rowValues[cartonUCCRow]);
        }


        if (jsVars['isCycleCarton']) {
            var cycleDate = jsVars['columnNumbers']['created_at'];

            if (rowValues[cycleDate] && dateDiff(rowValues[cycleDate])) {
                $(row).css('color', 'blue');
                $(row).attr('title', 'This carton was added by cycle count ' +
                    'function.');
            }
        }

    }
};

/*
********************************************************************************
*/

dtMods['batches'] = {

    fnRowCallback: function(row, rowValues) {

        var plateColumn = jsVars['columnNumbers']['plate'];

        if (~$.inArray(jsVars['modify'], ['addCartons', 'splitBatches'])
         && rowValues[plateColumn] != 'Not Received') {

            var method = jsVars['modify'],
                batchColumn = jsVars['columnNumbers']['batchID'];

            var titlePrefix = jsVars['modify'] == 'addCartons' ?
                'Add Cartons to' : 'Split Batch';

            var link = httpBuildQuery(jsVars['urls'][method], {
                batchID: rowValues[batchColumn]
            });

            var $anchor = getHTMLLink({
                link: link,
                title: titlePrefix + ' ' + rowValues[batchColumn],
                getObject: true
            });

            $('td', row).eq(batchColumn).html('').append($anchor);
        }
    }
};

/*
********************************************************************************
*/

dtMods['containers'] = {

    fnRowCallback: function(row, rowValues) {

        if (jsVars['modify'] == 'addBatches') {

            var nameColumn = jsVars['columnNumbers']['name'],
                recNumColumn = jsVars['columnNumbers']['recNum'];

            var link = httpBuildQuery(jsVars['urls']['addBatches'], {
                container: rowValues[recNumColumn]
            });

            var $anchor = getHTMLLink({
                link: link,
                title: 'Add Batches ' + rowValues[nameColumn],
                getObject: true
            });

            $('td', row).eq(nameColumn).html('').append($anchor);
        }
    }
};

/*
********************************************************************************
*/

dtMods['modifyContainers'] = {

    fnRowCallback: function(row, rowValues) {

        if (jsVars['editable'] == 'containers') {

            var nameColumn = jsVars['columnNumbers']['name'],
                modifyColumn = jsVars['columnNumbers']['modify'],
                recNumColumn = jsVars['columnNumbers']['recNum'];

            var link = httpBuildQuery(jsVars['urls']['modify'], {
                container: rowValues[recNumColumn]
            });

            var $anchor = getHTMLLink({
                link: link,
                title: 'Modify ' + rowValues[nameColumn],
                getObject: true
            });

            $('td', row).eq(modifyColumn).html('').append($anchor);
        }
    }
};

/*
********************************************************************************
*/

splitter = {

    uom: null,
    ucc: null,
    intUOM: null,
    newUOM: null,

    splitByPieces: function () {
        var uom = splitter.uom = $(this).attr('data-uom');
        splitter.ucc = $(this).attr('data-ucc');
        splitter.intUOM = Number(uom);

        $('#showUOM').text(uom);
        $('#newUOM').val(null);

        splitDialog.dialog('option', 'buttons', {});

        if (uom == 1) {
            $('#splitterForm p').hide();
            $('#splitterForm #oneUOM').show();
        } else {
            $('#splitterForm p').show();
            $('#splitterForm #oneUOM').hide();
        }

        $('.calculated, .remainderMessage').hide();

        $('#calculate').unbind('click').bind('click', splitter.splitCalculate);

        splitDialog.dialog('open');
    },

    /*
    ****************************************************************************
    */

    splitCalculate: function() {
        var newUOM = this.newUOM = $('#newUOM').val();

        // Validate new UOM
        if (newUOM % 1 !== 0
        ||  newUOM < 1
        ||  newUOM >= splitter.intUOM) {
            $('.calculated').hide();
            splitDialog.dialog('option', 'buttons', null);
            var message = 'Invalid UOM submitted for new cartons.';
            defaultAlertDialog(message);
            return false;
        }

        $('#pieceCount').text(newUOM);


        var pluralPieces = newUOM == 1 ? 'none' : 'inline-block';
        $('#pluralPieces').css('display', pluralPieces);

        var cartonCount = Math.floor(splitter.uom / newUOM);
        $('#cartonCount').text(cartonCount);

        var displayPlural = cartonCount == 1 ? 'none' : 'inline-block';
        $('#pluralCartons').css('display', displayPlural);

        var peiceRemainder = splitter.uom % newUOM;

        var remainderPlural = peiceRemainder == 1 ? 'none' : 'inline-block';
        $('#remainderPlural').css('display', remainderPlural);

        $('.calculated').show();
        var displayRemainderMessage = peiceRemainder > 0 ? 'block' : 'none';
        $('.remainderMessage').css('display', displayRemainderMessage);

        $('#pieceRemainder').text(peiceRemainder);

        splitDialog.dialog('option', 'buttons', {
            'Split Carton': splitter.splitCarton
        });

        return true;
    },

    /*
    ****************************************************************************
    */

    splitCarton: function() {
        // Recheck the calculation incase the value has changed
        if (! splitter.splitCalculate()) {
            return;
        }

        $.ajax({
            url: jsVars['urls']['splitCarton'],
            type: 'post',
            dataType: 'json',
            data: {
                ucc: splitter.ucc,
                uom: splitter.intUOM,
                newUOM: splitter.newUOM
            },
            success: splitter.splitSuccess
        });
    },

    /*
    ****************************************************************************
    */

    splitSuccess: function(response)
    {
        splitDialog.dialog('close');

        var message = 'New Cartons Created: <br>'
            + response.children.join('<br>');

        defaultAlertDialog(message);

        dataTables['cartons'].fnDraw();
    }
};

/*
********************************************************************************
*/

function reprintLabels()
{
    var printFrom = $('#printFrom').val();
    var intPrintFrom = parseInt(printFrom);
    var start = printFrom == intPrintFrom ? intPrintFrom : 0;

    var printTo = $('#printTo').val();
    var intPrintTo = parseInt(printTo);
    var finish = printTo == intPrintTo ? intPrintTo : 0;

    var records = parseInt($('#printTo').attr('maxValue'));

    if (start == 0) {

        var message = '"Print from" value should be greater then 0';
        defaultAlertDialog(message);
        return false;
    } else if (finish == 0) {
        var message = '"Print to" value should be greater then 0';
        defaultAlertDialog(message);
        return false;
    } else if (finish > records) {
        var message = '"Print to" value should not be greater then '+records;
        defaultAlertDialog(message);
        return false;
    } else if (start > records) {
        var message = '"Print from" value should not be greater then '+records;
        defaultAlertDialog(message);
        return false;
    } else if (start > finish) {
        var message = '"Print from" value should be not greater then "Print to" value';
        defaultAlertDialog(message);
        return false;
    } else if (finish - start >= 900) {
        var message = 'Can not print more than 900 bar codes at once';
        defaultAlertDialog(message);
        return false;
    }

    start = start ? start - 1 : 0;
    var scope = finish - start;

    var originalForm = {
        'action': $('#searchForm').prop('action'),
        'target': $('#searchForm').prop('target'),
        'data_post': $('#searchForm').prop('data_post')
    };

    $('#searchForm')
        .prop('action', jsVars['urls']['printLabels']+'/'+start+'/scope/'+scope)
        .prop('target', '_blank')
        .prop('data_post', true)
        .submit();

    $('#searchForm')
        .prop('action', originalForm.action)
        .prop('target', originalForm.target)
        .prop('data_post', originalForm.data_post);
}

/*
********************************************************************************
*/

function submitSplit()
{
    var uomA = $('.uomA');
    var uomB = $('.uomB');

    var noErrors = true;

    $('.UCC').each(function(index, ucc) {

        var row = offset + index;

        var uccValue = ucc.value;

        if (! noErrors || ! uccValue) {
            return false;
        }

        var uom = uccValue.substring(uccValue.length - 7, uccValue.length - 4);

        var thisUOMA = uomA[index];
        var thisUOMB = uomB[index];

        if (! thisUOMA.value || ! thisUOMB.value) {
            var message = 'Input Error: uomA or uomB can\'t be empty if UCC is '
                         +'provided. Please check Row '+row+' !';
            defaultAlertDialog(message, thisUOMA);

            noErrors = false;
        }

        var intUOMA = parseInt(thisUOMA.value, 10),
            intUOMB = parseInt(thisUOMB.value, 10);

        if (parseInt(uom, 10) != intUOMA + intUOMB) {

            var message = 'Input Error: the sum of uomA and uomB are not equal '
                         +'to old UOM. Please check Row: '+row+' !';
            defaultAlertDialog(message, thisUOMB);

            noErrors = false;
        }
    });

    return noErrors;
}

/*
*******************************************************************************
*/

function printPage()
{
    var container = $('#container').val();

    var topInfo = '<b>Container  '+container+'  Tally</b>';

    $('#pagePrintContainerInfo').html(topInfo);
    $('#dontPrint').removeClass('dontPrint');

    for (var i=0; i<50; i++) {
        var styleInfo = $('.styles').eq(i).val();

        $('.forPagePrint').eq(i).html(styleInfo);
    }

    window.print();

    for (var i=0; i<50; i++) {
        $('.forPagePrint').eq(i).html('');
    }

    $('#dontPrint').addClass('dontPrint');
    $('#pagePrintContainerInfo').html('');
}

/*
 ******************************************************************************
*/

function createCartons()
{
    var addCartons = $('#createCartonsCount').val();
    var licencePlate = $('#licencePlate').val();

    var addCartonsInt = $.isNumeric(addCartons) ? parseInt(addCartons) : false ;

    var validInput = addCartonsInt && addCartonsInt == addCartons;

    var errMsg = validInput && licencePlate == 'unselected'
                 ? 'You have not selected a License Plate' : null;

    var errHTML = '<span class="failedMessage plateMsg">'+errMsg+'</span><br><br>';

    $('#successMessage').remove();
    $('#failedMessage').remove();

    $('.plateMsg').length
        ? (
            $('.plateMsg').next().remove(),
            $('.plateMsg').next().remove(),
            $('.plateMsg').remove()
          )
        : null;

    errMsg ? $(errHTML).insertAfter('#addCartonsTitle') : null;

    if (! errMsg && validInput) {
        $('#createCartonsCount').val('0');
        $.ajax({
            url: jsVars['urls']['createCartons'],
            type: 'post',
            data: {
                addCartons: addCartons,
                posiblePlates: licencePlate
            },
            dataType: 'json',
            success: function (response) {
                createCartonsSuccess(response);
            }
        });
    }
}

/*
*******************************************************************************
*/

function createCartonsSuccess(response)
{
    var $spanHTML = $('<span>');

    if (response.cartonAdded) {

        var plural = response.cartonAdded > 1 ? 's were' : ' was';

        $spanHTML
            .addClass('showsuccessMessage')
            .attr('id', 'successMessage')
            .html(response.cartonAdded + ' carton' + plural + ' added to the batch');
    } else {
        $spanHTML
            .addClass('showFailedMessage')
            .attr('id', 'failedMessage')
            .html('Error adding cartons: ' + response.error);
    }

    $spanHTML.insertAfter('#addCartonsTitle');

    tableAPI = dataTables['cartons'].api();
    tableAPI.draw();
}

/*
*******************************************************************************
*/

dtMods['printSplitCartonLabels'] = {

    fnRowCallback: function(nRow, row, index) {
        var parentCol = 1;
        var printLabelCol = 0;

        if (jsVars['dataTables']['printSplitCartonLabels'][index]) {
            $(nRow).addClass('multiRow');
        }

        var printLabelLink = row[parentCol] == null ? null :
            '<input type="checkbox" class="printSelect"'
                +'data-split-carton-label="'+row[parentCol]+'">'
                +'Print Carton Labels';

        $('td:eq('+printLabelCol+')', nRow).html(printLabelLink);
    }
};

dtMods['printUnSplitCartonLabels'] = {

    fnRowCallback: function(nRow, row, index) {
        var parentCol = 2;
        var printLabelCol = 0;

        if (jsVars['dataTables']['printUnSplitCartonLabels'][index]) {
            $(nRow).addClass('multiRow');
        }

        var printLabelLink = row[parentCol] == null ? null :
        '<input type="checkbox" class="printSelect"'
        +'data-split-carton-label="'+row[parentCol]+'">'
        +'Print Carton Labels';

        $('td:eq('+printLabelCol+')', nRow).html(printLabelLink);
    }
};

/*
*******************************************************************************
*/
var parentCol = 0;

dtMods['unsplitCartons'] = {
    fnRowCallback: function (nRow, row, index) {
        var currentTd = 'td:eq(' + parentCol + ')';
        var rowObj = $(currentTd, nRow);
        var carton = rowObj.text();

        var input = '<input type="checkbox" data-ucc128="' + carton + '"'
            + ' class="chkItem"  value="" />';

        rowObj.prepend(input);
    }
};

/*
 ******************************************************************************
 */

dtMods['locBatches'] = {

    fnPreDrawCallback  : function() {
        $('#locBatches').dataTable().makeEditable({
            aoColumns: jsVars['editables']['locBatches'].aoColumns,
            sUpdateURL: jsVars['urls']['editLocationBatch']
        });
    },

    fnRowCallback: function(nRow, row, index) {

        var input = '<input type="checkbox" class="chkItem" value="'
            + nRow.id + '"/>';

        $('td:eq(0)', nRow).prepend(input);

        row[0] = input + row[0];
    }
}

/*
 ******************************************************************************
 */

function fnCheckAll(chkAll) {
    var c = 1 - parseInt(chkAll.value);

    $('#locBatches .chkItem').prop('checked', c);
    chkAll.value = c;
}

/*
 ******************************************************************************
 */

function searchMultiLocation() {

    var multiLocation = $('#multiLocation').val();

    if (! multiLocation) {
        alert('Please input location');
        return;
    }

    var urlSegment = '&andOrs%5B%5D=and';
    var arr = multiLocation.split(' ');

    urlSegment += '&searchTypes%5B%5D=l.displayName&searchValues%5B%5D=' + arr[0];
    for (var i=1; i<arr.length; i++) {
        urlSegment += '&andOrs%5B%5D=or&searchTypes%5B%5D='
                     +'l.displayName&searchValues%5B%5D='+arr[i];
    }

    var finalURL = searcher.externalURL({
        url: jsVars['urls']['searcher'],
        urlSegment: urlSegment
    });

    // Update the datatable
    var modelName = jsVars['searcher']['modelName'];

    var tableAPI = dataTables[modelName].api();

    var tableAjax = tableAPI.ajax;

    finalURL ? tableAjax.url(finalURL).load() : tableAPI.clear().draw();
}

/*
 ******************************************************************************
 */

function fnSetInActive() {

    var arrChecked = [];
    $('#locBatches .chkItem:checked').each(function(){
        arrChecked.push(this.value);
    });

    if (! arrChecked.length) {
        alert('Please select item from list');
        return;
    }

    $.ajax({
        url: jsVars['urls']['editLocationBatch'],
        data: {
            columnId : -1,
            id: arrChecked,
            value: ''
        },
        type: 'POST',
        success: function(){
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

function printSplitLabels(event)
{
    event.preventDefault();

    var splitCartonLabels = '';

    $('.printSelect:checked').each(function() {
        splitCartonLabels += ','+$(this).attr('data-split-carton-label');
    });

    if (splitCartonLabels) {
        $('#splitCartonLabels').val(splitCartonLabels);
        $('#split').submit();
    }
}

/*
********************************************************************************
*/

function mergeCartons()
{
    makeCheckedItems();
    if (! checkedItems.length) {
        var message = 'Need select Parent Cartons.';
        defaultAlertDialog(message, $(this));
        return false;
    }
    var message = 'Merge parent carton(s): ' + checkedItems.join('\n') +
        '. Are you sure?';

    defaultConfirmDialog(message, 'mergeCartonExecute');
}

/*
 ********************************************************************************
 */

function mergeCartonExecute()
{
    $.ajax({
        type: 'post',
        url: jsVars['urls']['unsplitCartons'],
        data: {
            parentID: checkedItems
        },
        dataType: 'json',
        success: responseMergeCartons
    });
}

/*
 ********************************************************************************
 */

function responseMergeCartons (response)
{
    if (response.errors) {

        var message = 'Error Merging Cartons:<br>' + response.errors.join('<br>');

        defaultAlertDialog(message, $(this));
    } else if (response.mergeCarton) {

        var message = 'New Cartons Created:<br>' + response.mergeCarton.join('<br>');

        defaultAlertDialog(message, $(this));

        dataTables['unsplitCartons'].api().clear().draw();

    } else {

        var message = 'Parent Carton Not Found';

        defaultAlertDialog(message, $(this));
    }
}

/*
 ********************************************************************************
 */

function makeCheckedItems()
{
    var box = $('.chkItem');
    checkedItems = [];
    box.each(function(){
        if ($(this).prop('checked')) {
            checkedItems.push($(this).data('ucc128'));
        }
    });
}

/*
********************************************************************************
*/

function dateDiff(date)
{
    var currentDate = new Date(),
        createDate = new Date(date);

    return currentDate.getFullYear() === createDate.getFullYear()
        && currentDate.getMonth() === createDate.getMonth()
        && currentDate.getDate() - createDate.getDate() < 2 ? true : false;
}

/*
********************************************************************************
*/




function getCustomer() {
    var warehouseID = $('#warehouse-input').val();

    if (warehouseID)
        $.ajax({
            type: 'post',
            url: jsVars['urls']['getCustomerByWarehouseID'],
            data: {
                warehouseID: warehouseID
            },
            dataType: 'json',
            success: function (response) {
                $('.vendor-name').html(response);
            }
        });
}

/*
********************************************************************************
*/

function changeUomByPlate() {

    var plate = $("#plate").val(),
        newUom = $("#new_uom").val(),
        batch = $("#batch").val(),
        invIds = [];

    $('.invId:checked').each(function(){
        invIds.push($(this).attr("value"));
    });

    var message = [];

    if (! plate) {
        message.push('Plate can not Blank');
    }

    if (! newUom) {
        message.push('New Uom can not Blank');
    }

    if (! invIds.length) {
        message.push('Please choose the inventory carton to change');
    }

    if (! batch) {
        message.push('Batch can not Blank');
    }

    if (message.length > 0) {
        defaultAlertDialog(message.join('<br>'));
        return false;
    }

    message = 'Are you sure to change?';
    var param = {
        plate: plate,
        newUom: newUom,
        batch: batch,
        invIds: invIds
    };

    defaultConfirmDialog(message, 'updateUomByPlate', param);
}

/*
********************************************************************************
*/

function updateUomByPlate(param)
{
    $.ajax({
        url: jsVars['urls']['updateCartonUomByPlate'],
        type: 'post',
        data: {
            plate: param['plate'],
            batch: param['batch'],
            newUom: param['newUom'],
            invIds: param['invIds']
        },
        dataType: 'json',
        beforeSend: function () {
            beforeSendAjaxDatatable();
        },
        error: responseAjaxDatatable,
        success: responeUpdateUomByPlate
    });
}

/*
********************************************************************************
*/

function responeUpdateUomByPlate(data)
{
    responseAjaxDatatable();

    defaultAlertDialog(data['msg']);

    if (data['sts'] == true) {
        // Update the datatable
        var modelName = jsVars['searcher']['modelName'];

        var tableAPI = dataTables[modelName].api();
        tableAPI.clear().draw();
    }
}

/*
********************************************************************************
*/

function beforeSendAjaxDatatable()
{
    $.blockUI({
        message: 'Please wait...'
    });
}

/*
********************************************************************************
*/

function responseAjaxDatatable()
{
    $.unblockUI();
}

/*
********************************************************************************
*/