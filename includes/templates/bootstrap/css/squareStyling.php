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
    *  05/2025  project: square_webPay v3.0.0 file: squareStyling.php
    */
    
?>
<style>
    #card-button {
        height: 0px
    }

    <?php
if (defined('MODULE_PAYMENT_SQ_WEBPAY_DISABLE_CREDIT_CARDS') && in_array(MODULE_PAYMENT_SQ_WEBPAY_DISABLE_CREDIT_CARDS, ['True', 'true', 'TRUE',])) {
?>
    #card-container {
        height: 0px
    }

    <?php
    }
    if (defined('MODULE_PAYMENT_SQ_WEBPAY_DISABLE_GOOGLE_PAY') && in_array(MODULE_PAYMENT_SQ_WEBPAY_DISABLE_GOOGLE_PAY, ['True', 'true', 'TRUE',])) {
        ?>
    #google-pay-button {
        height: 0px
    }

    <?php
    }
        if (defined('MODULE_PAYMENT_SQ_WEBPAY_DISABLE_APPLE_PAY') && in_array(MODULE_PAYMENT_SQ_WEBPAY_DISABLE_APPLE_PAY, ['True', 'true', 'TRUE',])) {
        ?>
    #apple-pay-button {
        height: 0px
    }

    <?php
    }
    ?>
</style>