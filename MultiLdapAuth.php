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
        $this->page = 'config';

        $this->version = '0.1';
        $this->requires = array(
            'MantisCore' => '2.3.0-dev',
        );

        $this->author = 'Starikov Anton';
        $this->contact = 'starikov_aa@mail.ru';
        $this->url = 'https://github.com/starikov-aa/MultiLdapAuth';
    }

    function schema()
    {
        $t_table_options = array(
            'mysql' => 'DEFAULT CHARSET=utf8',
            'pgsql' => 'WITHOUT OIDS',
        );

        return [
            ['CreateTableSQL',
                [plugin_table('ip_ban'),
                    "id INT NOT NULL AUTOINCREMENT PRIMARY,
                    ip TEXT NOT NULL,
	                attempts TEXT NOT NULL,
	                last_attempt_time INT NOT NULL DEFAULT '1'",
                    $t_table_options
                ]
            ]
        ];
    }

    function config()
    {
        return [
            'ip_ban_enable' => ON,
            'ip_ban_time' => 300,
            'ip_ban_max_failed_attempts' => 5
        ];
    }

    function init()
    {
        plugin_require_api('core/mla_Tools.class.php');
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
            'EVENT_CORE_READY' => 'add_user_id_to_cache',
            'EVENT_LAYOUT_RESOURCES' => 'add_html_headers'
//            'EVENT_MANAGE_USER_UPDATE_FORM' => 'test22',
            // эти события для редактирования пользователей из админки
//            'EVENT_MANAGE_USER_PAGE' => 'test22', // тут будем записывать js код который будет блокировать редактирование имени и почты на странице редактирования пользователей
//            'EVENT_MANAGE_USER_UPDATE' => '', // тут проверять какое имя и почту записали в базу при редактировании юзера, при необходжимости менять.
        );

        return $t_hooks;
    }

    /**
     * @return string
     * @throws Exception
     */
    function add_html_headers()
    {
        $resources = '<link rel="stylesheet" type="text/css" href="' . plugin_file('mla_style.css') . '" />';
        $resources .= '<script type="text/javascript" src="' . plugin_file('mla_script.js') . '"></script>';
        $resources .= $this->add_js_flags();
        return $resources;
    }

    /**
     * @return string
     * @throws Exception
     */
    function add_js_flags()
    {
        if (auth_is_user_authenticated()) {
            $tools = new mla_Tools();
            $t_userid = gpc_get_string('user_id', null) ?? auth_get_current_user_id();
            $t_username = user_get_username($t_userid);
            $ldap_options = $tools->get_ldap_options_from_username($t_username);
            $flags['user_is_local'] = mla_Tools::user_is_local($t_username) ? ON : OFF;
            $flags['use_ldap_email'] = $ldap_options['use_ldap_email'] ?? OFF;
            $flags['use_ldap_realname'] = $ldap_options['use_ldap_realname'] ?? OFF;
            $html = '<script type="text/javascript">window.mla_user_flags=JSON.parse(\'' . json_encode($flags) . '\');</script>';
            return $html;
        }
    }

    function check_user_edit_data()
    {

    }

    /**
     * @throws \Mantis\Exceptions\ClientException
     */
    function add_user_id_to_cache()
    {
        $cond = preg_match("#login(_password)?_page#i", $_SERVER['REQUEST_URI']);
        if ($cond) {
            $t_username = trim(gpc_get_string('username', ''));
            $t_user_id = user_get_id_by_name($t_username);
            if ($t_user_id === false) {
                $GLOBALS['g_cache_user'][0] = [
                    'id' => 0,
                    'username' => $t_username
                ];
            }
        }
    }

    /**
     * @param $p_event_name
     * @param $p_args
     * @return AuthFlags|null
     * @throws Exception
     */
    function auth_user_flags($p_event_name, $p_args)
    {
        # Don't access DB if db_is_connected() is false.

//        print_r($this->get_servers_config('username_postfix', 'corp.lab2.com'));
//        echo config_get( 'user_login_valid_regex' );

        $t_username = empty($p_args['username']) ? trim(gpc_get_string('username', '')) : $p_args['username'];
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

        $mla_AuthApi = new mla_AuthApi(new mla_LdapApi(), new AuthFlags());
        $t_flags = $mla_AuthApi->set_user_auth_flags($t_username);;
        return $t_flags;

    }
}