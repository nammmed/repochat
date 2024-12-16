<?php
function addH3ToTables($content) {
    $parts = explode('[ck_content_h', $content);

    foreach ($parts as $i => $part) {
        if ($i > 0) {
            $textStartPos = strpos($part, 'text="') + 6;
            $textEndPos = strpos($part, '"', $textStartPos);
            $text = substr($part, $textStartPos, $textEndPos - $textStartPos);

            $tagStartPos = strpos($part, '<');
            $tagEndPos = strpos($part, '>', $tagStartPos);
            $tag = substr($part, $tagStartPos, $tagEndPos - $tagStartPos + 1);

            if (strpos($tag, '<table') === 0) {
                $part = substr($part, 0, $tagStartPos) . "<h3>$text</h3>" . substr($part, $tagStartPos);
            }

            $parts[$i] = $part;
        }
    }
    $content = implode('[ck_content_h', $parts);

    return $content;
}

function ckt_translit($str)
{
    $str = str_replace(" ", "-", $str);
    $rus = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я');
    $lat = array('A', 'B', 'V', 'G', 'D', 'E', 'E', 'Zh', 'Z', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Shh', 'Y', 'Y', '', 'E', 'Ju', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'zh', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'shh', 'y', 'y', '', 'e', 'ju', 'ya');

    /*$rus = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я');
    $lat = array('A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'I', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', '', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'i', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', '', 'e', 'yu', 'ya');*/
    return mb_strtolower(str_replace($rus, $lat, $str));
}


function getCommentDeclension(int $number): string {
    // Берем абсолютное значение числа (на случай, если число отрицательное)
    $number = abs($number);

    // Получаем остаток от деления на 100
    $lastTwoDigits = $number % 100;

    // Если число заканчивается на 11-19, используем форму "комментариев"
    if ($lastTwoDigits >= 11 && $lastTwoDigits <= 19) {
        return "комментариев";
    }

    // Получаем последнюю цифру числа
    $lastDigit = $number % 10;

    // Определяем правильное окончание
    if ($lastDigit === 1) {
        return "комментарий";
    } elseif ($lastDigit >= 2 && $lastDigit <= 4) {
        return "комментария";
    } else {
        return "комментариев";
    }
}

function clearEmoji($text) {
    return trim(preg_replace('/([^\p{L}\p{N}\p{P}\p{Z}\p{M}])|([\x{200d}\x{fe0f}])|([\x{0030}-\x{0039}]\x{fe0f}?[\x{20e3}])/u', '', $text));
}