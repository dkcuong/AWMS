/*
********************************************************************************
* GLOBAL VARIABLES
********************************************************************************
*/

var cycleOn = false;
var nextStart = 0;

var receiving = {
    type: 'receiving',
    categories: [
        'In Transit',
        'Received',
        'Racked'
    ],
    hideCategories: [
        [''],
        [
            'Received',
            'Racked'
        ],
        [
            'In Transit',
            'Racked'
        ],
        [
            'In Transit',
            'Received'
        ]
    ],
    categoryBars : {
        '0': 'All',
        '1': 'In Transit', 
        '2': 'Received', 
        '3': 'Racked'
    }, 
    searchFields: {
        'In Transit': 'iin',
        'Received': 'rc',
        'Racked': 'rk'
    },
    titleClicked: '',
    columnTitles: [],
    hiddenTitles: [],
    visibleTitlesIndices: []
};

var shipping = {
    type: 'shipping',
    categories: [
        'Rush',
        'Checked In',
        'Routing',
        'Picking',
        'Work Orders',
        'Order Processing',
        'Shipping'
    ],
    hideCategories: [
        [''],
        [
            'Routing',
            'Picking',
            'Order Processing',
            'Work Orders',
            'Shipping'
        ],
        [
            'Checked In',
            'Picking',
            'Order Processing',
            'Work Orders',
            'Shipping'
        ],
        [
            'Checked In',
            'Routing',
            'Order Processing',
            'Work Orders',
            'Shipping'
        ],
        [
            'Checked In',
            'Routing',
            'Picking',
            'Work Orders',
            'Shipping'
        ],
        [
            'Checked In',
            'Routing',
            'Picking',
            'Order Processing',
            'Shipping'
        ],
        [
            'Checked In',
            'Routing',
            'Picking',
            'Order Processing',
            'Work Orders'
        ]
    ],
    categoryBars: {
        '0': 'All',
        '1': 'Checked In',
        '2': 'Routing',
        '3': 'Picking',
        '4': 'Order Processing',
        '5': 'Work Orders',
        '6': 'Shipping'
    }, 
    searchFields: {
        'Checked In': 'wmco',
        'Routing': 'rtco',
        'Picking': 'pkco',
        'Order Processing': 'opco',
        'Work Orders': 'woco',
        'Shipping': 'shco'
    },
    incomplete: {
        'wmco': 'wmci',
        'rtco': 'rtci',
        'pkco': 'pkci',
        'opco': 'opci',
        'woco': 'woci',
        'shco': 'lsci'
    },
    titleClicked: '',
    columnTitles: [], 
    hiddenTitles: [], 
    visibleTitlesIndices: [], 
    blinkDate: '', 
    blinkToggle: false,
    logDateField: -1,
    statusField: -1
};

var dashboard = jsVars['urls']['type'] == 'receiving' ? receiving : shipping;

if (jsVars['urls']['type'] == 'shipping') {
    getLogColumnIndex();    
}

/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/

funcStack.dashBoards = function () {

    typeof searcher === 'undefined'|| searcher.outsideDataTable();

    $('#dashView').click(dashboardDisplay);

    $('.dataTables_scrollHead th').click(function(e) {
        dashboard.titleClicked = $(this).html();
    });
    
    addCategoryDropDown();

    hideShowColumns(0);

    $('#category').change(function() {
        hideShowColumns(this.value);
    });
};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

function dashboardDisplay() {
    $('#searcher').hide(600);
    $('.dataTables_wrapper').children().each(function (index, element) {
        $(element).hasClass('dataTables_scroll') ? null : $(element).hide(600);
    });
    $('#dashView, .exportSearcher').hide(600);
    cycleOn = true;
}

/*
********************************************************************************
*/

dtMods['shipping'] = {
    fnDrawCallback: dashDraw,
    fnRowCallback: dashRow
};

/*
********************************************************************************
*/

dtMods['receiving'] = {
    fnDrawCallback: dashDraw,
    fnRowCallback: dashRow
};

/*
********************************************************************************
*/

