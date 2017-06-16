/*
********************************************************************************
* GLOBAL VARIABLES
********************************************************************************
*/

var rowCounter = 1;
var table;
var measureSystem;
var commentDialog;

/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/

funcStack.containers = function () {

    if (typeof jsVars['multiSelect'] !== 'undefined') {
       searcher.useExternalParams();
    }

    switch (jsVars['requestMethod']) {
        case 'list' :
            listContainers();
            break;
        case 'add' :
            addContainer();
            break;
        default:
            break;
    }

    commentDialog = $('#commentForm').dialog({
        autoOpen: false,
        width: 350,
        modal: true,
        resizable: false,
        position: ['center',20],
        buttons: {
                "OK": containersReceived.submitNotes,
                "Cancel": function() {
                   $(this).dialog('close');
                }
           }
        });

        if (jsVars['isClient']) {
            $('#containersReceived').on('click', '.note', function() {
                var recNum = $(this).attr('data-rec-num');
                var notes = $(this).attr('data-notes');

                commentDialog.dialog('open');

                $('#commentNote').val(notes);
                $('#commentNote').attr('data-rec-num', recNum);

                return false;
            });
        }


    $( document ).tooltip();
};

/*
********************************************************************************
*/

var containersReceived = {

    addLinks: function (row, rowValues) {
        var columns = jsVars['columnNumbers'];
        var fields = jsVars['fields'];

        var noteNumColumn = columns.clientNotes;
        var noteValue = rowValues[noteNumColumn] ? rowValues[noteNumColumn] : '';
        var recNumColumn = columns.recNum;
        var recValue = rowValues[recNumColumn];

        var titleHead = rowValues[noteNumColumn] ? 'View Notes' : 'Add Notes';

        if (! jsVars['isClient']) {
            var titleHead = rowValues[noteNumColumn] ? 'View Notes' : '';
        }

        var $anchor = $('<a></a>')
                .addClass('note')
                .attr('title',noteValue)
                .attr('data-rec-num',recValue)
                .attr('data-notes',noteValue)
                .html(titleHead);

        $('td', row).eq(noteNumColumn).html($anchor);
    },

    submitNotes : function() {
        var recNum = $('#commentNote', this).attr('data-rec-num');
        var comment = $('#commentNote', this).val();

        $.ajax({
                type: 'post',
                url: jsVars['urls']['addClientNotes'],
                dataType: 'json',
                data: {
                    recNum: recNum,
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

dtMods['batches'] = {

    fnServerParams: function(aoData) {
        aoData = addControllerSearch(aoData, 'batches');
    }
};

/*
*******************************************************************************
*/

dtMods['containersReceived'] = {
        fnRowCallback: function (row, rowValues) {
            containersReceived.addLinks(row, rowValues);
        }
};

/*
********************************************************************************
*/

function listContainers() {

}

/*
********************************************************************************
*/

function addContainer() {
    updateMeasureSystem();

    $('#measureID').change(updateMeasureSystem);

    table = $('.display').DataTable({
        bFilter: false,
        scrollX: true
    });

    $('form').submit(checkRequired);

    $('#addContainers').click( function() {
        $('form').submit();
    } );

    $('#addButton').on('click', addRows);

    $('#removeButton').on('click', removeRows);

    window.setInterval(updateTime, 1000);

    $('.numericOnly').blur(isNumeric);

    $('.height').blur({
        leastImperial: 2,
        mostImperial: 60,
        imperialName: 'inches',
        leastMetric: 5,
        mostMetric: 150,
        metricName: 'centimeters'
    }, checkMeasurement);

    $('.width').blur({
        leastImperial: 2,
        mostImperial: 48,
        imperialName: 'inches',
        leastMetric: 5,
        mostMetric: 120,
        metricName: 'centimeters'
    }, checkMeasurement);

    $('.length').blur({
        leastImperial: 2,
        mostImperial: 48,
        imperialName: 'inches',
        leastMetric: 5,
        mostMetric: 120,
        metricName: 'centimeters',
        decimalRequired: true
    }, checkMeasurement);

    $('.weight').blur({
        leastImperial: 0.125,
        mostImperial: 80,
        imperialName: 'lbs',
        leastMetric: 40,
        mostMetric: 0.001,
        metricName: 'kg',
        decimalRequired: true
    }, checkMeasurement);

    $('.upc').blur(checkUPC);

    $('.totalCarton').blur(checkTotalCartons);

    $('.uom').blur(checkUOM);
}

/*
********************************************************************************
*/

function checkUOM() {
    if (! isNumeric(this)) {
        return false;
    }

    var inputName = $(this).attr('data-input-type');
    var displayName = jsVars['allFields'][inputName]['display'];

    return validRange(this, 1, 244, '',
        displayName+' must be between num1 and num2 name');
}

function checkUPC() {
    if (! isNumeric(this)) {
        return false;
    }

    if (this.value.length < 11 || this.value.length > 13 ) {
        var message = 'UPC must be between 11 and 13 digits';
        defaultAlertDialog(message, this);

        return false;
    }

    return true;
}

function checkTotalCartons() {
    if (! isNumeric(this)) {
        return false;
    }

    if (this.value.length > 4) {
        var message = 'Carton count must be more than 4 digits';
        defaultAlertDialog(message, this);

        return false;
    }

    return true;
}

/*
********************************************************************************
*/

function updateTime() {
    var now = new Date();
    var month = now.getMonth() + 1;
    var outStr = now.getFullYear()+'-'
        +month+'-'
        +now.getDate()+' '
        +now.getHours()+':'
        +now.getMinutes()+':'
        +now.getSeconds();
    $('#dateTime').val(outStr);
}

function isInt(value) {
    return Math.floor(value) == value && $.isNumeric(value);
}

function addRows() {
    var numberOfRows = $('#addQuantity').val();

    if (! isInt(numberOfRows)) {
        return false;
    }

    for (i = 0; i < numberOfRows; i++) {
        var inputRow = [];
        $.each(jsVars['fieldNames'], function(dontNeed, name) {
            // Check if field is required
            var reqClass = typeof jsVars['allFields'][name].optional == 'undefined'
                ? 'required' : null;

            var cellInput = $('<input>').attr({
                name: 'inputs['+rowCounter+']['+name+']',
                type: 'text',
                'data-input-type': name
            }).addClass(reqClass)
              .addClass('containerInput '+name);
            inputRow.push(cellInput[0].outerHTML);
        });
        table.row.add(inputRow);
        rowCounter++;
    }

    table.draw();

    $('#addQuantity').val('');

    return false;
}

function removeRows() {
    var numberOfRows = $('#removeQuantity').val();

    if (! isInt(numberOfRows)) {
        return false;
    }

    numberOfRows = rowCounter - 1 < numberOfRows ? rowCounter - 1 : numberOfRows;

    for (i = 0; i < numberOfRows; i++) {
        rowCounter--;
        table.row(rowCounter).remove();
    }

    table.draw();

    $('#removeQuantity').val('');

    return false;
}

/*
********************************************************************************
*/

function updateMeasureSystem() {
    measureSystem = $('#measureID').val();
}

/*
********************************************************************************
*/

function checkRequired() {
    var makeSubmit = true;
    $('.required').each(function (index, input) {
        if ($(input).val() == '' || $(input).val() == 0) {
            $(input).focus();
            var inputName = $(input).attr('data-input-type');
            var displayName = jsVars['allFields'][inputName]['display'];
            var message = displayName+' is required.';
            defaultAlertDialog(message);
            makeSubmit = false;
            return false;
        }
    });
    return makeSubmit;
}

/*
********************************************************************************
*/

function isNumeric(element, isDecimal) {
    if (element.value == '') {
        return false;
    }

    if (! isDecimal && ! /^\d+$/.test(element.value)) {
        var message = 'Only Integer Values Allowed';
        defaultAlertDialog(message, element);

        return false;
    }

    if (isDecimal && ! /^(\d+\.?\d*|\.\d+)$/.test(element.value)) {
        var message = 'Only Decimal Values Allowed';
        defaultAlertDialog(message, element);

        return false;
    }

    return true;
}

/*
********************************************************************************
*/

function checkMeasurement(event) {
    if (! isNumeric(this, typeof event.data.decimalRequired != 'undefined')) {
        return false;
    }

    updateMeasureSystem();

    var inputName = $(this).attr('data-input-type');
    var displayName = jsVars['allFields'][inputName]['display'];

    return measureSystem == 1
        ? validRange(this, event.data.leastImperial,
            event.data.mostImperial, event.data.imperialName,
            displayName+' must be between num1 and num2 name')
        : validRange(this, event.data.leastMetric,
            event.data.mostMetric, event.data.metricName,
            displayName+' must be between num1 and num2 name');
}


/*
********************************************************************************
*/

function validRange(input, leastVal, mostVal, measurement, alertMessage) {
    if (input.value < leastVal || input.value > mostVal) {
        // Put the values in the alert
        alertMessage = alertMessage.replace('num1', leastVal)
           .replace('num2', mostVal)
           .replace('name', measurement);

        defaultAlertDialog(alertMessage, input);
    }
}

/*
********************************************************************************
*/
