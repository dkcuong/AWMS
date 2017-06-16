/*
********************************************************************************
* GLOBAL VARIABLES
********************************************************************************
*/

// This variable can be referenced anywhere
var globalJSVar = null,
    counter = 0,
    totalBatchGroups,
    importer = {};

/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/

funcStack.imports = function () {
    importer.runAJAX();
};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

importer = {
    ajaxURL: null,

    runAJAX: function () {
        if (! jsVars['ajaxURLs'].length) {
            var message = 'Completed Batch Group: ' + totalBatchGroups
                        + ' of ' + totalBatchGroups;

            $('#createUCCLabels').html(message);
            return;
        }

        totalBatchGroups = jsVars['payload']['containerBatches'].length;

        this.ajaxURL = jsVars['ajaxURLs'].shift();

        if ($('#successMessage').length > 0) {
            $('#successMessageBreak').remove();
            $('#successMessage').remove();
        }

        $.ajax({
            type: 'post',
            url: this.ajaxURL.url,
            dataType: 'json',
            data: {
                payload: {
                    recNums: jsVars['payload']['recNums'],
                    vendorIDs: jsVars['payload']['vendorIDs'],
                    client: jsVars['payload']['client'],
                    warehouse: jsVars['payload']['warehouse']
                }
            },
            success: this.startBatches,
            error: function (message) {
                var display = 'Label creation has stopped due to error: ' + "\n"
                            + message.responseText;
                defaultAlertDialog(display);
            }
        });
    },

    /*
    ****************************************************************************
    */

    startBatches: function () {
        var display = importer.ajaxURL.input;

        $('#'+display).html('Completed');

        importer.runAJAX();

        if (! jsVars['payload']['containerBatches'].length) {
            return;
        }

        $('#createUCCLabels').html('Now creating UCC Labels');

        var batches = jsVars['payload']['containerBatches'].shift();


        var message = 'Completed Batch Group: ' + counter + ' of '
                    + totalBatchGroups;
        $('#createUCCLabels').html(message);

        counter++;


        $.ajax({
            type: 'post',
            url: jsVars['urls']['createUCCLabels'],
            dataType: 'json',
            data: {
                batches: JSON.stringify(batches)
            },
            success: function () {
                importer.startBatches();

                if (counter == totalBatchGroups) {
                    $('<br>')
                        .attr('id', 'successMessageBreak')
                        .insertAfter('#importInventory');

                    var $spanHTML = $('<span>');

                    $spanHTML
                        .addClass('showsuccessMessage')
                        .attr('id', 'successMessage')
                        .html('Import cartons were added to the inventory');

                    $spanHTML.insertAfter('#successMessageBreak');
                }
            }
        });
    }
};

/*
********************************************************************************
*/