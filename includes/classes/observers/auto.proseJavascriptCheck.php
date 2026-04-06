<?php
    /*
    *  developed, copyrighted and brought to you by @proseLA (github)
    *  https://mxworks.cc
    *  copyright 2025 proseLA
    *
    *  payment of license fee allows customer use of this software
    *  on a single domain.
    *
    *  consider an annual donation of 5 basis points of your sales if you want to keep this module going.
    *
    *  use of this software constitutes acceptance of license
    *  mxworks will vigilantly pursue any violations of this license.
    *
    *  some portions of code may be copyrighted and licensed by www.zen-cart.com
    *
    *  05/2025  project: square_webPay v3.0.0 file: auto.proseJavascriptCheck.php
    */



    class zcObserverProseJavascriptCheck extends base
    {
        public function __construct()
        {
            $this->attach($this, [
                'NOTIFY_HTML_HEAD_END',
            ]);
        }

        public function update(&$class, $eventID, &$p1, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7)
        {
            switch ($eventID) {
                case 'NOTIFY_HTML_HEAD_END':
                    if (($p1 === FILENAME_LOGIN) || (isset($_SESSION['emp_admin_login']))) {
                        ?>
                        <script type="text/javascript">
                            $(document).ready(function () {
                                $.get('<?= HTTPS_SERVER . DIR_WS_HTTPS_CATALOG . 'setJS.php'; ?>');
                            });
                        </script>
                        <?php
                    }
                    $user_styles = DIR_WS_TEMPLATE . 'css/squareStyling.php';
                    if (file_exists($user_styles)) {
                        require $user_styles;
                    } else {
                        trigger_error('squareStyling.php is in the wrong location or missing!');
                    }
                    break;
                default:
                    break;
            }
        }
    }
