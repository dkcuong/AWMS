/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/

funcStack.transfers = function () {
    if (typeof jsVars['listTransfer'] !== 'undefined') {
        searcher.useExternalParams();
    }
};

/*
********************************************************************************
*/

dtMods['transfers'] = {

    fnRowCallback: function(row, rowValues, rowID) {

        var idColumnNo = jsVars['columnNumbers']['id'],
            labelColumnNo = jsVars['columnNumbers']['printLabels'],
            confirmColumnNo = jsVars['columnNumbers']['confirmation'];

        var pickTicket = rowValues[idColumnNo];

        var printPickTicketLink = httpBuildQuery(jsVars['urls']['printPickTicket'], {
            id: pickTicket
        });

        var printLabelsLink = httpBuildQuery(jsVars['urls']['printLabels'], {
            transfer: pickTicket
        });

        var $printPickTicketAnchor = getHTMLLink({
            link: printPickTicketLink,
            title: 'View # ' + pickTicket,
            getObject: true
        });

        var $printLabelsAnchor = getHTMLLink({
            link: printLabelsLink,
            title: 'Print UCC Labels',
            getObject: true
        });

        $('td', row).eq(idColumnNo).html('').append($printPickTicketAnchor);
        $('td', row).eq(labelColumnNo).html('').append($printLabelsAnchor);

        var $confirmArrival = $('td', row).eq(confirmColumnNo);

        switch ($confirmArrival.html()) {
            case 'Pending':
                break;
            case 'Confirmed':
                $confirmArrival.addClass('confirmed');
                break;
            default:
                $confirmArrival.addClass('discrepant');
                break;
        }
    }
};