<?php
/**
 *  Plugin for authorization in MantisBT on multiple LDAP servers
 *  Copyright (C) 2021  Starikov Anton - starikov_aa@mail.ru
 *  https://github.com/starikov-aa/Mantis-MultiLdapAuth
 */

class mla_LdapApi
{
    /**
     * LDAP data source
     * @var null
     */
    public $t_ds = null;

    /**
     * The LDAP config parameters
     * @var null
     */
    private $ldap_config = null;

    /**
     * mla_LdapApi constructor.
     */
    public function __construct()
    {
        $this->ldap_config = mla_ServerConfig::get_servers_config();

        if (!$this->ldap_config) {
            $msg = 'Incorrect LDAP settings. Check the "' . $this->opt_ldap_config . '" plugin option.';
            log_event(LOG_LDAP, $msg);
        }
    }

    /**
     * Logs the most recent LDAP error
     * @param resource $this ->p_ds LDAP resource identifier returned by ldap_connect.
     * @return void
     */
    function log_error()
    {
        log_event(LOG_LDAP, 'ERROR #' . ldap_errno($this->t_ds) . ': ' . ldap_error($this->t_ds));
    }


    /**
     * Connect and bind to the LDAP directory
     * @param string $p_binddn DN to use for LDAP bind.
     * @param string $p_password Password to use for LDAP bind.
     * @return resource|false
     */
    function connect_bind($p_binddn, $p_password, $t_ldap_server,
                          $t_network_timeout, $t_protocol_version, $t_follow_referrals)
    {
        if (!extension_loaded('ldap')) {
            log_event(LOG_LDAP, 'Error: LDAP extension missing in php');
            trigger_error(ERROR_LDAP_EXTENSION_NOT_LOADED, ERROR);
        }

        log_event(LOG_LDAP, 'Attempting connection to LDAP server/URI \'' . $t_ldap_server . '\'.');

        $this->t_ds = @ldap_connect($t_ldap_server);

        if ($this->t_ds !== false && $this->t_ds > 0) {
            log_event(LOG_LDAP, 'Connection accepted by LDAP server');

            if ($t_network_timeout > 0) {
                log_event(LOG_LDAP, "Setting LDAP network timeout to " . $t_network_timeout);
                $t_result = @ldap_set_option($this->t_ds, LDAP_OPT_NETWORK_TIMEOUT, $t_network_timeout);
                if (!$t_result) {
                    $this->log_error();
                }
            }

            if ($t_protocol_version > 0) {
                log_event(LOG_LDAP, 'Setting LDAP protocol version to ' . $t_protocol_version);
                $t_result = @ldap_set_option($this->t_ds, LDAP_OPT_PROTOCOL_VERSION, $t_protocol_version);
                if (!$t_result) {
                    $this->log_error();
                }
            }

            # Set referrals flag.
            $t_result = @ldap_set_option($this->t_ds, LDAP_OPT_REFERRALS, $t_follow_referrals);
            if (!$t_result) {
                $this->log_error();
            }

            # If no Bind DN and Password is set, attempt to login as the configured
            #  Bind DN.
            if (!is_blank($p_binddn) && !is_blank($p_password)) {
                log_event(LOG_LDAP, 'Attempting bind to ldap server with username and password');
                $t_br = @ldap_bind($this->t_ds, $p_binddn, $p_password);
            } else {
                # Either the Bind DN or the Password are empty, so attempt an anonymous bind.
                log_event(LOG_LDAP, 'Attempting anonymous bind to ldap server');
                $t_br = @ldap_bind($this->t_ds);
            }

            if (!$t_br) {
                $this->log_error();
                log_event(LOG_LDAP, 'Bind to ldap server failed');
                trigger_error(ERROR_LDAP_SERVER_CONNECT_FAILED, ERROR);
            } else {
                log_event(LOG_LDAP, 'Bind to ldap server successful');
            }
        } else {
            log_event(LOG_LDAP, 'Connection to ldap server failed');
            trigger_error(ERROR_LDAP_SERVER_CONNECT_FAILED, ERROR);
        }

        //todo Понять для чего это
        if ($this->t_ds === false) {
            $this->log_error();
            trigger_error(ERROR_LDAP_AUTH_FAILED, ERROR);
        }

        return $this->t_ds;
    }


