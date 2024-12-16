<?php
$content = get_the_content();

// Базовые замены
$content = str_replace('[ck_content_up]', '', $content);
$content = str_replace('theme="main"', 'theme="turbo"', $content);
$content = str_replace('[ck_casinolist ', '[casino-table id="main_ru" theme="turbo" ', $content);

// Очищаем контент от эмодзи
$content = preg_replace('/[\x{1F100}-\x{1F1FF}]|[\x{1F300}-\x{1F9FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]|[\x{1F600}-\x{1F64F}]|[\x{1F680}-\x{1F6FF}]|[\x{2300}-\x{23FF}]|[\x{2B00}-\x{2BFF}]|[\x{1F900}-\x{1F9FF}]|[\x{1F018}-\x{1F270}]|[\x{FE00}-\x{FE0F}]/u', '', $content);

// Замена шорткодов на турбо-версии
// FAQ
$content = str_replace('[ck_faq]', '[ck_faq_turbo]', $content);
$content = str_replace('[/ck_faq]', '[/ck_faq_turbo]', $content);
$content = preg_replace(
    '/\[ck_faq_item(\s+[^\]]*)\]/',
    '[ck_faq_item_turbo$1]',
    $content
);

// HowTo
$content = preg_replace(
    '/\[ck_howto(\s+[^\]]*)\]/',
    '[ck_howto_turbo$1]',
    $content
);
$content = preg_replace(
    '/\[ck_howto_item(\s+[^\]]*)\]/',
    '[ck_howto_item_turbo$1]',
    $content
);
$content = str_replace('[/ck_howto]', '[/ck_howto_turbo]', $content);

// Questions
$content = preg_replace(
    '/\[ck_question(\s+[^\]]*)\]/',
    '[ck_question_turbo$1]',
    $content
);
$content = preg_replace(
    '/\[ck_question_item(\s+[^\]]*)\]/',
    '[ck_question_item_turbo$1]',
    $content
);
$content = str_replace('[/ck_question]', '[/ck_question_turbo]', $content);

// Comments
$content = str_replace('[ck_user_comments]', '[ck_user_comments_turbo]', $content);
$content = str_replace('[/ck_user_comments]', '[/ck_user_comments_turbo]', $content);
$content = preg_replace(
    '/\[ck_user_comments_item(\s+[^\]]*)\]/',
    '[ck_user_comments_item_turbo$1]',
    $content
);

// Content Menu
$content = str_replace('[ck_content_menu]', '[ck_content_menu_turbo]', $content);
$content = preg_replace(
    '/\[ck_content_h(\s+[^\]]*)\]/',
    '[ck_content_h_turbo$1]',
    $content
);
$content = str_replace('[/ck_content_h]', '[/ck_content_h_turbo]', $content);

// Description
$content = str_replace('[ck_desc', '[ck_desc_turbo', $content);
$content = str_replace('[/ck_desc', '[/ck_desc_turbo', $content);
$content = str_replace('[ck_desc_short', '[ck_desc_short_turbo', $content);
$content = str_replace('[/ck_desc_short', '[/ck_desc_short_turbo', $content);

// Дополнительные замены
$content = str_replace('https://new.', 'https://', $content);
$content = str_replace('</span><span class="m-col-link-text">Ссылка</span>', '</span>', $content);

// Выполняем шорткоды
$content = do_shortcode($content);

// Заменяем год, где он почему-то указан без []
$content = str_replace('ck_year', date('Y'), $content);
$content = str_replace('[ck_related_last]', '', $content);

// Подготавливаем заголовок
$item_title = get_the_title();
$item_title = clearEmoji($item_title);
?>
<item turbo="true">
    <title><?= $item_title; ?></title>
    <turbo:extendedHtml>true</turbo:extendedHtml>
    <turbo:goal type="yandex" turbo-goal-id="goal-link" name="click" id="<?= get_option('goal_id'); ?>" />
    <link><?php echo str_replace('https://new.', 'https://', get_permalink()); ?></link>
    <turbo:content>
        <header>
            <h1><?php echo $item_title; ?></h1>
        </header>
        <![CDATA[
        <?php echo $content; ?>
        ]]>
    </turbo:content>
</item>