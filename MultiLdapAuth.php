<?php
/**
 * Plugin for authorization in MantisBT on multiple LDAP servers
 * Copyright (C) 2021  Starikov Anton - starikov_aa@mail.ru
 * https://github.com/starikov-aa/MultiLdapAuth
 */

class MultiLdapAuthPlugin extends MantisPlugin
{
    /**
     * A method that populates the plugin information and minimum requirements.
     * @return void
     */
    function register()
    {
        $this->name = plugin_lang_get('title');
        $this->description = plugin_lang_get('description');
        $this->page = '';

        $this->version = '0.1';
        $this->requires = array(
            'MantisCore' => '2.3.0-dev',
        );

        $this->author = 'Starikov Anton';
        $this->contact = 'starikov_aa@mail.ru';
        $this->url = 'https://github.com/starikov-aa/MultiLdapAuth';
    }

    function init()
    {
        plugin_require_api('core/mla_AuthApi.class.php');
        plugin_require_api('core/mla_LdapApi.class.php');
    }



    /**
     * plugin hooks
     * @return array
     */
    function hooks()
    {
        $t_hooks = array(
            'EVENT_AUTH_USER_FLAGS' => 'auth_user_flags',
            'EVENT_CORE_READY' => 'add_user_id_to_cache'
//            'EVENT_MANAGE_USER_UPDATE_FORM' => 'test22',
            // эти события для редактирования пользователей из админки
//            'EVENT_MANAGE_USER_PAGE' => 'test22', // тут будем записывать js код который будет блокировать редактирование имени и почты на странице редактирования пользователей
//            'EVENT_MANAGE_USER_UPDATE' => '', // тут проверять какое имя и почту записали в базу при редактировании юзера, при необходжимости менять.
        );

        return $t_hooks;
    }

    function add_user_id_to_cache()
    {
        //todo нужно сделать какую нить проверку. Т.к. в таком варианте ломается стандартная регистрация новых пользователей!
        $t_username = trim(gpc_get_string('username', ''));
        $t_user_id = user_get_id_by_name($t_username);
        if ($t_user_id === false) {
            $GLOBALS['g_cache_user'][0] = [
                'id' => 0,
                'username' => $t_username
           ];
        }
    }

    function get_servers_config($find_by = null, $find_value = null){

        $config = plugin_config_get('servers_config');

        if (is_null($config)){
            return false;
        }

        if (!is_null($find_by) && !is_null($find_value)){
            if ($server_item = array_search($find_value, array_column($config, $find_by)) !== false){
                return $config[$server_item];
            }
        } else {
            return $config;
        }
        return false;
    }

    function auth_user_flags($p_event_name, $p_args)
    {
        # Don't access DB if db_is_connected() is false.

//        print_r($this->get_servers_config('username_postfix', 'corp.lab2.com'));
        echo config_get( 'user_login_valid_regex' );

        $t_username = empty($p_args['user_id']) ? trim(gpc_get_string('username', '')) : $p_args['user_id'];
        $t_user_id = $p_args['user_id'];

        log_event(LOG_PLUGIN, $t_username . " = " . $t_user_id);
        log_event(LOG_PLUGIN, print_r($GLOBALS['g_cache_user'], true));

        # If anonymous user, don't handle it.
        if (user_is_anonymous($t_user_id)) {
            return null;
        }

        if ($t_user_id !== 0) {
            if (stristr($t_username, "Administrator") !== false &&
                user_get_access_level($t_user_id, ALL_PROJECTS) >= ADMINISTRATOR) {
                return null;
            }
        }

        $config = plugin_config_get('servers_config');
        $mla_AuthApi = new mla_AuthApi(new mla_LdapApi($config), new AuthFlags());
        $t_flags = $mla_AuthApi->set_user_auth_flags($t_username);;
        return $t_flags;

    }
}