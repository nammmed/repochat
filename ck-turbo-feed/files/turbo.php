<?php
if ($is_header):
?>
    <table>
        <tbody>
<?php
endif; 

$rating = round(5 - 1.5 * ($number - 1) / $this::$settings['count'], 1);
$bonus = getCASINObonus($casino['bonus_set'], 'Unique');
$promo = ck_promocodes($casino['alias'], '');
$golink = ck_info('go_url') . '/' . $casino['alias'] . '.html';
?>
<tr>
    <td>
        <a href="#" class="table-logo casino-<?php echo $casino['alias']; ?>">
            <img src="<?= ck_info('logo_url') . $casino['alias']; ?>.png" width="100" height="60" alt="<?= $casino['name_ru']; ?>">
        </a>
        <div class="table-rating">
            <b>Рейтинг:</b> <?= number_format($rating, 1, '.', ''); ?> ⭐
        </div>
    </td>
    <td>
        <div class="table-bonus">
            <b>Бонус:</b> 
            <?php if ($bonus = getCASINObonus($casino['bonus_set'], 'Unique')): ?>
                <span class="bonus-green"><?= $bonus['num_of_bonus'] ?> ₽</span>
                <div class="bonus-details">
                    до <?= $bonus['match_bonus'] ?>% <?= ck_get_translate('promo_depo'); ?>
                    <?php if (!empty($bonus['free_spins'])): ?>
                        + <?= $bonus['free_spins']; ?> <?= getWordForms($bonus['free_spins'], 
                            ck_get_translate('promo_freespins1'), 
                            ck_get_translate('promo_freespins2'), 
                            ck_get_translate('promo_freespins3')); ?>
                    <?php endif; ?>
                </div>
            <?php elseif ($bonus = getCASINObonus($casino['bonus_set'], '1stdep')): ?>
                <span class="bonus-green"><?= $bonus['num_of_bonus'] ?> ₽</span>
                <div class="bonus-details">
                    до <?= $bonus['match_bonus'] ?>% <?= ck_get_translate('promo_depo'); ?>
                    <?php if (!empty($bonus['free_spins'])): ?>
                        + <?= $bonus['free_spins']; ?> <?= getWordForms($bonus['free_spins'], 
                            ck_get_translate('promo_freespins1'), 
                            ck_get_translate('promo_freespins2'), 
                            ck_get_translate('promo_freespins3')); ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?= ck_translate('promo_no_bonus'); ?>
            <?php endif; ?>
        </div>
        <?= $promo ? $promo : ck_translate('promo_no_code'); ?>
    </td>
</tr>
<tr>
    <td colspan="2">
        <button
            formaction="<?= $golink; ?>"
            data-background-color="blue"
            data-color="white"
            data-turbo="false"
            data-primary="true"
        >
            Начать игру
        </button>
    </td>
</tr>
<?php

if ($is_footer):
?>
        </tbody>
    </table>
<?php
endif;
?>