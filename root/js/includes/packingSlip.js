/*
********************************************************************************
* GLOBAL VARIABLES
********************************************************************************
*/

var link = jsVars['link'];

/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/

funcStack.packingSlip = function () {
};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

dtMods['packingSlip'] = {

    fnRowCallback: function(row, rowValues, rowID) {

        var links = {
            batch: jsVars['columnNumbers']['batch'],
            order: jsVars['columnNumbers']['order']
        };

        $.each(links, function (field, column) {

            var value = rowValues[column],
                params = {};

            params[field] = value;

            var $anchor = getHTMLLink({
                link: httpBuildQuery(jsVars['urls']['print'], params),
                title: 'Print ' + value,
                getObject: true
            });

            $('td', row).eq(column).html('').append($anchor);
        });
    }
};

/*
********************************************************************************
*/
