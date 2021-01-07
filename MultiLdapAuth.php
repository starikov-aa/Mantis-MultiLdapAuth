<?php
# Copyright (c) MantisBT Team - mantisbt-dev@lists.sourceforge.net
# Licensed under the MIT license

/**
 * Sample Auth plugin
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

        $this->author = 'MantisBT Team';
        $this->contact = 'mantisbt-dev@lists.sourceforge.net';
        $this->url = 'https://www.mantisbt.org';
    }

    function init()
    {
//        require_api('config_api.php');
//        require_api('constant_inc.php');
//        require_api('logging_api.php');
//        require_api('user_api.php');
//        require_api('utility_api.php');
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
        $t_username = trim(gpc_get_string('username', ''));
        $t_user_id = auth_get_user_id_from_login_name($t_username);
        if ($t_user_id === false) {
            $GLOBALS['g_cache_user'][0] = [
                'id' => 0,
                'username' => $t_username
            ];
        }
    }

    function auth_user_flags($p_event_name, $p_args)
    {
        # Don't access DB if db_is_connected() is false.

        $t_username = empty($p_args['user_id']) ? trim(gpc_get_string('username', '')) : $p_args['user_id'];
        $t_user_id = $p_args['user_id'];

        log_event(LOG_PLUGIN, $t_username . " = " . $t_user_id);

        log_event(LOG_PLUGIN, print_r($GLOBALS['g_cache_user'], true));

        # If anonymous user, don't handle it.
        if (user_is_anonymous($t_user_id)) {
            //return null;
        }

        if ($t_user_id !== 0) {
            $t_access_level = user_get_access_level($t_user_id, ALL_PROJECTS);

            # Have administrators use default login flow
            if ($t_access_level >= ADMINISTRATOR) {
                return null;
            }
        }

        $mla_AuthApi = new mla_AuthApi(new mla_LdapApi(config_get('mla_ldap')), new AuthFlags());
        $t_flags = $mla_AuthApi->set_user_auth_flags($t_username);;
        return $t_flags;

    }
}