/*
********************************************************************************
* MAIN FUNCTION
********************************************************************************
*/

funcStack.users = function () {

    $('#resetAllPassword').click(resetAllPassword);

    $('.toggleLink').click(toggleDiv);
};

/*
********************************************************************************
* ADDITIONAL FUNCTIONS
********************************************************************************
*/

dtMods['resetPassword'] = {

    fnRowCallback: function (row, rowValues) {

        var actionResetColumn = jsVars['columnNumbers']['actionReset'],
            usernameColumn = jsVars['columnNumbers']['username'];

        var tdActionReset = $('td', row).eq(actionResetColumn);

        tdActionReset.css('text-align', 'center').html('');

        $('<button>')
            .prop('type', 'buton')
            .html('Reset Password')
            .addClass('columnResetPassword')
            .attr('value', rowValues[actionResetColumn])
            .attr('username', rowValues[usernameColumn])
            .attr('onClick', 'resetPassword(this)')
            .prependTo(tdActionReset);
    }

};

/*
********************************************************************************
*/

function resetPassword(element)
{

    var username = $(element).attr('username'),
        userID = $(element).attr('value'),
        msg = 'Are you sure reset password "' + username + '"',
        params = {
            userID: userID,
            username: username
        };

    defaultConfirmDialog(msg, 'processRequestAjax', params);
}

/*
********************************************************************************
*/

function resetAllPassword()
{
    var params = {
        inputContent: $('#emailValue').val()
    };

    if (jsVars['isDevelop']) {
        var msg = 'Passwords will be reset. Are you sure?';
        defaultConfirmDialog(msg, 'processRequestAjax', params);

    } else {
        defaultAlertDialog('Access denied!');
    }
}

/*
********************************************************************************
*/

function processRequestAjax(data)
{
    $.ajax({
        url: jsVars['urls']['resetPassword'],
        data: data,
        type: 'POST',
        beforeSend: function () {
            $.blockUI({
                message: 'Please wait...'
            });
        },
        success: function (result) {
            $.unblockUI();

            if (result) {
                defaultAlertDialog(result);
            }
        }
    });
}

/*
********************************************************************************
*/

function toggleDiv(e)
{
    e.preventDefault();

    $(this).siblings('.toggleDiv').slideToggle('slow');

    var caption = $(this).html() == 'Hide' ? 'Display Input' : 'Hide';

    $(this).html(caption);
}

/*
********************************************************************************
*/