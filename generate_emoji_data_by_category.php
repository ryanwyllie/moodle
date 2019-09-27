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
$emojibycategory = [];
$obsoletes = [];

foreach ($jsondata as $data) {
    $category = $data['category'];
    $unified = $data['unified'];

    if ($category === 'Skin Tones') {
        continue;
    }

    if (!empty($data['obsoleted_by'])) {
        $obsoletes[] = [
            'shortname' => $data['short_name'],
            'by' => $data['obsoleted_by']
        ];
        continue;
    }

    if (!isset($emojibycategory[$category])) {
        $emojibycategory[$category] = [
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

    $emojibycategory[$category]['emojis'][] = [
        'sortorder' => (int) $data['sort_order'],
        'unified' => $unified,
        'shortnames' => [$data['short_name']],
        'skinvariations' => $skinvariations
    ];
}

$emojibycategory = array_values($emojibycategory);
$emojibycategory = array_map(function($category) {
    usort($category['emojis'], function($a, $b) {
        return $a['sortorder'] <=> $b['sortorder'];
    });
    return $category;
}, $emojibycategory);

foreach ($obsoletes as $obsolete) {
    $emojibycategory = array_map(function($category) use ($obsolete) {
        $category['emojis'] = array_map(function($emoji) use ($obsolete) {
            if ($obsolete['by'] == $emoji['unified']) {
                $emoji['shortnames'] = array_merge($emoji['shortnames'], [$obsolete['shortname']]);
            }
            unset($emoji['sortorder']);
            return $emoji;
        }, $category['emojis']);
        return $category;
    }, $emojibycategory);
}

usort($emojibycategory, function($a, $b) use ($categorysortorder) {
    $aindex = array_search($a['name'], $categorysortorder);
    $bindex = array_search($b['name'], $categorysortorder);

    return $aindex <=> $bindex;
});

echo json_encode($emojibycategory, JSON_PRETTY_PRINT);