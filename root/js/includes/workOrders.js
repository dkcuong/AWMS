/*
********************************************************************************
* GLOBAL VARIABLES
********************************************************************************
*/

// This variable can be referenced anywhere
var globalJSVar = null;

/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/

if (typeof includeJS != 'undefined'
 && typeof includeJS['custom/js/common/workOrders.js'] != 'undefined') {

    var workOrdersLabor = workOrdersLabor();
}

funcStack.workOrders = function () {

    var params = {
        workOrderNumbers: jsVars['workOrderNumbers'],
        isCheckOut: jsVars['isCheckOut']
    };

    if (! jsVars['isCheckOut']) {
        params.scanOrderNumbers = jsVars['scanOrderNumbers'];
    }

    if (typeof workOrdersLabor != 'undefined') {
        workOrdersLabor.editWorkOrderNumber(params);
    }
};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

