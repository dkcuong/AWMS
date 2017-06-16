var labelModel = jsVars['labelModel'];

funcStack.labelMaker = function () {
    
    $('button').click(function () {
        $('.successMessage').hide();
        var quantity = parseInt($('#quantity').val()),
            userID = parseInt($('#userID').val()),
            message = '';

        if (! userID) {
            message += 'Please select user.<br>';
        }
        if (! quantity) {
            message += 'Please input quantity.';
        } else if (quantity > 500) {
            message += 'Amount of labels created should not exceed 500.'
        }

        if (message) {
            defaultAlertDialog(message);
        } else {
            $.ajax({
                type: 'post',
                dataType: 'json',
                url: jsVars['urls']['addLabels'],
                data: {
                    model: labelModel,
                    quantity: quantity,
                    userID: userID
                },
                success: addLabel
            });
        }
    });
    
};

/*
********************************************************************************
*/

dtMods[labelModel] = {
    fnRowCallback: function (row, data) {
        var labelLink = '<a href="'+jsVars['urls']['displayLabels']
                   + data[0]+'">Print Label '+data[0]+'</a>';
        $('td', row).eq(0).html(labelLink);
        
        var dateLink = '<a href="'+jsVars['urls']['labelsByDate']
                   + data[1]+'">Print Labels From '+data[1]+'</a>';
        $('td', row).eq(1).html(dateLink);

        var batchLink = '<a href="'+jsVars['urls']['labelsByBatch']
                   + data[2]+'">Print Batch '+data[2]+'</a>';
        $('td', row).eq(2).html(batchLink);
    }
};

/*
********************************************************************************
*/

function addLabel(response) 
{
    if (response !== false) {
        response > 1 ? $('#plural').show() : $('#plural').hide();
        response == 1 ? $('#singular').show() : $('#singular').hide();
        // Update Label Table
        $('.successMessage').show(800);
        dataTables[labelModel].fnReloadAjax();
    }
}

/*
********************************************************************************
*/

