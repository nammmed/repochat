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

function ck_turbo_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['ck_turbo_post_types'], $_POST['goal_id'], $_POST['post_selection'], $_POST['feed_slug'])) {
        update_option('ck_turbo_post_types', $_POST['ck_turbo_post_types']);
        update_option('goal_id', $_POST['goal_id']);
        update_option('post_selection', $_POST['post_selection']);
        update_option('ck_turbo_feed_slug', sanitize_title($_POST['feed_slug']));
        flush_rewrite_rules();
    }

    $saved_post_types = get_option('ck_turbo_post_types', ['post', 'page']);
    $saved_goal_id = get_option('goal_id', '');
    $saved_post_selection = get_option('post_selection', 'all');
    $post_types = get_post_types(['public' => true]);
    $saved_feed_slug = get_option('ck_turbo_feed_slug', 'turbo-feed2');

    // Получаем текущий домен и формируем ссылку на Яндекс.Вебмастер
    $site_url = get_site_url();
    $parsed_url = parse_url($site_url); // Парсим URL
    $domain = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    $webmaster_url = 'https://webmaster.yandex.ru/site/https:' . $domain . ':443/turbo/settings/css/';

    // Проверяем статус копирования файла
    $copy_failed = get_option('ck_turbo_file_copy_failed', false);
    $theme_path = get_stylesheet_directory();
    $destination = $theme_path . '/tables/turbo.php';
    $source = plugin_dir_path(__FILE__) . 'files/turbo.php';

    // Читаем содержимое CSS файла
    $css_file = plugin_dir_path(__FILE__) . 'files/turbo.css';
    $css_content = file_exists($css_file) ? file_get_contents($css_file) : '';

    ?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>

        <?php if ($copy_failed || !file_exists($destination)): ?>
            <div class="notice notice-error">
                <p>Не удалось автоматически скопировать файл turbo.php. Вы можете создать его вручную:</p>
                <p><strong>Путь к файлу:</strong><br>
                    <code><?php echo $destination; ?></code></p>
            </div>

            <h3>Содержимое файла turbo.php:</h3>
            <textarea readonly style="width: 100%; height: 300px; font-family: monospace; white-space: pre; overflow: auto;"><?php
                echo htmlspecialchars(file_get_contents($source));
                ?></textarea>
        <?php endif; ?>

        <form action="" method="post">
            <table class="form-table">
                <tr>
                    <th scope="row">Выбор записей</th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio" name="post_selection" value="all"
                                    <?= $saved_post_selection == 'all' ? 'checked' : ''; ?>>
                                Все записи
                            </label><br>
                            <label>
                                <input type="radio" name="post_selection" value="marked"
                                    <?= $saved_post_selection == 'marked' ? 'checked' : ''; ?>>
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
                                           value="<?= $post_type; ?>"
                                        <?= in_array($post_type, $saved_post_types) ? 'checked' : ''; ?>>
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
                        <input type="text" id="goal_id" name="goal_id"
                               value="<?= $saved_goal_id; ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="feed_slug">URL фида</label>
                    </th>
                    <td>
                        <input type="text" id="feed_slug" name="feed_slug"
                               value="<?php echo esc_attr($saved_feed_slug); ?>" class="regular-text">
                        <p class="description">
                            Фид доступен по адресу: <?php echo esc_html(get_site_url() . '/' . $saved_feed_slug . '.xml'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label>Стили Turbo-страниц</label><br>
                        <a href="<?php echo esc_url($webmaster_url); ?>" target="_blank"
                           class="button button-secondary" style="margin-top:5px">
                            Редактировать в Я.Вебмастер
                        </a>
                    </th>
                    <td>
                        <textarea readonly style="width: 100%; height: 200px; font-family: monospace;"><?php
                            echo esc_textarea($css_content);
                            ?></textarea>
                    </td>
                </tr>
                <?php do_action('ck_turbo_settings_after_form'); ?>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
?>