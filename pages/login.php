<?php
# Copyright (c) MantisBT Team - mantisbt-dev@lists.sourceforge.net
# Licensed under the MIT license

//require_once('core.php');
//require_api('authentication_api.php');
//require_api('user_api.php');

$mla_AuthApi = new mla_AuthApi(new mla_LdapApi(config_get('mla_ldap')), new AuthFlags())

$g_cache_ldap_email = array();

$f_username = gpc_get_string('username', '');
$f_password = gpc_get_string('password', '');
$f_reauthenticate = gpc_get_bool('reauthenticate', false);
$t_return = string_url(string_sanitize_url(gpc_get_string('return', config_get_global('default_home_page'))));

# TODO: use custom authentication method here.

$t_user_id = is_blank($f_username) ? false : user_get_id_by_name($f_username);
//$t_user_id = auth_get_user_id_from_login_name($f_username);

//gpc_set_cookie( config_get_global( 'cookie_prefix' ) . '_secure_session', $f_secure_session ? '1' : '0' );

if ($mla_AuthApi->attempt_login($f_username, $f_password, $f_perm_login)) {
    log_event(LOG_PLUGIN, 'LOGIN: ' . $f_username . " = " . $f_password);
    session_set('secure_session', $f_secure_session);
    $t_redirect_url = 'login_cookie_test.php?return=' . $t_return;
} else {
    $t_query_args = array(
        'error' => 1,
        'username' => $f_username,
    );

    if (!is_blank('return')) {
        $t_query_args['return'] = $t_return;
    }

    if ($f_reauthenticate) {
        $t_query_args['reauthenticate'] = 1;
    }

    $t_query_text = http_build_query($t_query_args, '', '&');

    $t_uri = auth_login_page($t_query_text);

    print_header_redirect($t_uri
}

layout_login_page_begin();

?>

<div class="position-relative">
	<div class="signup-box visible widget-box no-border" id="login-box">
		<div class="widget-body">
			<div class="widget-main">
				<h4 class="header lighter bigger">
					<i class="ace-icon fa fa-sign-in"></i>
					<?php echo $t_form_title ?>
				</h4>
				<div class="space-10"></div>
	<form id="login-form" method="post" action="login.php">
		<fieldset>

			<?php
			if( !is_blank( $f_return ) ) {
				echo '<input type="hidden" name="return" value="', string_html_specialchars( $f_return ), '" />';
			}

			if( $t_upgrade_required ) {
				echo '<input type="hidden" name="install" value="true" />';
			}


			echo sprintf( lang_get( 'enter_password' ), string_html_specialchars( $t_username ) );

			# CSRF protection not required here - form does not result in modifications
			?>
			<input hidden readonly type="text" name="username" class="hidden" tabindex="-1" value="<?php echo string_html_specialchars( $t_username ) ?>" id="hidden_username" />
			<label for="password" class="block clearfix">
				<span class="block input-icon input-icon-right">
					<input id="password" name="password" type="password" placeholder="<?php echo lang_get( 'password' ) ?>"
						   size="32" maxlength="<?php echo auth_get_password_max_size(); ?>"
						   class="form-control autofocus">
					<i class="ace-icon fa fa-lock"></i>
				</span>
			</label>

			<?php if( $t_show_remember_me ) { ?>
				<div class="clearfix">
					<label for="remember-login" class="inline">
						<input id="remember-login" type="checkbox" name="perm_login" class="ace" <?php echo ( $f_perm_login ? 'checked="checked" ' : '' ) ?> />
						<span class="lbl padding-6"><?php echo lang_get( 'save_login' ) ?></span>
					</label>
				</div>
			<?php } ?>
			<?php if( $t_session_validation ) { ?>
				<div class="clearfix">
					<label for="secure-session" class="inline">
						<input id="secure-session" type="checkbox" name="secure_session" class="ace" <?php echo ( $t_default_secure_session ? 'checked="checked" ' : '' ) ?> />
						<span class="lbl padding-6"><?php echo lang_get( 'secure_session_long' ) ?></span>
					</label>
				</div>
			<?php } ?>

			<?php if( $f_reauthenticate ) {
				echo '<input id="reauthenticate" type="hidden" name="reauthenticate" value="1" />';
			} ?>

			<div class="space-10"></div>

			<input type="submit" class="width-40 pull-right btn btn-success btn-inverse bigger-110" value="<?php echo lang_get( 'login_button' ) ?>" />
			<div class="clearfix"></div>
			<?php
			# lost password feature disabled or reset password via email disabled -> stop here!
			if( $t_show_reset_password ) {
				echo '<a class="pull-right" href="lost_pwd_page.php?username=', urlencode( $t_username ), '">', lang_get( 'lost_password_link' ), '</a>';
			}
			?>
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
//print_header_redirect( $t_redirect_url );