/*
********************************************************************************
* SET HEIGHT JS                                                                *
********************************************************************************
*/

function setIframeHeight(id)
{
    var winHeight = document.all ? document.body.clientHeight :
        window.innerHeight;

    var tempHeight = 30;

    winHeight = winHeight - tempHeight;

    var iframe = document.getElementById(id);
    if (! iframe) {
        iframe = parent.document.getElementById(id);
    }

    var doc = iframe.contentDocument ? iframe.contentDocument :
        iframe.contentWindow.document;

    var heightDocIframe = getDocHeight(doc);

    if (heightDocIframe < winHeight) {
        iframe.style.height  = '100%';
    }

    iframe.style.visibility = 'visible';
}

/*
********************************************************************************
*/

function getDocHeight(doc)
{
    doc = doc || document;

    var body = doc.body,
        html = doc.documentElement;

    var height = Math.max(body.scrollHeight, body.offsetHeight,
        html.clientHeight, html.scrollHeight, html.offsetHeight);

    return height;
}

/*
********************************************************************************
*/
