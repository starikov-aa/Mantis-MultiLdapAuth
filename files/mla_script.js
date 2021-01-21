/*
 * Plugin for authorization in MantisBT on multiple LDAP servers
 * Copyright (C) 2021  Starikov Anton - starikov_aa@mail.ru
 * https://github.com/starikov-aa/MultiLdapAuth
 */

const AJAX_URL = 'plugin.php?page=MultiLdapAuth/ajax';

$(document).ready(function () {
    if(typeof(window.mla_user_flags) != "undefined"){
        disable_user_field();
        disable_admin_field();
    }
    check_bt_collapse();
});

/**
 * Сворачивает bootstrap collapse в зависимости от состояния связанного checkbox
 */
function check_bt_collapse() {
    $('input').each(function () {
        if ($(this).data('toggle') == 'collapse') {
            let elem = $($(this).data('target'));
            if ($(this).attr('checked')){
                elem.collapse('show');
            } else {
                elem.collapse('hide');
            }
        }
    })
}

/**
 * В зависимости от настроек для сервера LDAP,
 * отключает редактирование полей на странице пользователя
 */
function disable_user_field() {
    let flags = window.mla_user_flags;

    if (!flags.user_is_local){
        if (flags.use_ldap_email) {
            $('#email-field').attr('disabled', 'disabled')
        }
        if (flags.use_ldap_realname) {
            $('#realname').attr('disabled', 'disabled')
        }
    }
}

/**
 * В зависимости от настроек для сервера LDAP,
 * отключает редактирование полей на странице пользователя в админке
 */
function disable_admin_field() {
    let flags = window.mla_user_flags;

    if (!flags.user_is_local){
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
        elem.map(e => $(e).attr('disabled', 'disabled'))
    }
}

function mla_load_server_settings() {
    $.getJSON(AJAX_URL, {'action': 'get_server_settings'}, function (data) {

    })
}

/**
 * Задает значение элементам указаной формы
 *
 * @param form_id ID формы
 * @param elem_values Объект с данными. Пример {'my_input_ID': 'my_input_VALUE', 'my_checkbox_ID': 1}
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
            //f_elem.find('option[selected]').prop('selected', false);
            f_elem.find('option[value=' + elem_values[key] + ']').attr('selected','selected');
        } else {
            console.log(`Elem id: ${key}, Elem Type: ${f_elem_type}`);
        }
    }
}

/**
 *
 * @param form_data FormData object
 */
function mla_post_request(form_data) {
    $.ajax({
        url: '/mantis-plugins/plugin.php?page=MultiLdapAuth/ajax',
        type: 'POST',
        data: form_data,
        processData: false,  // Сообщить jQuery не передавать эти данные
        contentType: false   // Сообщить jQuery не передавать тип контента
    });
}