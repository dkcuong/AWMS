/*
********************************************************************************
* GLOBAL VARIABLES
********************************************************************************
*/

/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/

funcStack.wavePicks = function () {

};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

dtMods['orderBatches'] = {

    fnRowCallback: function(row, rowValues) {

        var batchColumn = jsVars['columnNumbers']['batchOrder'];

        var batch = rowValues[batchColumn];

        $('td', row).eq(batchColumn).on('click',function(e){
            checkMezzanineStorage(batch);
            e.preventDefault();
        });

        var $anchor = getHTMLLink({
            title: 'Create Wave Pick Ticket ' + batch,
            getObject: true
        });

        $('td', row).eq(batchColumn).html('').append($anchor);
    }
};

dtMods['wavePicks'] = {

    fnRowCallback: function(row, rowValues) {

        var batchColumn = jsVars['columnNumbers']['orderBatch'],
            orderColumn = jsVars['columnNumbers']['scanOrderNumber'],
            link = jsVars['urls']['display'];

        var batch = rowValues[batchColumn],
            order = rowValues[orderColumn];

        var batchWavePickLink = httpBuildQuery(link, {
            batch: batch,
            printType: 'wavePick'
        });

        var batchWavePickByOrderLink = httpBuildQuery(link, {
            batch: batch,
            printType: 'wavePick',
            printByOrder: 1
        });

        var batchVerificationLink = httpBuildQuery(link, {
            batch: batch,
            printType: 'verificationList'
        });

        var batchUCCLabelLink = httpBuildQuery(link, {
            batch: batch,
            printType: 'uccLabels'
        });

        var batchUCCLabelByOrderLink = httpBuildQuery(link, {
            batch: batch,
            printType: 'uccLabels',
            printByOrder: 1
        });

        var orderWavePickLink = httpBuildQuery(link, {
            order: order,
            printType: 'wavePick'
        });

        var orderVerificationLink = httpBuildQuery(link, {
            order: order,
            printType: 'verificationList'
        });

        var orderUCCLabelLink = httpBuildQuery(link, {
            order: order,
            printType: 'uccLabels'
        });

        $('td', row).eq(batchColumn).html('Print '
            + getHTMLLink({
                link: batchWavePickLink,
                title: 'Wave Pick'
            }) + ' / '
            + getHTMLLink({
                link: batchWavePickByOrderLink,
                title: 'Wave Pick by Orders'
            }) + ' / '
            + getHTMLLink({
                link: batchVerificationLink,
                title: 'Processing Verification'
            }) + ' / '
            + getHTMLLink({
                link: batchUCCLabelLink,
                title: 'UCC Labels'
            }) + ' / '
            + getHTMLLink({
                link: batchUCCLabelByOrderLink,
                title: 'UCC Labels by Orders'
            }) + ' for Batch # ' + batch);

        $('td', row).eq(orderColumn).html('Print '
            + getHTMLLink({
                link: orderWavePickLink,
                title: 'Wave Pick'
            }) + ' / '
            + getHTMLLink({
                link: orderVerificationLink,
                title: 'Processing Verification'
            }) + ' / '
            + getHTMLLink({
                link: orderUCCLabelLink,
                title: 'UCC Labels'
            }) + ' for Order # ' + order);
    }
};

/*
********************************************************************************
*/

function checkMezzanineStorage(batchOrder) {
    var batchOrder = batchOrder;

    $.ajax({
        url: jsVars['urls']['checkMezzanineStorage'],
        type: 'post',
        data: {
            request: 'checkStorage',
            batchOrder: batchOrder
        },
        dataType: 'json',
        success: function (data) {

            if (data === true) {
                window.location.href = './display/batch/' + batchOrder;
            } else if (data === false){
                var message = 'System cause some Error!';
                defaultAlertDialog(message);
            }

            if (data.type == 'notEnoughMezzanine') {

                var message = 'The stock quantity is insufficient. Create a '
                            + 'manual Transfer to replenish Mezzanine inventory.'
                            + '<br><br>' + data.shortages.join('<br>');

                defaultAlertDialog(message);
            }

            if (data.type == 'notInRange') {
                var message = 'The UPC '+ data.upc +' has no MIN/MAX setting ' +
                              'and MIN/Max range.';
                defaultAlertDialog(message);
            }

            return false;
        }
    });
}

/*
*******************************************************************************
*/
