<?php
function get_image_alt($image) {
    if (str_contains($image, 'hegai')) {
        return 'Hegai.org';
    }
    if (str_contains($image, 'LION')) {
        return 'Lion';
    }
    if (str_contains($image, 'phone')) {
        return 'Phone';
    }
    if (str_contains($image, 'FB')) {
        return 'Facebook logo';
    }
    return '';
}
