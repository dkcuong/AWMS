/*
********************************************************************************
* GLOBAL VARIABLES                                                             *
********************************************************************************
*/

// This variable can be referenced anywhere
var invoiceType = jsVars['type'];

/*
********************************************************************************
* MAIN FUNCTION                                                                *
********************************************************************************
*/

var cust = getCustModel();
funcStack.invoices = jsVars.process ? new processModel() : getInvModel(cust);

/*
********************************************************************************
* FUNCTION INVOICE MODEL CREATE                                                *
********************************************************************************
*/

function getInvModel(cust)
{

    var self = {

        url: null,

        json: {},

        cust: cust,

        tables: {},

        target: null,

        catsOn: {},

        noCats: true,

        payment: null,

        tablesModel: {},

        inter: {
            'storSelected': 'Storage',
            'recSelected': 'Receiving',
            'orderSelected': 'orderPrc'
        },

        expandFields: {
            'id': 'ID',
            'cat': 'Category',
            'cust': 'Client',
            'name': 'Customer Name',
            'custNum': 'Customer Order Number',
            'clientOrdNum': 'Client Order Number',
            'cq': 'Carton Quantity',
            'pq': 'Pieces Quantity',
            'dt': 'Date',
            'order': 'Order',
            'meas': 'Measurement',
            'request Date': 'Request Date',
            'complete Date': 'Complete Date',
            'request By': 'Request By'
        },

        init: function () {

            /*
             * only get details if customer is selected
             *
             * to do:
             *
             * dont show create inv button if no cats and status is
             * invoice or paid
             *
             * dont show create inv if cats and no items checked
             *
             * uncheck select all if one item is unchecked
             *
             * check select all if all items are checked
             *
             * when the client, period or cats change, there needs to be an
             * ajax request and table updates
             *
             * if cust changes update the ctcID
             */

            self.cust.init();

            self.tablesModel = new invoiceTable(self).search();

            $('#vendorID, #fromDate, #toDate').change(self.tablesModel.search);
            self.setTarget('dts').makeTables().setData();
            $('#profilesVendors').click(self.clickEditCust);
            $('#createInv').click(processInvoices);
            self.url = jsVars['url'];
            $('#invCats input').click(self.clickCats);
            $('#toDate').val(jsVars['toDate']);
            $('#fromDate').val(jsVars['fromDate']);
            $('#vendorID').change(self.changeCustomer);
            self.update().clickCats();
            $('.datepicker').datepicker({'dateFormat': 'yy-mm-dd'});
            $('#processInvoices').click(processInvoices);
            $('.invoicesSearch').change(invoicesSearch);
            self.payment = new payment().init();

            $('.printStatementButton').click(self.printStatementPDF);

            $('#selectProcessing').click(function () {
                var isChecked = $(this).prop('checked');
                $('.invChecks input').prop('checked', isChecked);
            });
            
            $('#orderSelected').click(function () {
                var check = $('#details tr:visible').hasClass('cancel');
                
                $('#note').toggle(check);
            });

            return this;
        },

        getTable: function (name) {
            return self.tables[name];
        },

        setData: function (target, varName) {

            target = target || self.target;

            if (varName) {
                self.json[target] = jsVars[varName];
                return this;
            }

            self.json[target] = [{
                id: "10000003",
                cat: "Receiving",
                cust: 'Just One',
                ext: {
                    'name': "Just_One_Container_1",
                    'meas': "Imperial"
                },
                dt: "2016-04-20"
            }, {
                id: "10001100000010010003",
                cat: "Storage",
                cust: 'Elite Brands',
                ext: {
                    uom: '001',
                    plate: '10000001',
                    loc: '001-A-L1-01',
                    order: '0001100001'
                },
                dt: "2016-04-21"
            }];

            return this;
        },

        setTarget: function (name) {
            self.target = name;
            return this;
        },

        changeCustomer: function () {
            var custID = $(this).val();

            $('#hiddenCustID').val(custID);

            var show = custID ? true : false;
            $('#profilesVendors, #invCats').toggle(show);

            var newCust = custID ? custID : false;
            self.cust.updateCust(newCust);
           
            // If no cust remove default contact
            custID ? $.ajax({
                url: jsVars['urls']['searchCustContactInfo'],
                data: {custID: custID},
                dataType: 'json',
                success: self.cust.updateDefault
            }) : self.cust.updateDefault(false);
        },

        clickCats: function () {

            var inputs = $('#invCats input');
            self.catsOn = {};
            self.noCats = true;

            $.map(inputs, function (input) {
                var inputID = input.id;
                var value = $(input).prop('checked');

                self.noCats = value ? false : self.noCats;

                self.catsOn[inputID] = value;
            });

            var total = 0,
                showing = 0;

            $.each(self.catsOn, function (boxName, isSet) {
                var $eachBox = $('.'+self.inter[boxName]);
                // Hide row expandable data if necessary
                if (! isSet) {
                    $.map($eachBox, function (row) {
                        self.expand(row, 'forceHide');
                    });
                }
                
                $eachBox.toggle(isSet);
                $('input', $eachBox).prop('disabled', ! isSet);
                var typeCount = $('.'+self.inter[boxName]).length;
                total += typeCount;
                showing += isSet ? typeCount : 0;
            });

            var min = showing ? 1 : 0;
            var filtered = showing < total ?
                '\(filtered from '+total+'\ total)' : '';
            var message = 'Showing '+min+' to '+showing+' entries '+filtered;
            $('#details_info').text(message);

            var showInvs = self.noCats || ! self.tablesModel.custID;

            $('#invHolder').toggle(showInvs);
            $('#dtsHolder').toggle(! showInvs);

            return this;
        },

        makeTables: function () {

            self.tables[self.target] = $('#details').DataTable({
                bPaginate: false,
                autoWidth: true,
                columns: [{
                        className: 'invChecks',
                        orderable: false,
                        data: null
                    }, {
                        className: 'detailsControl',
                        orderable: false,
                        defaultContent: '',
                        data: null

                    }, {
                        data: 'cat',
                        title: 'CTG'
                    }, {
                        data: 'id',
                        title: 'ID'
                    }, {
                        data: 'dt',
                        title: 'DT'
                }],
                fnRowCallback: function(nRow, row) {

                    if (row.hasOwnProperty('order') && row.order === 'Cancel') {
                        var highlight =  'cancel';
                    }
                    
                    var className = row.cat;
                    var desc = '';
                    
                    switch (row.cat) {
                        case 'Work Order': className = 'workOrder';
                            break;
                        case 'Order Processing': className = 'orderPrc';
                    }

                    $(nRow).addClass(className)
                           .addClass(highlight);
                  
                    
                    var $checkbox = $('<input>').prop({
                        type: 'checkbox',
                        name: 'items['+className+']['+row.id+']'
                    });
                    $('td', nRow).eq(jsVars['deleteColumnNo']).html($checkbox);
                    
                    if (row.cat === 'Storage') {
                        switch (row.uom) {
                            case 'CARTON':
                            case 'CARTON_CURRENT':
                                desc = row.id + ': ' + row.qty + ' CARTONS';
                                break;
                            case 'VOLUME':
                            case 'MONTHLY_VOLUME':
                            case 'VOLUME_CURRENT':
                                desc = row.id + ': ' + row.qty + ' CUFT';
                                break;
                            case 'MONTHLY_SMALL_CARTON':
                            case 'MONTHLY_MEDIUM_CARTON':
                            case 'MONTHLY_LARGE_CARTON':
                            case 'MONTHLY_XL_CARTON':
                            case 'MONTHLY_XXL_CARTON':
                                desc =  row.id + ': ' + row.qty + ' CUFT/MONTH';
                                break;
                            case 'PALLET_CURRENT':
                            case 'MONTHLY_PALLET':
                                desc =  row.id + ': ' + row.qty + ' PALLETS';
                                break;
                            default:
                                desc =  row.id + ': ' + row.qty;
                                break;  
                        }
                        $('td', nRow).eq(jsVars['idColumnNo']).html(desc);
                    }
                },
                
                fnDrawCallback: function() {
                    var count = $('#details tr.orderPrc.cancel').is(':visible');
                    var order = $('#orderSelected').is(':checked');
                  
                    var value =  order && count;
                   
                    $('#note').toggle(value);
                },
                
                order: [[4, 'desc']]
            });

            // Add event listener for opening and closing details
            self.tables[self.target].on('click', 'td.detailsControl', self.expand);
            
            self.tables.invs = $('#invoices').DataTable({
                bPaginate: false,
                columns: [
                    {
                        className: 'invRadios',
                        orderable: false,
                        data: 'radio'
                    }, {
                        title: 'INV STS',
                        className: 'alignCenter',
                        data: 'sts'
                    }, {
                        data: 'name',
                        className: 'alignCenter',
                        title: 'CUSTOMER'
                    }, {
                        data: 'invNbr',
                        className: 'alignCenter',
                        title: 'INV NBR'
                    }, {
                        data: 'invDT',
                        className: 'alignCenter',
                        title: 'INV DT'
                    }, {
                        data: 'cnclNbr',
                        className: 'alignCenter',
                        title: 'CNCL INV NBR'
                    }, {
                        data: 'currency',
                        className: 'alignCenter',
                        title: 'CUR'
                    }, {
                        data: 'total',
                        className: 'alignCenter',
                        title: 'AMT'
                    }, {
                        data: 'pmntDT',
                        title: 'PMNT RCV DT',
                        className: 'payButtonCell'
                    }, {
                        data: 'check',
                        className: 'alignCenter',
                        title: 'CHECK NBR'
                }],
                fnRowCallback: self.fnRowCallback,
                fnDrawCallback: self.fnDrawCallback,
                order: [[4, 'desc']]
            });

            self.noCats ? $(self.tables.invs).show() : $(self.tables.invs).hide();
            self.noCats ? $(self.tables[self.target]).hide() :
                $(self.tables[self.target]).show();

            return this;
        },

        expand: function(passRow, forceHide) {

            var $tr = passRow.type === 'click' ? 
                $(passRow.target).closest('tr') : $(passRow);

            var row =  self.tables[self.target].row($tr);

            var isShown = row.child.isShown();

            var hide = isShown || forceHide;

            if (hide) {
                row.child.hide();
                $tr.removeClass('shown');
            } else {
                var data = row.data();
                var formatted = self.format(data);
                row.child(formatted).show();
                $tr.addClass('shown');
            }
        },

        fnRowCallback: function(nRow, row, index) {

            var $invoiceCell = $('td', nRow).eq(jsVars['invoiceColumnNo']),
                $paymentDateCell = $('td', nRow).eq(jsVars['paymentDateColumnNo']),
                status = $('td', nRow).eq(jsVars['defaultColumnNo']).text(),
                $row = $('td', nRow).parent();

            if (status === 'Open') {
                var $checkbox = $('<input>').prop({
                    type: 'radio',
                    name: 'openCust',
                    value: row.custID
                });

                $('td', nRow).eq(jsVars['deleteColumnNo']).html($checkbox);
            }

            $('.cancelButton', $invoiceCell).remove();

            var invoice = $invoiceCell.text();

            var cancellation = invoice.substr(invoice.length - 1) === 'C';

            var invoiceNo = invoice ? invoice.replace(' - O', '')
                    .replace(' - C', '') : '';

            var invoiceNum = $invoiceCell.text();
            var href = httpBuildQuery(jsVars['urls']['processInvoices'], {
                inv: invoiceNo
            });

            var $link = $('<a>').attr('href', href).html(invoiceNum);

            $invoiceCell.html($link);

            var paymentDate = $paymentDateCell.text().replace('Clear', '')
                    .replace('Receive Payment', '');

            $row.addClass('invoiceRow')
                .addClass(status)
                .attr('data-status', status)
                .attr('data-payment-date', paymentDate)
                .attr('data-invoice', invoiceNo);

            if (status != 'Open') {
                $row.hide();
            }
         
            $('.paymentButton', $paymentDateCell).remove();

            switch (status) {
                case 'Invoiced':

                    if (cancellation) {
                        break;
                    }

                    $('.cancelButton', $invoiceCell).remove();

                    var $cancelButton = $('<button>')
                        .attr('data-invoice', invoiceNo)
                        .addClass('cancelButton')
                        .html('Cancel');

                    $invoiceCell.append($cancelButton);

                    var $paymentButton = $('<button>')
                        .attr('data-invoice', invoiceNo)
                        .attr('data-row', index)
                        .addClass('paymentButton')
                        .attr('data-type', 'makePayment')
                        .attr('data-paid', false)
                        .html('Receive Payment');

                    $paymentDateCell.append($paymentButton);

                    break;
                case 'Paid':

                    $('.cancelButton', $invoiceCell).remove();

                    var $clearButton = $('<button>')
                        .attr('data-invoice', invoiceNo)
                        .attr('data-row', index)
                        .addClass('paymentButton')
                        .attr('data-type', 'clearPayment')
                        .attr('data-paid', true)
                        .html('Clear');

                    $paymentDateCell.append($clearButton);

                    break;
                default:
                    break;
            }
        },
        
        fnDrawCallback: function() {
            var count = 0;
        
            $('#invoices tr.invoiceRow').each(function() {
                if ($(this).is(':visible')) {
                    count++;
                }
            });  

            var start = count ? 1 : 0;
            var message = 'Showing '+start+' to '+count+' entries ';
            $('#invoices_info').text(message);
        },

        format: function (row) {
            var $table = $('<table>').addClass('expandRow').attr('cellspacing', 0);
            self.addRows($table, row);
            return $table;
        },

        addRows: function ($table, row) {

            $.each(row, function (name, value) {
                if (name === 'ext') {
                    return self.addRows($table, value);
                }

                name = name in self.expandFields ? 
                    self.expandFields[name] : name;
                
                var $title = $('<td>').text(name);
                var $value = $('<td>').text(value);
                 
                var $tr = $('<tr>').append($title).append($value);
                                    
                if (value === 'Cancel')  {
                    $tr.addClass('highlight');
                }
                 
                $table.append($tr);
            });
        },

        update: function (target) {
            target = target || self.target;
            self.tables[target].clear();
            self.json[target].map(self.tables[target].row.add);
            self.tables[target].draw();
            return this;
        },

        filter: function () {

            var url = $(this).attr('id'),
                messages = [];

            var $vendorSelector = $('#vendorID option:selected'),
                $statusSelector = $('#statusID option:selected'),
                params = {};

                    
            if ($vendorSelector.val() > 0) {
                params.vendor = $vendorSelector.text().trim();
                params.vendorID = $vendorSelector.val();
            }

            if ($statusSelector.val() > 0) {
                params.status = $statusSelector.text().trim();
            }

            params.fromDate = $('#fromDate').val();
            params.toDate = $('#toDate').val();

            if (! params.vendorID) {
                messages.push('Select a Client');
            }

            if (! params.fromDate && ! params.toDate) {
                messages.push('Input date range');
            } else if (! params.fromDate || ! params.toDate) {

                missingDate = params.fromDate ? 'To' : 'From';

                messages.push('Input "' + missingDate + '" date');
            } else if (params.fromDate > params.toDate) {
                messages.push('"From" date can not be greater than "To" date');
            }

            if (messages.length) {
                var message = messages.join('<br>');
                return defaultAlertDialog(message);
            }

            window.location = httpBuildQuery(jsVars['profilesVendors'], {
                editables: 'display'
            });
        },

        storeCust: function (cust) {
            self.cust = cust;
        },

        clickEditCust: function () {

            var custID = self.cust.getCust();

            if (! custID) {
                var message = 'Select a Client';
                return defaultAlertDialog(message);
            }

            var newSource = httpBuildQuery(jsVars['urls']['customContacts'], {
                field: 'cust_id',
                value: self.cust.getCust(),
                concat: 'and'
            }, 'jsonLink');

            dataTables.customerContact.fnReloadAjax(newSource);

            return self.cust.dialog();
        },

        printStatementPDF: function (event) {

            event.preventDefault();

            var vendorID = $('#vendorID').val(),
                fromDate = $('#fromDate').val(),
                toDate = $('#toDate').val(),
                messages = [];

            var details = [];

            $('tr', $("#invoices")).map( function () {

                var status = $('td', $(this)).eq(1).text(),
                    invoiceNo = $(this).attr('data-invoice');

                if (status != 'Open' && invoiceNo) {
                    details.push({
                        radio: null,
                        sts: $('td', $(this)).eq(1).text(),
                        name: $('td', $(this)).eq(2).text(),
                        invNbr: $(this).attr('data-invoice'),
                        invDT: $('td', $(this)).eq(4).text(),
                        cnclNbr: $('td', $(this)).eq(5).text(),
                        currency: $('td', $(this)).eq(6).text(),
                        total: $('td', $(this)).eq(7).text(),
                        pmntDT: $(this).attr('data-payment-date'),
                        check: $('td', $(this)).eq(9).text()
                    });
                }
            });

            var tableData = JSON.stringify(details);

            if (! vendorID) {
                messages.push('Select a Customer');
            }

            if (! fromDate) {
                messages.push('Select From Date');
            }

            if (! toDate) {
                messages.push('Select To Date');
            }

            if (tableData === '[]') {
                messages.push('No data to output');
            }

            if (messages.length) {
                return defaultAlertDialog(messages.join('<br>'));
            }

            $('#printSatementVendorID').val(vendorID);
            $('#printSatementFromDate').val(fromDate);
            $('#printSatementToDate').val(toDate);
            $('#printSatementTableData').val(tableData);

            $('#printStatement').submit();
        }
    };

    return self.init;
}

