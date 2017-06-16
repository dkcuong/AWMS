/*
********************************************************************************
* SET TO MISSING INPUT FIELDS JS                                                                *
********************************************************************************
*/

function scrollToMissingField()
{
    var scrollToElement = $(".missField").first(),
        checkElement = scrollToElement.length,
        titleHeight = window.parent.$('#titleCell').height(),
        headerHeight = window.parent.$('#headerImage td').height();

    window.parent.$('body, html').css('height', 'inherit');

    if (checkElement) {
        window.parent.$('body, html').animate({
            scrollTop: scrollToElement.offset().top + titleHeight + headerHeight
        });
    }
}