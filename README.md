The plugin allows you to use multiple LDAP servers for authorization. At the same time, you can still use local accounts.

### Capabilities
- Authorization through multiple LDAP servers
- Automatic user creation on first login
- Automatic transfer of a new user to the specified project

### Installation
1. Add to the Mantis config:
```php
$g_login_method = MD5;
$g_custom_headers = array( 'Content-Security-Policy:' );
$g_user_login_valid_regex = "/(^[a-z\d\-.+_ ]+@[a-z\d\-.]+\.[a-z]{2,4})|(^[a-z\d\\\\\\-\.+_ ]+)$/i";
```

2. Modify the file `core / authentication_api.php`.
Modify the ** auth_flags ** function from [PR # 1712] (https://github.com/mantisbt/mantisbt/pull/1712 "PR # 1712").
P.S. This item will be relevant until the changes from the above PR are accepted.

3. Modify the file `login_password_page.php`. Strings
```php
$t_user_id = auth_get_user_id_from_login_name( $t_username );
if( $t_user_id !== false && auth_credential_page( '', $t_user_id ) != AUTH_PAGE_CREDENTIAL ) {
```
replace with
```php
$t_user_id = auth_get_user_id_from_login_name( $t_username );
$t_user_id_tmp = $t_user_id === false ? NO_USER : $t_user_id;
if(auth_credential_page( '', $t_user_id_tmp ) != AUTH_PAGE_CREDENTIAL ) {
```
P.S. This point will be relevant until changes from [PR #1712](https://github.com/mantisbt/mantisbt/pull/1712 "PR #1712") are accepted.

4.  Go to plugins folder
```
cd {MANTIS_ROOT_DIR}/plugins
```
5.  Clone the repository
```
git clone https://github.com/starikov-aa/Mantis-MultiLdapAuth.git MultiLdapAuth
```
6. Go to plugins management in the Mantis admin page and enable MultiLdapAuth
7. To go to the settings, click on the plugin name or follow the link https://{MANTIS_URL}/plugin.php?page=MultiLdapAuth/config
8. Add LDAP servers
