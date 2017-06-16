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


/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

dtMods['printMasterLabels'] = {

    fnRowCallback: function(row, rowValues) {

        var batchColumn = jsVars['columnNumbers']['batchNumber'];

        var link = httpBuildQuery(jsVars['urls']['print'], {
            batch: rowValues[batchColumn]
        });

        var $anchor = getHTMLLink({
            link: link,
            title: 'Print Batch Labels ' + rowValues[batchColumn],
            getObject: true
        });

        $('td', row).eq(batchColumn).html('').append($anchor);
    }
};

/*
********************************************************************************
*/

dtMods['masterLabels'] = {

    fnRowCallback: function(row, rowValues) {

        var barcodeColumn = jsVars['columnNumbers']['barcode'];

        var link = httpBuildQuery(jsVars['urls']['print'], {
            label: rowValues[barcodeColumn]
        });

        var $anchor = getHTMLLink({
            link: link,
            title: 'Print Batch Labels ' + rowValues[barcodeColumn],
            getObject: true
        });

        $('td', row).eq(barcodeColumn).html('').append($anchor);
    }

/*
********************************************************************************
*/
    
};