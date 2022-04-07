<?php
/**
 * Plugin for authorization in MantisBT on multiple LDAP servers
 * Copyright (C) 2021 Starikov Anton - starikov_aa@mail.ru
 * https://github.com/starikov-aa/MultiLdapAuth
 */

auth_reauthenticate();
//access_ensure_global_level(config_get('manage_site_threshold'));

?>

<!--<html>-->
<!--<body>-->
<!--<form method="get">-->
<!--    <input type="text" name="username">-->
<!--    <input type="submit" name="send" value="Send">-->
<!--</form>-->
<!--</body>-->
<!--</html>-->

<?php

echo "<pre>";


//if (gpc_get('send')) {
    $t_user_id = auth_get_current_user_id();
    $p_username = user_get_username($t_user_id);


    echo "CURRENT USER ID: " . $t_user_id . "\r\n";
    echo "CURRENT USER NAME: " . $p_username . "\r\n";
    echo "<br>";

    $t_mla_AuthApi = new mla_AuthApi(new mla_LdapApi(), new AuthFlags());
    $t_mla_AuthApi->adding_user_to_project_by_department(true);
//}