/*
********************************************************************************
* FUNCTION CUSTOMERS MODEL CREATE                                              *
********************************************************************************
*/

function getCustModel()
{
    var self = {

        curCust: false,

        ctcDft: 0,

        dtAPI: null,

        dftCol: jsVars['defaultColumnNo'],

        init: function () {

            // For some reason datatables will only populate the last radio
            // button for default cust ctc when opening the dialog
            setInterval(self.contactDraw, 1000);
            self.dtAPI = dataTables.customerContact.api();
            $('#deleteContacts').click(self.deleteContacts);
            $('#updateCustomer').click(self.updateCustomer);
            return this;
        },

        //**********************************************************************F

        contactDraw: function () {
            var defCtc = 'input:radio[name=dft][data-ctc-id='+self.ctcDft+']';
            $(defCtc).prop('checked', true);
        },
        
        //**********************************************************************

        updateCust: function (curCust) {
            self.curCust = curCust;
        },

        //**********************************************************************

        getCust: function () {
            return self.curCust;
        },

        //**********************************************************************

        updateDefault: function (ctcDft) {
            self.ctcDft = ctcDft;
        },

        //**********************************************************************

        dialog: function () {

            $.ajax({
                url: jsVars['urls']['getCustomerInfo'],
                type: 'post',
                data: {vendorID: self.curCust},
                dataType: 'json',
                success: self.displayCustomer
            });

            $input = $('<input>').attr({
                id: 'cust_id',
                rel: 6,
                type: 'hidden',
                name: 'cust_id',
                value: self.curCust
            }).addClass('valid');

            $('#formAddNewRow').append($input);

            $("a.cust").attr("href", jsVars['customer']+'/vendorID/'+self.curCust);

            $('#profileDialog').dialog({
                modal: true,
                width: 'auto'
            });

            return false;
        },

        //**********************************************************************

        displayCustomer: function (customer) {
            self.ctcDft = customer.cust_ctc_id;
           
            $('.custCode').val(customer.cust_cd);
            $('.custType').val(customer.cust_type);
            $('.custName').val(customer.vendorName);
            $('.billAdd').val(customer.bill_to_add);
            $('.custCity').val(customer.bill_to_city);
            $('.custState').val(customer.bill_to_state);
            $('.custCnty').val(customer.bill_to_cnty);
            $('.custZip').val(customer.bill_to_zip);
            $('.terms').val(customer.net_terms);

            $('.shipAdd').val(customer.ship_to_add);
            $('.shipCity').val(customer.ship_to_city);
            $('.shipState').val(customer.ship_to_state);
            $('.shipCnty').val(customer.ship_to_cnty);
            $('.shipZip').val(customer.ship_to_zip);
            
            var defCtc = $('input[type=radio][data-ctc-id='+self.ctcDft+']');
            $(defCtc).prop('checked', true);
        },

        //**********************************************************************

        deleteContacts: function () {
            var $checkBoxes = $('.selectDelete');
            var deletes = [];

            if (! $checkBoxes.length) {
                var msg = 'Please choose contacts to delete.';
                defaultAlertDialog(msg);
                return;
            }

            $.makeArray($checkBoxes).map(function (box) {
                var $box = $(box);
                var isOn = $box.prop('checked');
                if (isOn) {
                    var ctcID = $box.attr('data-ctc-id');
                    deletes.push(ctcID);
                }
            });

            $.ajax({
                type: 'post',
                url: jsVars['urls']['deleteCustContacts'],
                dataType: 'json',
                data: {
                    ctcIDs: deletes,
                    custID: self.curCust
                },
                success: function () {
                    dataTables.customerContact.fnReloadAjax();
                }
            });

            return false;
        },

        //**********************************************************************

        customInput: function (params) {

            var $td = $('td', params.nRow).eq(jsVars[params.columnName]),
                attributeCol = jsVars[params.attributeCol],
                ctcID = params.row[attributeCol];

            var callback = params.name === 'dft' ? function () {
                    $.ajax({
                        type: 'post',
                        url: jsVars['urls']['updateCustContactInfo'],
                        dataType: 'json',
                        data: {
                            ctcID: ctcID,
                            custID: self.curCust
                        },
                        success: function () {
                            self.ctcDft = ctcID;
                        }

                    });
                } : null;

            var $input = $('<input>')
                .prop('name', params.name)
                .prop('type', params.type)
                .addClass(params.className)
                .attr('data-ctc-id', ctcID)
                .bind('click', callback);

            $td.html($input);

            return this;
        },

        //**********************************************************************

        updateCustomer: function () {
            var params = {
               data: {
                    billTo: $('#billTo .serialize').serialize(),
                    shipTo: $('#shipTo .serialize').serialize()
                },
                vendorID: self.curCust
            };

            $.ajax({
                url: jsVars['urls']['updateCustomerInfo'],
                dataType: 'json',
                type: 'post',
                data: params,
                success: function (response) {
                    if (response.errors) {
                       return defaultAlertDialog(response.errors);
                    } else {
                        var message = 'Customer Information was updated';
                        return defaultAlertDialog(message);
                    }

                }
            });

            return false;
        }
    };

    dtMods['customerContact'] = {

        fnRowCallback: function(nRow, row) {

            self.customInput({
                name: 'del[]',
                row: row,
                nRow: nRow,
                type: 'checkbox',
                className: 'selectDelete',
                columnName: 'deleteColumnNo',
                attributeCol: 'ctcIDColumnNo'
            }).customInput({
                name: 'dft',
                row: row,
                nRow: nRow,
                type: 'radio',
                className: 'selectCtcDft',
                columnName: 'defaultColumnNo',
                attributeCol: 'ctcIDColumnNo'
            });
        },

        fnDrawCallback: self.contactDraw
    };

    return self;
}

