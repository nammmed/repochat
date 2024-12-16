<?php
// Добавляем HTML и CSS в админку
function add_turbo_preview_styles() {
    ?>
    <style>
        .turbo-modal {
            display: none;
            position: fixed;
            z-index: 999999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
        }
        .turbo-modal-content {
            position: relative;
            background-color: #1a1a1a;
            margin: 20px auto;
            padding: 20px;
            width: 375px; /* iPhone размер */
            height: 812px; /* iPhone размер */
            border-radius: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }
        .turbo-frame {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 30px;
            background: #fff;
        }
        .turbo-close {
            position: absolute;
            right: -40px;
            top: 0;
            color: #fff;
            font-size: 28px;
            cursor: pointer;
        }
        .device-notch {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            height: 30px;
            background: #1a1a1a;
            border-bottom-left-radius: 15px;
            border-bottom-right-radius: 15px;
            z-index: 1000;
        }
    </style>
    <?php
}
add_action('admin_head', 'add_turbo_preview_styles');

// Добавляем кнопку предпросмотра в настройки
function add_turbo_preview_button_to_settings() {
    ?>
    <tr>
        <th scope="row">Предпросмотр Турбо-страницы</th>
        <td>
            <button type="button" id="show-turbo-preview" class="button button-secondary">
                Открыть предпросмотр
            </button>

            <div id="turbo-modal" class="turbo-modal">
                <div class="turbo-modal-content">
                    <div class="device-notch"></div>
                    <span class="turbo-close">&times;</span>
                    <iframe id="turbo-frame" class="turbo-frame"></iframe>
                </div>
            </div>

            <script>
                jQuery(document).ready(function($) {
                    var modal = $('#turbo-modal');
                    var btn = $('#show-turbo-preview');
                    var span = $('.turbo-close');

                    btn.click(function() {
                        // Получаем первую запись из фида
                        $.ajax({
                            url: ajaxurl,
                            data: {
                                'action': 'get_first_turbo_url'
                            },
                            success: function(url) {
                                if(url) {
                                    var turboUrl = 'https://yandex.ru/turbo?turbo_preview=true&text=' + encodeURIComponent(url);
                                    $('#turbo-frame').attr('src', turboUrl);
                                    modal.show();
                                } else {
                                    alert('Не удалось получить URL для предпросмотра');
                                }
                            }
                        });
                    });

                    span.click(function() {
                        modal.hide();
                    });

                    $(window).click(function(e) {
                        if($(e.target).is(modal)) {
                            modal.hide();
                        }
                    });
                });
            </script>
        </td>
    </tr>
    <?php
}
add_action('ck_turbo_settings_after_form', 'add_turbo_preview_button_to_settings');

// Добавляем AJAX-обработчик для получения первого URL из фида
function get_first_turbo_url() {
    $post_types = get_option('ck_turbo_post_types', ['post', 'page']);
    $post_selection = get_option('post_selection', 'all');

    $args = [
        'post_type' => $post_types,
        'post_status' => 'publish',
        'posts_per_page' => 1
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

    if ($query->have_posts()) {
        $query->the_post();
        echo get_permalink();
    } else {
        echo '';
    }

    wp_die();
}
add_action('wp_ajax_get_first_turbo_url', 'get_first_turbo_url');