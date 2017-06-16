/*
********************************************************************************
* ORDERS JS
********************************************************************************
*/

/*
********************************************************************************
*/

funcStack.orders = function () {

    // hide searcher form
    $('#searcher').hide();
    $('#searcher').height(0);

    addMultiselectFilter();

    $('.reportDate').click(reportDate);
};

/*
********************************************************************************
*/

dtMods['openOrdersReport'] = {

    fnDrawCallback: function () {
        // prevent sorting on column title click
        $('th').unbind('click.DT').removeClass('sorting');
    },

    fnRowCallback: function (row) {

        $.each(jsVars['openOrders']['daysColors'], function(date, className) {

            var $td = $('td', row);
            var rowDate = $td.eq(jsVars['openOrders']['dateColumnNo']).text();

            if (rowDate <= jsVars['openOrders']['fromDate'] || rowDate == date) {

                $td.addClass(className);

                return false;
            }
        });
    }
};

/*
********************************************************************************
*/

function reportDate()
{
    var value = $(this).attr('data-date');

    jsVars['searchParams'] = [];

    var firstDate = Object.keys(jsVars['openOrders']['daysColors'])[0];

    if (value != 'all') {
        // when the first date is chosen all dates before it shall be included
        var start = value == firstDate ? 1 : 0;

        for (count=start; count<2; count++) {

            var type = count ? 'ending' : 'starting';

            jsVars['searchParams'].push({
                andOrs: ['AND'],
                searchTypes: ['cancelDate[' + type + ']'],
                searchValues: [value],
                compareOperator: ['exact']
            });
        }
    }

    runSearcher();
}

/*
********************************************************************************
*/

