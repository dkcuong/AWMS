/*
********************************************************************************
* GLOBAL VARIABLES
********************************************************************************
*/

var coloring = {
    titleClicked: ''
};

/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/

funcStack.outbound = function () {

    addMultiselectFilter();

    $('.dataTables_scrollHead th').click(function() {
        coloring.titleClicked = $(this).html();
    });

    addBackgroundColorClasses(jsVars['backgroundColors']);
};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

dtMods['outbound'] = {
    fnDrawCallback: function () {
        tableColoring('outbound');
    },
    fnRowCallback: rowColoring
};

/*
********************************************************************************
*/
