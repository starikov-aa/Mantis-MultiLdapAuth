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

        $this->version = '0.2';
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
            ],
            ['CreateTableSQL',
                [plugin_table('server_settings'),
                    "id INT NOT NULL AUTOINCREMENT PRIMARY,
                    server TEXT NOT NULL,
	                root_dn TEXT NOT NULL,
	                bind_dn TEXT NOT NULL,
	                bind_passwd TEXT NOT NULL,
	                uid_field TEXT NOT NULL,
	                realname_field TEXT NOT NULL,
	                organization TEXT NOT NULL,
	                network_timeout TEXT NOT NULL,
	                network_timeout INT NOT NULL,
	                protocol_version INT NOT NULL,
	                follow_referrals INT NOT NULL,
	                username_prefix TEXT NOT NULL,
	                use_ldap_email INT NOT NULL,
	                use_ldap_realname INT NOT NULL,
	                autocreate_user INT NOT NULL,
	                default_new_user_project INT NOT NULL",
                    $t_table_options
                ]
            ],
            ['CreateTableSQL',
                [plugin_table('udpp_rules'),
                    "id INT NOT NULL AUTOINCREMENT PRIMARY,
                    project_id INT NOT NULL,
	                department TEXT NOT NULL,
	                domain TEXT NOT NULL,
	                right INT NOT NULL",
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
        plugin_require_api('core/mla_ServerConfig.class.php');
        plugin_require_api('core/mla_UserDistributionPerProjects.class.php');
    }


    /**
     * plugin hooks
     * @return array
     */
    function hooks()
    {
        $t_hooks = array(
            'EVENT_AUTH_USER_FLAGS' => 'auth_user_flags',
            'EVENT_CORE_READY' => 'func_for_event_core_ready',
            'EVENT_LAYOUT_RESOURCES' => 'add_html_headers'
        );

        return $t_hooks;
    }

    /**
     * Add CSS and scripts to pages.
     *
     * @return string
     * @throws Exception
     */
    function add_html_headers()
    {
        // добавляем CSS
        $resources = '<link rel="stylesheet" type="text/css" href="' . plugin_file('mla_style.css') . '" />';

        // добавляем JS функции
        $resources .= '<script type="text/javascript" src="' . plugin_file('mla_script.js') . '"></script>';

        // добавляем на страницу логина, селект с префиксами юзернеймов
        // и удаляем из имени пользвателя префикс
        if (preg_match('/.*\/login_page\.php/i', $_SERVER['SCRIPT_NAME'])) {
            $prefixes = json_encode(array_column(mla_ServerConfig::get_servers_config(), 'username_prefix'));
            $resources .= '<script type="text/javascript">
                        $(function() { 
                            $("#username").val(function(i, v) {
                                return v.replace(/^.*\\\\/, "")
                            })
                            add_select_with_prefixes(\'' . $prefixes . '\')
                        });   
                   </script>';
        }

        $resources .= $this->add_js_flags();

        return $resources;
    }

    /**
     * Generates a list of flags controlling editing Email, RealName.
     * Further JS script will block / unblock fields for editing by these flags.
     *
     * @return string
     * @throws Exception
     */
    function add_js_flags()
    {
        if (auth_is_user_authenticated()) {
            $tools = new mla_Tools();
            $t_userid = gpc_get_string('user_id', null) ?? auth_get_current_user_id();
            $t_username = user_get_username($t_userid);
            if (!mla_Tools::user_is_local($t_username)) {
                $ldap_options = $tools->get_server_config_by_username($t_username);
                $flags['user_is_local'] = mla_Tools::user_is_local($t_username) ? ON : OFF;
                $flags['use_ldap_email'] = (int)$ldap_options['use_ldap_email'] ?? OFF;
                $flags['use_ldap_realname'] = (int)$ldap_options['use_ldap_realname'] ?? OFF;
                $html = '<script type="text/javascript">window.mla_user_flags=JSON.parse(\'' . json_encode($flags) . '\');</script>';
                return $html;
            }
        }
    }

    /**
     * Runs functions when the EVENT_CORE_READY event occurs
     *
     * @throws \Mantis\Exceptions\ClientException
     */
    function func_for_event_core_ready()
    {
        // Функция format_username должна выполняться первая!!!
        // т.к. она формирует логин в нужном формате.
        $this->format_username();
        // $this->add_user_id_to_cache(); // todo remove after fix #0027836
    }

    /**
     * Temporary function which allows the plugin to work.
     * It adds user with ID 0 (not existing user in DB) to cache.
     *
     * todo remove after fix #0027836
     *
     * @throws \Mantis\Exceptions\ClientException
     */
    function add_user_id_to_cache()
    {
        // Only fire on pages:
        // /login_password_page.php
        // /plugin.php?page=MultiLdapAuth/login_password_page
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
     * From the data received from login_page, form the username in the required format.
     * then username is passed to login_password_page.php
     */
    function format_username()
    {
        // Only fire on pages:
        // /login_password_page.php
        // /plugin.php?page=MultiLdapAuth/login_password_page
        if (preg_match("#login(_password)?_page#i", $_SERVER['REQUEST_URI'])) {
            $f_username = gpc_get_string('username', '');
            $f_username_prefix = gpc_get_string('username_prefix', '');
            if (!empty($f_username) && !empty($f_username_prefix)) {
                $_POST['username'] = $f_username_prefix . '\\' . $f_username;
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
        $t_flags = $mla_AuthApi->set_user_auth_flags($t_username);

        return $t_flags;

    }
}