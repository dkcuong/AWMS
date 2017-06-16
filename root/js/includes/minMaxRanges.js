/*
********************************************************************************
* GLOBAL VARIABLES
********************************************************************************
*/

var warehouseID = 0;

/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/
    
funcStack.minMaxRanges = function () {  

    searcher.useExternalParams();

    var vendorID = $('#vendor').val();
    
    warehouseID = $('option:selected', $('#vendor')).attr('data-warehouse-id');
    
    $('.vendor').change(function() {
        changeVendor(this);
    });
    
    locationAutocomplete(vendorID);

    $('#submitRange').click(submitMinMaxRange);
};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

function submitMinMaxRange(event) 
{
    event.preventDefault();

    var message = '',
        startLocation = $('#startLocation').val(),
        endLocation = $('#endLocation').val();

    var data = {
        vendorID: $('#vendor').val(),
        startLocation: startLocation.trim(),
        endLocation: endLocation.trim(),
    };

    $.ajax({
        url: jsVars['urls']['submitMinMaxRange'],
        type: 'post',
        dataType: 'json',
        data: data,
        success: function(response) {
            if (response.errors) {
                
                message = 'Min Max range values can not be changed due to the '
                    + 'following errors:<br>';
                
                $.map(response.errors, function(error) {
                    message += '<br>' + error;
                });

                defaultAlertDialog(message);

            } else {
                
                message = 'Min Max Range associated with the selected '
                        + 'Client will be updated. Proceede?';
                
                defaultConfirmDialog(message, 'submitMinMaxRangeExecute', data);                
            }
        }
    });
}

/*
********************************************************************************
*/

function submitMinMaxRangeExecute(data) 
{
    $.ajax({
        url: jsVars['urls']['updateMinMaxRange'],
        type: 'post',
        dataType: 'json',
        data: data,
        success: function(response) {
            $('#submitSearch').click();
        }
    });
}

/*
********************************************************************************
*/
