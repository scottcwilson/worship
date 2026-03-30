<?php
// -----
// Part of the Bootstrap template for Zen Cart. Loads the matchHeight jQuery
// class and ensures that any sidebox carousels' cards are equal heights to
// prevent screen 'jitter'.
//
// Bootstrap v3.7.0.
//
?>
<script src="<?= $template->get_template_dir('jquery.matchHeight.min.js', DIR_WS_TEMPLATE, $current_page_base, 'jscript') . '/jquery.matchHeight.min.js' ?>"></script>
<script>
$(document).ready(function() {
    $('.sideBoxContent .carousel-item .card').matchHeight();
});
</script>
