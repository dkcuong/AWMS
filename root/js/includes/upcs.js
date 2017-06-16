/*
********************************************************************************
* GLOBAL VARIABLES                                                             *
********************************************************************************
*/

// This variable can be referenced anywhere
var upcsChecked = [];

/******************************************************************************
 * MAIN FUNCTION                                                              *
 ******************************************************************************
 */

funcStack.upcs = function() {
    typeof jsVars['searcher']['multiID'] === 'undefined' ? null :
        searcher.useExternalParams();

    if (typeof jsVars['urls'] != 'undefined'
        &&  typeof jsVars['urls']['getAutocompleteUpc'] != 'undefined'
    ) {

        $('#upcAdjust').autocomplete({
            source: jsVars['urls']['getAutocompleteUpc']
        });
    }
};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS                                                         *
********************************************************************************
*/

dtMods['upcs'] = {
    
    fnRowCallback: function(nRow, row, index) {

        if (typeof jsVars['checkBoxColumn'] !== 'undefined') {
            // Edit Upcs page
            var rowObj = $('td:eq(' + jsVars['checkBoxColumn'] + ')', nRow);
            var upc = rowObj.text();

            var $input = $('<input>')
                .attr('type', 'checkbox')
                .attr('data-upcs', upc)
                .addClass('chkItem');

            rowObj.prepend($input);
        }
    }
};

/*
 *******************************************************************************
 */

function makeUpcsCheckedData()
{
    var box = $('.chkItem');
    upcsChecked = [];
    box.each(function(){
        if($(this).prop('checked')) {
            upcsChecked.push($(this).data('upcs'));
        }
    });
}

/*
 *******************************************************************************
 */

function updateUpcInfo()
{
    var upcAdjust = $('#upcAdjust').val();
    if (! upcAdjust.length) {
        var message = 'Need input an Adjust UPC';
        defaultAlertDialog(message, $(this));

        return false;
    }

    if (!(/^\d+$/.test(upcAdjust))) {
        var message = 'Only Numeric Values Allowed';
        defaultAlertDialog(message, $(this));

        return false;
    }

    makeUpcsCheckedData();

    if (! upcsChecked.length) {
        var message = 'Need select UPC.';
        defaultAlertDialog(message, $(this));

        return false;
    }

    $.ajax({
        type: 'post',
        url: jsVars['urls']['updateUpcInfo'],
        data: {
            upcAdjust: upcAdjust,
            listUPCs: upcsChecked
        },
        dataType: 'json',
        success: function (response) {
            if (response) {
                var message = 'Ajust UPC completed';
                defaultAlertDialog(message, $(this));

                var tableAPI = dataTables['upcs'].api();
                tableAPI.clear().draw();

            } else {
                var message = 'Adjust UPC input Not Found';
                defaultAlertDialog(message, $(this));

                return false;
            }

        }
    });
}

/*
 *******************************************************************************
 */
