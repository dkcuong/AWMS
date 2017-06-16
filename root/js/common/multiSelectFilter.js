function addMultiselectFilter()
{
    searcher.outsideDataTable();
    searcher.useExternalParams();

    $('.multiSelectRadio').change(switchMultiselectFilter);
}

/*
********************************************************************************
*/

function switchMultiselectFilter()
{
    var currentSelect = $(this).val(),
        disabedSelects = jsVars['clientView'].slice(0);

    disabedSelects.splice($.inArray(currentSelect, disabedSelects), 1);

    $('#' + disabedSelects.join(', #')).attr('disabled', 'disabled');
    $('#' + currentSelect).removeAttr('disabled');

    searcher.useExternalParams();
}

/*
********************************************************************************
*/
