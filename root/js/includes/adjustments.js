/*
********************************************************************************
* GLOBAL VARIABLES
********************************************************************************
*/

// This variable can be referenced anywhere
var dialogParam,
    adjustDialog,
    adjustments;

/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/

funcStack.adjustments = function () {
    adjustDialog = $('#adjustForm').dialog({
        autoOpen: false,
        width: 350,
        modal: true,
        buttons: {
            'Submit': adjustments.updateStatus
        }
    });

    $('#adjustButton').click(adjustments.openDialog);

    $('#submitSearch').click(storeLastSearch);

    $('#toggleAll').click(toggleCheckboxes);

    adjustments.getFieldColumns();

    adjustments.disableLocDD();

    $('input[name=locationType]').change(adjustments.disableLocDD);
};

/*
********************************************************************************
*/

adjustments = {

    fields: [
        'locID',
        'statusID',
        'mLocID',
        'mStatusID',
        'batchID',
        'cartonID'
    ],

    discrepancies: {},

    saveRow: {},

    cartons: [],

    uncheckedCartons: [],

    discFields: {
        'statuses': [
            'statusID',
            'mStatusID'
        ],
        'locations': [
            'locID',
            'mLocID'
        ]
    },

    fieldColumns: {},

    searchData: '',

    /*
    ****************************************************************************
    */

    getFieldColumns: function () {
        $(this.fields).each(function (dontNeed, field) {
            adjustments.fieldColumns[field] = jsVars['fieldColumns'][field];
        });
    },

    /*
    ****************************************************************************
    */

    openDialog: function () {
        adjustments.discrepancies = {
            mandatoryStatuses: ['IN', 'RK'],
            statuses: {},
            locations: {}
        };

        adjustments.cartons = [];
        adjustments.uncheckedCartons = [];

        if ($('#toggleAll').attr('data-status') === 'on') {
            $('input[type=checkbox]:not(:checked)').each (function () {

                var uncheckedCarton = $(this).parent().text();

                uncheckedCarton = uncheckedCarton.trim();

                adjustments.uncheckedCartons.push(uncheckedCarton);
            });

            $.blockUI({
                message: 'Getting Table Data. Do NOT Close This Window.'
            });

            $.ajax({
                type: 'post',
                url: jsVars['urls']['getAdjustInventory'],
                dataType: 'json',
                data: {
                    skipCartons: adjustments.uncheckedCartons,
                    searchData: adjustments.searchData
                },
                success: function (response) {
                    adjustments.getAdjustInventoryAjaxSuccess(response);
                }
            });

        } else {

            var checkedBoxes = 'input[type=checkbox]:checked';

            if (! $(checkedBoxes).length) {

                noCartonsAlert();

                return;
            }

            $(checkedBoxes).each(function () {

                var boxID = $('input[type=checkbox]').index(this);

                var rowValues = $('tr', '#inventory tbody').eq(boxID);

                adjustments.saveRow = {};

                $.each(adjustments.fieldColumns, function (field, column) {
                    adjustments.saveRow[field] = $('td', rowValues).eq(column).html();
                });

                adjustments.setDiscrepancyFields();
            });

            adjustments.updateDropdowns();
        }
    },

    /*
    ****************************************************************************
    */

    getAdjustInventoryAjaxSuccess: function (response) {

        var fields = '';

        $.map(response, function (values) {

            adjustments.saveRow = {};

            fields = Object.keys(adjustments.fieldColumns);

            $.map(fields, function (field) {
                if (values[field] !== null) {
                    adjustments.saveRow[field] = values[field];
                }
            });

            adjustments.setDiscrepancyFields();
        });
        // display dialog box if only response associative array is not empty
        fields ? adjustments.updateDropdowns() : noCartonsAlert();

        $.unblockUI();
    },

    /*
    ****************************************************************************
    */

    updateDropdowns: function () {

        adjustDialog.dialog('open');

        $('#locationBox, #updateStatus').hide();

        updateDropdown({
            values: adjustments.discrepancies.locations,
            display: '#locationBox',
            dropdown: '#locationDD'
        });

        $.each(adjustments.discrepancies.mandatoryStatuses, function (key, status) {
            if (typeof(adjustments.discrepancies.statuses[status]) === 'undefined') {
                adjustments.discrepancies.statuses[status] = true;
            }
        });

        updateDropdown({
            values: adjustments.discrepancies.statuses,
            display: '#updateStatus',
            dropdown: '#statusDD'
        });
    },

    /*
    ****************************************************************************
    */

    setDiscrepancyFields: function () {

        // Store the discrepancy fields
        $.each(adjustments.discFields, function (category, row) {
            $.map(row, function (field) {
                var target = adjustments.saveRow[field];
                adjustments.discrepancies[category][target] = true;
            });
        });

        var cartonData = {
            batchID: adjustments.saveRow['batchID'],
            cartonID: adjustments.saveRow['cartonID']
        };

        adjustments.cartons.push(cartonData);
    },

    /*
    ****************************************************************************
    */

    updateStatus: function () {
        adjustDialog.dialog('close');

        var message = 'Are you sure?';

        defaultConfirmDialog(message, 'updateStatusExecute');
    },

    /*
    ****************************************************************************
    */

    locationUpdate: function () {
        return $('input[type=radio]:checked').val() == 'update';
    },

    /*
    ****************************************************************************
    */

    disableLocDD: function () {
        var active = ! adjustments.locationUpdate();
        $('#locationDD').prop('disabled', active);
    }
};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