/*
********************************************************************************
*/

function processInvoices()
{
    var custID = $('input[name=openCust]:checked', '#billable').val();

    var checkedItems = false;

    $('.invChecks input').map(function () {
        checkedItems = $(this).prop('checked') && ! $(this).prop('disabled') ?
            true : checkedItems;
    });

    var noneSelected = typeof custID === 'undefined' && ! checkedItems;

    noneSelected ? alert('No Invoice Selected') : null;

    return ! noneSelected;
}

/*
********************************************************************************
*/

function invoicesSearch()
{
    var params = getSearchParams();

    jsVars['searchParams'] = [];

    params.vendor && addSearcherInput('vendor', params.vendor);
    params.status && addSearcherInput('status', params.status);
    params.fromDate && addSearcherInput('create_dt', params.fromDate, 'starting');
    params.toDate && addSearcherInput('create_dt', params.toDate, 'ending');
  
return;
    runSearcher();
}

/*
********************************************************************************
*/

function addSearcherInput(type, value, typeDetails)
{
    return;
    typeDetails = typeDetails ? '[' + typeDetails + ']' : '';

    jsVars['searchParams'].push({
        andOrs: ['AND'],
        searchTypes: [type + typeDetails],
        searchValues: [value],
        compareOperator: ['exact']
    });
}

