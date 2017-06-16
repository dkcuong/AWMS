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
    
funcStack.minMax = function () {  

    searcher.useExternalParams();

    var vendorID = $('#addMinMax #vendor').val();

    warehouseID = $('option:selected', $('#addMinMax #vendor'))
        .attr('data-warehouse-id');

    $('#addMinMax #vendor').change(function() {
        changeVendor(this);
    });

    locationAutocomplete(vendorID);
    
    $('#upc').autocomplete({
        source: jsVars['urls']['getAutocompleteUpc']
    });

    $.widget('custom.mcautocomplete', $.ui.autocomplete, {
        _renderItem: function (ul, item) {
            var text = '';

            $.each(this.options.columns, function (index, column) {

                var color = index % 2 == 0 ? 'blue' : 'black',
                    field = column.valueField ? column.valueField : 'NA';
                
                text += '<span style="color: ' + color + '">&nbsp' + item[field]
                    + '&nbsp</span>';
            });
            
            var $anchor = $('<a></a>')
                .addClass('mcacAnchor')
                .html(text);
            
            var result = $('<li></li>')
                .data('ui-autocomplete-item', item)
                .append($anchor)
                .appendTo(ul);
            
            return result;
        }
    });

    $('#sku').mcautocomplete({
        columns: [{
            valueField: 'sku'
        }, {
            valueField: 'size'
        }, {
            valueField: 'color'
        }, {
            valueField: 'upc'
        }],
        minLength: 1,
        source: function(request, response) {
            $.ajax({
                url: jsVars['urls']['getAutocompleteSku'],
                dataType: 'json',
                data: {
                    term: request.term
                },
                success: function(data) {
                    if (data.length > 0) {
                        response(data);
                    }
                }
            });
        },
        select: function(event, ui) {

            $('#sku').val(ui.item.sku);
            $('#color').val(ui.item.color);
            $('#size').val(ui.item.size);

            return false;
        }
    });

    $('#category').change(function() {
        changeType(this.value);
    });
    
    $('#submitMinMax').click(submitMinMax);

    $('#submitClientMinMax').click(submitClientMinMax);
    
    $('.toggleLink').click(toggleDiv);
    
    $('.toggleLink').click();
};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

function changeType(category) 
{
    var hideInput = category == 'UPC' ? 'sku' : 'upc',
        showInput = category == 'UPC' ? 'upc' : 'sku';

    $('#' + hideInput)
        .val('')
        .hide();

    $('#' + showInput).show();
    
    if (category == 'SKU') {
        $('.skuDescription').show();
    } else {
        $('.skuDescription').hide();
        $('input.skuDescription').val('');
    }
}

/*
********************************************************************************
*/

function submitMinMax(event) 
{
    event.preventDefault();

    var category = $('#category option:selected').text();
    var value = category == 'UPC' ? $('#upc').val() : $('#sku').val(),
        location = $('#location').val(),
        message = '';

    var $addMinMax = $('#addMinMax');

    var data = {
        vendorID: $('#vendor', $addMinMax).val(),
        location: location.trim(),
        category: category,
        value: value.trim(),
        color: $('#color').val(),
        size: $('#size').val(),
        minCount: $('#locationMin', $addMinMax).val(),
        maxCount: $('#locationMax', $addMinMax).val()
    };
    
    $.ajax({
        url: jsVars['urls']['submitMinMax'],
        type: 'post',
        dataType: 'json',
        data: data,
        success: function(response) {
            if (response.errors) {
                
                message = 'Min Max insert / update can not be performed '
                    + 'due to the following errors:<br>';
                
                $.map(response.errors, function(error) {
                    message += '<br>' + error;
                });

                defaultAlertDialog(message);

            } else if (response.warning) {
                
                $.map(response.warning, function(warning) {
                    message += '<br>' + warning;
                });
                
                defaultConfirmDialog(message, 'submitMinMaxExecute', data);
                
            } else {
                submitMinMaxExecute(data);
            }
        }
    });
}

/*
********************************************************************************
*/

function submitMinMaxExecute(data) 
{
    $.ajax({
        url: jsVars['urls']['updateMinMax'],
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

function submitClientMinMax(event) 
{
    event.preventDefault();

    var $clientMinMax = $('#clientMinMax'),
        message = '';

    var data = {
        vendorID: $('#vendor', $clientMinMax).val(),
        minCount: $('#locationMin', $clientMinMax).val(),
        maxCount: $('#locationMax', $clientMinMax).val()
    };

    $.ajax({
        url: jsVars['urls']['submitMinMax'],
        type: 'post',
        dataType: 'json',
        data: data,
        success: function(response) {
            if (response.errors) {
                
                message = 'Client Min Max values can not be changed due to the '
                    + 'following errors:<br>';
                
                $.map(response.errors, function(error) {
                    message += '<br>' + error;
                });

                defaultAlertDialog(message);

            } else {
                
                message = 'All Min Max values associated with the selected '
                        + 'Client will be updated. Proceede?';
                
                defaultConfirmDialog(message, 'submitClientMinMaxExecute', data);                
            }
        }
    });
}

/*
********************************************************************************
*/

function submitClientMinMaxExecute(data) 
{
    $.ajax({
        url: jsVars['urls']['updateClientMinMax'],
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

function toggleDiv() 
{
    $(this).siblings('.toggleDiv').slideToggle('slow');
    
    var caption = $(this).html() == 'Hide' ? 'Display' : 'Hide';
    
    $(this).html(caption);
}

/*
********************************************************************************
*/

