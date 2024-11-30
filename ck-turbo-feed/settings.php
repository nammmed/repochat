<?php
function ck_turbo_settings_page()
{
    add_options_page(
        'Настройки CK Turbo Feed',
        'CK Turbo Feed',
        'manage_options',
        'ck-turbo-feed',
        'ck_turbo_settings_page_html'
    );
}

add_action('admin_menu', 'ck_turbo_settings_page');

function ck_turbo_settings_page_html()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['ck_turbo_post_types'], $_POST['goal_id'], $_POST['post_selection'])) {
        update_option('ck_turbo_post_types', $_POST['ck_turbo_post_types']);
        update_option('goal_id', $_POST['goal_id']);
        update_option('post_selection', $_POST['post_selection']);
    }

    $saved_post_types = get_option('ck_turbo_post_types', ['post', 'page']);
    $saved_goal_id = get_option('goal_id', '');
    $saved_post_selection = get_option('post_selection', 'all');

    $post_types = get_post_types(['public' => true]);

    ?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>
        <form action="" method="post">
            <table class="form-table">
                <tr>
                    <th scope="row">Выбор записей</th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio" name="post_selection" value="all" <?= $saved_post_selection == 'all' ? 'checked' : ''; ?>>
                                Все записи
                            </label><br>
                            <label>
                                <input type="radio" name="post_selection" value="marked" <?= $saved_post_selection == 'marked' ? 'checked' : ''; ?>>
                                Только отмеченные
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Типы записей</th>
                    <td>
                        <?php foreach ($post_types as $post_type): ?>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="ck_turbo_post_types[]"
                                           value="<?= $post_type; ?>" <?= in_array($post_type, $saved_post_types) ? 'checked' : ''; ?>>
                                    <?= $post_type; ?>
                                </label>
                            </fieldset>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="goal_id">Yandex ID</label>
                    </th>
                    <td>
                        <input type="text" id="goal_id" name="goal_id" value="<?= $saved_goal_id; ?>" class="regular-text">
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

?>