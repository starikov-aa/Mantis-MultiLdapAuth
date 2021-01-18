<?php
/**
 * Plugin for authorization in MantisBT on multiple LDAP servers
 * Copyright (C) 2021  Starikov Anton - starikov_aa@mail.ru
 * https://github.com/starikov-aa/MultiLdapAuth
 */

auth_reauthenticate();
access_ensure_global_level( config_get( 'manage_site_threshold' ) );

$mla_tools = new mla_Tools();

$t_action = gpc_get_string('action');
$t_server = gpc_get_string('server', '');

if ($t_action == "get_settings"){
    echo json_encode($mla_tools->get_ldap_config());
}