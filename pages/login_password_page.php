<?php
/**
 * Plugin for authorization in MantisBT on multiple LDAP servers
 * Copyright (C) 2021  Starikov Anton - starikov_aa@mail.ru
 * https://github.com/starikov-aa/MultiLdapAuth
 */

// Блокируем пользователя

if (!mla_AuthApi::is_ip_login_request_allowed()) {
    html_robots_noindex();
    layout_login_page_begin();
    $html = '<div class="mla_ip_ban_message">';
    $html .= '    <div class="login-logo"><img src="' . helper_mantis_url(config_get('logo_image')) . '"></div>';
    $html .= '    <p id="mla_ip_ban_text">' . plugin_lang_get('ip_ban_message') . '</p>';
    $html .= '</div>';
    echo $html;
    layout_login_page_end();
    return;
}

$f_error = gpc_get_bool('error');
$f_cookie_error = gpc_get_bool('cookie_error');
$f_return = string_sanitize_url(gpc_get_string('return', ''));
$f_username = trim(gpc_get_string('username', ''));
$f_reauthenticate = gpc_get_bool('reauthenticate', false);
$f_perm_login = gpc_get_bool('perm_login', false);
$f_secure_session = gpc_get_bool('secure_session', false);
$f_secure_session_cookie = gpc_get_cookie(config_get_global('cookie_prefix') . '_secure_session', null);
//$f_domen = gpc_get_string('domen', '');
//$f_username = empty($f_domen) ? $f_username : $f_domen . '\\' . $f_username;

# Set username to blank if invalid to prevent possible XSS exploits
$t_username = auth_prepare_username($f_username);

if (is_blank($t_username)) {
    $t_query_args = array(
        'error' => 1,
        'return' => $f_return,
    );

    $t_query_text = http_build_query($t_query_args, '', '&');

    $t_redirect_url = auth_login_page($t_query_text);
    print_header_redirect($t_redirect_url);
}

$t_user_id = auth_get_user_id_from_login_name($f_username);
$t_session_validation = !$f_reauthenticate && (ON == config_get_global('session_validation'));
$t_show_remember_me = !$f_reauthenticate && auth_allow_perm_login($t_user_id, $t_username);
$t_form_title = $f_reauthenticate ? lang_get('reauthenticate_title') : lang_get('login_title');

# If user is already authenticated and not anonymous
if (auth_is_user_authenticated() && !current_user_is_anonymous() && !$f_reauthenticate) {
    # If return URL is specified redirect to it; otherwise use default page
    if (!is_blank($f_return)) {
        print_header_redirect($f_return, false, false, true);
    } else {
        print_header_redirect(config_get_global('default_home_page'));
    }
}

# Determine if secure_session should default on or off?
# - If no errors, and no cookies set, default to on.
# - If no errors, but cookie is set, use the cookie value.
# - If errors, use the value passed in.
if ($t_session_validation) {
    if (!$f_error && !$f_cookie_error) {
        $t_default_secure_session = is_null($f_secure_session_cookie) ? true : $f_secure_session_cookie;
    } else {
        $t_default_secure_session = $f_secure_session;
    }
}

# Login page shouldn't be indexed by search engines
html_robots_noindex();

layout_login_page_begin();

?>

    <div class="col-md-offset-3 col-md-6 col-sm-10 col-sm-offset-1">
        <div class="login-container">
            <div class="space-12 hidden-480"></div>
            <?php layout_login_page_logo() ?>
            <div class="space-24 hidden-480"></div>
            <div class="position-relative">
                <div class="signup-box visible widget-box no-border" id="login-box">
                    <div class="widget-body">
                        <div class="widget-main">
                            <h4 class="header lighter bigger">
                                <i class="ace-icon fa fa-sign-in"></i>
                                <?php echo $t_form_title ?>
                            </h4>
                            <div class="space-10"></div>
                            <form id="login-form" method="post" action="<?= plugin_page('login'); ?>">
                                <fieldset>

                                    <?php
                                    if (!is_blank($f_return)) {
                                        echo '<input type="hidden" name="return" value="', string_html_specialchars($f_return), '" />';
                                    }

                                    echo sprintf(lang_get('enter_password'), string_html_specialchars($t_username));

                                    # CSRF protection not required here - form does not result in modifications
                                    ?>
                                    <input hidden readonly type="text" name="username" class="hidden" tabindex="-1"
                                           value="<?php echo string_html_specialchars($t_username) ?>"
                                           id="hidden_username"/>
                                    <label for="password" class="block clearfix">
				<span class="block input-icon input-icon-right">
					<input id="password" name="password" type="password"
                           placeholder="<?php echo lang_get('password') ?>"
                           size="32" maxlength="<?php echo auth_get_password_max_size(); ?>"
                           class="form-control autofocus">
					<i class="ace-icon fa fa-lock"></i>
				</span>
                                    </label>

                                    <?php if ($t_show_remember_me) { ?>
                                        <div class="clearfix">
                                            <label for="remember-login" class="inline">
                                                <input id="remember-login" type="checkbox" name="perm_login"
                                                       class="ace" <?php echo($f_perm_login ? 'checked="checked" ' : '') ?> />
                                                <span class="lbl padding-6"><?php echo lang_get('save_login') ?></span>
                                            </label>
                                        </div>
                                    <?php } ?>
                                    <?php if ($t_session_validation) { ?>
                                        <div class="clearfix">
                                            <label for="secure-session" class="inline">
                                                <input id="secure-session" type="checkbox" name="secure_session"
                                                       class="ace" <?php echo($t_default_secure_session ? 'checked="checked" ' : '') ?> />
                                                <span class="lbl padding-6"><?php echo lang_get('secure_session_long') ?></span>
                                            </label>
                                        </div>
                                    <?php } ?>

                                    <?php if ($f_reauthenticate) {
                                        echo '<input id="reauthenticate" type="hidden" name="reauthenticate" value="1" />';
                                    } ?>

                                    <div class="space-10"></div>

                                    <input type="submit"
                                           class="width-40 pull-right btn btn-success btn-inverse bigger-110"
                                           value="<?php echo lang_get('login_button') ?>"/>
                                    <div class="clearfix"></div>
                                </fieldset>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php

layout_login_page_end();