<?php

$content = file_get_contents('./emoji_pretty.json');
$jsondata = json_decode($content, true);
$emojibyshortname = array_reduce($jsondata, function($carry, $data) {
    $unified = null;
    $shortname = $data['short_name'];

    if (!empty($data['obsoleted_by'])) {
        $unified = $data['obsoleted_by'];
    } else {
        $unified = $data['unified'];
    }

    $carry[$shortname] = $unified;
    return $carry;
}, []);

echo json_encode($emojibyshortname, JSON_PRETTY_PRINT);