    /**
     * Escapes the LDAP string to disallow injection.
     *
     * @param string $p_string The string to escape.
     * @return string The escaped string.
     */
    function escape_string($p_string)
    {
        $t_find = array('\\', '*', '(', ')', '/', "\x00");
        $t_replace = array('\5c', '\2a', '\28', '\29', '\2f', '\00');

        $t_string = str_replace($t_find, $t_replace, $p_string);

        return $t_string;
    }


    /**
     * Attempt to authenticate the user against the LDAP directory
     * return true on successful authentication, false otherwise
     * @param integer $p_user_id A valid user identifier.
     * @param string $p_password A password to test against the user user.
     * @return boolean
     */
    function authenticate($p_user_id, $p_password)
    {
        # if password is empty and ldap allows anonymous login, then
        # the user will be able to login, hence, we need to check
        # for this special case.
        if (is_blank($p_password)) {
            return false;
        }

        $t_username = user_get_field($p_user_id, 'username');

        return $this->authenticate_by_username($t_username, $p_password);
    }


    /**
     * Authenticates an user via LDAP given the username and password.
     *
     * @param string $p_username The user name.
     * @param string $p_password The password.
     * @return true: authenticated, false: failed to authenticate.
     */
    function authenticate_by_username($p_username, $p_password)
    {

        if (!$t_username_param = mla_Tools::get_prefix_and_login_from_username($p_username)) {
            log_event(LOG_LDAP, 'Error getting parameters from username (' . $p_username . ')');
            return null;
        }

        $t_authenticated = false;

        if (!is_null($this->ldap_config)) {

            foreach ($this->ldap_config as $config) {

                if ($config['username_prefix'] != $t_username_param['prefix']) {
                    continue;
                }

                $t_ldap_organization = $config['organization'];
                $t_ldap_root_dn = $config['root_dn'];
                $t_ldap_uid_field = $config['uid_field'];
                $t_ldap_server = $config['server'];
                $t_network_timeout = $config['network_timeout'];
                $t_protocol_version = $config['protocol_version'];
                $t_follow_referrals = ON == $config['follow_referrals'];
                $p_binddn = $config['bind_dn'];
                $p_bind_password = $config['bind_passwd'];

                $t_authenticated = $this->lookup($t_ldap_organization, $t_ldap_root_dn,
                    $t_ldap_uid_field, $t_ldap_server, $t_network_timeout,
                    $t_protocol_version, $t_follow_referrals,
                    $p_binddn, $p_bind_password, $t_username_param['username'], $p_password);

                if ($t_authenticated == true) {
                    break;
                }
            }

        }


        # If user authenticated successfully then update the local DB with information
        # from LDAP.  This will allow us to use the local data after login without
        # having to go back to LDAP.  This will also allow fallback to DB if LDAP is down.
        if ($t_authenticated) {
            $t_user_id = user_get_id_by_name($p_username);

            if (false !== $t_user_id && $t_user_id !== 0) {

                $t_fields_to_update = array('password' => md5($p_password));

                if (ON == mla_Tools::get_server_config_by_username($p_username)['use_ldap_realname']) {
                    $t_fields_to_update['realname'] = $this->realname($t_user_id);
                }

                if (ON == mla_Tools::get_server_config_by_username($p_username)['use_ldap_email']) {
                    $t_fields_to_update['email'] = $this->email_from_username($p_username);
                }

                user_set_fields($t_user_id, $t_fields_to_update);
            }
            log_event(LOG_LDAP, 'User \'' . $p_username . '\' authenticated');
        } else {
            log_event(LOG_LDAP, 'Authentication failed');
        }

        return $t_authenticated;
    }


    /**
     * Lookup on one LDAP server
     *
     */

