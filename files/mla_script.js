/*
 * Plugin for authorization in MantisBT on multiple LDAP servers
 * Copyright (C) 2021  Starikov Anton - starikov_aa@mail.ru
 * https://github.com/starikov-aa/MultiLdapAuth
 */

$(document).ready(function () {
    disable_user_field();
    disable_admin_field();
});

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