<?php
/*
Plugin Name: CK Turbo Feed
Description: Создает Yandex Turbo Feed для выбранных типов постов.
Version: 1.0
Author: prostoweb.su
*/
include plugin_dir_path(__FILE__) . 'settings.php';
include plugin_dir_path(__FILE__) . 'shortcodes.php';
include plugin_dir_path(__FILE__) . 'functions.php';
include plugin_dir_path(__FILE__) . 'preview.php';

function ck_turbo_feed_activation() {
    // Получаем пути
    $theme_path = get_stylesheet_directory();
    $tables_dir = $theme_path . '/tables';
    $plugin_dir = dirname(__FILE__);
    $source = $plugin_dir . '/files/turbo.php';
    $destination = $tables_dir . '/turbo.php';

    // Пробуем создать директорию
    if (!file_exists($tables_dir)) {
        $dir_created = @mkdir($tables_dir, 0755, true);
    }

    // Пробуем скопировать файл
    if (file_exists($source)) {
        $copy_result = @copy($source, $destination);

        if (!$copy_result) {
            // Сохраняем информацию о неудачной попытке
            update_option('ck_turbo_file_copy_failed', true);
        }
    } else {
        update_option('ck_turbo_file_copy_failed', true);
    }

    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'ck_turbo_feed_activation');

function ck_turbo_feed() {
    $feed_slug = get_option('ck_turbo_feed_slug', 'turbo-feed2');
    add_feed($feed_slug, 'render_turbo_feed');
    add_rewrite_rule($feed_slug . '.xml$', 'index.php?feed=' . $feed_slug, 'top');
}
add_action('init', 'ck_turbo_feed');

function render_turbo_feed() {
    $feed_slug = get_option('ck_turbo_feed_slug', 'turbo-feed2');
    $cache_key = 'ck_turbo_feed_cache_' . $feed_slug;
    $cache_duration = 3600; // Время жизни кэша в секундах

    // Устанавливаем заголовок Content-Type для XML
    header('Content-Type: application/rss+xml; charset=' . get_option('blog_charset'), true);

    // Проверяем, есть ли сохранённый результат в кэше
    //$cached_feed = get_transient($cache_key);

    if ($cached_feed) {
        // Если кэш существует, выводим его и завершаем выполнение
        echo $cached_feed;
        return;
    }

    // Если кэша нет, генерируем фид
    $post_types = get_option('ck_turbo_post_types', ['post', 'page']);
    $post_selection = get_option('post_selection', 'all');
    $args = [
        'post_type' => $post_types,
        'post_status' => 'publish',
        'posts_per_page' => -1
    ];
    if ($post_selection == 'marked') {
        $args['meta_query'] = [
            [
                'key' => 'yandex_turbo_status',
                'value' => 1,
            ]
        ];
    }

    $query = new WP_Query($args);

    // Буферизация вывода
    ob_start();
    include plugin_dir_path(__FILE__) . 'template/feed.php';
    $feed_output = ob_get_clean();

    // Сохраняем результат в кэш
    set_transient($cache_key, $feed_output, $cache_duration);

    // Выводим фид
    echo $feed_output;

    wp_reset_postdata();
}