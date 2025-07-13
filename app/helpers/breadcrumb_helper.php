<?php
/*
 * Breadcrumb Helper
 *
 * Generates Bootstrap 5 breadcrumb HTML from an array of crumbs.
 */

function generate_breadcrumbs($crumbs) {
    if (empty($crumbs)) {
        return '';
    }

    $html = '<nav aria-label="breadcrumb">
                <ol class="breadcrumb">';

    $last_key = count($crumbs) - 1;

    foreach ($crumbs as $key => $crumb) {
        if ($key == $last_key) {
            // Last item is active
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($crumb['label']) . '</li>';
        } else {
            // Other items are links
            $html .= '<li class="breadcrumb-item"><a href="' . htmlspecialchars($crumb['url']) . '">' . htmlspecialchars($crumb['label']) . '</a></li>';
        }
    }

    $html .= '    </ol>
            </nav>';

    return $html;
}
