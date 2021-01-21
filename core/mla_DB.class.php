<?php


class mla_DB
{
    const SERVER_SETTINGS_TABLE = 'server_settings';

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
        $config_options = self::validate_config_option($config_options);
        $fields = join(",", array_keys($config_options));
        $values = join(",", array_values($config_options));

        db_param_push();
        $query = "INSERT INTO " . $tbl_name . " (" . $fields . ") VALUES (" . $values . ");";
        db_query($query);

    }

    static function update_server_settings($server_id, $config_options)
    {
        $tbl_name = plugin_table(self::SERVER_SETTINGS_TABLE);
        $config_options = self::validate_config_option($config_options);

        array_walk($config_options, function (&$v, $k) {
            $v = $k . "='" . $v . "'";
        });

        $query = "UPDATE " . $tbl_name . " SET " . join(",", $config_options) . " WHERE id=" . db_param();

        db_query($query, [$server_id]);
    }

    static function delete_server_settings($server_id)
    {
        $tbl_name = plugin_table(self::SERVER_SETTINGS_TABLE);
        db_query("DELETE FROM " . $tbl_name . " WHERE id=" . db_param(), [$server_id]);
    }

    static function get_server_settings_by_config_option($option_name, $option_value)
    {

    }

    static function validate_config_option($options_list)
    {
        $all_options = [
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

        foreach ($all_options as $item) {
            if (!array_key_exists($item, $options_list)){
                $options_list[$item] = null;
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
            'filter' => FILTER_VALIDATE_INT,
            'options' => ['default' => 0]
        ];

        $args = [
            'server' => [
                'filter' => FILTER_VALIDATE_REGEXP,
                'options' => ['regexp' => '/^ldap[s]?:\/\/[a-z0-9\.-]+$/i']
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
            'default_new_user_project' => $int_filter
        ];

        return filter_var_array($options_list, $args, true);
    }


}