/*
********************************************************************************
*/

function getSearchParams()
{
    var $vendorSelector = $('#vendorID option:selected'),
        $statusSelector = $('#statusID option:selected');

    var params = {};

    if ($vendorSelector.val() > 0) {
        params.vendor = $vendorSelector.text().trim();
        params.vendorID = $vendorSelector.val();
    }

    if ($statusSelector.val() > 0) {
        params.status = $statusSelector.text().trim();
    }

    params.fromDate = $('#fromDate').val();
    params.toDate = $('#toDate').val();

    return params;
}

/*
********************************************************************************
*/

function createInvoice()
{
    return false;
    var values = '';

    $('.select:checked').each(function() {
        values += ',' + $(this).parent().text();
    });

    if (values) {
        $('#values').val(values);
        $('#type').val(jsVars['invoiceType']);
        $('#createInvoiceValues').submit();
    }
}

/*
********************************************************************************
* PAYMENT CLASS                                                                *
********************************************************************************
*/

function payment()
{
    var self = this;

    self.dialog = null;
            
    var status = ['Open', 'Invoiced', 'Paid', 'Canceled'];

    self.selector = $('#paymentDialog');

    //**************************************************************************

    self.init = function () {

        $('#submitPayment').click(self.updatePaymnt);

        $(document).on('click', '.paymentButton', self.makePaymnt);
        $(document).on('click', '.cancelButton', self.cancelInvoice);
        $(document).on('change', '#statusID', self.displayHideInvoices);

        self.dialog = self.selector.dialog({
            title: 'Receive Payment',
            autoOpen: false,
            width: 450,
            modal: true
        });
    };

    //**************************************************************************

    self.displayHideInvoices = function () {

        var currentStatus = $('#statusID option:selected').text();
        
        var total =0;
        
        $('.invoiceRow').map(function (asdf, row) {

            var rowStatus = $(row).attr('data-status');
            var showRow = rowStatus === currentStatus ||
                          currentStatus === 'Display All';
            
            $(row).toggle(showRow);
        });

     
        $.each(status, function(index, value) {
           total += $('.'+ value).length;
        });
    
        var count = currentStatus === 'Display All' ? total 
                                          : $('.'+ currentStatus).length;
        var start = count ? 1 : 0;
        var message = 'Showing '+start+' to '+count+' entries ';
       
        $('#invoices_info').text(message);
    };

    //**************************************************************************

    self.makePaymnt = function () {

        var $this = $(this);

        var isPaid = $this.attr('data-paid') === 'true',
            row = $this.attr('data-row'),
            invoice = $this.attr('data-invoice');

        if (isPaid) {

            self.updateInvoicePayment({
                action: 'cancelPayment',
                invoice: invoice
            }, row);
        } else {

            self.selector.attr('data-row', row);
            self.selector.attr('data-invoice', invoice);

            $('#paymentDialog input').val('');

            self.dialog.dialog('open');
        }

        return false;
    };

    //**************************************************************************

    self.cancelInvoice = function () {

        var invoiceNo = $(this).attr('data-invoice');

        cancelInvoice($(this), invoiceNo, self);

        return false;
    };

    //**************************************************************************

    self.cancelInvoiceAjaxSuccess = function($this, invoiceNo) {

        var $parent = $this.parent().parent();

        var $paymentDateCell = $('td', $parent).eq(jsVars['paymentDateColumnNo']);

        $('td', $parent).eq(jsVars['defaultColumnNo']).html('Cancelled');

        $this.parent().html(invoiceNo + ' - C');

        $this.hide();

        $('.paymentButton', $paymentDateCell).hide();
    };

    //**************************************************************************

    self.updatePaymnt = function () {

        var messages = [];

        $('#paidDate').val() || messages.push('Paid Date is a mandatary input');
        $('#paidReference').val() || messages.push('Paid Reference is a mandatary input');

        if (messages.length) {

            defaultAlertDialog(messages.join('<br>'));

            return false;
        }

        var $paymentInputs = $('.payment', self.selector);

        var data = formToArray($paymentInputs);

        data.invoice = self.selector.attr('data-invoice');
        data.action = 'makePayment';

        var row = self.selector.attr('data-row');

        self.updateInvoicePayment(data, row);

        self.selector.dialog('close');
    };

    //**************************************************************************

    self.updateInvoicePayment = function (data, row) {

        self.row = row;
        self.data = data;

        $.ajax({
            type: 'post',
            url: jsVars['urls']['updateInvoicePayment'],
            data: data,
            dataType: 'json',
            success: self.updateSuccess
        });
    };

    //**************************************************************************

    self.updateSuccess = function(response) {

        if (response.errors.length) {
            defaultAlertDialog(response.errors.join('<br>'));
        } else {

            var $tr = $('#invoices tbody tr').eq(self.row),
                isPaid = self.data.date;

            $('.cancelButton', $tr).remove();

            var buttonText = isPaid ? 'Clear' : 'Receive Payment';

            var $button = $('<button>')
                .attr('data-type', 'clearPayment')
                .attr('data-invoice', self.data.invoice)
                .attr('data-row', self.row)
                .attr('data-paid', !! isPaid)
                .addClass('paymentButton')
                .html(buttonText);

            $(document).on('click', '.paymentButton', self.makePaymnt);

            $('td', $tr).eq(jsVars['paymentDateColumnNo'])
                .html('')
                .html(self.data.date)
                .append($button);

            var statusText = isPaid ? 'Paid' : 'Invoiced';

            isPaid ? $tr.switchClass('Invoiced', 'Paid') :
                $tr.switchClass('Paid', 'Invoiced');

            $tr.attr('data-status', statusText);

            $('td', $tr).eq(jsVars['defaultColumnNo']).html(statusText);
            $('td', $tr).eq(jsVars['checkNumberColumnNo'])
                .html(response.reference);
        }
    };

}

