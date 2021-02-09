/*
 * Plugin for authorization in MantisBT on multiple LDAP servers
 * Copyright (C) 2021  Starikov Anton - starikov_aa@mail.ru
 * https://github.com/starikov-aa/MultiLdapAuth
 */

const AJAX_URL = 'plugin.php?page=MultiLdapAuth/ajax';

$(document).ready(function () {
    if (typeof (window.mla_user_flags) != "undefined") {
        disable_user_field();
        disable_admin_field();
    }
    check_bt_collapse();
});

/**
* Collapses bootstrap collapse based on the state of the associated checkbox
 */
function check_bt_collapse() {
    $('input').each(function () {
        if ($(this).data('toggle') == 'collapse') {
            let elem = $($(this).data('target'));
            if ($(this).attr('checked')) {
                elem.collapse('show');
            } else {
                elem.collapse('hide');
            }
        }
    })
}

/**
* Depending on the settings for the LDAP server,
 * disables editing fields on the user page
 */
function disable_user_field() {
    let flags = window.mla_user_flags;

    if (!flags.user_is_local) {
        if (flags.use_ldap_email) {
            $('#email-field').attr('readonly', true)
        }
        if (flags.use_ldap_realname) {
            $('#realname').attr('readonly', true)
        }
    }
}

/**
* Depending on the settings for the LDAP server,
 * disables editing of fields on the user page in the admin panel
 */
function disable_admin_field() {
    let flags = window.mla_user_flags;

    if (!flags.user_is_local) {
        let elem = [
            '#edit-username',
            '#manage-user-delete-form > fieldset > span > input[type="submit"]',
            '#manage-user-reset-form > fieldset > span > input[type="submit"]'
        ];

        if (flags.use_ldap_email) {
            elem.push('#email-field');
        }
        if (flags.use_ldap_realname) {
            elem.push('#edit-realname');
        }
        elem.map(e => $(e).attr('readonly', true))
    }
}

function mla_load_server_settings() {
    $.getJSON(AJAX_URL, {'action': 'get_server_settings'}, function (data) {

    })
}

/**
 * Sets the value to elements of the specified form
 *
 * @param form_id Form ID
 * @param elem_values Data object. Example {'my_input_ID': 'my_input_VALUE', 'my_checkbox_ID': 1}
 */
function set_form_elem_value(form_id, elem_values) {
    for (let key in elem_values) {

        let f_elem = $(`#${form_id} #${key}`);
        let f_elem_type = f_elem.attr('type');

        if (f_elem_type == 'text' || f_elem_type == 'hidden') {
            f_elem.val(elem_values[key]);
        } else if (f_elem_type == 'checkbox' && elem_values[key] == 1) {
            f_elem.prop('checked', true);
        } else if (f_elem.prop('tagName') == 'SELECT') {
            f_elem.find('option[value=' + elem_values[key] + ']').prop('selected', true);
        } else {
            console.log(`Elem id: ${key}, Elem Type: ${f_elem_type}`);
        }
    }
}

/**
 * POST the request and place the response in a div
 *
 * @param form_data FormData object
 * @param alert_id ID div in which to place the message
 */
function mla_post_request(form_data, alert_id = 'alert-main') {
    $.ajax({
        url: AJAX_URL,
        type: 'POST',
        data: form_data,
        processData: false,
        contentType: false,
        complete: function (response, status) {
            parse_server_response(response.responseText, status, alert_id);
        }
    });
}

/**
 * Parses server response and interferes with messages in div with specified IDs
 *
 * @param response server response
 * @param status status (success, error, etc)
 * @param alert_id ID div in which to place the message
 */
function parse_server_response(response, status, alert_id) {
    if (status == 'success') {
        try {
            let json = JSON.parse(response);
            if (json['error'] !== '') {
                display_alert(alert_id, json['error'], 'alert-danger')
            } else {
                display_alert(alert_id, json['result'], 'alert-success')
            }
        } catch (e) {
            display_alert(alert_id, 'JSON parsing error in server response', 'alert-danger')
        }
    } else {
        display_alert(alert_id, 'An error occurred while sending your request', 'alert-danger')
    }
}

/**
 * Writes a message to a div with the specified ID
 *
 * @param alert_id ID div in which to place the message
 * @param text Message text
 * @param type message type (alert-success, alert-danger, alert-info, alert-warning)
 */
function display_alert(alert_id, text, type = 'alert-success') {
    $('#' + alert_id)
        .addClass(type)
        .text(text);
}