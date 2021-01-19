<?php
/**
 * Plugin for authorization in MantisBT on multiple LDAP servers
 * Copyright (C) 2021  Starikov Anton - starikov_aa@mail.ru
 * https://github.com/starikov-aa/MultiLdapAuth
 */

auth_reauthenticate();
access_ensure_global_level(config_get('manage_site_threshold'));

layout_page_header();

layout_page_begin('manage_overview_page.php');

print_manage_menu('manage_plugin_page.php');

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
            <td><a href=\"#editServerSettings\" data-toggle=\"modal\" data-username-prefix='" . $server['username_prefix'] . "'>✏</a>&nbsp;
            <a href=\"#DeleteServerSettings\" data-toggle=\"modal\" data-username-prefix='" . $server['username_prefix'] . "'>❌</a></td></tr>";
}


?>
<?php
if (!preg_match(config_get_global('user_login_valid_regex'), 'domen\user'))
    echo '<div class="alert alert-danger" role="alert">
Не правильная конфигруация парамера $g_user_login_valid_regex. <br>
Установите в конфиге: <br>
$g_user_login_valid_regex = "/(^[a-z\d\-.+_ ]+@[a-z\d\-.]+\.[a-z]{2,4})|(^[a-z\d\\\\\\-\.+_ ]+)$/i"; <br>
</div>';
?>
    <div style="margin: 20px;">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#ldap_servers" aria-controls="home" role="tab"
                                                      data-toggle="tab">Серверы
                    LDAP</a></li>
            <li role="presentation"><a href="#general_settings" aria-controls="profile" role="tab" data-toggle="tab">Общие
                    настройки</a></li>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content">
            <div role="tabpanel" class="tab-pane active" id="ldap_servers">
                <div class="form-group row">
                    <div class="col-sm-10">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                                data-target="#editServerSettings" data-action="add">
                            Добавить сервер
                        </button>
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
            <div role="tabpanel" class="tab-pane" id="general_settings">
                <form>
                    <div class="form-group row">
                        <div class="col-sm-10">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ip_ban_enable"
                                       data-toggle='collapse'
                                       data-target='#ip_ban_settings' <?= mla_Tools::convert_on_to_checked(plugin_config_get('ip_ban_enable')); ?>>
                                <label class="form-check-label" for="ip_ban_enable">
                                    Включить блокировку по IP
                                </label>
                            </div>
                        </div>
                    </div>
                    <div id="ip_ban_settings" class="collapse">
                        <div class="form-group row">
                            <label for="ip_ban_max_failed_attempts" class="col-sm-2 col-form-label">Максимальное
                                количество
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
            </div>
        </div>
    </div>

    <div class="spinner">
        <div class="rect1"></div>
        <div class="rect2"></div>
        <div class="rect3"></div>
        <div class="rect4"></div>
        <div class="rect5"></div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="editServerSettings" tabindex="-1" role="dialog"
         aria-labelledby="exampleModalCenterTitle"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Настройка сервера</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="server_settings" method="post" name="server_settings">
                    <div class="modal-body">
                        <div class="form-group row">
                            <label for="server"
                                   class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_server'); ?></label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" id="server" name="server">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="ip_ban_max_failed_attempts"
                                   class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_root_dn'); ?></label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" id="root_dn" name="root_dn">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="root_dn"
                                   class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_bind_dn'); ?></label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" id="bind_dn" name="bind_dn" value="test">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="bind_passwd"
                                   class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_bind_passwd'); ?></label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" id="bind_passwd" name="bind_passwd">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="uid_field"
                                   class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_uid_field'); ?></label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" id="uid_field" name="uid_field">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="realname_field"
                                   class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_realname_field'); ?></label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" id="realname_field" name="realname_field">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="network_timeout"
                                   class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_network_timeout'); ?></label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" id="network_timeout" name="network_timeout">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="protocol_version"
                                   class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_protocol_version'); ?></label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" id="protocol_version" name="protocol_version">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="follow_referrals"
                                   class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_follow_referrals'); ?></label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" id="follow_referrals" name="follow_referrals">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="username_prefix"
                                   class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_username_prefix'); ?></label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" id="username_prefix" name="username_prefix">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-10">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="use_ldap_email"
                                           name="use_ldap_email" value="1">
                                    <label class="form-check-label" for="use_ldap_email">
                                        <?= plugin_lang_get('config_server_edit_use_ldap_email'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-10">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="use_ldap_realname"
                                           name="use_ldap_realname" value="1">
                                    <label class="form-check-label" for="use_ldap_realname">
                                        <?= plugin_lang_get('config_server_edit_use_ldap_realname'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-10">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="autocreate_user"
                                           name="autocreate_user" value="1">
                                    <label class="form-check-label" for="autocreate_user">
                                        <?= plugin_lang_get('config_server_edit_autocreate_user'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="save_srv_set">Save changes</button>
            </div>
        </div>
    </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="DeleteServerSettings" tabindex="-1" role="dialog"
         aria-labelledby="exampleModalCenterTitle"
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

    <script type="application/javascript">
        let servers_settings = '<?=json_encode($mla_tools->get_ldap_config());?>';

        function put_servers_settings(username_prefix) {
            let json = JSON.parse(servers_settings);
            json.forEach(function (srv, i) {
                if (srv['username_prefix'] == username_prefix) {
                    for (var k in srv) { // for(let [name, value] of formData) {
                        var form_elem = $('#' + k);
                        if (form_elem.attr('type') == 'text') {
                            form_elem.val(srv[k]);
                        } else if (form_elem.attr('type') == 'checkbox' && srv[k] == 1) {
                            form_elem.prop('checked', true);
                        }
                    }
                }
            });
        }

        $('a').click(function () {
            put_servers_settings($(this).data('username-prefix'));
        });

        $('#save_srv_set').click(function () {
            let f = new FormData($('#server_settings')[0]);
            $.ajax({
                url: '/mantis/plugin.php?page=MultiLdapAuth/ajax',
                type: 'POST',
                data: f,
                processData: false,  // Сообщить jQuery не передавать эти данные
                contentType: false   // Сообщить jQuery не передавать тип контента
            });
        })

        var loading = $('.spinner').hide();
        $(document)
            .ajaxStart(function () {
                loading.show();
            })
            .ajaxStop(function () {
                loading.hide();
            });


    </script>


<?php
layout_page_end();
?>