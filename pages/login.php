<?php
/**
 * Plugin for authorization in MantisBT on multiple LDAP servers
 * Copyright (C) 2021  Starikov Anton - starikov_aa@mail.ru
 * https://github.com/starikov-aa/MultiLdapAuth
 */

$mla_AuthApi = new mla_AuthApi(new mla_LdapApi(), new AuthFlags());

$g_cache_ldap_email = array();

$f_username		= gpc_get_string( 'username', '' );
$f_password		= gpc_get_string( 'password', '' );
$t_return		= string_url( string_sanitize_url( gpc_get_string( 'return', config_get_global( 'default_home_page' ) ) ) );
$f_from			= gpc_get_string( 'from', '' );
$f_secure_session = gpc_get_bool( 'secure_session', false );
$f_reauthenticate = gpc_get_bool( 'reauthenticate', false );

$t_user_id = auth_get_user_id_from_login_name( $f_username );
$t_allow_perm_login = auth_allow_perm_login( $t_user_id, $f_username );
$f_perm_login	= $t_allow_perm_login && gpc_get_bool( 'perm_login' );

if (!empty($f_password) && $mla_AuthApi->attempt_login($f_username, $f_password, $f_perm_login)) {
    log_event(LOG_PLUGIN, 'LOGIN IS OK: ' . $f_username . " = " . $f_password);
    session_set('secure_session', $f_secure_session);
    $t_redirect_url = 'login_cookie_test.php?return=' . $t_return;
} else {
    log_event(LOG_PLUGIN, 'LOGIN IS FAIL !!!: ' . $f_username . " = " . $f_password);
    $t_query_args = array(
        'error' => 1,
        'username' => $f_username,
    );

    if (!is_blank('return')) {
        $t_query_args['return'] = $t_return;
    }

    if ($f_reauthenticate) {
        $t_query_args['reauthenticate'] = 1;
    }

    $t_query_text = http_build_query($t_query_args, '', '&');

    $t_redirect_url = auth_login_page($t_query_text);
}

print_header_redirect($t_redirect_url);