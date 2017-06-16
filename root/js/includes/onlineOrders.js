funcStack.onlineOrdersMain = function () {

    if(typeof jsVars['searcher'] != 'undefined'
    && typeof jsVars['searcher']['multiID'] != 'undefined'
    ) {
        searcher.useExternalParams();
    }

    // Update possible custom data to global js vars
    $.each(dataTables, function (tableName, table) {
        dataTables[tableName]['tableAPI'] = table.api();
        dataTables[tableName]['tableAPI'].on('xhr', function () {
            var json = dataTables[tableName]['tableAPI'].ajax.json();
            jsVars['dataTables'][tableName]['custom'] = json.custom;
        });
    });

    $('#importOrders').on('submit', function () {
       if ($('#vendorID').val() == 0) {
           var message = 'Please select a Vendor';
           defaultAlertDialog(message);

           return false;
       }

       if ($('#dealSiteID').val() == 0) {
           var message = 'Please select a Deal Site';
           defaultAlertDialog(message);

           return false;
       }

       return true;
    });

    if (typeof jsVars['isOpenReportOnlineOrder'] != 'undefined') {
        // hide searcher form
        $('#searcher').hide();
        $('#searcher').height(0);
        addMultiselectFilter();
    } else {
        $('.exportSearcher').click(checkExportTable);
    }
};

/*
********************************************************************************
*/

dtMods['onlineOrders'] = {

    // Add the carrier links
    fnRowCallback: function(row, rowValues, rowID) {

        var upsLinkColumn = jsVars['columnNumbers']['upsLink'],
            batchColumn = jsVars['columnNumbers']['batch'],
            orderColumn = jsVars['columnNumbers']['order'],
            carrierColumn = jsVars['columnNumbers']['carrier'],
            noInventoryColumn = jsVars['columnNumbers']['noInventory'];

        if (jsVars['dataTables']['onlineOrders']['custom'][rowID]) {
            $(row).addClass('multiRow');
        }

        var carrier = rowValues[carrierColumn] ? rowValues[carrierColumn] :
            'No Carrier';

        $('td', row).eq(upsLinkColumn).text(carrier);

        if (rowValues[noInventoryColumn] == 'No Error') {
            // do not display export links for error orders
            var batchLink = httpBuildQuery(jsVars['urls']['listExported'], {
                carrier: rowValues[carrierColumn],
                batch: rowValues[batchColumn],
                editable: 'display',
                firstDropdown: 'batch_order'
            });

            var $batchAnchor = getHTMLLink({
                link: batchLink,
                title: 'Export Batch ' + rowValues[batchColumn],
                getObject: true
            });

            $('td', row).eq(batchColumn).html('').append($batchAnchor);
        }

        var orderLink = httpBuildQuery(jsVars['urls']['printLabel'], {
            orderNumber: rowValues[orderColumn]
        });

        var $orderAnchor = getHTMLLink({
            link: orderLink,
            title: 'Print Batch Labels  ' + rowValues[orderColumn],
            getObject: true
        });

        $('td', row).eq(orderColumn).html('').append($orderAnchor);
    }
};

/*
********************************************************************************
*/

dtMods['onlineOrdersFails'] = {

    // Add the carrier links
    fnDrawCallback: function() {
        $('.searchTime').on('click', searchTable);
        $('#onlineOrdersFails_filter input').prop('name', 'searchTime');
    },
    fnRowCallback: function(nRow, row, index) {

        var timeDateCol = 0;

        if (jsVars['dataTables']['onlineOrdersFails']['custom'][index]) {
            $(nRow).addClass('multiRow');
        }

        $('td:eq('+timeDateCol+')', nRow).addClass('searchTime');

    }
};

/*
********************************************************************************
*/

function searchTable()
{
    var submitTime = $(this).html();
    dataTables['onlineOrdersFails']['tableAPI'].search(submitTime).draw();
}

/*
********************************************************************************
*/

function checkExportTable()
{
    var table = [];

    $('#onlineOrderExports tr').each(function() {

        var rows = [];
        var tableData = $(this).find('td');

        tableData.each(function() {

            var cellText = $(this).text();

            rows.push(cellText);
        });

        table.push(rows);
    });

    $.ajax({
        type: 'post',
        url: jsVars['urls']['checkExportTable'],
        data: {
            table: table
        },
        dataType: 'json',
        success: function(response) {
            if (response) {

                var message = '';

                $.each(response, function(key, value) {
                    message += '<br>'+value;
                });

                defaultAlertDialog(message);
            }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            defaultAlertDialog(xhr.responseText);
        }
    });
}

/*
********************************************************************************
*/