function dashDraw(oSettings) {
    // Get next display
    nextDisplay = oSettings._iDisplayStart + oSettings._iDisplayLength;
    // Don't go over
    nextDisplay = nextDisplay >= oSettings._iRecordsTotal ? 0 : nextDisplay;

    if (dashboard.titleClicked) {
        // refreshing array of visible columns (is needed when colouring table cells)
        if (dashboard.visibleTitlesIndices.length == 0) {
            
            dashboard.visibleTitlesIndices = [];
            // creating an array of visible columns (is needed when colouring table cells)
            $('#'+dashboard.type+' th').each(function() {
                
                var title = $(this).text();
                var index = $(this).index();
                
                visibleColumnIndex (title, index);
            });
        }

        if (categoryTitle(dashboard.titleClicked)) {
            
            var cellID = dashboard.visibleTitlesIndices[dashboard.titleClicked];
            // sorting on columns which cells can be highlighted
            var $rows = $('#'+dashboard.type).dataTable().fnGetNodes();
            
            for (var row = 0; row < $rows.length; row++) {

                var $cell = $($rows[row]).find('td').eq(cellID);
                var cellText = $cell.html();

                if (cellText == 'Rush' || complete(cellText)) {
                    // remove dataTables native class to enable custom highlighting
                    $cell.removeClass('sorting_1');
                }
            }
        }
    }
}

/*
********************************************************************************
*/

function dashRow(nRow, row) {
    dashboard.columnTitles.length || defineArrays();
    
    $.each(row, function (cellID, cellText) {
        
        var title = dashboard.columnTitles[cellID];
        var $cell = $('td', nRow).eq(cellID);

        var isCancelled = row[10] == 'Cancelled';

        isCancelled ? $(nRow).addClass('cancelledOrder') : null;

        if (dashboard.type == 'shipping' && title == 'Cancel Date') {
            if (cellText && ! isCancelled && dashboard.blinkDate 
            &&  cellText < dashboard.blinkDate) {
                // applying blink for the row
                nRow.setAttribute('data-blink', '1');
            }
            
            //change cancel date to red if less than current date
            var currentDate = new Date();
            var cellDate = new Date(cellText);

            if (cellDate < currentDate){
                $cell.addClass('alarmDate');
            }
        }

        if (cellText == 'Rush') {
            $cell.addClass('rush');
        } else if (categoryTitle(title)) {
            // getting visible column new index by its title
            cellID = title == 'Rush' ? cellID 
                : dashboard.visibleTitlesIndices[title];
            
            if (jsVars['urls']['type'] == 'shipping') {
                if (title != 'Rush') {
                    var $cell = $('td', nRow).eq(cellID);
                    var field = dashboard.searchFields[title];
                    
                    displayCellText(field, cellText, row, $cell);
                }                
            }
            if (complete(cellText)) {
                $('td', nRow).eq(cellID).addClass('complete');

                if (title == 'Shipping') {
                    // remove blink if the order is shipped
                    nRow.removeAttribute('data-blink');
                }
            } else {
                $('td', nRow).eq(cellID).addClass('incomplete');
            }
        }
    });
};

/*
********************************************************************************
*/

setInterval(function () {
    if (cycleOn) {
        var modelName = jsVars['searcher']['modelName'];
        
        tableAPI = dataTables[modelName].api();
        
        var pageInfo = tableAPI.page.info();
        var targetPage = pageInfo.page == pageInfo.pages - 1 ? 'first' : 'next';
        
        pageInfo.pages > 1 ? tableAPI.page(targetPage).draw(false) : null;
    }
}, 5000);

/*
********************************************************************************
*/

setInterval(function() {
    if (dashboard.type == 'shipping') {
// intentionally do not use toggleClass function as it causes alternate blink on 
// different parts of the table should it be extended from one order column to all
        if (dashboard.blinkToggle) {
            // no blink
            toggleBlink('blinkRow_', '');
            $('[data-blink="1"] td').removeClass('blinkRow');    
        } else {
            // blink
            toggleBlink('', 'blinkRow_');
            $('[data-blink="1"] td').addClass('blinkRow');
        }
    }
}, 2000);

/*
********************************************************************************
*/

function toggleBlink(remove, add) {
    $.each(['sorting_1', 'rush', 'complete', 'alarmDate'], function(key, value) {
        $('[data-blink="1"] .'+remove+value).addClass(add+value);
        $('[data-blink="1"] .'+add+value).removeClass(remove+value);
    });
    dashboard.blinkToggle = ! dashboard.blinkToggle;
}

/*
********************************************************************************
*/

function hideShowComplete() {
    var caption = $('#category :selected').text();
    var category = caption == 'All' ? '' : dashboard.searchFields[caption];

    $('#completion')
        .attr('data-search-field', category)
        .attr('disabled', ! category);

    searcher.useExternalParams();
}

/*
********************************************************************************
*/

