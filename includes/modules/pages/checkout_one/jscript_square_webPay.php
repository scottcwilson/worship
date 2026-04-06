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
    *  05/2025  project: square_webPay v3.0.0 file: jscript_square_webPay.php
    *   specifically for One Page Checkout
    */


    if (!file_exists($squareWebPay_jscript = DIR_FS_CATALOG . DIR_WS_MODULES .  'pages/checkout_payment/jscript_square_webPay.php')) {
        return false;
    }

    require $squareWebPay_jscript;
