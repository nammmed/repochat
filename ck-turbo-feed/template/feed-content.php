<?php
$content=get_the_content();
$content=str_replace('theme="main"', 'theme="main_ru"', $content);
$content=str_replace('[ck_casinolist ', '[casino-table id="main_ru" theme="main" ', $content);
// Очищаем контент от эмодзи
$content = preg_replace('/[\x{1F100}-\x{1F1FF}]|[\x{1F300}-\x{1F9FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]|[\x{1F600}-\x{1F64F}]|[\x{1F680}-\x{1F6FF}]|[\x{2300}-\x{23FF}]|[\x{2B00}-\x{2BFF}]|[\x{1F900}-\x{1F9FF}]|[\x{1F018}-\x{1F270}]|[\x{FE00}-\x{FE0F}]/u', '', $content);
//echo $content;

//превращаем ck_faq в турбо-аккордеон
$content = str_replace('[ck_faq]', '<div data-block="accordion">', $content);
$content = str_replace('[/ck_faq]', '</div>', $content);
$pattern = '/\[ck_faq_item question="(.*?)" answer="(.*?)"\]/i';
$replacement = '<div data-block="item" data-title="$1"><p>$2</p></div>';
$content = preg_replace($pattern, $replacement, $content);

//заменяем шорткод комментариев на турбо
$content = str_replace('[ck_user_comments]', '[ck_user_comments_turbo]', $content);
$content = str_replace('[/ck_user_comments]', '[/ck_user_comments_turbo]', $content);





//добавляем заголовок h3 внутрь [ck_content_h], если внутри только таблица
$content = addH3ToTables($content);

//продолжаем как ни в чем не бывало
$content=do_shortcode($content);

//еще раз очищаем от эмодзи
$content = preg_replace('/[\x{1F100}-\x{1F1FF}]|[\x{1F300}-\x{1F9FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]|[\x{1F600}-\x{1F64F}]|[\x{1F680}-\x{1F6FF}]|[\x{2300}-\x{23FF}]|[\x{2B00}-\x{2BFF}]|[\x{1F900}-\x{1F9FF}]|[\x{1F018}-\x{1F270}]|[\x{FE00}-\x{FE0F}]/u', '', $content);

//делаем h3 вместо div в ck_questions
$pattern = '/<div class="questions__title"(?: id="([^"]+)")?>([^<]+)<\/div>/'; // Изменено регулярное выражение
$replacement = function($matches) {
    $id = !empty($matches[1]) ? $matches[1] : ckt_translit($matches[2]); // Генерируем id, если он не указан
    return '<h3 class="questions__title" id="' . $id . '">' . $matches[2] . '</h3>';
};
$content = preg_replace_callback($pattern, $replacement, $content);

// **Добавляем id="contenttable" к заголовку содержания**
$content = str_replace('<ol class="content-nav">', '<h3 id="contenttable">Содержание</h3><ol class="content-nav">', $content);



// Удаляем id="suggestedAnswer"
$content = str_replace('id="suggestedAnswer"', '', $content);

$title = get_the_title_rss();
if (!function_exists('clearEmoji')) {
    function clearEmoji($title) {
        $title = preg_replace('/[\x{1F100}-\x{1F1FF}]|[\x{1F300}-\x{1F9FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]|[\x{1F600}-\x{1F64F}]|[\x{1F680}-\x{1F6FF}]|[\x{2300}-\x{23FF}]|[\x{2B00}-\x{2BFF}]|[\x{1F900}-\x{1F9FF}]|[\x{1F018}-\x{1F270}]|[\x{FE00}-\x{FE0F}]/u', '', $title);
        $title = preg_replace('/\s+/', ' ', $title);
        return trim($title);
    }
}
$title = clearEmoji($title);

$content=str_replace('https://new.', 'https://', $content);
//удаляем задвоение анкоров в текстом рейтинге
$content=str_replace('</span><span class="m-col-link-text">Ссылка</span>', '</span>', $content);

$item_title = get_the_title();
$item_title = clearEmoji($item_title);

ck_update_shuffle(get_the_ID());
?><item turbo="true">
    <title><?= $title; ?></title>
    <turbo:extendedHtml>true</turbo:extendedHtml>
    <turbo:goal type="yandex" turbo-goal-id="goal-link" name="click" id="<?= get_option('goal_id'); ?>" />
    <link><?php echo str_replace('https://new.', 'https://', get_permalink()); ?></link>
    <turbo:content><header><h1><?php echo $item_title; ?></h1> </header><![CDATA[ <?php echo $content; ?> ]]></turbo:content>
</item>