function hightlightCells() {
    $('#'+dashboard.type+' tr td').each(function () {
        var col = $(this).parent().children().index($(this));
        var title = $('#'+dashboard.type+' th').eq(col).text();
        
        if (categoryTitle(title)) {
            var cellText = $(this).html();
            
            if (cellText == 'Rush' && title == 'Rush' || complete(cellText)) {
                $(this).addClass(cellText.toLowerCase());
            }
        }
    });
}

/*
********************************************************************************
*/

function hideShowColumns(hideIndex) {
    var $oTable = $('#'+dashboard.type).DataTable();
    
    dashboard.hiddenTitles = dashboard.hideCategories[hideIndex];

    // hiding columns that are in hiddenTitles list
    $.each(dashboard.categories, function(key, title) {
        
        var isVisible = ! hiddenTitle(title);
        var index = dashboard.categories[title];
        
        $oTable.column(index).visible(isVisible);
    });

    // refreshing array of visible columns (is needed when colouring table cells)
    dashboard.visibleTitlesIndices = [];
    
    $('#'+dashboard.type+' th').each(function() {
        visibleColumnIndex($(this).text(), $(this).index());
    });
    
    hideShowComplete();

    hightlightCells();
}

/*
********************************************************************************
*/

function addCategoryDropDown() {
    // creating assoc array of columns that can be hidden
    $('#'+dashboard.type+' th').each(function() {
        var title = $(this).text();
        
        if (categoryTitle(title)) {
            $(this).attr('category', title);
            dashboard.categories[title] = $(this).index();
        }
    });

    // creating drop down with categories to hide
    var count = 0;
    
    for (var value in dashboard.categoryBars) {
        count++;
        $('<option />', {
            value: value, 
            text: dashboard.categoryBars[value]
        }).appendTo($('#category'));
    }
    
    $('#category').attr('size', count);
}

/*
********************************************************************************
*/

function defineArrays(nRow, row) {
    if (dashboard.type == 'shipping') {            
        // getting date for blinking
        var date = new Date();
        // day of week (0 - Sunday)
        var dow = date.getDay();
        // 2 business days warning
        switch(dow) {
            case 6:
                date.setDate(date.getDate() + 3);
                break;
            case 4:
            case 5:
                date.setDate(date.getDate() + 4);
                break;
            default:
                date.setDate(date.getDate() + 2);
        }

        var day = date.getDate();
        var month = date.getMonth() + 1;
        // adding leading zeros to day/month
        var properDay = ('0'+day).slice(-2);
        var properMonth = ('0'+month).slice(-2);

        dashboard.blinkDate = date.getFullYear()+'-'+properMonth+'-'+properDay;
    }

    // defining arrays when the table is displayed at the first time
    $('th').each(function() {
        var index = $(this).index();
        var title = $(this).text();
        
        dashboard.columnTitles[index] = title;
        visibleColumnIndex (title, index);
    });
}

/*
********************************************************************************
*/

function visibleColumnIndex(title, index) {
    if (categoryTitle(title) && ! hiddenTitle(title)) {
        dashboard.visibleTitlesIndices[title] = index;
    }
}

/*
********************************************************************************
*/

function complete(text) {
    return text == 'Complete' || text == 'Date not found' 
        || text.match(/^\d{4}-((0\d)|(1[012]))-(([012]\d)|3[01])$/);
}

/*
********************************************************************************
*/

function categoryTitle(title) {
    return ~$.inArray(title, dashboard.categories);
}

/*
********************************************************************************
*/

function hiddenTitle(title) {
    return ~$.inArray(title, dashboard.hiddenTitles);
}

/*
********************************************************************************
*/

function displayCellText(field, cellText, row, $cell) {
    var logDateField = row[dashboard.logDateField],
        statusField = row[dashboard.statusField];
    if (logDateField === null || statusField === null) {
        return;
    }

    var status = row[dashboard.statusField].toLowerCase();

    $.each(dashboard.incomplete, function (key, value) {
        // key - check out (wmco, ..)
        // value - check in (wmci, ..)
        if (field == key 
            && (cellText == 'Complete' && status == key || status == value)) {
            if (row[dashboard.logDateField]) {               
                $cell.html(row[dashboard.logDateField]);
            }

            return false;
        }
    });
}

/*
********************************************************************************
*/

function getLogColumnIndex() {
    if (jsVars['urls']['type'] == 'shipping') {
        $.each(jsVars['fields'], function (index, field) {
            switch (field) {
                case 'logDate': 
                    dashboard.logDateField = index;
                    break;
                case 'lastStatus': 
                    dashboard.statusField = index;
                    break;
                default:
                    break;
            }
        });    
    }
}

/*
********************************************************************************
*/
