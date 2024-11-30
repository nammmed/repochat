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

function ck_turbo_feed_activation() {
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'ck_turbo_feed_activation');


function ck_turbo_feed() {
    add_feed('turbo-feed2', 'render_turbo_feed');
    add_rewrite_rule('turbo-feed2.xml$', 'index.php?feed=turbo-feed2', 'top');
}
add_action('init', 'ck_turbo_feed');

function render_turbo_feed() {
    $post_types = get_option('ck_turbo_post_types', ['post', 'page']);
    $post_selection = get_option('post_selection', 'all');
    $args = [
        'post_type' => $post_types,
        'post_status' => 'publish',
        'posts_per_page' => 5
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

    include plugin_dir_path(__FILE__) . 'template/feed.php';

    wp_reset_postdata();
}