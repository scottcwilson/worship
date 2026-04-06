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
    *  03/2026  project: square_webPay v3.1.2 file: lang.square_webPay.php
    */
    

    $define = [
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_DESCRIPTION' => 'Accept credit cards in less than 5 minutes.<br>No monthly fees and no setup fees.<br>PCI Compliant. Customer never leaves your store!<br>Standard rates are 2.9% + $0.30 per transaction.<br>Funds are deposited in your bank account in 1-2 business days.<br><br>
       <a href="https://www.zen-cart.com/partners/square" rel="noopener" target="_blank">Get more information, or Sign up for an account</a><br><br>
       <a href="https://squareup.com/login" rel="noopener" target="_blank">Log In To Your Square Account</a>',

        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_ADMIN_TITLE' => 'Square WebPay',  // Payment option title as displayed in the admin
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_CATALOG_TITLE' => 'Credit Card',  // Payment option title as displayed to the customer

        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_NOTICES_TO_CUSTOMER' => '',  // Payment option sub-text displayed to the customer, above the cc input fields

        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_CREDIT_CARD_POSTCODE' => 'Postal Code:',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_CREDIT_CARD_NUMBER' => 'Card Number:',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_CREDIT_CARD_LABEL' => 'Card Ending in: ',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_CREDIT_CARD_EXPIRES' => 'Expiry Date:',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_CVV' => 'CVV Number:',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_CREDIT_CARD_TYPE' => 'Credit Card Type:',

        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_ERROR' => 'Your transaction failed due to an error: ',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_MISCONFIGURATION' => 'We have a problem on our end.  So Sorry! Please report this error to the Store Owner: SQ-MISSING-TOKEN',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_NO_CC_DATA' => 'Hmmm...  seems like a problem...  did you enter a credit card number?',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_COMM_ERROR' => 'Unable to process payment due to a communications error. You may try again or contact us for assistance.',
        'MODULE_PAYMENT_SQ_WEBPAY_ERROR_INVALID_CARD_DATA' =>
            'We could not initiate your transaction because of a problem with the card data you entered. Please correct the card data, or report this error to the Store Owner: SQ-NONCE-FAILURE',
        'MODULE_PAYMENT_SQ_WEBPAY_ERROR_DECLINED' => 'Sorry, your payment could not be authorized. Please select an alternate method of payment.',

        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_TRANSACTION_SUMMARY' => '<strong>Transaction Summary</strong>',
        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_TRANSACTION_ACTIONS' => '<strong>Actions</strong>',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_UPDATE_FAILED' => 'Sorry, the attempted transaction update failed unexpectedly. See logs for details.',

        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_REFUND_TITLE' => '<strong>Refund Transaction</strong>',
        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_REFUND' => 'You may refund money to the customer here:',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_REFUND_CONFIRM_CHECK' => 'Check this box to confirm your intent: ',
        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_REFUND_AMOUNT_TEXT' => 'Enter the amount you wish to refund',
        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_REFUND_TEXT_COMMENTS' => 'Notes (will show on Order History):',
        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_REFUND_DEFAULT_MESSAGE' => 'Refund Issued',
        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_REFUND_SUFFIX' => 'You may refund an order within 120 days, up to the original amount tendered. You must supply the original transaction ID and tender ID<br>See the Square site for more <a href="https://squareup.com/help/us/en/article/5060" rel="noopener" target="_blank">information on Square refunds</a>.',
        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_REFUND_BUTTON_TEXT' => 'Do Refund',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_REFUND_CONFIRM_ERROR' => 'Error: You requested to do a refund but did not check the Confirmation box.',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_INVALID_REFUND_AMOUNT' => 'Error: You requested a refund but entered an invalid amount.',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_REFUND_INITIATED' => 'Refunded ',

        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_CAPTURE_TITLE' => '<strong>Capture Transaction</strong>',
        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_CAPTURE' => 'You may capture previously-authorized funds here:',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_CAPTURE_CONFIRM_CHECK' => 'Check this box to confirm your intent: ',
        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_CAPTURE_TEXT_COMMENTS' => 'Notes (will show on Order History):',
        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_CAPTURE_DEFAULT_MESSAGE' => '',
        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_CAPTURE_SUFFIX' => 'Captures must be performed within 6 days of the original authorization. You may only capture an order ONCE.',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_CAPTURE_CONFIRM_ERROR' => 'Error: You requested to do a capture but did not check the Confirmation box.',
        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_CAPTURE_BUTTON_TEXT' => 'Do Capture',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_TRANS_ID_REQUIRED_ERROR' => 'Error: You need to specify a Transaction ID.',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_CAPT_INITIATED' => 'Funds Capture initiated. Transaction ID: %s',

        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_VOID_TITLE' => '<strong>Voiding Transaction</strong>',
        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_VOID' => 'You may void an authorization which has not been captured.',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_VOID_CONFIRM_CHECK' => 'Check this box to confirm your intent:',
        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_VOID_TEXT_COMMENTS' => 'Notes (will show on Order History):',
        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_VOID_DEFAULT_MESSAGE' => 'Transaction Cancelled',
        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_VOID_SUFFIX' => '',
        'MODULE_PAYMENT_SQ_WEBPAY_ENTRY_VOID_BUTTON_TEXT' => 'Do Void',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_VOID_CONFIRM_ERROR' => 'Error: You requested a Void but did not check the Confirmation box.',
        'MODULE_PAYMENT_SQ_WEBPAY_TEXT_VOID_INITIATED' => 'Void Initiated. Transaction ID: %s',
        'MODULE_PAYMENT_SQ_WEBPAY_IS_DISABLED' => 'Sorry this module is currently not enabled.',
        'MODULE_PAYMENT_SQ_WEBPAY_CARD_BUTTON' => 'Javascript is needed to pay by card',
        'MODULE_PAYMENT_SQ_WEBPAY_CARD_JAVASCRIPT' => '',
    ];

    if (IS_ADMIN_FLAG === true) {
        $define['MODULE_PAYMENT_SQ_WEBPAY_TEXT_NEED_ACCESS_TOKEN'] =
            '<span class="text-danger"><strong>ALERT: Access Token not set:</strong></span> <br>
    1. Make sure the OAuth Redirect URL in your Square Account "app" is set to <u><nobr><pre>' . str_replace(['index.php?main_page=', 'http://'], ['', 'https://'],  zen_catalog_href_link('squareWebPay_handler.php', '', 'SSL')) .
            '</pre></nobr></u><br>
    2. And then <a href="%s" rel="noopener" target="_blank" class="onClickStartCheck"><button class="btn btn-xs btn-success">Click here to login and Authorize your account</button></a><br>
    3. Once authorized from the square dashboard, if the module is not green, please try and reload this page.';
    }

    return $define;
