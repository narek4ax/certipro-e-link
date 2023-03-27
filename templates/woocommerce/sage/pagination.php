<?php

echo '<div class="cp_pagination_div">';
$args = [
    'base'         => $base. '%_%',
    'format'       => '%#%',
    'total'        => $total,
    'current'      => $page,
    'show_all'     => false,
    'end_size'     => 1,
    'mid_size'     => 2,
    'prev_next'    => true,
    'prev_text'    => __('Previous'),
    'next_text'    => __('Next'),
    'type'         => 'plain',
    'add_args'     => false,
    'add_fragment' => '',
    'before_page_number' => '',
    'after_page_number'  => ''
];

echo paginate_links( $args );

echo '</div>';