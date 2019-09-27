<?php

$categorysortorder = [
    'Smileys & People',
    'Animals & Nature',
    'Food & Drink',
    'Travel & Places',
    'Activities',
    'Objects',
    'Symbols',
    'Flags'
];
$content = file_get_contents('./emoji_pretty.json');
$jsondata = json_decode($content, true);

$emojibycategory = array_values(array_reduce($jsondata, function($carry, $data) {
    $category = $data['category'];

    if ($category === 'Skin Tones') {
        return $carry;
    }

    if (!isset($carry[$category])) {
        $carry[$category] = [
            'name' => $category,
            'emojis' => []
        ];
    }

    $skinvariations = [];
    if (!empty($data['skin_variations'])) {
        foreach ($data['skin_variations'] as $key => $value) {
            $skinvariations[$key] = $value['unified'];
        }
    }

    $carry[$category]['emojis'][] = [
        'unified' => $data['unified'],
        'shortname' => $data['short_name'],
        'skinvariations' => $skinvariations
    ];

    return $carry;
}, []));

usort($emojibycategory, function($a, $b) use ($categorysortorder) {
    $aindex = array_search($a['name'], $categorysortorder);
    $bindex = array_search($b['name'], $categorysortorder);

    return $aindex <=> $bindex;
});

echo json_encode($emojibycategory, JSON_PRETTY_PRINT);