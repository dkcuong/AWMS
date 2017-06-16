function scc(urls, params) 
{
    var self = this;

    self.dialogInfo = {
        '#addCatDialog': {
            url: urls.sccCatTypes,
            button: '#addCatButton',
            acInput: '.popCatType'
        },
        '#addItemDialog': {
            url: urls.sccCats,
            button: '#addItemButton',
            acInput: '.popCat'
        }
    };
    
    self.table = null;
    
    self.inputs = {};
    self.adding = false;
    self.validUpdate = false;
    
    self.tableFields = params.tableFields;
    
    //**************************************************************************

    self.init = function () {
        
        $('#testOrder input').change(self.changeReason);
        
        self.table = params.dataTables[params.dtName];
        
        $.each(self.dialogInfo, self.createDialog);
        
        self.inputs = {
            changeInput: $('#changeQty #changeInput'),
            currentInput: $('#changeQty #current'),
            resultingInput: $('#changeQty #new')
        };
        
        $(document).keyup(self.changeQty);
        
        $('#changeRadios input').change(self.toggleAdd);
    };
    
    //**************************************************************************
    
    self.isTest = false;
    
    self.changeReason = function (passed) {

        var value = typeof passed === 'string' ? passed : this.value;
        self.isTest = value === 'test';

        self.isTest ? $('#changeRadios input').prop('checked', true) : null;
        self.isTest ? self.toggleAdd('subtract') : null;
        self.toggleRow('.testOnly', self.isTest);

        $('#reasonTitles').show();
        $('#reasonTitles span').hide();
        $('#reasonTitles #'+this.value).show();
    };
    
    //**************************************************************************
    
    self.toggleRow = function (row, trigger) {
        $(row).toggle(trigger);
        $('input', row).prop('disabled', ! trigger);
    };
    
    //**************************************************************************
    
    self.toggleAdd = function (passed) {
        
        self.adding = passed !== 'subtract' && this.value === 'add';

        var $subRows = $('#testOrder, #requestByRow, #styleRow, #reasonTitles');
        self.toggleRow($subRows, ! self.adding);
        self.toggleRow('#supplierRow', self.adding);

        self.toggleRow('.testOnly', self.isTest);

        self.changeQty('radioChange');
        
        var show = self.adding ? '#changeQty #add' : '#changeQty #subtract';

        $('#changeRow').css({display: 'table-row'});
        $('#changeRow span').hide();
        
        $(show).show();
    };
    
    //**************************************************************************
    
    self.changeQty = function (radioChange) {
        
        var changeInputFocused = self.inputs.changeInput.is(':focus');
        
        if (! changeInputFocused && typeof radioChange !== 'string') {
            return;
        }

        var value = self.inputs.changeInput.val();
        var currentValue = self.inputs.currentInput.val();

        self.validUpdate = value % 1 === 0 && value > 0;

        var adding = self.adding ? parseInt(value) : -1 * parseInt(value);

        var newValue = self.validUpdate ? 
            parseInt(currentValue) + adding : currentValue;

        self.validUpdate = newValue < 0 ? false : self.validUpdate;

        self.validUpdate ? self.inputs.changeInput.removeClass('required') : 
            self.inputs.changeInput.addClass('required');

        self.inputs.resultingInput.val(newValue);
    };
    
    //**************************************************************************
    
    self.createDialog = function (dialogID, row) {
        
        $(row.acInput).autocomplete({
            source: row.url,
            appendTo: dialogID
        });

        var dialog = new dialogModel({
            dialogID: dialogID, 
            updateURL: urls.sccUpdate,
            table: self.table
        });
        
        $(row.button).click(dialog.open);
    };
        
    //**************************************************************************

    self.stsDialog = null;
    
    self.dtMod = function (tableRow, data) {
    
        new itemRowModel({
            scc: self,
            data: data,
            tableRow: tableRow,
            updateURL: urls.sccUpdate,
            historyURL: urls.getHistory
        }).addLink({
            target: 'qty',
            change: 'changeQty',
            testValue: false,
            text: 'Change Qty'
        }).addLink({
            target: 'test_qty',
            change: 'changeTestQty',
            testValue: true,
            text: 'Change Test Qty'
        }).addLink({
            change: 'history',
            text: 'Stock History'
        });
        
        var activeCol = params.tableFields.active;
        $('td', tableRow).eq(activeCol)
            .addClass('sccLink').click(function () {
                var lastCol = data.length - 1;
                $('#stsDialog #itemID').val(data[lastCol]);
                
                $('#stsDialog select').val(data[activeCol]);
                $('#stsDialog b').text(data[params.tableFields.sku]);
                
                self.stsDialog = $('#stsDialog').dialog({
                    width: 'auto',
                    height: 'auto',
                    modal: true,
                    buttons: {
                        Submit: function () {
                            $.ajax({
                                type: 'post',
                                url: urls.sccUpdate,
                                dataType: 'json',
                                data: {
                                    id: data[lastCol],
                                    updateStatus: true,
                                    newStatus: $('#stsDialog select').val()
                                },
                                success: function () {
                                    $(self.stsDialog).dialog('close');
                                    self.get('table').fnDraw();
                                }
                            });
                        }
                    }
                });
                
            });
        
    };
        
    //**************************************************************************

    self.historyTable = null;

    self.get = function (name) {
        return self[name];
    };
        
    self.set = function (name, value) {
        self[name] = value;
    };
        
    //**************************************************************************
        
    return self;
}

//******************************************************************************

//******************************************************************************