/*
********************************************************************************
* INVOICE TABLE CLASS                                                          *
********************************************************************************
*/

function invoiceTable(invModel)
{
    var self = this;

    self.invModel = invModel;

    self.custRow = {};

    self.searched = [];

    self.custID = 0;

    self.search = function () {

        self.custID = $('#vendorID').val();

        var toDate = $('#toDate').val();
        var fromDate = $('#fromDate').val();

        $.blockUI({message: 'Searching for billable criteria...'});

        $.ajax({
            url: jsVars['urls']['updateInvoTables'],
            data: {
                custID: self.custID,
                startDate: fromDate ? fromDate : jsVars.fromDate,
                endDate: toDate ? toDate : jsVars.toDate,
                sums: true
            },
            dataType: 'json',
            success: self.custSums
        });

        $('#statusID option:contains("Open")').prop('selected', true);

        return self;
    };

    self.custSums = function (response) {

        var invTable = invModel.getTable('invs');
        var dtsTable = invModel.getTable('dts');

        invTable.clear();
        dtsTable.clear();

        $.map(response.invoices, function (invoice) {

            var vendorID = invoice.custID;

            if (jsVars.custList.hasOwnProperty(vendorID)) {

                var row = jsVars.custList[vendorID],
                    total = Math.round(invoice.total * 100) / 100;

                row.check = '';
                row.cnclDT = '';
                row.cnclNbr = '';
                row.invDT = '';
                row.invNbr = '';
                row.pmntDT = '';
                row.total = parseFloat(total).toFixed(2);
                row.sts = status;

                if (invoice.sts == 'Open') {
                    row.sts = 'Open';
                } else {

                    var type = invoice.type;

                    delete invoice.total;
                    delete invoice.custID;
                    delete invoice.type;

                    $.each(invoice, function (key, value) {

                        var suffix = key == 'invNbr' ? ' - ' + type : '';

                        row[key] = value + suffix;
                    });
                }

                invTable.row.add(row);
            }
        });

        response.items ? $.map(response.items, dtsTable.row.add) : null;

        invTable.draw();
        dtsTable.draw();
        self.invModel.clickCats();

        $.unblockUI();
    };
}

