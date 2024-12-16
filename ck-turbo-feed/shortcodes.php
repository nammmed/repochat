<?php
function ck_howto_turbo($atts, $content) {
    extract(shortcode_atts(array(
        'title' => '',
        'menutitle' => '',
        'text' => '',
    ), $atts));

    // Генерируем ID из menutitle или title
    $id_text = !empty($menutitle) ? $menutitle : $title;
    $id_text = do_shortcode($id_text);
    $id_text = preg_replace('/[\x{1F100}-\x{1F1FF}]|[\x{1F300}-\x{1F9FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]|[\x{1F600}-\x{1F64F}]|[\x{1F680}-\x{1F6FF}]|[\x{2300}-\x{23FF}]|[\x{2B00}-\x{2BFF}]|[\x{1F900}-\x{1F9FF}]|[\x{1F018}-\x{1F270}]|[\x{FE00}-\x{FE0F}]/u', '', $id_text);
    $id = ckt_translit(trim($id_text));
    $id = str_replace(array(',', '.', '#', '*', '?', '!'), '', $id);

    // Формируем начало аккордеона
    $output = "<h3 class=\"howto__title\" id=\"$id\">$title</h3>\n";
    if (!empty($text)) {
        $output .= "<p>$text</p>\n";
    }

    // Обрабатываем контент и получаем элементы
    $items = do_shortcode($content);

    // Добавляем элементы, если они есть
    if (!empty($items)) {
        $output .= "<div data-block=\"accordion\">\n";
        $output .= $items;
        $output .= "</div>";
    }

    return $output;
}

function ck_howto_item_turbo($atts, $content = null) {
    extract(shortcode_atts(array(
        'title' => '',
        'text' => '',
    ), $atts));

    $item = "<div data-block=\"item\" data-title=\"$title\">\n";
    $item .= "    <p>$text</p>\n";
    $item .= "</div>";

    return $item; // Возвращаем HTML элемента
}

add_shortcode('ck_howto_turbo', 'ck_howto_turbo');
add_shortcode('ck_howto_item_turbo', 'ck_howto_item_turbo');

function ck_faq_turbo($atts, $content) {
    // Обрабатываем вложенные элементы
    $items = do_shortcode($content);

    // Начинаем аккордеон, только если есть элементы
    if (!empty($items)) {
        $output = '<div data-block="accordion">';
        // Добавляем собранные элементы
        $output .= $items;
        // Закрываем аккордеон
        $output .= '</div>';
        return $output;
    }
    return ''; // Ничего не возвращаем, если нет элементов
}

function ck_faq_item_turbo($atts, $content = null) {
    extract(shortcode_atts(array(
        'question' => '',
        'answer' => '',
    ), $atts));

    // Формируем элемент аккордеона
    $item = "<div data-block=\"item\" data-title=\"$question\">\n";
    $item .= "    <p>$answer</p>\n";
    $item .= "</div>";

    return $item; // Возвращаем HTML элемента
}

add_shortcode('ck_faq_turbo', 'ck_faq_turbo');
add_shortcode('ck_faq_item_turbo', 'ck_faq_item_turbo');

function ck_question_turbo($atts, $content) {
    extract(shortcode_atts(array(
        'title' => '',
        'menutitle' => '',
        'text' => '',
    ), $atts));

    // Генерируем ID из menutitle или title
    $id_text = !empty($menutitle) ? $menutitle : $title;
    $id = ckt_translit(trim($id_text));
    $id = str_replace(array(',', '.', '#', '*', '?', '!'), '', $id);

    // Начинаем блок
    $output = "<h3 class=\"questions__title\" id=\"$id\">$title</h3>\n";
    if (!empty($text)) {
        $output .= "<p>$text</p>\n";
    }

    // Подготовка для сбора элементов
    $items = array();
    $content = do_shortcode($content);

    // Получаем перемешанные вопросы
    global $post;
    $ck_shuffling_question = get_post_field('ck_shuffling_question', $post->ID);
    $ck_shuffling_question = explode(';', $ck_shuffling_question);

    // Извлекаем элементы из контента
    if (preg_match_all('/<div data-block="item" data-title="(.*?)">(.*?)<\/div>/s', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $items[] = array('title' => $match[1], 'content' => $match[2]);
        }
    }

    // Добавляем только первые три вопроса в нужном порядке
    $accordion_content = '';
    $has_items = false;
    for ($x = 0; $x < 3; $x++) {
        if (isset($ck_shuffling_question[$x]) && isset($items[$ck_shuffling_question[$x]])) {
            $item = $items[$ck_shuffling_question[$x]];
            $accordion_content .= "<div data-block=\"item\" data-title=\"" . esc_attr($item['title']) . "\">" . $item['content'] . "</div>\n";
            $has_items = true;
        } elseif (isset($items[$ck_shuffling_question[4]])) {
            $item = $items[$ck_shuffling_question[4]];
            $accordion_content .= "<div data-block=\"item\" data-title=\"" . esc_attr($item['title']) . "\">" . $item['content'] . "</div>\n";
            $has_items = true;
        }
    }

    if ($has_items) {
        $output .= '<div data-block="accordion">';
        $output .= $accordion_content;
        $output .= '</div>';
    }

    return $output;
}

