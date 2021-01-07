<?php
/**
 * Search Plugin for MantisBT
 * Copyright (C) 2021  Starikov Anton - starikov_aa@mail.ru
 * https://github.com/starikov-aa/mantisbt-search
 */

class mla_AuthApi
{
    // Что будем использовать для авторизации пользователя
    /**
     * @var mla_LdapApi|null
     */
    public $auth_provider = null;

    /**
     * @var AuthFlags|null
     */
    public $auth_flags = null;

    /**
     * mla_AuthApi constructor.
     * @param mla_LdapApi $auth_provider
     * @param AuthFlags $auth_flags
     */
    function __construct(mla_LdapApi $auth_provider, AuthFlags $auth_flags)
    {
        log_event(LOG_PLUGIN, 'mla_AuthApi CREATED');
        $this->auth_provider = $auth_provider;
        $this->auth_flags = $auth_flags;
    }

    /**
     * Attempt to login the user with the given password
     * If the user fails validation, false is returned
     * If the user passes validation, the cookies are set and
     * true is returned.  If $p_perm_login is true, the long-term
     * cookie is created.
     * @param string $p_username A prepared username.
     * @param string $p_password A prepared password.
     * @param boolean $p_perm_login Whether to create a long-term cookie.
     * @return boolean indicates if authentication was successful
     * @access public
     */
    function attempt_login($p_username, $p_password, $p_perm_login = false)
    {
        log_event(LOG_PLUGIN, 'attempt login as user: "' . $p_username . '" and pass "' . $p_password . '"');

        $t_user_id = auth_get_user_id_from_login_name($p_username);

        if ($t_user_id === false || $t_user_id === 0) {
            $t_user_id = $this->auto_create_user($p_username, $p_password);
            if ($t_user_id === false) {
                return false;
            }
        }

        if (!user_is_login_request_allowed($t_user_id)) {
            return false;
        }

        # check for anonymous login
        if (!user_is_anonymous($t_user_id)) {
            # anonymous login didn't work, so check the password
            if (!$this->auth_provider->authenticate_by_username($p_username, $p_password)) {
                user_increment_failed_login_count($t_user_id);
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
     */
    function auto_create_user($p_username, $p_password)
    {
        $t_user_id = user_get_id_by_name($p_username);
        if ($t_user_id === 0) {
            unset($GLOBALS['g_cache_user'][0]);
        }

        if ($this->auth_provider->authenticate_by_username($p_username, $p_password)) {
            $user_param = $this->auth_provider->get_login_and_postfix_from_username($p_username);
            $server_config = $this->auth_provider->get_servers_config('username_postfix', $user_param['postfix']);
            if ($server_config['use_ldap_email'] == ON) {
                $ldap_email = $this->auth_provider->get_field_from_username($p_username, 'mail');
            } else {
                $ldap_email = '';
            }
            log_event(LOG_PLUGIN, $user_param['postfix'] . " = " . print_r($server_config, true) . " = " . $ldap_email);
            $t_cookie_string = user_create($p_username, md5($p_password), $ldap_email);
            if ($t_cookie_string === false) {
                return false;
            }
            log_event(LOG_PLUGIN, 'User created');
            # ok, we created the user, get the row again
            return user_get_id_by_name($p_username);
        }

        return false;
    }

    /**
     * @param $p_username
     * @return AuthFlags|null
     */
    function set_user_auth_flags($p_username)
    {
        if ($this->user_is_local($p_username)){
            return null;
        }

        $this->auth_flags->setCanUseStandardLogin(false);
        $this->auth_flags->setPasswordManagedExternallyMessage('Passwords are no more, you cannot change them111!');
        $this->auth_flags->setCredentialsPage(helper_url_combine(plugin_page('login_password_page', /* redirect */ true), 'username=' . $p_username));
        $this->auth_flags->setLogoutRedirectPage(plugin_page('logout', /* redirect */ true));

        # No long term session for identity provider to be able to kick users out.
        //$this->auth_flags->setPermSessionEnabled(false);

        # Enable re-authentication and use more aggressive timeout.
        $this->auth_flags->setReauthenticationEnabled(true);
        $this->auth_flags->setReauthenticationLifetime(10);

        return $this->auth_flags;
    }

    /**
     * Check user is local
     *
     * @param $p_username
     * @return bool
     */
    function user_is_local($p_username)
    {
        if (stristr($p_username, '@') === false) {
            return true;
        } else {
            return false;
        }

    }


}