<?php
/**
 * Plugin for authorization in MantisBT on multiple LDAP servers
 * Copyright (C) 2021  Starikov Anton - starikov_aa@mail.ru
 * https://github.com/starikov-aa/MultiLdapAuth
 */

$mla_tools = new mla_Tools();
$servers_config = $mla_tools->get_ldap_config();

$trs = "";
foreach ($servers_config as $server) {
    $use_email = $server['use_ldap_email'] ? plugin_lang_get('msg_yes') : plugin_lang_get('msg_no');
    $use_realname = $server['use_ldap_realname'] ? plugin_lang_get('msg_yes') : plugin_lang_get('msg_no');
    $autocreate_user = $server['autocreate_user'] ? plugin_lang_get('msg_yes') : plugin_lang_get('msg_no');
    $trs .= "<tr><td>" . $server['server'] . "</td>
            <td>" . $server['username_prefix'] . "</td>
            <td>" . $use_email . "</td>
            <td>" . $use_realname . "</td>
            <td>" . $autocreate_user . "</td></tr>";
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Настройки плагина MultiLdapAuth</title>
    <link rel="stylesheet" type="text/css" href="http://10.0.0.12/mantis/css/bootstrap-3.4.1.min.css"/>
    <link rel="stylesheet" type="text/css" href="http://10.0.0.12/mantis/css/bootstrap-datetimepicker-4.17.47.min.css"/>
</head>
<body>

<?php
if (!preg_match(config_get_global('user_login_valid_regex'), 'domen\user'))
    echo '<div class="alert alert-danger" role="alert">
Не правильная конфигруация парамера $g_user_login_valid_regex. <br>
Установите в конфиге: <br>
$g_user_login_valid_regex = "/(^[a-z\d\-.+_ ]+@[a-z\d\-.]+\.[a-z]{2,4})|(^[a-z\d\\\\\-\.+_ ]+)$/i"; <br>
</div>';
?>

<div style="margin: 20px;">
    <hr class="mb-4">
    <h4 class="md-6">Общие настройки</h4>
    <hr class="mb-4">
    <form>
        <div class="form-group row">
            <div class="col-sm-10">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="ip_ban_enable">
                    <label class="form-check-label" for="ip_ban_enable">
                        Включить блокировку по IP
                    </label>
                </div>
            </div>
        </div>
        <div class="form-group row">
            <label for="ip_ban_max_failed_attempts" class="col-sm-2 col-form-label">Максимальное количество
                попыток</label>
            <div class="col-sm-1">
                <input type="text" class="form-control" id="ip_ban_max_failed_attempts"
                       value="<?= plugin_config_get('ip_ban_max_failed_attempts'); ?>">
            </div>
        </div>
        <div class="form-group row">
            <label for="ip_ban_time" class="col-sm-2 col-form-label">Время блокировки, сек</label>
            <div class="col-sm-1">
                <input type="text" class="form-control" id="ip_ban_time"
                       value="<?= plugin_config_get('ip_ban_time'); ?>">
            </div>
        </div>

        <div class="form-group row">
            <div class="col-sm-10">
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </div>
    </form>
    <hr class="mb-4">
    <h4 class="md-6">Настройки серверов LDAP</h4>
    <hr class="mb-4">
    <div class="form-group row">
        <div class="col-sm-10">
            <button type="button" class="btn">Добавить сервер</button>
        </div>
    </div>
    <table class="table">
        <thead>
        <tr>
            <th scope="col">Сервер</th>
            <th scope="col">Префикс</th>
            <th scope="col">Использовать Email</th>
            <th scope="col">Использовать имя</th>
            <th scope="col">Автосоздание пользователя</th>
            <th scope="col"></th>
        </tr>
        </thead>
        <tbody>
        <?=$trs; ?>
        </tbody>
    </table>

</div>

</body>
</html>
</html>
