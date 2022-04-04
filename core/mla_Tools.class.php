<?php
/**
 * Plugin for authorization in MantisBT on multiple LDAP servers
 * Copyright (C) 2021  Starikov Anton - starikov_aa@mail.ru
 * https://github.com/starikov-aa/MultiLdapAuth
 */

class mla_Tools
{
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
     * Получает настройки сервера по префиксу пользователя
     *
     * @param $p_username
     * @return bool|array Массив с настройками или false при неудаче
     */
    static function get_server_config_by_username($p_username)
    {
        $t_user_param = self::get_prefix_and_login_from_username($p_username);
        $config = mla_ServerConfig::get_server_settings_by_config_option('username_prefix', $t_user_param['prefix']);
        if ($config) {
            return $config;
        } else {
            return false;
        }
    }

    /**
     * Get IP
     *
     * @return mixed
     */
    static function get_user_ip()
    {
        return filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
    }

    /**
     * Converts 1 to "Checked" for checkbox
     *
     * @param int $value
     * @return string
     */
    static function convert_on_to_checked($value)
    {
        return $value == 1 ? 'checked' : '';
    }

    /**
     * Updates the value of the settings. If the option is missing, it will be created.
     *
     * @param array $settings_list associative array with settings
     * @return bool
     */
    static function update_general_settings($settings_list)
    {
        $settings_list = self::validate_general_settings($settings_list);
        array_walk($settings_list, function ($val, $key) {
            plugin_config_set($key, $val);
        });

        return true;
    }

    /**
     * Checks the value of the settings for correctness
     *
     * @param array $settings_list associative array with settings
     * @return mixed
     */
    static function validate_general_settings($settings_list)
    {

        $all_options = [
            'ip_ban_enable',
            'ip_ban_max_failed_attempts',
            'ip_ban_time'
        ];

        foreach ($all_options as $item) {
            if (!array_key_exists($item, $settings_list)) {
                $settings_list[$item] = null;
            }
        }

        $args = [
            'ip_ban_enable' => FILTER_SANITIZE_NUMBER_INT,
            'ip_ban_max_failed_attempts' => FILTER_SANITIZE_NUMBER_INT,
            'ip_ban_time' => FILTER_SANITIZE_NUMBER_INT
        ];

        return filter_var_array($settings_list, $args);
    }

    /**
     * Generate list with users rights
     *
     * @return array
     */
    static function get_rights_list(): array
    {
        $rights_list = [];
        $t_enum_values = MantisEnum::getValues(config_get('access_levels_enum_string'));

        foreach ($t_enum_values as $t_enum_value) {
            $rights_list[$t_enum_value] = get_enum_element('access_levels', $t_enum_value);
        }

        return $rights_list;
    }

    /**
     * Generate options for select
     *
     * @param $data array [key => val]
     * @param null $selected_value if this value is exists in the data keys, then it will be marked to as selected
     * @return string return HTML for option tags
     */
    static function gen_select_options($data, $selected_value = null)
    {
        $options = "";
        foreach ($data as $key => $value) {
            $selected = $selected_value == $key ? "selected" : "";
            $options .= "<option ".$selected." value='" . $key . "'>" . $value . "</option>";
        }

        return $options;
    }
}