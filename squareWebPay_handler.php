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
    *  05/2025  project: square_webPay v3.0.0 file: squareWebPay_handler.php
    */
    

    header('Access-Control-Allow-Origin: *');

    $mode = 'cli';
    if (!empty($_GET['code'])) {
        $mode = 'web';
    }

    $verbose = false;
    require 'includes/application_top.php';
    require DIR_WS_CLASSES . 'payment.php';

    $module = new payment('square_webPay');
    $squareWebPay = new square_webPay();

    if ($mode === 'web') {
        if ($verbose) {
            error_log('SQUARE TOKEN REQUEST - auth code for exchange: ' . $_GET['code'] . "\n\n" . print_r($_GET, true));
        }
        $squareWebPay->exchangeForToken($_GET['code']);
        exit(0);
    }
    if ($mode === 'cli') {
        if (!defined('MODULE_PAYMENT_SQ_WEBPAY_STATUS') || MODULE_PAYMENT_SQ_WEBPAY_STATUS !== 'True') {
            if ($verbose) {
                echo 'MODULE DISABLED';
            }
            http_response_code(417);
            exit(1);
        }
        $is_browser = (isset($_SERVER['HTTP_HOST']) || PHP_SAPI !== 'cli');
        $result = $squareWebPay->token_refresh_check($verbose);
        if ($verbose) {
            echo $result;
        }
        if ($result === 'failure') {
            if (!$is_browser) {
                echo 'Square Token Refresh Failure. See logs.';
            }
            http_response_code(417);
            exit(1);
        }
    }
    exit(0);