function itemRowModel(params) 
{
    var self = this;

    self.scc = params.scc;
    self.data = params.data;
    self.tableRow = params.tableRow;
    self.updateURL = params.updateURL;
    self.historyURL = params.historyURL;
    self.tableFields = params.tableFields;
    
    //**************************************************************************

    self.addLink = function (params) {

        var tableFields = self.scc.get('tableFields');

        var cols = {
            idCol: tableFields[params.change],
            itemCol: tableFields['sku'],
            valueCol: tableFields[params.target]
        };

        $('td', self.tableRow)
            .eq(tableFields[params.change])
            .text(params.text)
            .addClass('sccLink').click(function () {
                params.change === 'history' ? self.history(cols) : 
                    self.stockDialog(cols, params);
            });
            
        return self;
    };
    
    //**************************************************************************

    self.history = function (cols) {
        
        $.ajax({
            url: self.historyURL,
            data: {id: self.data[cols.idCol]},
            dataType: 'json',
            success: function (response) {
                $('#historyTable').on('draw.dt', function () {
                    $('#historyDialog').dialog({
                        width: 'auto',
                        height: 'auto',
                        modal: true
                    });
                });
                
                var historyTable = self.scc.get('historyTable');

                historyTable ? historyTable.clear().rows.add(response).draw() : 
                    self.createHistory(response, historyTable);
            }
        });        
    };
    
    //**************************************************************************

    self.createHistory = function (response, historyTable) {
        
        var columns = [
            { title: 'Item', data: 'item' }, 
            { title: 'DT', data: 'dt', class: 'noWrap' }, 
            { title: 'Prev Val', class: 'rightCol', data: 'fromVal' }, 
            { title: 'Diff', class: 'rightCol', data: 'diff' }, 
            { title: 'New Val', class: 'rightCol', data: 'toVal' }, 
            { title: 'User', data: 'username' }, 
            { title: 'Supplier', data: 'supplier' }, 
            { title: 'Reason', data: 'reason' }, 
            { title: 'Tran ID', data: 'tranID' }, 
            { title: 'Style', data: 'style' }, 
            { title: 'Req By', data: 'requestedBy' }
        ];
        
        var dataCols = {};
        columns.map(function (row, index) {
            dataCols[row.data] = index;
        });
        
        historyTable = $('#historyTable').DataTable({
            sort: null,
            columns: columns,
            data: response,
            fnRowCallback: function (row, data) {
                
                [
                    { target: 'style',       cond: data.reason !== 'Test' }, 
                    { target: 'requestedBy', cond: data.reason !== 'Test' }, 
                    { target: 'supplier',    cond: data.diff < 0 }, 
                    { target: 'reason',      cond: data.diff > 0 }, 
                    { target: 'tranID',      cond: data.diff > 0 },

                    { target: 'diff',        cond: data.diff === '0' }, 
                    { target: 'supplier',    cond: data.diff === '0' }, 
                    { target: 'tranID',      cond: data.diff === '0' }, 
                    { target: 'reason',      cond: data.diff === '0' } 
                ].map(function (changes) {
                    var NA = $('<i>').text('N/A').css('color', '#aaa');
                    changes.cond ? $('td', row).eq(dataCols[changes.target]).html(NA) : null;
                });
            }
        });

        self.scc.set('historyTable', historyTable);
    };
    
    //**************************************************************************

    self.stockDialog = function (cols, params) {
        
        $('#testQty').val(params.testValue);
        $('#displayItem b').text(self.data[cols.itemCol]);
        $('#changeQty #itemID').val(self.data[cols.idCol]);
        $('#new, #current', '#changeQty').val(self.data[cols.valueCol]);

        $('#changeQty').dialog({
            width: 'auto',
            height: 'auto',
            modal: true,
            open: function () {
//                $('#addButtonSpan').show();
                $('#changeRow').css({display: 'none'});
                $('#changeRadios input, #testOrder input')
                    .prop('checked', false);
                $('#changeQty #changeInput, #supplier, #tranID').val(null);
            },
            buttons: {'Submit': self.submitButton}
        });
    };
    
    //**************************************************************************

    self.submitButton = function () {
        
        var noQtyChange = ! self.scc.get('inputs').changeInput.is(':visible');
        var validUpdate = self.scc.get('validUpdate');
        if (! validUpdate || noQtyChange) {
            return;
        }

        // Update the value
        $.ajax({
            type: 'post',
            url: self.updateURL,
            data: $('form', this).serializeArray(),
            dataType: 'json',
            success: function () {
                $('#changeQty').dialog('close');
                self.scc.get('table').fnDraw();
            }
        });        
    };
        
    return self;
}

//******************************************************************************

//******************************************************************************

function dialogModel(params) 
{
    var self = this;
    
    //**************************************************************************

    self.open = function () {
        
        $(params.dialogID).dialog({
            width: 'auto',
            minHeight: 250,
            modal: true,
            buttons: {'Submit': self.submit}
        });
    };

    //**************************************************************************

    self.submit = function (event) {
        
        event.preventDefault();

        $('.sccErrors').hide();

        $.ajax({
            type: 'post',
            url: params.updateURL,
            data: $('form', params.dialogID).serializeArray(),
            dataType: 'json',
            success: self.success
        });
    };

    //**************************************************************************

    self.success = function (response) {

        if (response.error) {
            $('#'+response.error).show();
            return;
        }

        if (response.missing) {
            $.map(response.missing, self.required);
            return;
        }

        $('table input', params.dialogID).val(null);

        params.table.fnDraw();

        $(params.dialogID).dialog('close');
    };

    //**************************************************************************

    self.required = function (name) {
        $('[name='+name+']', params.dialogID)
            .addClass('required').focus(self.focus);
    };

    //**************************************************************************

    self.focus = function () {
        $(this).removeClass('required');
    };

    //**************************************************************************
    
    return self;
};