dtMods['inventory'] = {
    fnRowCallback: function(row, rowValues) {
        var firstCol = 0;

        var $newDisplay = $('<input>').attr('type', 'checkbox');

        $('td', row).eq(firstCol).html($newDisplay).append(' ')
                    .append(rowValues[firstCol]);
    }
};

/*
********************************************************************************
*/

function toggleCheckboxes()
{
    var $toggleButton = $('#toggleAll');
    var newStatus = $toggleButton.attr('data-status') === 'off' ? 'on' : 'off';
    var checked = newStatus === 'on';

    $toggleButton.attr('data-status', newStatus);

    $('input[type=checkbox]').prop('checked', checked);
}

/*
********************************************************************************
*/

function updateStatusExecute()
{
    var cartonCount = adjustments.cartons.length;

    if (cartonCount > 100) {

        var message = 'There are ' + cartonCount + ' cartons to adjust. It may '
                    + 'take a lot of time. Proceed anyway?';

        defaultConfirmDialog(message, 'updateStatusRun');

    } else {
        updateStatusRun();
    }
}

/*
********************************************************************************
*/

function updateStatusRun()
{
    $.blockUI({
        message: 'Adjusting cartons. Do NOT Close This Window.'
    });

    $.ajax({
        type: 'post',
        url: jsVars['urls']['adjustInventory'],
        dataType: 'json',
        data: {
            cartons: JSON.stringify(adjustments.cartons),
            newStatus: $('#statusDD option:selected').val(),
            newLocation: $('#locationDD option:selected').val(),
            locationUpdate: adjustments.locationUpdate()
        },
        success: function () {
            $.blockUI.defaults.onUnblock = adjustInventoryAjaxSuccess();
            $.unblockUI();
        }
    });
}

/*
********************************************************************************
*/

function adjustInventoryAjaxSuccess()
{
    var message = 'Adjust carton successfully!';
    defaultAlertDialog(message);
    var modelName = jsVars['modelName'];
    dataTables[modelName].fnDraw();
}

/*
********************************************************************************
*/

function updateDropdown(params)
{
    $(params.display).show();

    $(params.dropdown).empty();

    $.each(params.values, function (value) {
        if (value !== 'undefined') {
            var optionHTML = $('<option>').text(value);
            $(params.dropdown).append(optionHTML);
        }
    });
}

/*
********************************************************************************
*/

function noCartonsAlert(params)
{
    var message = 'No Cartons to Adjust';

    defaultAlertDialog(message);
}

/*
********************************************************************************
*/

function storeLastSearch()
{
    $('#toggleAll').attr('data-status', 'off');
    // store last searcher data to use it once "Toggle All" button is clicked
    adjustments.searchData = $('#searchForm').serialize();
}

/*
********************************************************************************
*/