/*
********************************************************************************
* PROCESSING MODEL CLASS                                                       *
********************************************************************************
*/

function processModel()
{
    var self = this;

    self.init = function () {
        $('#invoiceCCs').DataTable({
            bFilter: false,
            bPaginate: false,
            autoWidth: true,
            columns: [{
                    title: 'ITEM'
                }, {
                    title: 'DESC'
                }, {
                    title: 'INFO'
                }, {
                    title: 'QTY',
                    className: 'alignCenter'
                }, {
                    title: 'UOM',
                    className: 'alignCenter'
                }, {
                    title: 'PRICE',
                    className: 'alignCenter'
                }, {
                    title: 'AMT',
                    className: 'alignCenter'
            }],
            fnRowCallback: function (nRow, row) {
                [5, 6].map(function (colID) {
                    var noComma = row[colID].replace(',', '');
                    var amount = parseFloat(Math.round(noComma * 100) / 100).toFixed(2);
                    $('td', nRow).eq(colID).text(jsVars['currencyCode'] + ' ' + amount);
                });
            }
        });

        $('#cancel').click(function () {
            window.close();
        });

        $('.printInvoice').click(self.printInvoicePDF);
        $('#cancelInvoice').click(self.cancelInvoice);

        $('#updateInvCust').click(function () {
            var custID = $('#updateInvCust').attr('data-ref-cust');

            var params = {
                data: {
                    billTo: $('#billTo .serialize').serialize(),
                    shipTo: $('#shipTo .serialize').serialize()
                },
                vendorID: custID,
                display: 'invoice'
            };

            $.ajax({
                url: jsVars['urls']['updateCustomerInfo'],
                dataType: 'json',
                type: 'post',
                data: params,
                success: function (response) {
                    if (response.errors) {
                       return defaultAlertDialog(response.errors);
                    } else {
                        var message = 'Customer Information was updated';
                        return defaultAlertDialog(message);
                    }

                }
            });

            return false;
       });
       
        $('.rcvDetail').click(function () {
            $('#viewRcvDetail').submit();
        });
        
        $('.ordDetail').click(function () {
            $('#viewOrdDetail').submit();
        });
       
    },
    self.cancelInvoice = function () {

        var invoiceNo = $('#invoiceNo').val();

        $(this).attr('data-invoice', invoiceNo);

        cancelInvoice($(this), invoiceNo);
    },
    self.printInvoicePDF = function () {

        if(jsVars['reprint']) {
            $('#printPage').submit();
        }

        var details = $('#invoiceCCs').dataTable().fnGetData();
            titles = [],
            items = {};

        var oTable = $('#invoiceCCs').DataTable();
        var columnCount = oTable.columns().nodes().length;

        for (var count = 0; count < columnCount; count++) {
            titles.push(oTable.columns(count).header().to$().text());
        }

        if (jsVars['storeItems']) {
            items = jsVars['storeItems'];
        } else {
            $.map(jsVars.invoInfo.items, function (item) {

                if (typeof items[item.cat] === 'undefined') {
                    items[item.cat] = [];
                }

                items[item.cat].push(item.id);
            });
        }

        $.blockUI({message: 'Saving Invoice data...'});

        $.ajax({
            url: jsVars['urls']['updateInvoiceProcessing'],
            type: 'post',
            data: {
                vendorID: jsVars['vendorID'],
                invoiceNo: jsVars['invoiceNo'],
                billTo: $('#billTo .serialize').serialize(),
                shipTo: $('#shipTo .serialize').serialize(),
                header: {
                    custRef: $('#custRef').text(),
                    invDt: $('#invDt').text(),
                    terms: $('#terms').text()
                },
                details: {
                    titles: titles,
                    data: JSON.stringify(details)
                },
                items: items,
                dateRange: {
                    startDate: jsVars['startDate'],
                    endDate: jsVars['endDate']
                }
            },
            dataType: 'json',
            success: function () {
                $.blockUI.defaults.onUnblock = $('#printPage').submit();
                $.unblockUI();
            }
        });
    };

    return self.init;
}

