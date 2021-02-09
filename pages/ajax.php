<?php
/**
 * Plugin for authorization in MantisBT on multiple LDAP servers
 * Copyright (C) 2021  Starikov Anton - starikov_aa@mail.ru
 * https://github.com/starikov-aa/MultiLdapAuth
 */

auth_reauthenticate();
access_ensure_global_level(config_get('manage_site_threshold'));

$t_id = gpc_get_int('id', '');
$t_action = gpc_get_string('action', '');

$post_data = $_POST;

if ($t_action == "get_server_config") {
    echo json_encode(mla_ServerConfig::get_servers_config());
}

if ($t_action == "add_server" || $t_action == "edit_server") {
    try {
        $post_data = mla_ServerConfig::validate_config_option($post_data);
    } catch (Exception $e) {
        ajax_response('', $e->getMessage());
        exit();
    }
}

if ($t_action == "add_server") {
    mla_ServerConfig::add_server_settings($post_data);
}

if ($t_action == "edit_server") {
    mla_ServerConfig::update_server_settings($t_id, $post_data);
}

if ($t_action == "delete_server") {
    ajax_response(mla_ServerConfig::delete_server_settings($t_id));
}

if ($t_action == "update_general_settings") {
    if (mla_Tools::update_general_settings($post_data)){
        ajax_response('ok');
    }
}

function ajax_response($msg = '', $err_msg = '')
{
    echo json_encode([
        'result' => $msg,
        'error' => $err_msg
    ]);
}