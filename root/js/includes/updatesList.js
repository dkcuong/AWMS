/******************************************************************************
* MAIN FUNCTION                                                              *
******************************************************************************
*/

funcStack.updatesList = function() {

    if (typeof searcher !== 'undefined') {
        searcher.outsideDataTable();
        searcher.useExternalParams();
    }

    $('#btnAddNewRow').on('click', setWidthFormAddNewRow);

};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS                                                         *
********************************************************************************
*/

function redirectToListFeature()
{
    var updatesListUrl = jsVars['urls']['updatesList'] +
        '/type/releaseVersion/editable/display';
    location.href = updatesListUrl;
}

/*
*******************************************************************************
*/

function redirectToVersion()
{
    var updatesListUrl = jsVars['urls']['updatesList'] +
        '/type/listFeature/editable/display';
    location.href = updatesListUrl;
}

/*
*******************************************************************************
*/

function setWidthFormAddNewRow()
{
    $('.ui-dialog').attr('aria-describedby','formAddNewRow').css('width', 'auto');
}

/*
*******************************************************************************
*/
