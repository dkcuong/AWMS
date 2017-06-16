/*
********************************************************************************
* SET HEIGHT JS                                                                *
********************************************************************************
*/

function formToArray($input)
{
    var params = {};

    $input.map(function () { 
        
        var name = $(this).attr('name'),
            value = $(this).val(),
            type = $(this).attr('type'),
            row = $(this).attr('data-row-index');

        if ((type == 'radio' || type == 'checkbox') && ! $(this).is(':checked')) {
            return;
        }

        if (typeof name !== 'undefined') {

            name = name.indexOf('[') < 0 ? name : 
                name.substring(0, name.indexOf('['));

            if (typeof row === 'undefined') {
                params[name] = value; 
            } else {
                if (typeof params.tableData === 'undefined') {
                    params.tableData = {};
                }

                if (typeof params.tableData[row] === 'undefined') {
                    params.tableData[row] = {};
                }

                params.tableData[row][name] = value;
            }
        }
    });

    return params;
}

/*
********************************************************************************
*/