    function lookup($t_ldap_organization, $t_ldap_root_dn,
                    $t_ldap_uid_field, $t_ldap_server, $t_network_timeout,
                    $t_protocol_version, $t_follow_referrals,
                    $p_binddn, $p_bind_password, $p_username, $p_password)
    {

        $c_username = $this->escape_string($p_username);

        $t_search_filter = '(&' . $t_ldap_organization . '(' . $t_ldap_uid_field . '=' . $c_username . '))';
        $t_search_attrs = array(
            $t_ldap_uid_field,
            'dn',
        );

        # Bind
        log_event(LOG_LDAP, 'Binding to LDAP server:' . $t_ldap_server);
        $this->t_ds = $this->connect_bind($p_binddn, $p_bind_password, $t_ldap_server,
            $t_network_timeout, $t_protocol_version, $t_follow_referrals);
        if ($this->t_ds === false) {
            return false;
        }

        # Search for the user id
        log_event(LOG_LDAP, 'Searching for ' . $t_search_filter);
        $t_sr = ldap_search($this->t_ds, $t_ldap_root_dn, $t_search_filter, $t_search_attrs);
        if ($t_sr === false) {
            $this->log_error();
            ldap_unbind($this->t_ds);
            log_event(LOG_LDAP, "Search '$t_search_filter' failed");
            trigger_error(ERROR_LDAP_AUTH_FAILED, ERROR);
        }

        $t_info = @ldap_get_entries($this->t_ds, $t_sr);
        if ($t_info === false) {
            $this->log_error();
            ldap_free_result($t_sr);
            ldap_unbind($this->t_ds);
            trigger_error(ERROR_LDAP_AUTH_FAILED, ERROR);
        }

        $t_authenticated = false;

        if ($t_info['count'] > 0) {
            # Try to authenticate to each until we get a match
            for ($i = 0; $i < $t_info['count']; $i++) {
                $t_dn = $t_info[$i]['dn'];
                log_event(LOG_LDAP, 'Checking ' . $t_info[$i]['dn']);

                # Attempt to bind with the DN and password
                if (@ldap_bind($this->t_ds, $t_dn, $p_password)) {
                    $t_authenticated = true;
                    break;
                }
            }
        } else {
            log_event(LOG_LDAP, 'No matching entries found');
        }

        //log_event( LOG_LDAP, 'Unbinding from LDAP server' );
        //ldap_free_result( $t_sr );
        //ldap_unbind( $this->t_ds );

        return $t_authenticated;
    }

    /**
     * Gets the value of a specific field from LDAP given the user name
     * and LDAP field name.
     *
     * @param string $p_username The user name.
     * @param string $p_field The LDAP field name.
     * @return string The field value or null if not found.
     * @todo Implement logging to LDAP queries same way like DB queries.
     *
     * @todo Implement caching by retrieving all needed information in one query.
     * @todo Сделать кэширование
     */
    function get_field_from_username($p_username, $p_field)
    {
        //todo Может замутить другую проверку??
        if (is_null($this->ldap_config)) {
            return null;
        }

        if (!$t_username_param = mla_Tools::get_prefix_and_login_from_username($p_username)) {
            log_event(LOG_LDAP, 'Error getting parameters from username (' . $p_username . ') when getting field: ' . $p_field);
            return null;
        }

        $t_fieldValue = null;

        foreach ($this->ldap_config as $config) {

            if ($config['username_prefix'] != $t_username_param['prefix']) {
                continue;
            }

            $t_ldap_organization = $config['organization'];
            $t_ldap_root_dn = $config['root_dn'];
            $t_ldap_uid_field = $config['uid_field'];
            $t_ldap_server = $config['server'];
            $t_network_timeout = $config['network_timeout'];
            $t_protocol_version = $config['protocol_version'];
            $t_follow_referrals = ON == $config['follow_referrals'];
            $p_binddn = $config['bind_dn'];
            $p_bind_password = $config['bind_passwd'];

            $t_fieldValue = $this->search($t_ldap_organization, $t_ldap_root_dn,
                $t_ldap_uid_field, $t_ldap_server, $t_network_timeout,
                $t_protocol_version, $t_follow_referrals,
                $p_binddn, $p_bind_password, $t_username_param['username'], $p_field);

            if ($t_fieldValue !== null) {
                break;
            }
        }

        return $t_fieldValue;
    }


    /**
     * Search for a value in one LDAP server
     *
     * @param $t_ldap_organization
     * @param $t_ldap_root_dn
     * @param $t_ldap_uid_field
     * @param $t_ldap_server
     * @param $t_network_timeout
     * @param $t_protocol_version
     * @param $t_follow_referrals
     * @param $p_binddn
     * @param $p_bind_password
     * @param $p_username
     * @param $p_field
     * @return |null
     */

