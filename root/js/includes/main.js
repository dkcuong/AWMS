/*
********************************************************************************
* MAIN MENU JS                                                                 *
********************************************************************************
*/

// Global Vars
var changes = [],
    displayingNotice = false,
    newFeatureDialog;


/*
********************************************************************************
* MAIN FUNCTION                                                                *
********************************************************************************
*/

funcStack.mainMenu = function () {

    var anchorURL = window.location.hash.substring(1);
    var activeURL = anchorURL == '' ? jsVars['defaultURL'] : anchorURL;
    
    // Don't go to active URL for mobileMenu
    jsVars.requestMethod === 'mobileMenu' ? null : getPage(activeURL);

    $('#accordion').accordion({
        heightStyle: 'content',
        active: getSubMenu(activeURL)
    });
    
    var accordionWidth = $('#accordion').width(),
        titleHeight = $('#titleCell').height(),
        headerHeight = $('#headerImage td').height();

    changes = [{
            target: '#accordion',
            change: {width: accordionWidth}
        },{
            target: '#titleCell',
            change: {height: titleHeight}
        },{
            target: '#headerImage',
            change: {height: headerHeight}
    }];

    $('.links').click(getPage);
    
    $('#mainDisplay').load(function () {

        // Reset the login timer
        loginDialog.resetTimer();
        
        if (window.needSetHeight){
            setIframeHeight(this.id);
        }

        window.needSetHeight = true;

        $(window).resize(function() {
            setIframeHeight('mainDisplay');
        });
        
        customHide('#accordion', {
            width: 0
        });

        customHide('#titleCell', {
            height: 0
        });

        customHide('#headerImage', {
            height: 0
        });

    });

    $(document).keyup(function(e) {
        if (e.keyCode == 27) { 
            customShow();
        }
    });

    newFeatureDialog = $('#new-feature').dialog({
        autoOpen: false,
        modal: true
    });

    var isShow = jsVars['isShowFeatures'];

    if (isShow) {
        newFeatureDialog.dialog({
            width: 600
        }).dialog('option', 'position', [
            'center',
            200
        ]).dialog('open');
    }

    $('.submitNewFeature').click( function() {
        var isCheck = $('.not-show-feature:checked').length > 0;

        if (isCheck) {
            $.ajax({
                url: jsVars['urls']['updateFeatureShowStatus'],
                type: 'post',
                data: {
                    request: 'updateFeatureShowStatus'
                }
            });
        }
        newFeatureDialog.dialog('close');
    } );
    
    $('#btnToggleAccordion').click(function() {
        displayDashNotice();
        
        var accordion = $('#accordion');
        var headerImage = $('#headerImage'); 
        
        $(this).toggle(300);
        accordion.toggle(300);
        headerImage.toggle(300);        
    });
};

/*
********************************************************************************
*/

function regularDisplay()
{
    var dashFrame = window.frames[0].window;
    
    var $searcher = $('iframe').contents().find('#searcher');
    var $dtElements = $('iframe').contents().find('.dataTables_wrapper ');
    $searcher.show(600);
    $dtElements.children().each(function (index, element) {
        $(element).hasClass('dataTables_processing') ? null : $(element)
            .show(600);
    });
    dashFrame.cycleOn = false;
}

/*
********************************************************************************
*/

function displayDashNotice()
{
    if (! displayingNotice) {
        displayingNotice = true;
        $('#dashNotice').animate({
            top: '57px'
        }, 1000, function () {
            $('#dashNotice').delay(3000).animate({
                top: '-100px'
            }, 1000, function () {
                displayingNotice = false;
            });
        });
    }
}

/*
********************************************************************************
*/

function customHide(myTarget, chagneValues)
{
    chagneValues.opacity = 0;
    $('#mainDisplay').contents().find('#dashView').click(function () {
        displayDashNotice();
        accordionWidth = $(myTarget).width();
        $(myTarget).hide(400, function () {
            $(myTarget).animate(chagneValues, 400);
        });
    });    
}

/*
********************************************************************************
*/

function customShow()
{
    regularDisplay();
    
    $.each(changes, function (index, info) {
        info.change.opacity = 100;
        $(info.target).animate(info.change, 400, function () {
            $(info.target).show(400);         
        });
    });    
    var $dashButtons = $('iframe')
            .contents()
            .find('#dashView, .exportSearcher');
    $dashButtons.show(400);
    
    $('#btnToggleAccordion').show();
}
    
/*
********************************************************************************
*/

function getPage(activeURL) 
{    
    $('.links').removeClass('activeLink');
    $(this).addClass('activeLink');
    
    var newURL = $(this).attr('data-link');
    newURL = typeof newURL == 'undefined' ? activeURL : newURL;
    
    // Break the frame when logging out
    if (newURL == jsVars['urls']['logout']) {
        window.location = newURL;
        return;
    }
    
    $('iframe').attr('src', '//' + jsVars['serverName'] + newURL);
    
    var newTitle = $(this).text();
    if (typeof newTitle != 'undefined') {
        $('title, #titleCell').html(newTitle);
       
    }
}

/*
********************************************************************************
*/

function getSubMenu(activeURL) 
{
    var subMenuIndex = subMenuCounter = 0;
    $.each(jsVars['menu'], function (title, links) {
        $.each(links, function (url, link) {
            if (activeURL == url) {
                $('.links[data-link="' + url + '"]').addClass('activeLink');
                subMenuIndex = subMenuCounter;
                var pageTitle = 'Seldat WMS: ' + link[0];
                $('title').html(pageTitle);
                $('#titleCell').html(link[0]);
            }
        });
        subMenuCounter++;
    });    
    return subMenuIndex;
}

/*
********************************************************************************
*/

function setCookie(cname, cvalue, exdays) 
{
    var d = new Date();
    var daysInyear = 24 * 60 * 60 * 1000;
    var expireDay = d.getTime() + (exdays * daysInyear);
    d.setTime(expireDay);
    
    var expires = 'expires=' + d.toUTCString();
    document.cookie = cname + '=' + cvalue + '; ' + expires;
}

/*
********************************************************************************
*/

function getCookie(cname) 
{
    var name = cname + '=';
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        
        while (c.charAt(0) === ' ') {
            c = c.substring(1);
        }
        
        if (c.indexOf(name) === 0) {
            return c.substring(name.length, c.length);
        }
    }
    
    return '';
}

/*
********************************************************************************
*/