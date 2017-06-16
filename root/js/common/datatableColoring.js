function tableColoring(id)
{
    if (coloring.titleClicked) {

        var cellID = jsVars['titles'][coloring.titleClicked];

        // sorting on columns which cells can be highlighted
        var $rows = $('#' + id).dataTable().fnGetNodes();

        for (var row = 0; row < $rows.length; row++) {
            // remove dataTables native class to enable custom highlighting
            $($rows[row]).find('td').eq(cellID).removeClass('sorting_1');
        }
    }

    $.each(jsVars['columnColors'], function (className, columns) {
        $.map(columns, function (column) {

            var $title = $('.dataTables_scrollHead th').eq(column);

            $title.addClass(className + 'Cell')
                .removeClass('sorting')
                .removeClass('sorting_asc')
                .removeClass('sorting_desc');
        });
    });
}

/*
********************************************************************************
*/

function rowColoring(nRow)
{
    $.each(jsVars['columnColors'], function (className, columns) {
        $.map(columns, function (column) {

            var $cell = $('td', nRow).eq(column);

            var cellClass = $cell.html() ? className : 'red';

            $cell.addClass(cellClass + 'Cell');
        });
    });
}

/*
********************************************************************************
*/

function addBackgroundColorClasses(colors)
{
    $.each(colors, function(className, color) {
        $('<style>')
            .attr('type', 'text/css')
            .html('.' + className + 'Cell {background-color: #' + color + ';}')
            .appendTo('head');
    });
}
