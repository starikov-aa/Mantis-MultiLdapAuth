<?php
/**
 * Plugin for authorization in MantisBT on multiple LDAP servers
 * Copyright (C) 2021  Starikov Anton - starikov_aa@mail.ru
 * https://github.com/starikov-aa/MultiLdapAuth
 */

auth_reauthenticate();
access_ensure_global_level(config_get('manage_site_threshold'));

$mla_tools = new mla_Tools();
$servers_config = $mla_tools->get_ldap_config();

$tbl_lines = "";
foreach ($servers_config as $server) {

    $use_email = $server['use_ldap_email'] ? plugin_lang_get('msg_yes') : plugin_lang_get('msg_no');
    $use_realname = $server['use_ldap_realname'] ? plugin_lang_get('msg_yes') : plugin_lang_get('msg_no');
    $autocreate_user = $server['autocreate_user'] ? plugin_lang_get('msg_yes') : plugin_lang_get('msg_no');

    $tbl_lines .= "<tr><td>" . $server['server'] . "</td>
            <td>" . $server['username_prefix'] . "</td>
            <td>" . $use_email . "</td>
            <td>" . $use_realname . "</td>
            <td>" . $autocreate_user . "</td>
            <td><a href=\"#editServerSettings\" data-toggle=\"modal\">✏</a>&nbsp;
            <a href=\"#DeleteServerSettings\" data-toggle=\"modal\">❌</a></td></tr>";
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Настройки плагина MultiLdapAuth</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css"
          integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"
            integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj"
            crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx"
            crossorigin="anonymous"></script>
    <script type="text/javascript" src="/mantis/plugin_file.php?file=MultiLdapAuth/mla_script.js"></script>
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
    <h4 class="md-6">Общие настройки</h4>
    <hr class="mb-4">
    <form>
        <div class="form-group row">
            <div class="col-sm-10">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="ip_ban_enable" data-toggle='collapse'
                           data-target='#ip_ban_settings' <?=mla_Tools::convert_on_to_checked(plugin_config_get('ip_ban_enable'));?>>
                    <label class="form-check-label" for="ip_ban_enable">
                        Включить блокировку по IP
                    </label>
                </div>
            </div>
        </div>
        <div id="ip_ban_settings" class="collapse">
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
        </div>
        <div class="form-group row">
            <div class="col-sm-10">
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </div>
    </form>
    <h4 class="md-6">Настройки серверов LDAP</h4>
    <hr class="mb-4">
    <div class="form-group row">
        <div class="col-sm-10">
            <button type="button" class="btn btn-primary">Добавить сервер</button>
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
        <?= $tbl_lines; ?>
        </tbody>
    </table>

</div>

<!-- Modal -->
<div class="modal fade" id="editServerSettings" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">Настройка сервера</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group row">
                    <label for="ip_ban_max_failed_attempts"
                           class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_server'); ?></label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" id="ip_ban_max_failed_attempts"
                               value="<?= plugin_config_get('ip_ban_max_failed_attempts'); ?>">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="ip_ban_max_failed_attempts"
                           class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_root_dn'); ?></label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" id="ip_ban_max_failed_attempts"
                               value="<?= plugin_config_get('ip_ban_max_failed_attempts'); ?>">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="ip_ban_max_failed_attempts"
                           class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_bind_dn'); ?></label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" id="ip_ban_max_failed_attempts"
                               value="<?= plugin_config_get('ip_ban_max_failed_attempts'); ?>">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="ip_ban_max_failed_attempts"
                           class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_bind_password'); ?></label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" id="ip_ban_max_failed_attempts"
                               value="<?= plugin_config_get('ip_ban_max_failed_attempts'); ?>">
                    </div>
                </div>

                <div class="form-group row">
                    <label for="ip_ban_max_failed_attempts"
                           class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_uid_field'); ?></label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" id="ip_ban_max_failed_attempts"
                               value="<?= plugin_config_get('ip_ban_max_failed_attempts'); ?>">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="ip_ban_max_failed_attempts"
                           class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_realname_field'); ?></label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" id="ip_ban_max_failed_attempts"
                               value="<?= plugin_config_get('ip_ban_max_failed_attempts'); ?>">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="ip_ban_max_failed_attempts"
                           class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_network_timeout'); ?></label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" id="ip_ban_max_failed_attempts"
                               value="<?= plugin_config_get('ip_ban_max_failed_attempts'); ?>">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="ip_ban_max_failed_attempts"
                           class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_protocol_version'); ?></label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" id="ip_ban_max_failed_attempts"
                               value="<?= plugin_config_get('ip_ban_max_failed_attempts'); ?>">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="ip_ban_max_failed_attempts"
                           class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_follow_referrals'); ?></label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" id="ip_ban_max_failed_attempts"
                               value="<?= plugin_config_get('ip_ban_max_failed_attempts'); ?>">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="ip_ban_max_failed_attempts"
                           class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_username_prefix'); ?></label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" id="ip_ban_max_failed_attempts"
                               value="<?= plugin_config_get('ip_ban_max_failed_attempts'); ?>">
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-sm-10">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="ip_ban_enable">
                            <label class="form-check-label" for="ip_ban_enable">
                                <?= plugin_lang_get('config_server_edit_use_ldap_email'); ?>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-sm-10">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="ip_ban_enable">
                            <label class="form-check-label" for="ip_ban_enable">
                                <?= plugin_lang_get('config_server_edit_use_ldap_realname'); ?>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-sm-10">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="ip_ban_enable">
                            <label class="form-check-label" for="ip_ban_enable">
                                <?= plugin_lang_get('config_server_edit_autocreate_user'); ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>


<!-- Modal -->
<div class="modal fade" id="DeleteServerSettings" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">Удаление настроек</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Вы точно хотите удалить настройки сервера?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger">Delete</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>
</html>
