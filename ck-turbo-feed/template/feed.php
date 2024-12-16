<?php header('Content-Type: text/xml'); ?>
<rss
        xmlns:yandex="http://news.yandex.ru"
        xmlns:media="http://search.yahoo.com/mrss/"
        xmlns:turbo="http://turbo.yandex.ru"
        version="2.0">
    <channel>
        <title><?= get_bloginfo_rss('name') ?></title>
        <link><?= get_bloginfo_rss('url') ?></link>
        <description><?= get_bloginfo_rss('description') ?></description>
        <turbo:analytics id="<?= get_option('goal_id'); ?>" type="Yandex"></turbo:analytics>
        <language>ru</language>

        <?php if ($query->have_posts()) : ?>
            <?php while ($query->have_posts()) : $query->the_post(); ?>
                <?php include plugin_dir_path(__FILE__) . '../template/feed-content.php'; ?>
            <?php endwhile; ?>
        <?php endif; ?>

    </channel>
</rss>
