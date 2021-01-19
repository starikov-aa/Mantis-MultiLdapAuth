<?php
/**
 * Plugin for authorization in MantisBT on multiple LDAP servers
 * Copyright (C) 2021  Starikov Anton - starikov_aa@mail.ru
 * https://github.com/starikov-aa/MultiLdapAuth
 */

auth_reauthenticate();
access_ensure_global_level( config_get( 'manage_site_threshold' ) );

$mla_tools = new mla_Tools();

$t_action = gpc_get_string('action', '');
$t_server = gpc_get_string('server', '');
$t_root_dn = gpc_get_string('root_dn', '');
$t_bind_dn = gpc_get_string('bind_dn', '');
$t_bind_passwd = gpc_get_string('bind_passwd', '');
$t_uid_field = gpc_get_string('uid_field', '');
$t_realname_field = gpc_get_string('realname_field', '');
$t_network_timeout = gpc_get_string('network_timeout', '');
$tprotocol_version = gpc_get_string('protocol_version', '');
$t_follow_referrals = gpc_get_string('follow_referrals', '');
$t_username_prefix = gpc_get_string('username_prefix', '');
$t_use_ldap_email = gpc_get_int('use_ldap_email', '');
$t_use_ldap_realname = gpc_get_int('use_ldap_realname', '');
$t_autocreate_user = gpc_get_int('autocreate_user', '');

if ($t_action == "get_settings"){
    echo json_encode($mla_tools->get_ldap_config());
}

$mla_tools->save_server_settings();

print_r($_POST);

