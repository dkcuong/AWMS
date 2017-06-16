/*
********************************************************************************
* GLOBAL VARIABLES
********************************************************************************
*/

var palletSheet;

/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/
// unblock when ajax activity stops 

funcStack.plates = function () {
    $('#printSheet').click(function () {
        palletSheet.create();
    });
    
    $('#palletNumber').change(function () {
        var palletNumber = $('#palletNumber').val();

        var buttonText = palletNumber == 1 ? 'Print Pallet Inventory Sheet'
            : 'Print Pallet Inventory Sheets';
        
        $('#printSheet').val(buttonText);
    });
    
    $('#tallyForm').submit(tallyForm);
    

    
};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

palletSheet = {

    info: {},
    
    busy: false,
    
    palletNumber: 0,
    
    palletIDs: [],
    
    requiredInputs: {
        'vendorID': {
            inputID: '#vendorSelect',
            message: 'No Vendor Selected',
            displayClass: '.vendorDisplay'
            
        },
        'warehouseID': {
            inputID: '#warehouseSelect',
            message: 'No Warehouse Selected',
            displayClass: '.warehouseDisplay'
        }
    },

    /*
    ****************************************************************************
    */

    checkInput: function (inputName, reqInput) {
        var inputID = reqInput.inputID;
        
        // Pass the input revalues to the palletSheet's info array
        palletSheet.info[inputName] = $(inputID).val();

        if (palletSheet.info[inputName] == 0) {
            palletSheet.quitCheck = true;
            defaultAlertDialog(reqInput.message);
            return false;
        }
    },
    
    /*
    ****************************************************************************
    */

    updateTitles: function (inputID, input) {
        inputID = input.inputID;
        var inputName = $('option:selected', inputID).text();
        $(input.displayClass).text(inputName);                    
    },
    
    /*
    ****************************************************************************
    */

    create: function () {
        
        // One request at a time
        palletSheet.busy = true;
        
        $.blockUI.defaults.css.padding = '8px';

        // Flag if missing input value
        this.quitCheck = false;

        // Check taht a vendor and warehouse were selected
        $.each(this.requiredInputs, this.checkInput);
        if (this.quitCheck) {
            return;
        }
        
        var palletNumber = this.info.palletNumber = $('#palletNumber').val();
        
        if (palletNumber > jsVars['nextPallet']) {
            var message = 'The next available pallet number is '+jsVars['nextPallet'];
            return defaultAlertDialog(message);        
        }

        if (palletNumber % 1 !== 0 || palletNumber == 0) {
            var message = 'Invalid Pallet Number';
            defaultAlertDialog(message);
            
        }

        $.blockUI({
            message: 'Creating Pallet Tally Sheets...'
        });                
        
        $.ajax({
            url: jsVars['urls']['addPalletSheets'],
            type: 'post',
            data: this.info,
            dataType: 'json',
            success: palletSheet.checkResults
        });
    },

    /*
    ****************************************************************************
    */

    checkResults: function (palletIDs) {

        palletSheet.palletIDs = palletIDs;
                
        var pageSheetCount = palletIDs.length;

        if (! pageSheetCount) {
            var message = 'Error: Pallet Sheets Not Created.';
            return defaultAlertDialog(message);
        }

        $.blockUI.defaults.onUnblock = palletSheet.printPages;
        $.unblockUI();
    },
    
    /*
    ****************************************************************************
    */

    printPages: function () {
        
        $('.sheetCopies').remove();

        $.each(palletSheet.palletIDs, function (index, palletNumber) {
            var $sheetCopy = $('#sheetPage').clone();
            $sheetCopy.addClass('sheetCopies printOnly')
                      .removeAttr('id');
            $('.palletNumberDisplay', $sheetCopy).text(palletNumber);
            $('body').append($sheetCopy);
        });

        // Update the vendor and warehouse displays
        $.each(palletSheet.requiredInputs, palletSheet.updateTitles);


        window.print();

        palletSheet.busy = false;
    }    
     
};

function tallyForm()
{
    var vendor = $('#vendor');
    var warehouse = $('#warehouse');
       
    if (vendor.val() == 0 || warehouse.val() == 0) {
        var message = 'Please select vendor and warehouse';
        defaultAlertDialog(message);
        
        return;
    }
    
};
