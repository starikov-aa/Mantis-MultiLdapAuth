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

$servers_config = mla_ServerConfig::get_servers_config();

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
            <td>" . project_get_name($server['default_new_user_project'], false) . "</td>
            <td><a href='#editServerSettings' class='server_action_button' data-action='edit_server' data-toggle='modal' data-id='" . $server['id'] . "'>✏</a>&nbsp;
            <a href='#DeleteServerSettings' class='server_action_button' data-action='delete_server' data-toggle='modal'data-id='" . $server['id'] . "'>❌</a></td></tr>";
}

$project_select_option = '';
foreach (project_get_all_rows() as $p_key => $p_data) {
    $project_select_option .= '<option value="' . $p_key . '">' . $p_data['name'] . '</option>';
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
        <div class="message" role="alert"></div>
        <!-- Nav tabs -->
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#ldap_servers" aria-controls="home" role="tab"
                                                      data-toggle="tab">Серверы LDAP</a></li>
            <li role="presentation"><a href="#general_settings" aria-controls="profile" role="tab" data-toggle="tab">Общие
                    настройки</a></li>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content">
            <div role="tabpanel" class="tab-pane active" id="ldap_servers">
                <div class="form-group row">
                    <div class="col-sm-10">
                        <button type="button" class="server_action_button btn btn-primary btn-sm" data-toggle="modal"
                                data-target="#editServerSettings" data-action="add_server">
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
                        <th scope="col">Проект по умолчанию</th>
                        <th scope="col"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?= $tbl_lines; ?>
                    </tbody>
                </table>
            </div>
            <div role="tabpanel" class="tab-pane" id="general_settings">
                <form name="form_general_settings" id="form_general_settings">
                    <div class="form-group row">
                        <div class="col-sm-10">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ip_ban_enable" name="ip_ban_enable"
                                       value="1"
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
                            <label for="ip_ban_max_failed_attempts" class="col-md-4 col-form-label">Максимальное
                                количество
                                попыток</label>
                            <div class="col-sm-1">
                                <input type="text" class="form-control" id="ip_ban_max_failed_attempts"
                                       name="ip_ban_max_failed_attempts"
                                       pattern="\d"
                                       value="<?= plugin_config_get('ip_ban_max_failed_attempts'); ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="ip_ban_time" class="col-md-4 col-form-label">Время блокировки, сек</label>
                            <div class="col-sm-1">
                                <input type="text" class="form-control" id="ip_ban_time" name="ip_ban_time"
                                       value="<?= plugin_config_get('ip_ban_time'); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-10">
                            <button type="button" id="save_general_settings" class="btn btn-primary btn-sm">Сохранить
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="action" value="update_general_settings">
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
                    <h5 class="modal-title"><?= plugin_lang_get('config_server_edit_server_title'); ?></h5>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active"><a href="#tab_ldap_servers_main" aria-controls="home"
                                                                  role="tab"
                                                                  data-toggle="tab">Основные</a></li>
                        <li role="presentation"><a href="#tab_ldap_servers_other" aria-controls="home" role="tab"
                                                   data-toggle="tab">Дополнительные</a></li>
                    </ul>
                    <form id="server_settings" method="post" name="server_settings">
                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane active" id="tab_ldap_servers_main">
                                <div class="form-group row">
                                    <label for="server"
                                           class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_server'); ?></label>
                                    <div class="col-sm-5">
                                        <input type="text" class="form-control" id="server" name="server">
                                        <span class="help-block">ldap://server.com or ldaps://server.com</span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="ip_ban_max_failed_attempts"
                                           class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_root_dn'); ?></label>
                                    <div class="col-sm-5">
                                        <input type="text" class="form-control" id="root_dn" name="root_dn">
                                        <span class="help-block">DC=lab,DC=winitlab,DC=com</span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="root_dn"
                                           class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_bind_dn'); ?></label>
                                    <div class="col-sm-5">
                                        <input type="text" class="form-control" id="bind_dn" name="bind_dn">
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
                                        <input type="text" class="form-control" id="realname_field"
                                               name="realname_field">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="network_timeout"
                                           class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_network_timeout'); ?></label>
                                    <div class="col-sm-5">
                                        <input type="text" class="form-control" id="network_timeout"
                                               name="network_timeout">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="protocol_version"
                                           class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_protocol_version'); ?></label>
                                    <div class="col-sm-5">
                                        <input type="text" class="form-control" id="protocol_version"
                                               name="protocol_version">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="follow_referrals"
                                           class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_follow_referrals'); ?></label>
                                    <div class="col-sm-5">
                                        <input type="text" class="form-control" id="follow_referrals"
                                               name="follow_referrals">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="username_prefix"
                                           class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_username_prefix'); ?></label>
                                    <div class="col-sm-5">
                                        <input type="text" class="form-control" id="username_prefix"
                                               name="username_prefix">
                                    </div>
                                </div>
                            </div>
                            <div role="tabpanel" class="tab-pane" id="tab_ldap_servers_other">
                                <div class="form-group row">
                                    <label for="username_prefix"
                                           class="col-sm-5 col-form-label"><?= plugin_lang_get('config_server_edit_server_default_new_user_project'); ?></label>
                                    <div class="col-sm-5">
                                        <select class="form-control" name="default_new_user_project"
                                                id="default_new_user_project">
                                            <?= $project_select_option; ?>
                                        </select>
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
                            </div>
                        </div>
                        <input type="hidden" id="id" name="id">
                        <input type="hidden" id="action" name="action">
                    </form>
                    <div class="message" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="save_server">Save changes</button>
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
                    <div class="message" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button id="delete_server" type="button" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script type="application/javascript">
        let servers_settings = '<?=json_encode(mla_ServerConfig::get_servers_config())?>'; //

        $('#save_general_settings').click(function () {
            let form_data = new FormData($(this).closest("form")[0]);
            mla_post_request(form_data);
        });

        $('.server_action_button').click(function () {
            let action = $(this).data('action');
            let id = $(this).data('id');
            $('#id').val(id);
            $('#action').val(action);

            if (action == "edit_server") {
                let config_options = JSON.parse(servers_settings).filter(e => e.id == id);
                set_form_elem_value('server_settings', config_options[0]);
            } else if (action == "add_server") {
                $('#server_settings').trigger("reset");
            }
        });

        $('#save_server, #delete_server').click(function () {
            let form_data = new FormData($('#server_settings')[0]);
            mla_post_request(form_data);
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