/*
********************************************************************************
*/

function cancelInvoice($this, invoiceNo, self)
{
    var message = 'Do you really want to cancel Invoice # ' + invoiceNo + ' ?',
        params = {
            row: $this,
            invoiceNo: invoiceNo,
            self: self
        };

    defaultConfirmDialog(message, 'cancelInvoiceExecute', params);
}

/*
********************************************************************************
*/

function cancelInvoiceExecute(data)
{
    var $this = data.row,
        invoiceNo = data.invoiceNo,
        self = data.self;

    $.blockUI({message: 'Cancelling Invoice # ' + invoiceNo + ' ...'});

    $.ajax({
        type: 'post',
        url: jsVars['urls']['cancelInvoice'],
        data: {
            invoiceNo: invoiceNo
        },
        dataType: 'json',
        success: function (response) {

            var message = response.errors ? response.errors.join('<br>') :
                    'Invoice # ' + invoiceNo + ' is successfully canceled';

            defaultAlertDialog(message);

            if (typeof self !== 'undefined') {
                self.cancelInvoiceAjaxSuccess($this, invoiceNo);
            }
            $.unblockUI();
        },
        error: $.unblockUI()
    });
}

/*
********************************************************************************
*/

dtMods['containers'] = {

    fnServerParams: function(aoData) {
        aoData = addControllerSearch(aoData, 'containers');
    }
};

/*
********************************************************************************
*/

dtMods['orders'] = {

    fnServerParams: function(aoData) {
        aoData = addControllerSearch(aoData, 'orders');
    }
};

/*
********************************************************************************
*/

