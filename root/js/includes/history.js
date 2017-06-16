/*
********************************************************************************
* GLOBAL VARIABLES
********************************************************************************
*/

// This variable can be referenced anywhere
var historyColumns = {
    time: 0,
    user: 1,
    subject: 2,
    field: 3,
    previousValue: 4,
    newValue: 5
};

/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/

funcStack.history = function () {

};

dtMods['history'] = {
    fnRowCallback: function (row, rowValues) {
        var tableModel   = rowValues[3];
        var uniqueID     = rowValues[4];
        var changedField = rowValues[5];
        var fromValue    = rowValues[6];
        var toValue      = rowValues[7];
        
        var tableClass = jsVars['tableFields'][tableModel];
        
        if (! tableClass) {
            return;
        }
        
        var uniqueName = tableClass.displaySingle || '[Missing Object Display-Single]';
        
        var fieldName = typeof tableClass.fields[changedField] != 'undefined'
                && typeof tableClass.fields[changedField].display != 'undefined'
                ? tableClass.fields[changedField].display 
                : '[Missing '+changedField+' Field Display]';

        $('td', row).eq(historyColumns.subject).html(uniqueName+' '+ uniqueID);
        $('td', row).eq(historyColumns.field).html(fieldName);
        $('td', row).eq(historyColumns.previousValue).html(fromValue);
        $('td', row).eq(historyColumns.newValue).html(toValue);

    }
};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

function customFunction() {
    
}

/*
********************************************************************************
*/

function anotherCustomFunction() {
    
}

/*
********************************************************************************
*/