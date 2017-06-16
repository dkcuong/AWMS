/*
********************************************************************************
* GLOBAL VARIABLES
********************************************************************************
*/

var volUOM = [
    'MONTHLY_SMALL_CARTON', 
    'MONTHLY_MEDIUM_CARTON', 
    'MONTHLY_LARGE_CARTON',
    'MONTHLY_XL_CARTON', 
    'MONTHLY_XXL_CARTON'
];

/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/
// unblock when ajax activity stops 

funcStack.costs = function () {
    
    enableDisable();
    
    $('#vendors').change(function () {
   
        var vendorID = $(this).val();
        var prefix = $('#prefix').val();
                
        $('.costs').val('');
        getClientCosts(vendorID, prefix);
        enableDisable();
    });

    $('#prefix').change(function () {
        var prefix = $('#prefix').val();
        getPrefixes();
    });
    
    $('.costs').keypress(preventKey);
    
    $('.update').click(updateClientCosts);
    
    $('.delete').click(deleteClientCosts);

};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

function preventKey(event) {
    if (event.keyCode === 10 || event.keyCode === 13) {
        event.preventDefault();
    }
}

/*
********************************************************************************
*/

function enableDisable() {
    var vendorID = $('#vendors').val();

    $('.costs').prop('disabled', vendorID === '0');
    $('.update').prop('disabled', vendorID === '0');
    $('.delete').prop('disabled', vendorID === '0');
}


/*
********************************************************************************
*/

function getPrefixes(prefix) {
    
    var vendorID = $('#vendors').val();
    var prefix = $('#prefix').val();

    $.ajax({
        url: jsVars['urls']['getChargeCodes'],
        type: 'post',
        data: { 
            vendorID: vendorID,
            prefix: prefix
        },
        dataType: 'json',
        success: function(charges) {
  
            $('tr:not(.filter').remove();

            $.each(charges, function (index, value) {

              var costs = value['chg_cd_price']  &&  value['chg_cd_price'] !== '0.00' ? 
                                        value['chg_cd_price'] : '';

                $tr = $('<tr>');
              
                $td = $('<td>')
                      .css('border', '1px solid black')
                      .html(value['chg_cd']);
                $tr.append($td);
              
                $td = $('<td>')
                     .css('border', '1px solid black')
                     .html(value['chg_cd_des']);
                $tr.append($td);
                
                $td = $('<td>')
                      .css('border', '1px solid black')
                      .attr('width', '4%')
                      .html(value['chg_cd_uom']);
                $tr.append($td);
                
                $td = $('<td>')
                     .css('border', '1px solid black')
                     .attr('width', '1%')
                     .html('USD');
                $tr.append($td);	
                
                $input  = $('<input>')
                            .addClass('costs')
                            .attr('type', 'text')
                            .attr('size', 8)
                            .attr('data-ref-id', index)
                            .on('keypress', preventKey)
                            .val(costs);
                $td = $('<td>')
                     .css('border', '1px solid black')
                     .attr('width', '5%')
                     .html($input);
                $tr.append($td);

                $updBtn = $('<button>')
                        .addClass('update')
                        .attr('value', index) 
                        .attr('data-ref-cat', value['chg_cd_type'])
                        .attr('data-ref-uom', value['chg_cd_uom'])
                        .on('click', updateClientCosts)
                        .html('Update');
                $td = $('<td>')
                      .css('border', '1px solid black')
                      .attr('align', 'center')
                      .attr('width', '3%')
                      .html($updBtn);
                $tr.append($td);
                
                $delBtn = $('<button>')
                        .addClass('delete')
                        .attr('value', index)   
                        .on('click', deleteClientCosts)
                        .html('Delete');
                $td = $('<td>')
                      .css('border', '1px solid black')
                      .attr('align', 'center')
                      .attr('width', '3%')
                      .html($delBtn);
                $tr.append($td);
                
                              
              $('tbody').append($tr);            
            });
            
            enableDisable();
        }   
   });
}


/*
********************************************************************************
*/

function getClientCosts(vendorID, prefix) {
    $.ajax({
        url: jsVars['urls']['getClientCosts'],
        data: { 
            vendorID: vendorID,
            prefix: prefix
        },
        dataType: 'json',
        success: function(costs) {
            $.each(costs, function (index, value) {
               valueCost = value['chg_cd_price'];
               
               var valueCost = valueCost && valueCost !== '0.00' ? valueCost : '';
                
                $('input[data-ref-id='+index+']').val(valueCost);
            });
        }
    });
}

/*
********************************************************************************
*/


function updateClientCosts(event) {
    
    event.preventDefault();
      
    var vendorID = $('#vendors').val();
    var chgID = $(this).val();
    var cost = $('input[data-ref-id='+chgID+']').val();
    var cat = $(this).attr('data-ref-cat');
    var uom = $(this).attr('data-ref-uom');
    var updatedCell = $('input[data-ref-id='+chgID+']');

    var param = {
            vendorID: vendorID,
            chgID: chgID,
            cost: cost,
            updatedCell: updatedCell
    };
    
    var volParam = {
            vendorID: vendorID,
            chgID: chgID,
            cat: cat,
            uom: uom
    };

    if (cost && cost > 0 && $.isNumeric(cost)) {
        if ($.inArray(uom, volUOM) !== -1) {
           
            checkVolumeRates(param, volParam);
        } else {
                    
            var message = 'Are you sure to update the cost?'; 

            defaultConfirmDialog(message, 'updateClientCostsExecute', param);
        }
    } else {
        var message = 'Only valid values are allowed!';
        updatedCell.val('');
        defaultAlertDialog(message, updatedCell);
    }
}

/*
********************************************************************************
*/

function updateClientCostsExecute(param) {
    var vendorID = param['vendorID'];
    var chgID = param['chgID'];
    var cost = param['cost'];
    var updatedCell = param['updatedCell'];

    $.ajax({
        url: jsVars['urls']['updateClientCosts'],
        type: 'post',
        data: { 
            vendorID: vendorID,
            chgID: chgID,
            cost: cost
        },
        dataType: 'json',
        success: function() {
            var value = Number(updatedCell.val()).toFixed(2);
            updatedCell.val(value || '');
            var message = 'Charge code price was updated';
            defaultAlertDialog(message);
        }
    });
}

/*
********************************************************************************
*/

function deleteClientCosts(event) {
    event.preventDefault();      

    var chgID = $(this).val();
    var updatedCell = $('input[data-ref-id='+chgID+']');
    var vendorID = $('#vendors').val();
      
    $.ajax({
        url: jsVars['urls']['deleteClientCosts'],
        type: 'post',
        data: { 
            vendorID: vendorID,
            chgID: chgID
        },
        dataType: 'json',
        success: function(response) {
            if (response) {
                message = 'Charge code price was removed for this Client selected';
                updatedCell.val('');
                defaultAlertDialog(message);
            }
        }
    });
}

/*
********************************************************************************
*/

function checkVolumeRates(param, volParam) {

    var  updatedCell = param['updatedCell'];
    
    $.ajax({
            url: jsVars['urls']['checkVolumeRates'],
            type: 'post',
            data: volParam,
            dataType: 'json',
            success: function(response) {
               
                if (response.success) { 
                    var message = 'Are you sure to update the cost?'; 

                    defaultConfirmDialog(message, 'updateClientCostsExecute', param);
                } else if (response.warning) {
                    updatedCell.val('');
                    
                    defaultAlertDialog(response.warning);
                }
            }
        });
}

/*
********************************************************************************
*/

