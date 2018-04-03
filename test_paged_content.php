<?php

require_once('./config.php');

require_login(0, false);
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/test_paged_content.php', []);
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Test paged content");
$PAGE->set_heading("Paged content testing page");

$context = [];
$pagingbarcontext = [
    'pagingbar' => [
        'itemsperpage' => 10,
        'previous' => true,
        'next' => true,
        'first' => true,
        'last' => true,
        'pages' => [
            [
                'page' => '1',
                'active' => true
            ],
            [
                'page' => '2'
            ],
            [
                'page' => '3'
            ]
        ]
    ],
    'pages' => []
];

for ($i = 1; $i <= 3; $i++) {
    $pagecontext = [
        'page' => $i,
        'content' => '<div class="card"><div class="card-body">'
    ];

    if ($i == 1) {
        $pagecontext['active'] = true;
    }

    for ($j = 1; $j <= 10; $j++) {
        $pagecontext['content'] .= sprintf("<p>Page %d item %d</p>\n", $i, $j);
    }

    $pagecontext['content'] .= '</div></div>';

    array_push($pagingbarcontext['pages'], $pagecontext);
}

$pagingdropdowncontext = [
    'pagingdropdown' => [
        'options' => [
            [
                'itemcount' => 5,
                'content' => '5',
                'active' => true
            ],
            [
                'itemcount' => 5,
                'content' => '10'
            ],
            [
                'itemcount' => 10,
                'content' => '20'
            ]
        ]
    ],
    'pages' => []
];

for ($i = 1; $i <= 4; $i++) {
    $pagecontext = [
        'page' => $i,
        'content' => '<div class="card"><div class="card-body">'
    ];

    if ($i == 1) {
        $pagecontext['active'] = true;
    }

    if ($i >= 3) {
        $count = 10;
    } else {
        $count = 5;
    }

    for ($j = 1; $j <= $count; $j++) {
        $pagecontext['content'] .= sprintf("<p>Page %d item %d</p>\n", $i, $j);
    }

    $pagecontext['content'] .= '</div></div>';

    array_push($pagingdropdowncontext['pages'], $pagecontext);
}

$context['serverpagingbar'] = $pagingbarcontext;
$context['serverpagingbarcontext'] = sprintf("%s", print_r($pagingbarcontext, true));
$context['serverpagingdropdown'] = $pagingdropdowncontext;
$context['serverpagingdropdowncontext'] = sprintf("%s", print_r($pagingdropdowncontext, true));

echo $OUTPUT->header();
echo '<div class="container">';
echo $OUTPUT->render_from_template('core/test_server_render_paging_bar', [
    'serverpagingbar' => $pagingbarcontext,
    'serverpagingbarcontext' => sprintf("%s", print_r($pagingbarcontext, true))
]);
echo $OUTPUT->render_from_template('core/test_client_render_paging_bar_static_list', []);
echo $OUTPUT->render_from_template('core/test_client_render_paging_bar_async', []);
echo $OUTPUT->render_from_template('core/test_client_render_paging_bar_async_unknown_pages_single_limit', []);
echo $OUTPUT->render_from_template('core/test_client_render_paging_bar_async_unknown_pages_variable_limit', []);
echo $OUTPUT->render_from_template('core/test_client_render_paging_bar_async_unknown_pages_variable_limit_bottom', []);
echo $OUTPUT->render_from_template('core/test_server_render_paging_dropdown', [
    'serverpagingdropdown' => $pagingdropdowncontext,
    'serverpagingdropdowncontext' => sprintf("%s", print_r($pagingdropdowncontext, true))
]);
echo $OUTPUT->render_from_template('core/test_client_render_paging_dropdown_static_list', []);
echo $OUTPUT->render_from_template('core/test_client_render_paging_dropdown_async', []);
echo $OUTPUT->render_from_template('core/test_client_render_paging_dropdown_async_unknown_pages_static_limit', []);
echo $OUTPUT->render_from_template('core/test_client_render_paging_dropdown_async_unknown_pages_static_limit_max_pages', []);
echo $OUTPUT->render_from_template('core/test_client_render_paging_dropdown_async_unknown_pages_variable_limit', []);
echo '</div>';
echo $OUTPUT->footer();