function ck_question_item_turbo($atts, $content = null) {
    global $ck_question_count;
    global $ck_info;

    if (!isset($ck_question_count)) {
        $ck_question_count = 0;
    }
    $ck_question_count++;

    // Вычисляем индекс эксперта с помощью остатка от деления
    $expert_index = ($ck_question_count - 1) % 6; // 6 - количество экспертов (0-5)

    // Получаем имя эксперта
    $expert_name = ck_info('expert_name' . $expert_index);

    $atts = shortcode_atts(array(
        'text' => '',
    ), $atts);

    // Формируем элемент
    $item = "<div data-block=\"item\" data-title=\"$expert_name\">\n";
    $item .= "    <p>{$atts['text']}</p>\n";
    $item .= "</div>";

    return $item;
}

add_shortcode('ck_question_turbo', 'ck_question_turbo');
add_shortcode('ck_question_item_turbo', 'ck_question_item_turbo');

function ck_user_comments_turbo($atts, $content) {
    // Получаем настройки перемешивания и авторов
    global $post;
    $ck_shuffling = get_post_meta($post->ID, 'ck_shuffling', true);
    $ck_page_shuffling = !empty($ck_shuffling) ? explode(';', $ck_shuffling) : array();
    $ck_page_shuffling = array_values(array_filter($ck_page_shuffling, function($n) {
        return $n <= 50;
    }));
    $user_comments_author = explode(';', get_option("ck_comments_author"));

    // Обрабатываем контент для получения комментариев
    $items = do_shortcode($content);

    // Формируем блок комментариев для Турбо-страниц
    $output = '<div data-block="comments" data-url="' . get_permalink() . '">';

    // Добавляем комментарии
    $output .= $items;

    $output .= '</div>';

    return $output;
}

function ck_user_comments_item_turbo($atts, $content = null) {
    static $comment_count = 0;
    $comment_count++;

    $today = date("Y-m-d");
    $timestamp = time();

    extract(shortcode_atts(array(
        'author' => 'Гость',
        'time' => $today,
        'text' => '',
    ), $atts));

    $text = ck_shortcode($text);

    // Вычисляем дату комментария
    $post_time = get_the_date('U', get_the_ID());
    $shuffle_interval = get_option('ck_shuffle_comments_int', 0);
    $comment_timestamp = $post_time + $comment_count * intval($shuffle_interval);
    $comment_date = date_i18n("d.m.Y", $comment_timestamp);

    // Проверяем, нужно ли показывать комментарий
    if ($timestamp > $comment_timestamp) {
        // Автоматическое назначение автора, если включено
        $author_to_use = $author;
        if (defined('CK_COMMENTS_AUTO') && CK_COMMENTS_AUTO == true) {
            global $post;
            $ck_shuffling = get_post_meta($post->ID, 'ck_shuffling', true);
            $ck_page_shuffling = !empty($ck_shuffling) ? explode(';', $ck_shuffling) : array();
            if (isset($ck_page_shuffling[$comment_count - 1]) && isset($GLOBALS['user-comments-author'][$ck_page_shuffling[$comment_count - 1]])) {
                $author_to_use = $GLOBALS['user-comments-author'][$ck_page_shuffling[$comment_count - 1]];
            }
        }

        // Формируем комментарий в формате Турбо-страниц
        $comment = '<div data-block="comment"';
        $comment .= ' data-author="' . esc_attr($author_to_use) . '"';
        $comment .= ' data-subtitle="' . esc_attr($comment_date) . '">';
        $comment .= '<div data-block="content">';
        $comment .= '<p>' . $text . '</p>';
        $comment .= '</div></div>';

        return $comment;
    }

    return ''; // Не отображаем комментарий, если условие не выполнено
}

add_shortcode('ck_user_comments_turbo', 'ck_user_comments_turbo');
add_shortcode('ck_user_comments_item_turbo', 'ck_user_comments_item_turbo');

