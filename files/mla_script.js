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
 * @param done_event the event name which will be called in the .done promise
 */
function mla_post_request(form_data, done_event = null) {
    $.ajax({
        url: AJAX_URL,
        type: 'POST',
        data: form_data,
        processData: false,
        contentType: false,
        complete: function (response, status) {
            parse_server_response(response.responseText, status);
        }
    })
        .done(function (){
            if (done_event != null) {
                $(document).trigger(done_event);
            }
        })
}

/**
 * Parses server response and interferes with messages in div with specified IDs
 *
 * @param response server response
 * @param status status (success, error, etc)
 * @param alert_id ID div in which to place the message
 * @param show_ok show or not the OK message. Default - true
 */
function parse_server_response(response, status, show_ok = true) {
    if (status == 'success') {
        try {
            let json = JSON.parse(response);
            if (json['status'] == 'error') {
                display_message(json['msg'] ?? 'Error', 'danger')
                return null;
            } else {
                if (show_ok) display_message(json['msg'] ?? 'OK', 'success')
                return json['response'];
            }
        } catch (e) {
            display_message('JSON parsing error in server response', 'danger')
            return null;
        }
    } else {
        display_message('An error occurred while sending your request', 'danger')
        return null;
    }
}

/**
 * Add a selectbox with prefixes to the login page
 *
 * @param array prefixes array with prefixes
 */
function add_select_with_prefixes(prefixes) {
    let new_select = $("<select name=\'username_prefix\' id=\'select_username_prefix\'>" +
        "<option value=\'\'>Локальный вход</option></select>")

    JSON.parse(prefixes).forEach(function (item) {
        new_select.append($("<option>", {
            value: item,
            text: item
        }))
    })

    new_select.insertAfter("label[for=\'username\']");
}

/**
 * Determine whether the modal window is currently used or not,
 * and depending on this displays a message in the desired div
 *
 * @param text message text
 * @param severity Possible values: success, danger, info, warning
 * @param msg_block_class_name The name of the DIV class where the message passed in text will be written. Default - message
 */
function display_message(text, severity = 'success', msg_block_class_name = 'message') {
    let modal = $('div').find('.modal.in');
    let elem = modal.length ? modal : $(document);
    elem.find('.' + msg_block_class_name + ':first')
        .attr("class", "alert message alert-" + severity)
        .text(text)
        .show();

    if (severity == 'success') {
        setTimeout(() => $('.message').css('display', 'none'), 3000);
    }
    //console.log(modal);
}

/**
 * Creates a new Select
 *
 * @param name name
 * @param id id
 * @param classes classes separated by spaces
 * @param data array ([[k=>v]]) from which options will be created
 * @param selected_value
 * @param is_multiple enable multi-selection
 * return HTML element code
 */
function mla_create_new_select(name, id = '', classes = '', data = null, selected_value = null, is_multiple = false) {
    let elem = $('<select>', {
        id: id,
        name: name,
        class: classes,
        multiple: is_multiple
    });

    if (data !== null) {
        elem = mla_add_options_to_select(elem, data, selected_value);
    }

    return elem.prop('outerHTML');
}

/**
 * Adding new options to the specified SELECT
 *
 * @param select_obj JQuery object SELECT
 * @param data array ([[k=>v]]) from which options will be created
 * @param selected_value
 */
function mla_add_options_to_select(select_obj, data, selected_value = null) {
    for (let key in data) {
        let is_selected = selected_value == key ? true : false;
        select_obj.append($('<option>', {
            value: key,
            text: data[key],
            selected: is_selected
        }));
    }

    return select_obj;
}

/**
 * Adding a new row to the table with rules for projects
 *
 * @param project_list array ([[id => name]])
 * @param domain_list array ([[name => name]])
 * @param right_list array ([[id => name]])
 * @param department filed values Department
 * @param project_selected the value of the selected in the project list
 * @param domain_selected the value of the selected in the domain list
 * @param right_selected the value of the selected in the right list
 */
function mla_udpp_add_new_rule(project_list, domain_list, right_list,
                               id = '-1',
                               department = '',
                               project_selected = null,
                               domain_selected = null,
                               right_selected = null) {

    let projects = mla_create_new_select('project[]', 'project', '', project_list, project_selected);
    let domains = mla_create_new_select('domain[]', 'domain', '', domain_list, domain_selected);
    let rights = mla_create_new_select('rights[]', 'rights', '', right_list, right_selected);

    $('#mla_udpp_rules_tbl').find('tbody').append(
        '<tr>' +
        '   <td>' + projects + '</td>' +
        '   <td><input class="mla_departments_list" id="mla_departments_list_' + id + '" name="department[]" value="' + department + '"></td>' +
        '   <td>' + domains + '</td>' +
        '   <td>' + rights + '</td>' +
        '   <td>' +
        '       <a href="#" class="mla_udpp_delete_rule" data-action="delete_rule" data-id="' + id + '">❌</a>' +
        '       <input type="hidden" class="id" name="id[]" value="' + id + '">' +
        '   </td>' +
        '</tr>'
    );
}

/**
 * Loading the contents of the rules table
 */
function mla_udpp_load_rules_table() {
    $('#mla_udpp_rules_tbl').find('tr:gt(0)').remove();
    $.ajax({
        url: AJAX_URL,
        type: 'POST',
        data: {'action': 'get_rule_udpp'},
        complete: function (response, status) {
            let dat = parse_server_response(response.responseText, status, false);
            if (dat == null) return;
            for (let k in dat){
                mla_udpp_add_new_rule(
                    window.MLA_PROJECTS_LIST,
                    window.MLA_DOMAINS_LIST,
                    window.MLA_RIGHTS_LIST,
                    dat[k]['id'],
                    dat[k]['department'],
                    dat[k]['project_id'],
                    dat[k]['domain'],
                    dat[k]['rights'],
                );
            }
        }
    });
}

/**
 * Adding a block with help text
 *
 * @param inputID The ID of the input element to add help text to
 * @param text Added text
 */
function mla_add_input_help_text(inputID, text) {
    let elem = $('#' + inputID).parent();
    if (!elem.find('[class="mla_input_help_block"]').length) {
        elem.append('<div class="mla_input_help_block">' + text + '</div>');
    }
}

/**
 * Removes help text
 *
 * @param inputID The ID of the input element whose help text should be removed
 */
function mla_del_input_help_text(inputID) {
    $('#' + inputID).parent().find('[class="mla_input_help_block"]').remove();
}
