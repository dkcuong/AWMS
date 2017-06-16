/*
********************************************************************************
* SET LOCATION AUTO COMPLETE                                                   *
********************************************************************************
*/

function changeVendor(vendors)
{
    var vendorID = $(vendors).val();
        selectedWarehouseID = $('option:selected', $(vendors)).attr('data-warehouse-id');

    if (selectedWarehouseID != warehouseID) {

        warehouseID = selectedWarehouseID;

        locationAutocomplete(vendorID);
    }
}

/*
********************************************************************************
*/

function locationAutocomplete(vendorID)
{
    $('.location').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: jsVars['urls']['getLocationNames'],
                dataType: 'json',
                data: {
                    term: request.term,
                    vendorID: vendorID,
                    clause: 'isMezzanine'
                },
                success: function(data) {
                    if (data.length > 0) {
                        response(data);
                    }
                }
            });
        }
    });
}

/*
********************************************************************************
*/
