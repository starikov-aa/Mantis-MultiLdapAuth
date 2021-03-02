<?php
/**
 *  Plugin for authorization in MantisBT on multiple LDAP servers
 *  Copyright (C) 2021  Starikov Anton - starikov_aa@mail.ru
 *  https://github.com/starikov-aa/Mantis-MultiLdapAuth
 */

use Mantis\Exceptions\ClientException;

class mla_AuthApi
{
    /**
     * @var mla_LdapApi|null
     */
    public $ldap = null;

    /**
     * @var AuthFlags|null
     */
    public $auth_flags = null;

    /**
     * mla_AuthApi constructor.
     * @param mla_LdapApi $auth_provider
     * @param AuthFlags $auth_flags
     * @throws Exception
     */
    function __construct(mla_LdapApi $auth_provider, AuthFlags $auth_flags)
    {
        $this->ldap = $auth_provider;
        $this->auth_flags = $auth_flags;
    }

    /**
     * Attempt to login the user with the given password
     * If the user fails validation, false is returned
     * If the user passes validation, the cookies are set and
     * true is returned.  If $p_perm_login is true, the long-term
     * cookie is created.
     *
     * @param string $p_username A prepared username.
     * @param string $p_password A prepared password.
     * @param boolean $p_perm_login Whether to create a long-term cookie.
     * @return boolean indicates if authentication was successful
     * @throws ClientException
     * @access public
     */
    function attempt_login($p_username, $p_password, $p_perm_login = false)
    {
        if (!self::is_ip_login_request_allowed()) {
            return false;
        }

        log_event(LOG_PLUGIN, 'attempt login as user: ' . $p_username);

        $t_user_id = auth_get_user_id_from_login_name($p_username);

        if ($t_user_id === false || $t_user_id === 0) {
            $t_user_id = $this->auto_create_user($p_username, $p_password);
            if ($t_user_id === false) {
                return false;
            }
        }

        # check for anonymous login
        if (!user_is_anonymous($t_user_id)) {
            # anonymous login didn't work, so check the password
            if (!$this->ldap->authenticate_by_username($p_username, $p_password)) {
                $this->increment_failed_login_user();
                return false;
            }
        }

        return auth_login_user($t_user_id, $p_perm_login);
    }

    /**
     * In the case where a user is attempting to authenticate but doesn't exist.
     * Check if the authentication provider supports auto-creation of users and
     * whether the password matches.
     *
     * @param string $p_username A prepared username.
     * @param string $p_password A prepared password.
     * @return int|boolean user id or false in case of failure.
     * @throws ClientException
     */
    function auto_create_user($p_username, $p_password)
    {
        $server_config = mla_Tools::get_server_config_by_username($p_username);

        if (OFF === $server_config['autocreate_user']) {
            log_event(LOG_LDAP, 'Auto-creation of users is disabled for the server ' . $server_config['server']);
            return false;
        }

        $t_user_id = user_get_id_by_name($p_username);
        user_clear_cache($t_user_id); // todo убрать после фикса #0027836

        if ($this->ldap->authenticate_by_username($p_username, $p_password)) {

            if ($server_config['use_ldap_email'] == ON) {
                $ldap_email = $this->ldap->get_field_from_username($p_username, 'mail');
            } else {
                $ldap_email = '';
            }

            $t_cookie_string = user_create($p_username, md5($p_password), $ldap_email);

            if ($t_cookie_string === false) {
                return false;
            }

            log_event(LOG_LDAP, 'User created: ' . $p_username);
            $t_user_id = user_get_id_by_name($p_username);
            project_add_user($server_config['default_new_user_project'], $t_user_id, REPORTER);

            return $t_user_id;
        } else {
            $this->increment_failed_login_user();
        }

        return false;
    }

    /**
     * @param $p_username
     * @return AuthFlags|null
     */
    function set_user_auth_flags($p_username)
    {
        if (mla_Tools::user_is_local($p_username)) {
            return null;
        }

        $this->auth_flags->setCanUseStandardLogin(false);
        $this->auth_flags->setPasswordManagedExternallyMessage(plugin_lang_get('PasswordManagedExternallyMessage'));
        $this->auth_flags->setCredentialsPage(helper_url_combine(plugin_page('login_password_page', /* redirect */ true), 'username=' . $p_username));
        $this->auth_flags->setLogoutRedirectPage(plugin_page('logout', /* redirect */ true));

        # No long term session for identity provider to be able to kick users out.
        $this->auth_flags->setPermSessionEnabled(true);

        # Enable re-authentication and use more aggressive timeout.
        $this->auth_flags->setReauthenticationEnabled(true);
        $this->auth_flags->setReauthenticationLifetime(3000);

        return $this->auth_flags;
    }

    function increment_failed_login_user()
    {
        $ip_address = mla_Tools::get_user_ip();
        $tbl_name = plugin_table('ip_ban');
        db_param_push();
        $t_query_select = db_query('SELECT * FROM ' . $tbl_name . ' WHERE ip=' . db_param(), [$ip_address]);
        if (db_result($t_query_select)) {
            db_query('UPDATE ' . $tbl_name . ' SET attempts=attempts+1, last_attempt_time=' . db_param() . ' WHERE ip=' . db_param(), [time(), $ip_address]);
        } else {
            db_query('INSERT INTO ' . $tbl_name . ' (`ip`, `attempts`, `last_attempt_time`) VALUES ("' . $ip_address . '", "1", "' . time() . '")');
        }
        return true;
    }

    /**
     * Reset to zero the failed login attempts
     *
     * @return boolean always true
     */
    static function reset_failed_login_count_to_zero()
    {
        $ip_address = mla_Tools::get_user_ip();
        $tbl_name = plugin_table('ip_ban');
        db_param_push();
        db_query('DELETE FROM ' . $tbl_name . ' WHERE ip=' . db_param(), [$ip_address]);
        return true;
    }

    /**
     * Get failed login attempts
     *
     * @return boolean
     */
    static function is_ip_login_request_allowed()
    {
        $ip_address = mla_Tools::get_user_ip();
        $tbl_name = plugin_table('ip_ban');
        db_param_push();
        $t_query_select = db_query('SELECT * FROM ' . $tbl_name . ' WHERE ip=' . db_param(), [$ip_address]);
        $t_ip_info = db_fetch_array($t_query_select);

        // ip нету в базе, пускаем
        if (!$t_ip_info) {
            return true;
        }

        if ($t_ip_info['attempts'] < plugin_config_get('ip_ban_max_failed_attempts')) {
            // Пускаем пользователя т.к. кол-во попыток еще не превышено
            return true;
        } elseif (($t_ip_info['last_attempt_time'] + plugin_config_get('ip_ban_time')) > time()) {
            // Не пускаем. Т.к. кол-во попыток превышено и время бана еще не закончилось
            log_event(LOG_PLUGIN, 'IP ' . $ip_address . ' blocked due to exceeded number of login attempts');
            user_clear_cache(0); // todo убрать после фикса #0027836
            return false;
        } else {
            // Пускаем. Т.к. время бана истекло. И сбрасываем кол-во попыток.
            self::reset_failed_login_count_to_zero();
            return true;
        }

        return true;
    }
}