function ck_content_menu_turbo($atts, $content) {
    global $post;
    $content = str_replace('ck_content_menu', '', $post->post_content);

    // Начинаем оглавление
    $output = '<h3 id="contenttable">Содержание</h3>';
    $output .= '<ol class="content-nav">';

    // Собираем элементы оглавления
    if (preg_match_all('/\[ck_content_h(.*?)\](.*?)\[\/ck_content_h\]/sm', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            // Получаем текст и ID для ссылки
            $text = '';
            $id = '';

            if (preg_match('/text="(.*?)"/sm', $match[0], $varies)) {
                $text = $varies[1];
                $id = ck_to_id($text);
            } elseif (preg_match('/id="(.*?)"/sm', $match[0], $varies)) {
                $id = $varies[1];
                $text = wp_kses($match[2], array());
            } else {
                $text = wp_kses($match[2], array());
                $id = ck_to_id($text);
            }

            // Добавляем элемент оглавления
            $output .= '<li><a href="#' . trim($id) . '">' . trim($text) . '</a></li>';
        }
    }

    $output .= '</ol>';

    return $output;
}

function ck_content_h_turbo($atts, $content) {
    $content = do_shortcode($content);

    if ($content == '') {
        $anchor = ck_to_id($atts['text']);
        return '<a id="' . trim($anchor) . '"></a>';
    }

    if (!empty($atts['id'])) {
        return '<h3 id="' . trim($atts['id']) . '">' . $content . '</h3>';
    }

    if (!empty($atts['text'])) {
        $anchor = ck_to_id($atts['text']);
        return '<h3 id="' . trim($anchor) . '">' . $content . '</h3>';
    }

    $anchor = ck_to_id(wp_kses($content, array()));
    return '<h3 id="' . trim($anchor) . '">' . $content . '</h3>';
}

add_shortcode('ck_content_menu_turbo', 'ck_content_menu_turbo');
add_shortcode('ck_content_h_turbo', 'ck_content_h_turbo');

function ck_desc_turbo($atts, $content) {
    global $post;

    // Подсчет комментариев
    $comments_count = substr_count($post->post_content, 'ck_user_comments_item');
    $time = get_the_date('U', get_the_id());
    $comments_count_bytime = floor((time() - $time) / get_option('ck_shuffle_comments_int'));
    if ($comments_count_bytime <= $comments_count) {
        $comments_count = $comments_count_bytime;
    }
    if ($comments_count == 0) {
        $comments_count = 1;
    }

    // Время чтения
    $time = round(mb_strlen(get_the_content()) / 1000);
    if ($time % 10 == 1 && $time % 100 != 11) {
        $time_text = $time . ' ' . ck_get_translate('desc_minutes_form1');
    } elseif ($time % 10 >= 2 && $time % 10 <= 4 && ($time % 100 <= 4 || $time % 100 >= 20)) {
        $time_text = $time . ' ' . ck_get_translate('desc_minutes_form2');
    } else {
        $time_text = $time . ' ' . ck_get_translate('desc_minutes_form3');
    }

    $output = '<div class="article-data">';
    $output .= '<div class="article-data__item article-data__item_update">↻ ' .
        ck_get_translate('desc_update_date') . ': ' .
        date(ck_get_translate('desc_date_format'), get_option('ck_shuffle_time')-get_option('ck_shuffle_int')) .
        '</div>';
    $output .= '<div class="article-data__item article-data__item_date">≣ ' .
        ck_get_translate('desc_publish_date') . ': ' .
        get_the_date(ck_get_translate('desc_date_format')) .
        '</div>';
    $output .= '<div class="article-data__item article-data__item_comments">✎ ' .
        $comments_count . ' ' .
        getCommentDeclension($comments_count) .
        '</div>';
    $output .= '<div class="article-data__item article-data__item_time">◷ ' .
        $time_text . ' ' .
        ck_get_translate('desc_reading_time') .
        '</div>';
    $output .= '</div>';

    if (!empty($content)) {
        $output .= '<p>' . do_shortcode($content) . '</p>';
        $output .= '<div class="author-review__about">';
        $output .= '<div class="author-review__note">Автор обзора: ' . ck_info('author_name') . '</div>';
        $output .= '</div>';
        $output .= '<div class="author-review__desc">' . ck_get_translate('desc_author_text') . '</div>';
    }

    return $output;
}

function ck_desc_short_turbo($atts, $content) {
    // Используем тот же код, что и в полной версии, но без информации об авторе
    return ck_desc_turbo($atts, '');
}

add_shortcode('ck_desc_turbo', 'ck_desc_turbo');
add_shortcode('ck_desc_short_turbo', 'ck_desc_short_turbo');