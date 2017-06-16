funcStack.tallySheet = function () 
{
    var totalCells = 30;
    
    $('.datepicker').datepicker({
        'dateFormat': 'yy-mm-dd'
    });
      
    $('.dateUpdate').datepicker({
        'dateFormat': 'yy-mm-dd',
        onSelect: function() {
            var curId = this.id.split("_")[1];
            var datePick = document.getElementById("dateInput_"+curId);

            $("#dateDisplay_"+curId).html(datePick.value);
            $('#dateDisplay_'+curId).show();
            $(this).change();
        }
    });
    
    
    $('#addRow input').click(function () {
        var $row = $('<tr>');
        for (cell=1; cell<=6; cell++) {
            var $cell = $('<td>');
            var $input = $('<input>').prop('name', 'pallet[]').prop('type', 'text');
            $cell.html(++totalCells).append($('<br>')).append($input);
            $row.append($cell);
        }
        
        $('#pallets #addRow').before($row);
    });
    
    $('#submit').click(function () {
        
        $.post( $('#infoID').attr('action'),
                $('#pallets :input').serializeArray());        
    });
    
    // Function to convert input to uppercase and display
    $('.input').keyup(function () { 
        var curId = this.id.split("_")[1];
        var inputID = document.getElementById("input_"+curId);
        inputID.value = inputID.value.toUpperCase();

        $("#display_"+curId).html(inputID.value);
        $('#display_'+curId).show();
    });
    
    $('.numericCheck').keyup(function () { 

        var n = this.value;
        if (n != '' && (isNaN(parseFloat(n)) || ! isFinite(n))) {
            var message = 'Only Integer Values Allowed';
            defaultAlertDialog(message);
            this.value = '';            
        }
    });    
};


