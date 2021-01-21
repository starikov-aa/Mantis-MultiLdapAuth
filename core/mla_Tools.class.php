<?php
/**
 * Plugin for authorization in MantisBT on multiple LDAP servers
 * Copyright (C) 2021  Starikov Anton - starikov_aa@mail.ru
 * https://github.com/starikov-aa/MultiLdapAuth
 */

class mla_Tools
{
    /**
     *
     */
    public $opt_ldap_config = 'ldap_config';

    /**
     * The LDAP config parameters
     * @var string|null
     */
    public $ldap_config = null;

    /**
     * mla_Tools constructor.
     * @throws Exception
     */
    function __construct()
    {
        $t_config = plugin_config_get($this->opt_ldap_config);

        if (@array_key_exists('server', $t_config[0])) {
            $this->ldap_config = $t_config;
        } else {
            $msg = 'Incorrect LDAP settings. Check the "' . $this->opt_ldap_config . '" plugin option.';
            log_event(LOG_LDAP, $msg);
            //throw new Exception($msg);
        }
    }

    /**
     * @param null $find_by
     * @param null $find_value
     * @param bool $return_index
     * @return bool|string|null
     */
    function get_ldap_config($find_by = null, $find_value = null, $return_index = false)
    {
        if (is_null($this->ldap_config)) {
            return false;
        }

        if (!is_null($find_by) && !is_null($find_value)) {
            $server_item = array_search($find_value, array_column($this->ldap_config, $find_by));
            if ($server_item !== false) {
                return $return_index ? $server_item : $this->ldap_config[$server_item];
            }
        } else {
            return $this->ldap_config;
        }
        return false;
    }

    /**
     * Check user is local
     *
     * @param $p_username
     * @return bool
     */
    public static function user_is_local($p_username)
    {
        if (stristr($p_username, '\\') === false) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * Get login and prefix from username.
     *
     * @param string $p_username
     * @return array|bool
     */
    public static function get_prefix_and_login_from_username($p_username)
    {
        $t_username = explode('\\', $p_username);
        if (count($t_username) == 2) {
            return [
                'prefix' => $t_username[0],
                'username' => $t_username[1]
            ];
        } else {
            return false;
        }
    }

    /**
     * @param $p_username
     * @return bool
     */
    function get_ldap_options_from_username($p_username)
    {
        $t_user_param = self::get_prefix_and_login_from_username($p_username);

        $servers_option = $this->get_ldap_config('username_prefix', $t_user_param['prefix']);
        if ($servers_option) {
            return $servers_option;
        } else {
            return false;
        }
    }

    /**
     * @return mixed
     */
    static function get_user_ip()
    {
        return filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
    }

    static function convert_on_to_checked($value)
    {
        return $value == 1 ? 'checked' : '';
    }

    function save_server_settings()
    {
        $result = [];
        $allow_fields = [
            'id',
            'server',
            'root_dn',
            'bind_dn',
            'bind_passwd',
            'uid_field',
            'realname_field',
            'network_timeout',
            'network_timeout',
            'protocol_version',
            'follow_referrals',
            'username_prefix',
            'use_ldap_email',
            'use_ldap_realname',
            'autocreate_user',
            'default_new_user_project'
        ];

        foreach ($_POST as $k => $v) {
            if (in_array($k, $allow_fields)) {
                $result[$k] = $v;
            }
        }

        $conf_id = $this->get_ldap_config('username_prefix', $_POST['username_prefix'], true);

        if (is_int($conf_id)) {
            $this->ldap_config[$conf_id] = $result;
        } else {
            $this->ldap_config[] = $result;
        }

        plugin_config_set('ldap_config', $this->ldap_config);
    }

    static function update_general_settings($settings_list)
    {
       $settings_list = self::validate_general_settings($settings_list);
       array_walk($settings_list, function ($val, $key){
           plugin_config_set($key, $val);
       });
    }

    static function validate_general_settings($settings_list)
    {
        $args = [
            'ip_ban_enable' => FILTER_SANITIZE_NUMBER_INT,
            'ip_ban_max_failed_attempts' => FILTER_SANITIZE_NUMBER_INT,
            'ip_ban_time' => FILTER_SANITIZE_NUMBER_INT
        ];

        return filter_input_array(INPUT_POST, $args);
    }


}