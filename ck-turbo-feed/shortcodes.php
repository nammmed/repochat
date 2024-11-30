<?php
add_shortcode( 'ck_user_comments_turbo', 'ck_user_comments_turbo' );

function ck_user_comments_turbo($atts, $content) {
    $GLOBALS['ck-page-shuffling'] = explode(';', get_field("ck_shuffling", get_the_ID()));
    $GLOBALS['ck-page-shuffling'] = array_values(array_filter($GLOBALS['ck-page-shuffling'], function($n) { return $n<=50; }));

    $GLOBALS['user-comments-author'] = explode(';', get_option("ck_comments_author"));

    $today = date('d.m.Y');
    $post_date = get_the_date('d.m.Y', get_the_ID());

    do_shortcode($content);

    $output = '<div data-block="comments" data-url="'.get_permalink().'">';

    $all_comments = [];

    $comments = get_comments([
        'post_id' => get_the_ID(),
        'status' => 'approve',
    ]);

    $comments = array_reverse($comments);

    foreach( $comments as $comment ){
        $all_comments[] = [
            'author' => $comment->comment_author,
            'date' => get_comment_date('d.m.Y', $comment->comment_ID),
            'text' => $comment->comment_content,
            'admin' => $comment->user_id > 0 ? 1 : 0,
            'temp_mark' => true,
        ];
    }

    $undated_count = 0;

    foreach ( $GLOBALS['ck_user_comments'] as $ck_user_comment ) {
        if (strtotime($today) >= strtotime($ck_user_comment['date'])) {
            $comment_date = $ck_user_comment['date'];
            if (!$ck_user_comment['date']) {
                $undated_count++;
                $comment_date = date('d.m.Y', strtotime($post_date) + $undated_count * get_option('ck_shuffle_int'));
            }

            $all_comments[] = [
                'author' => $ck_user_comment['author'],
                'date' => $comment_date,
                'text' => $ck_user_comment['text'],
                'admin' => $ck_user_comment['admin'],
                'temp_mark' => false, ////////////////////////////////
            ];
        }
    }

    usort($all_comments, function ($a, $b) {
        if ($a["date"] == $b["date"]) {
            return 0;
        }
        return (strtotime($a["date"]) < strtotime($b["date"])) ? -1 : 1;
    });

    $all_comments = array_reverse($all_comments);

    foreach( $all_comments as $item ){
        $output .= '<div data-block="comment" data-author="'.$item['author'].'" data-subtitle="'.$item['date'].'"><div data-block="content"><p>'.$item['text'].'</p></div></div>';
    }

    $output .= '</div>';

    return $output;
}