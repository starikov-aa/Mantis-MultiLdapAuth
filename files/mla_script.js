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
