/*
********************************************************************************
* GLOBAL VARIABLES
********************************************************************************
*/

// This variable can be referenced anywhere
var consolidate;

/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/

funcStack.consolidation = function () {
    $('.createReport').click(consolidate.createReport);
    
    $('.confirmConsolidation').click(consolidate.confirmConsolidation);
    
    $('.selectAll').click(consolidate.checkAll);
    
    $(document).on('change', '.checkboxes', consolidate.updateTotalSaved);
    
    $('#consolidateClient').change(consolidate.clientChange);
    
    $('.printUCCs').click(consolidate.printUCCs);
    
    $('.printPlates').click(consolidate.printPlates);
    
    $('.startWaveTwo').click(consolidate.startWaveTwo);
};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

consolidate = {
    wave: 'one',
    waveID: '#waveOne',
    clientID: 0,
    isWaveOne: true,
    allSelected: false,
    uccsPrinted: false,
    newLocations: [],
    selectedUPCs: [],
    currentClient: 0,
    platesPrinted: false,
    selectedLocations: [],
    hasMadeConsolidation: false,
    

    tableFields: {
        'one': [
            'Include',
            'Style',
            'UPC',
            'Locations Saved by Consolidating'
        ],
        'two': [
            'Include',
            'Style',
            'UPC',
            'From Location',
            'Carton Quantity',
            'To Location',
            'Carton Quantity',
            'Total Quantity',
        ],
        'movements': [
            'Style',
            'UPC',
            'From Location',
            'Carton Quantity',
            'To Location',
            'Carton Quantity',
            'Total Quantity',
        ]
    },
    
    /*
    ****************************************************************************
    */
    
    createReport: function () {
        var upcs = new Array();
        
        var locations = new Array();
        
        $('.checkboxes').each(function (index, checkbox) {
            var isChecked = $(checkbox).prop('checked');
            
            if (isChecked) {
                var upc = $('.upcs').eq(index).text();
                upcs.push(upc);

                var location = $('.locations').eq(index).text();
                locations.push(location);
            }
        });
        
        consolidate.selectedUPCs = upcs;
        consolidate.selectedLocations = locations;
       
        // If the consolidation has been made the ajax can't be run because
        // the report will be blank, instead use the values save before the
        // consolidation
        if (consolidate.hasMadeConsolidation) {
            return window.print();
        }
       
        $.ajax({
            url: jsVars['urls']['getUPCMovements'],
            type: 'post',
            data: {
                upcs: upcs,
                wave: consolidate.wave,
                clientID: consolidate.clientID,
                locations: locations
            },
            dataType: 'json',
            success: consolidate.createReportComplete
        });
    },
    
    /*
    ****************************************************************************
    */

    getTableFields: function (tableID, fieldsKey) {
        var $firstRow = $('<tr>');

        $.each(consolidate.tableFields[fieldsKey], function (dontNeed, field) {
            var fieldCell = $('<td>').text(field);
            $firstRow.append(fieldCell);
        });

        $(tableID).append($firstRow);
    },
    
    /*
    ****************************************************************************
    */

    createReportComplete: function (response) {
        $('#movements tr').remove();
        
        consolidate.waveNumber = response.wave;
              
        var hadReport = consolidate.newLocations.length;
        
        consolidate.getTableFields('#movements', 'movements');

        $.each(response.report, function (upc, movements) {

            var rowSpan = movements.length;
            var firstRowOfUPC = true;

            $.each(movements, function (dontNeed, row) {
                // Wave two will have the UPC in the movement data
                upc = consolidate.isWaveOne ? upc : row.upc;

                var styleContent = 'SKU: ' + row.sku + '<br>HEIGHT: ' + 
                row.height + '<br>WIDTH: ' + row.width + '<br>LENGTH: ' + 
                row.length + '<br>WEIGHT: ' + row.weight;
                
                var $styleCell = $('<td>').html(styleContent);
                var $upcCell = $('<td>').html(upc);
                
                consolidate.isWaveOne 
                    ? $upcCell.attr('rowspan', rowSpan) : false;

                // Save new locations for creating labels later
                hadReport ? null : consolidate.newLocations.push(row.to);

                var $row = $('<tr>');
                var fromCell = $('<td>').text(row.from);
                var cartonFromLocCell = 
                        $('<td>').text(row.cartonOfUpcAtFromLoc);
                var toCell = $('<td>').text(row.to);
                var cartonToLocCell = $('<td>').text(row.cartonOfUpcAtToLoc);
                var totalCartonCell = $('<td>').text(row.totalCartonOfUpc);
                var emptyCell = $('<td>');
                
                // Only add the UPC for its first row
                if (firstRowOfUPC) {
                    $row.append($styleCell, $upcCell, fromCell, 
                    cartonFromLocCell, toCell, cartonToLocCell, 
                    totalCartonCell);
                } else {
                    $row.append(emptyCell, emptyCell, fromCell,
                    cartonFromLocCell, toCell, cartonToLocCell,
                    totalCartonCell);
                }

                // Only wave one reports are by UPC
                firstRowOfUPC = consolidate.isWaveOne ? false : firstRowOfUPC;

                $('#movements').append($row);

            });
        });
        
        window.print();
        
        $('.confirmConsolidation', consolidate.waveID).show();
    },
            
    /*
    ****************************************************************************
    */
   
    printUCCs: function () {
        var firstAnd = 'and';

        $.each(consolidate.newLocations, function (dontNeed, location) {

            var andOr = $('<input>')
                    .prop('type', 'hidden')
                    .prop('name', 'andOrs[]')
                    .val(firstAnd);
            var searchType = $('<input>')
                    .prop('type', 'hidden')
                    .prop('name', 'searchTypes[]')
                    .val('l.displayName');
            var searchValue = $('<input>')
                    .prop('type', 'hidden')
                    .prop('name', 'searchValues[]')
                    .prop('value', location);

            firstAnd = 'or';

            $('#printLabels')
                .append(andOr)
                .append(searchType)
                .append(searchValue);

        });

        $('#printLabels').submit();

        $('#printLabels input').remove();

        consolidate.uccsPrinted = true;
        
        consolidate.waveTwoButton();
    },
            
    /*
    ****************************************************************************
    */
   
    printPlates: function () {
        $.each(consolidate.newLocations, function (dontNeed, location) {

            var locInput = $('<input>')
                    .prop('type', 'hidden')
                    .prop('name', 'term[]')
                    .val(location);

            $('#printPlatesLabels').append(locInput);

        });

        var searchInput = $('<input>')
                .prop('type', 'hidden')
                .prop('name', 'search')
                .prop('value', 'location');

        $('#printPlatesLabels').append(searchInput).submit();

        $('#printPlatesLabels input').remove();
        
        consolidate.platesPrinted = true;

        consolidate.waveTwoButton();
    },
            
    /*
    ****************************************************************************
    */
   
    confirmConsolidation: function () {
        var message = 'WARNING: you are about to permanently move these cartons'
                    + 'in the system. This can not be undone';
        
        defaultConfirmDialog(message, 'consolidationMove')        
    },
            
    /*
    ****************************************************************************
    */
   
    confirmConsolidationComplete: function () {
        // Can't recreate the report after this point
        consolidate.hasMadeConsolidation = true;

        var message = 'The items selected have been relocated in the WMS '
            + 'system. Please physically move these items. And make sure '
            + 'to print a copy of the Consolidation Requirements and ' 
            + 'labels, as you will not be able to recreate them.';

        defaultAlertDialog(message);

        $('.printUCCs, .printPlates', consolidate.waveID).show();
        
        $('.confirmConsolidation', consolidate.waveID).hide();
        
        // Disable the check boxes
        $('.checkboxes').each(function (dontNeed, checkbox) {
            var isChecked = $(checkbox).prop('checked');
            isChecked ? $(checkbox).prop('disabled', true) : null;
        });
    },
            
    /*
    ****************************************************************************
    */
   
    clientChange: function () {
        var notPrinted = consolidate.mustPrintLabels('create another report');

        if (notPrinted) {
            return $('#consolidateClient').val(consolidate.clientID);
        }

        if (this.value == 'Select') {
            return;
        }

        consolidate.newLocations = [];
        
        consolidate.clientID = $('#consolidateClient').val();
        
        // Reset the consolidation flag
        consolidate.hasMadeConsolidation = false;

        $.ajax({
            url: jsVars['url']['clientConsolidate'],
            data: {
                clientID: consolidate.clientID
            },
            dataType: 'json',
            success: consolidate.clientChangeComplete
        });
        
    },
    
    /*
    ****************************************************************************
    */
   
    clientChangeComplete: function (response) {
        // Remove all rows from the moves table
        $('#locsSaved tr').remove();
        $('.consolidateHidden').hide();

        // Reset these values
        consolidate.uccsPrinted = consolidate.platesPrinted = false;
        consolidate.allSelected = false;
        
        consolidate.checkAll();

        if (! response.report) {
            var message = 'No Consolidation Opportunities Found';
            return defaultAlertDialog(message);   
            
        }

        consolidate.getTableFields('#locsSaved', response.wave);

        consolidate.wave = response.wave;

        consolidate.isWaveOne = response.wave == 'one';

        consolidate.waveID = consolidate.isWaveOne ? '#waveOne' : '#waveTwo';

        var report = consolidate.isWaveOne ? 
            response.report : response.report.all;

        $.each(report, function (dontNeed, row) {
            
            var styleContent = 'SKU: ' + row.sku + '<br>HEIGHT: ' + row.height 
            + '<br>WIDTH: ' + row.width + '<br>LENGTH: ' + row.length + 
            '<br>WEIGHT: ' + row.weight;
    
            var upcContent = '<span class="upcs">'+ row.upc + '</span>';
            var $row = $('<tr>');
            var checkbox = $('<input/>').attr('type', 'checkbox')
                    .addClass('checkboxes');
            var checkboxCell = $('<td>').append(checkbox);
            var styleCell = $('<td>').html(styleContent);
            var upcCell = $('<td>').html(upcContent);
            
            if (consolidate.isWaveOne) {
                var quantityCell = $('<td>').text(row.quantity).addClass('quantities');
    
                $row.append(checkboxCell, styleCell, upcCell, quantityCell);
            } else {

                var fromCell = $('<td>').text(row.from).addClass('locations');
                var cartonFromLocCell = 
                        $('<td>').text(row.cartonOfUpcAtFromLoc);
                var toCell = $('<td>').text(row.to);
                var cartonToLocCell = $('<td>').text(row.cartonOfUpcAtToLoc);
                var totalCartonCell = $('<td>').text(row.totalCartonOfUpc);

                $row.append(checkboxCell, styleCell, upcCell, fromCell, 
                cartonFromLocCell, toCell, cartonToLocCell, totalCartonCell);
            }
                
            $('#locsSaved').append($row);
        });

        var $lastRow = $('<tr>');
        var checkboxCell = $('<td>').text('Totals');
        var upcCell = $('<td>').text(0).attr('id', 'totalUPCs');
        if (consolidate.isWaveOne) {
            var quantityCell = $('<td>').text(0).attr('id', 'totalSaved');
            $lastRow.append(checkboxCell, upcCell, quantityCell);
        } else {
            var emptyUOMCell = $('<td>');
            var emptyCell = $('<td>');
            var otherEmptyCell = $('<td>');
            $lastRow.append(checkboxCell, upcCell, emptyUOMCell, emptyCell, 
            otherEmptyCell, emptyCell, emptyCell);
        }

        $('#locsSaved').append($lastRow);

        $('#locsSaved').show();
        $(consolidate.waveID).css('display', 'inline-block');
        $('.selectAll', consolidate.waveID).show();
    },

    /*
    ****************************************************************************
    */
    
    checkAll: function () {
        var notPrinted = consolidate.mustPrintLabels('modify this report');
        
        if (notPrinted) {
            return $(this).prop('checked', false);
        }

        $('input[type=checkbox]:enabled').prop('checked', consolidate.allSelected);

        consolidate.allSelected = ! consolidate.allSelected;

        var buttonText = consolidate.allSelected ? 'Select All' : 'Unselect All';

        $('.selectAll', consolidate.waveID).html(buttonText);
        
        consolidate.updateTotalSaved();
    },

    /*
    ****************************************************************************
    */

    updateTotalSaved: function () {
        var notPrinted = consolidate.mustPrintLabels('modify this report');
        
        if (notPrinted) {
            return $(this).prop('checked', false);
        }

        var total = 0,
            totalUPCs = 0;
        
        $('.checkboxes').each(function (index, checkbox) {
        
            var isDisabeled = $(checkbox).prop('disabled');
        
            var quantity = $('.quantities').eq(index).text();

            var isChecked = $(checkbox).prop('checked');
            
            var checkedQuantity = ! isDisabeled && isChecked ? quantity : 0;
            
            var checkedUPC = ! isDisabeled && isChecked ? 1 : 0;
            
            total += parseInt(checkedQuantity);
            
            totalUPCs += checkedUPC;

        });
        
        $('#totalSaved').text(total);
        $('#totalUPCs').text(totalUPCs);
        
        totalUPCs ? 
            $('.createReport', consolidate.waveID).show() : 
            $('.createReport', consolidate.waveID).hide();
         
        // The report has changed and has to be reprinted
        $('.confirmConsolidation, .printUCCs, .printPlates').hide();
        
        consolidate.uccsPrinted = consolidate.platesPrinted = false;
        consolidate.hasMadeConsolidation = false;
        
        consolidate.newLocations = [];
    },
    
    waveTwoButton: function () {
        if (consolidate.platesPrinted && consolidate.uccsPrinted 
        &&  consolidate.isWaveOne && ! $('input[type=checkbox]:enabled').length
        ) {
            $('.startWaveTwo').show();
        }
    },
    
    startWaveTwo: function () {
        var message = 'Are you sure you want to continue to Wave Two? All of ' 
                    + 'your Wave One reports and labels will no longer be available.';
        
        defaultConfirmDialog(message, 'startWaveTwoExecute');
    },
    
    mustPrintLabels: function (action) {
        // Don't allow the user to select another UPC if a consolidation has 
        // been made but the labels have not been printed
        
        var labelNotPrinted = ! consolidate.uccsPrinted || ! consolidate.platesPrinted;
        
        if (consolidate.hasMadeConsolidation && labelNotPrinted) {
            
            var message = 'You can\'t '+action+' until you have '
                        +' printed the new UCC Labels and License Plates';

            defaultAlertDialog(message);
            
            return true;
        }
        return false;
    }

};

/*
********************************************************************************
*/

function consolidationMove() {
    $.ajax({
        url: jsVars['urls']['consolidationMove'],
        type: 'post',
        data: {
            wave: consolidate.wave,
            upcs: consolidate.selectedUPCs,
            clientID: consolidate.clientID,
            locations: consolidate.selectedLocations
        },
        dataType: 'json',
        success: consolidate.confirmConsolidationComplete
    });    
};

/*
********************************************************************************
*/

function startWaveTwoExecute() {
    consolidate.waveID = '#waveTwo';

    consolidate.clientChange();
}

/*
********************************************************************************
*/
