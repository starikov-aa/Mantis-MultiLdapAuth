<?php
/**
 * Plugin for authorization in MantisBT on multiple LDAP servers
 * Copyright (C) 2021 Starikov Anton - starikov_aa@mail.ru
 * https://github.com/starikov-aa/MultiLdapAuth
 */

auth_reauthenticate();
access_ensure_global_level(config_get('manage_site_threshold'));

const AJAX_STATUS_OK = 'ok';
const AJAX_STATUS_ERROR = 'error';

$t_id = gpc_get('id', '');
$t_action = gpc_get_string('action', '');

$post_data = $_POST;

if ($t_action == "get_server_config") {
    echo json_encode(mla_ServerConfig::get_servers_config());
}

if ($t_action == "add_server" || $t_action == "edit_server") {
    try {
        $post_data = mla_ServerConfig::validate_config_option($post_data);
    } catch (Exception $e) {
        mla_ajax_response(AJAX_STATUS_ERROR, null, $e->getMessage());
        exit();
    }
}

if ($t_action == "add_server") {
    if (mla_ServerConfig::add_server_settings($post_data)) {
        mla_ajax_response(AJAX_STATUS_OK);
    }
}

if ($t_action == "edit_server") {
    if (mla_ServerConfig::update_server_settings($t_id, $post_data)) {
        mla_ajax_response(AJAX_STATUS_OK);
    }
}

if ($t_action == "delete_server") {
    if (mla_ajax_response(mla_ServerConfig::delete_server_settings($t_id))) {
        mla_ajax_response(AJAX_STATUS_OK);
    }
}

if ($t_action == "update_general_settings") {
    if (mla_Tools::update_general_settings($post_data)) {
        mla_ajax_response(AJAX_STATUS_OK);
    }
}

if ($t_action == "update_rules_udpp") {
    $mla_udpp = new mla_UserDistributionPerProjects();
    if ($mla_udpp->processing($post_data)) {
        mla_ajax_response(AJAX_STATUS_OK);
    }
}

if ($t_action == "delete_rule_udpp") {
    if (mla_UserDistributionPerProjects::delete_rule(gpc_get_int('id'))) {
        mla_ajax_response(AJAX_STATUS_OK);
    } else {
        mla_ajax_response(AJAX_STATUS_ERROR);
    }
}

if ($t_action == "get_rule_udpp") {
    $mla_udpp_rules = mla_UserDistributionPerProjects::get_rules();
    if (is_array($mla_udpp_rules)) {
        mla_ajax_response(AJAX_STATUS_OK, $mla_udpp_rules);
    } else {
        mla_ajax_response(AJAX_STATUS_ERROR);
    }
}

function mla_ajax_response($status, $data = null, $msg = null)
{
    echo json_encode([
        'status' => $status, // ok / error
        'response' => $data,
        'msg' => $msg
    ]);
}