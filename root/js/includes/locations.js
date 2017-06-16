/*
 ********************************************************************************
 * GLOBAL VARIABLES
 ********************************************************************************
 */

// This variable can be referenced anywhere

/*
 ********************************************************************************
 * MAIN FUNCTION
 ********************************************************************************
 */
funcStack.locations = function () {

    if (typeof jsVars['utilization'] != 'undefined') {
        addMultiselectFilter();
    }

    var locIndex = $('#locID').attr('rel');
    if (typeof locIndex != 'undefined') {
        $('#locID').autocomplete({
            source: jsVars['urls']['autocomplete'][locIndex]
        });
    }

    var upcIndex = $('#upc').attr('rel');
    if (typeof locIndex != 'undefined') {
        $('#upc').autocomplete({
            source: jsVars['urls']['autocomplete'][upcIndex]
        });
    }

    $('#btnAddNewRow').on('click', function setWidthFormAddNewRow() {
        $('.ui-dialog').attr('aria-describedby','formAddNewRow').css('width', 'auto');
    });
};
/*
 ********************************************************************************
 * ADDITIONAL FUNCTIONS
 ********************************************************************************
 */

function customAutoCompleteFunc (obj, settings, original)
{
    var colIndex = $(original).index();
    var rowIndex = $(original).parent().index();

    var message = 'Warehouse of Vendor does not exist this location';

    var vendorName = $("td", $("tr", "table#locationInfo tbody").eq(rowIndex))
        .eq(0).text();
    var currentLocation = $("td", $("tr", "table#locationInfo tbody")
        .eq(rowIndex)).eq(1).text();
    var fieldValue = '';

    $('input', obj).autocomplete({
        source: jsVars['urls']['autocomplete'][colIndex]
    });

    $('input', obj).keypress(function(event){
        var keyCode = (event.keyCode ? event.keyCode : event.which);

        if(keyCode == '13') {
            $.ajax({
                url: jsVars['urls']['checkMezzanineLocation'],
                type: 'post',
                async: false,
                data: {
                    fieldName: 'l.displayName',
                    fieldValue: fieldValue,
                    vendorName: vendorName
                },
                success: function (data) {
                    if (! JSON.parse(data)) {
                        $('input', obj).val(currentLocation);
                        defaultAlertDialog(message);
                    }
                }
            });
        }
    });
}

/*
 ********************************************************************************
 */

dtMods['utilization'] = {

    fnRowCallback: function (row, rowValues) {
        var clientColumn = jsVars['columnNumbers']['customers'],
            plateColumn = jsVars['columnNumbers']['plates'];

        $('td', row).eq(clientColumn).html(rowValues[clientColumn]
            .replace(/,/g, '<br>'));

        $('td', row).eq(plateColumn).html(rowValues[plateColumn]
            .replace(/,/g, '<br>'));
    }
};

/*
 ********************************************************************************
 */