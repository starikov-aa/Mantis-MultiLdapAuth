<?php


/**
 * Class mla_ServerConfig
 */
class mla_ServerConfig
{
    /**
     *
     */
    const SERVER_SETTINGS_TABLE = 'server_settings';

    const OPTIONS_LIST = [
        'server' => ['requires' => true, 'default'],
        'root_dn' => ['requires' => true, 'default'],
        'bind_dn' => ['requires' => true, 'default'],
        'bind_passwd' => ['requires' => true, 'default'],
        'uid_field' => ['requires' => false, 'default' => 'sAMAccountName'],
        'realname_field' => ['requires' => false, 'default' => 'CN'],
        'network_timeout' => ['requires' => false, 'default' => 5],
        'protocol_version' => ['requires' => false, 'default' => 3],
        'follow_referrals' => ['requires' => false, 'default' => 0],
        'username_prefix' => ['requires' => true, 'default'],
        'use_ldap_email' => ['requires' => false, 'default' => 0],
        'use_ldap_realname' => ['requires' => false, 'default' => 0],
        'autocreate_user' => ['requires' => false, 'default' => 1],
        'default_new_user_project' => ['requires' => false, 'default' => 0],
        'use_starttls' => ['requires' => false, 'default' => 0],
        'tls_protocol_min' => ['requires' => false, 'default' => LDAP_OPT_X_TLS_PROTOCOL_TLS1_2]
    ];

    /**
     * Get server settings
     *
     * @return false|array Return array with servers setting, else false
     */
    static function get_servers_config()
    {
        $result = false;
        $tbl_name = plugin_table(self::SERVER_SETTINGS_TABLE);
        $query_select = db_query('SELECT * FROM ' . $tbl_name);
        while ($row = db_fetch_array($query_select)) {
            $result[] = $row;
        }

        return $result;
    }

    static function add_server_settings($config_options)
    {
        $tbl_name = plugin_table(self::SERVER_SETTINGS_TABLE);

        // проверяем нет ли совпадений
        if (self::get_server_settings_by_config_option('username_prefix', $config_options['username_prefix']) !== false) {
            return false;
        }

        array_walk($config_options, function (&$v) {
            $v = "'" . $v . "'";
        });

        $fields = join(",", array_keys($config_options));
        $values = join(",", array_values($config_options));

        db_param_push();
        $query = "INSERT INTO " . $tbl_name . " (" . $fields . ") VALUES (" . $values . ");";
        db_query($query);

        return true;

    }

    static function update_server_settings($server_id, $config_options)
    {

        // проверяем нет ли совпадений
        $config = self::get_server_settings_by_config_option('username_prefix', $config_options['username_prefix']);
        if ($config !== false && $config['id'] != $server_id) {
            return false;
        }

        $tbl_name = plugin_table(self::SERVER_SETTINGS_TABLE);

        array_walk($config_options, function (&$v, $k) {
            $v = $k . "='" . $v . "'";
        });

        $query = "UPDATE " . $tbl_name . " SET " . join(",", $config_options) . " WHERE id=" . db_param();

        db_query($query, [$server_id]);

        return true;
    }

    static function delete_server_settings($server_id)
    {
        $tbl_name = plugin_table(self::SERVER_SETTINGS_TABLE);
        db_query("DELETE FROM " . $tbl_name . " WHERE id=" . db_param(), [$server_id]);
    }

    /**
     * Возвращает массив с настройками сервера найденый по заданой опции и ее значению
     *
     * @param string $find_by имя опции
     * @param mixed $find_value значении опции
     * @return array|bool
     */
    static function get_server_settings_by_config_option(string $find_by, $find_value)
    {
        $config = self::get_servers_config();

        if (!$config) return false;

        if (!is_null($find_by) && !is_null($find_value)) {
            $server_item = array_search($find_value, array_column($config, $find_by));
            if ($server_item !== false) {
                return $config[$server_item];
            }
        }

        return false;
    }

    /**
     * @param array $options_list
     * @return mixed
     */
    static function validate_config_option(array $options_list)
    {
        foreach (self::OPTIONS_LIST as $opt_name => $item) {
            if (!array_key_exists($opt_name, $options_list)) {
                $options_list[$opt_name] = null;
            }
        }

        $base_regexp = [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => ['regexp' => "/^[a-z0-9\.,-=]+$/i"]
        ];

        $password_regexp = [
            'filter' => FILTER_VALIDATE_REGEXP,
            'options' => ['regexp' => "/^[a-z0-9\.-_*$@!]+$/i"]
        ];

        $int_filter = [
            'filter' => FILTER_VALIDATE_INT
        ];

        $args = [
            'server' => [
                'filter' => FILTER_VALIDATE_REGEXP,
                'options' => ['regexp' => '/^ldap[s]?:\/\/[a-z0-9\.\-:]+$/i']
            ],
            'bind_passwd' => $password_regexp,
            'root_dn' => $base_regexp,
            'bind_dn' => $base_regexp,
            'uid_field' => $base_regexp,
            'realname_field' => $base_regexp,
            'username_prefix' => $base_regexp,
            'network_timeout' => $int_filter,
            'protocol_version' => $int_filter,
            'follow_referrals' => $int_filter,
            'use_ldap_email' => $int_filter,
            'use_ldap_realname' => $int_filter,
            'autocreate_user' => $int_filter,
            'default_new_user_project' => $int_filter,
            'use_starttls' => $int_filter,
            'tls_protocol_min' => FILTER_VALIDATE_FLOAT
        ];

        $options_list = filter_var_array($options_list, $args, true);
        $error = [];

        foreach ($options_list as $oname => $oval) {
            $def = self::OPTIONS_LIST[$oname];
            if ($def['requires']) {
                if (empty($oval)) {
                    $error[] = $oname;
                }
            } else {
                $options_list[$oname] = empty($oval) ? $def['default'] : $oval;
            }
        }

        if (count($error)) {
            throw new Exception('Invalid values in the following parameters: ' . join(', ', $error));
        } else {
            return $options_list;
        }

    }


}