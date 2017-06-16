
dtMods['PickedCheckoutUcclabels'] = {

    // Add the carrier links
    fnRowCallback: function(row, rowValues, rowID) {
        var ordernumColumn = jsVars['columnNumbers']['ordernum'],
            fromEdiColumn = jsVars['columnNumbers']['fromEdi'],
            uccsColumn = jsVars['columnNumbers']['uccs'];

        var printLabelLink = httpBuildQuery(jsVars['urls']['printUCCLabels'], {
            orderNumber: rowValues[ordernumColumn ]
        });

        var printUCCLink = getHTMLLink({
            link: printLabelLink,
            attributes: {
                'class': 'printUccLabel',
                'orderNumber' : ordernumColumn,
                'target' : '_blank'
            },
            title: 'Print UCC Label ' + rowValues[ordernumColumn ],
            getObject: true
        });

        $('td', row).eq(ordernumColumn ).html('').append(printUCCLink);
    }
};
