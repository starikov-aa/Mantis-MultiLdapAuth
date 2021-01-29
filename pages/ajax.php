<?php
/**
 * Plugin for authorization in MantisBT on multiple LDAP servers
 * Copyright (C) 2021 Starikov Anton - starikov_aa@mail.ru
 * https://github.com/starikov-aa/MultiLdapAuth
 */

auth_reauthenticate();
access_ensure_global_level(config_get('manage_site_threshold'));

$t_id = gpc_get_int('id', '');
$t_action = gpc_get_string('action', '');

if ($t_action == "get_server_config") {
    echo json_encode(mla_ServerConfig::get_servers_config());
}

if ($t_action == "add_server") {
    ajax_response(mla_ServerConfig::add_server_settings($_POST));
}

if ($t_action == "edit_server") {
    ajax_response(mla_ServerConfig::update_server_settings($t_id, $_POST));
}

if ($t_action == "delete_server") {
    ajax_response(mla_ServerConfig::delete_server_settings($t_id));
}

if ($t_action == "update_general_settings") {
    ajax_response(mla_Tools::update_general_settings($_POST));
}

function ajax_response($data)
{
    echo '{"result":' . $data . '}';
}