    private function search($t_ldap_organization, $t_ldap_root_dn,
                            $t_ldap_uid_field, $t_ldap_server, $t_network_timeout,
                            $t_protocol_version, $t_follow_referrals,
                            $p_binddn, $p_bind_password, $p_username, $p_field)
    {

        log_event(LOG_LDAP, 'Binding to LDAP server' . $t_ldap_server);
        $c_username = $this->escape_string($p_username);

        log_event(LOG_LDAP, 'Retrieving field \'' . $p_field . '\' for \'' . $p_username . '\'');

        # Bind
        $t_ds = @$this->connect_bind($p_binddn, $p_bind_password, $t_ldap_server,
            $t_network_timeout, $t_protocol_version, $t_follow_referrals);
        if ($t_ds === false) {
            $this->log_error($t_ds);
            return null;
        }

        # Search
        $t_search_filter = '(&' . $t_ldap_organization . '(' . $t_ldap_uid_field . '=' . $c_username . '))';
        $t_search_attrs = array($t_ldap_uid_field, $p_field, 'dn');

        log_event(LOG_LDAP, 'Searching for ' . $t_search_filter);
        $t_sr = @ldap_search($t_ds, $t_ldap_root_dn, $t_search_filter, $t_search_attrs);
        if ($t_sr === false) {
            $this->log_error($t_ds);
            ldap_unbind($t_ds);
            log_event(LOG_LDAP, 'ldap search failed');
            return null;
        }

        # Get results
        $t_info = ldap_get_entries($t_ds, $t_sr);
        if ($t_info === false) {
            $this->log_error($t_ds);
            log_event(LOG_LDAP, 'ldap_get_entries() returned false.');
            return null;
        }

        # Free results / unbind
        log_event(LOG_LDAP, 'Unbinding from LDAP server');
        ldap_free_result($t_sr);
        ldap_unbind($t_ds);

        # If no matches, return null.
        if ($t_info['count'] == 0) {
            log_event(LOG_LDAP, 'No matches found.');
            return null;
        }

        # Make sure the requested field exists
        if (is_array($t_info[0]) && array_key_exists($p_field, $t_info[0])) {
            $t_value = $t_info[0][$p_field][0];
            log_event(LOG_LDAP, 'Found value \'' . $t_value . '\' for field \'' . $p_field . '\'.');
        } else {
            log_event(LOG_LDAP, 'WARNING: field \'' . $p_field . '\' does not exist');
            return null;
        }

        return $t_value;
    }


    /**
     * Return an email address from LDAP, given a username
     * @param string $p_username The username of a user to lookup.
     * @return string
     */
    function email_from_username($p_username)
    {
        $t_email = $this->get_field_from_username($p_username, 'mail');
        if ($t_email === null) {
            return '';
        }

        return $t_email;
    }


    /**
     * Gets a user real name given their user name.
     *
     * @param string $p_username The user's name.
     * @return string The user's real name.
     */
    function realname_from_username($p_username)
    {
        $t_ldap_realname_field = 'cn';

        //todo Исправить. Тут похоже обрабатывается только первый сервер ЛДАП в массиве.
        if (array_key_exists('realname_field', $this->ldap_config)) {
            $t_ldap_realname_field = $this->ldap_config['realname_field'];
        } elseif (array_key_exists('realname_field', $this->ldap_config[0])) {
            $t_ldap_realname_field = $this->ldap_config[0]['realname_field'];
        }

        $t_realname = $this->get_field_from_username($p_username, $t_ldap_realname_field);
        if ($t_realname === null) {
            return '';
        }

        return $t_realname;
    }


    /**
     * Gets a user's real name (common name) given the id.
     *
     * @param integer $p_user_id The user id.
     * @return string real name.
     */
    function realname($p_user_id)
    {
        return $this->realname_from_username(user_get_username($p_user_id));
    }


    /**
     * returns an email address from LDAP, given a userid
     * @param integer $p_user_id A valid user identifier.
     * @return string
     */
    function email($p_user_id)
    {
        global $g_cache_ldap_email;
        //todo Сделать кэширование через функцию
        if (isset($g_cache_ldap_email[(int)$p_user_id])) {
            return $g_cache_ldap_email[(int)$p_user_id];
        }

        $t_username = user_get_username($p_user_id);
        $t_email = $this->email_from_username($t_username);

        $g_cache_ldap_email[(int)$p_user_id] = $t_email;
        return $t_email;
    }

}
