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
    *  03/2026  project: square_webPay v3.1.2 file: square_webPay.php
    */
    
    


    if (!defined('TABLE_SQUARE_PAYMENTS')) {
        define('TABLE_SQUARE_PAYMENTS', DB_PREFIX . 'square_payments');
    }

    if (!file_exists($sdk_loader = DIR_FS_CATALOG . 'includes/modules/payment/square_webPay/autoload.php')) {
        return false;
    }

    require $sdk_loader;

    use Square\Environments;
    use Square\SquareClient;
    use Square\Locations\Requests\GetLocationsRequest as GetLocationsRequest;

    use Square\Payments\Requests\CreatePaymentRequest;
    use Square\Types\Money;
    use Square\Types\Currency;

    use Square\Types\Address;
    use Square\Orders\Requests\GetOrdersRequest;
    use Square\Refunds\Requests\RefundPaymentRequest;
    use Square\Payments\Requests\CancelPaymentsRequest;
    use Square\Payments\Requests\CompletePaymentRequest;
    use Square\OAuth\Requests\ObtainTokenRequest;

    /**
     * Square Payments module class
     */
    class square_webPay extends base
    {
        public $tokenResult;
        public $verifyBuyerResult;
        /**
         * $code determines the internal 'code' name used to designate "this" payment module
         *
         * @var string
         */
        public $code;
        /**
         * $moduleVersion is the plugin version number
         */
        public $moduleVersion = '3.1.2';

        /**
         * $title is the displayed name for this payment method
         *
         * @var string
         */
        public $title;
        /**
         * $description is admin-display details for this payment method
         *
         * @var string
         */
        public $description;
        /**
         * $enabled determines whether this module shows or not... in catalog.
         *
         * @var boolean
         */
        public $enabled;
        /**
         * $sort_order determines the display-order of this module to customers
         */
        public $sort_order;
        /**
         * transaction vars hold the IDs of the completed payment
         */
        public $transaction_id, $transaction_messages, $auth_code;
        protected $currency_comment, $transaction_date;

        private $sandbox = false;
        private $tokenTTL, $refreshToken;

        private $token, $client, $sdkApiVersion, $_logDir, $_check;
        public $order_status;

        private $ccFields, $appleFields, $googleFields, $selectionFields;
        private $authUrl = '';

        /**
         * Constructor
         */
        public function __construct()
        {
            global $order;
            $this->code = 'square_webPay';
            $this->enabled = (defined('MODULE_PAYMENT_SQ_WEBPAY_STATUS') && MODULE_PAYMENT_SQ_WEBPAY_STATUS == 'True');
            $this->sort_order = defined('MODULE_PAYMENT_SQ_WEBPAY_SORT_ORDER') ? MODULE_PAYMENT_SQ_WEBPAY_SORT_ORDER : null;
            $this->title = MODULE_PAYMENT_SQ_WEBPAY_TEXT_CATALOG_TITLE; // Payment module title in Catalog
            $this->description = '<strong>Square Web Payments Module ' . $this->moduleVersion . '</strong>';

            $environment = Environments::Production;

            $this->token= '';
            $this->tokenTTL = '';
            $this->refreshToken = '';
            if (defined('MODULE_PAYMENT_SQ_WEBPAY_ACCESS_TOKEN')) {
                $this->token = MODULE_PAYMENT_SQ_WEBPAY_ACCESS_TOKEN;
            }
            if (defined('MODULE_PAYMENT_SQ_WEBPAY_REFRESH_TOKEN')) {
                $this->refreshToken = MODULE_PAYMENT_SQ_WEBPAY_REFRESH_TOKEN;
            }
            if (defined('MODULE_PAYMENT_SQ_WEBPAY_TOKEN_EXPIRES_AT')) {
                $this->tokenTTL = MODULE_PAYMENT_SQ_WEBPAY_TOKEN_EXPIRES_AT;
            }

            $this->checkSandbox();

            if ((defined('MODULE_PAYMENT_SQ_WEBPAY_TESTING_MODE') && MODULE_PAYMENT_SQ_WEBPAY_TESTING_MODE === 'Sandbox') || $this->sandbox) {
                $environment = Environments::Sandbox;
                $this->token= '';
                $this->tokenTTL = '';
                $this->refreshToken = '';
                if (defined('MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_TOKEN')) {
                    $this->token = MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_TOKEN;
                }
                if (defined('MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_REFRESH_TOKEN')) {
                    $this->refreshToken = MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_REFRESH_TOKEN;
                }
                if (defined('MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_TOKEN_EXPIRES_AT')) {
                    $this->tokenTTL = MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_TOKEN_EXPIRES_AT;
                }
            }

            $this->client = new SquareClient(
                token:   $this->token,
                options: [
                             'baseUrl' => $environment->value,
                         ]);

            if (IS_ADMIN_FLAG === true) {
                $this->sdkApiVersion = $this->getSdkVersion();
                $this->description .= '<br>[using SDK: ' . $this->sdkApiVersion . ']';
            }

            $this->description .= '<br><br>' . MODULE_PAYMENT_SQ_WEBPAY_TEXT_DESCRIPTION;
            if (defined('MODULE_PAYMENT_SQ_WEBPAY_APPLICATION_ID')) {
                $this->authUrl = $this->getAuthorizeURL();
            }

            if (IS_ADMIN_FLAG === true) {
                $this->title = MODULE_PAYMENT_SQ_WEBPAY_TEXT_ADMIN_TITLE;
                if (defined('MODULE_PAYMENT_SQ_WEBPAY_STATUS')) {
                    if (MODULE_PAYMENT_SQ_WEBPAY_APPLICATION_ID == '') {
                        $this->title .= '<span class="alert"> (not configured; API details needed)</span>';
                    }
                    if ($this->token === '') {
                        $this->title .= '<span class="alert"> (Access Token needed)</span>';
                        $this->description .= "\n" . '<br><br>' . sprintf(MODULE_PAYMENT_SQ_WEBPAY_TEXT_NEED_ACCESS_TOKEN, $this->authUrl);
                        $this->description .= '<script>
                    function tokenCheckSqH(){
                        $.ajax({
                            url: "' . str_replace(['index.php?main_page=index', 'http://'], ['squareWebPay_handler.php', 'https://'], zen_catalog_href_link(FILENAME_DEFAULT, '', 'SSL')) .
                            '",
                            cache: false,
                            success: function() {
                              window.location.reload();
                            }
                          });
                          return true;
                    }
                    $(".onClickStartCheck").click(function(){setInterval(function() {tokenCheckSqH()}, 8000)});
                    </script>';
                    }
                    if ($this->sandbox) {
                        $this->title .= '<span class="alert"> (Sandbox mode)</span>';
                    }
                }
                $this->tableCheckup();
            }

            // determine order-status for customer transactions
            if (defined('MODULE_PAYMENT_SQ_WEBPAY_ORDER_STATUS_ID') && (int)MODULE_PAYMENT_SQ_WEBPAY_ORDER_STATUS_ID > 0) {
                $this->order_status = (int)MODULE_PAYMENT_SQ_WEBPAY_ORDER_STATUS_ID;
            }
            // Reset order status to pending if capture pending:
            if (defined('MODULE_PAYMENT_SQ_WEBPAY_TRANSACTION_TYPE') && MODULE_PAYMENT_SQ_WEBPAY_TRANSACTION_TYPE == 'authorize') {
                $this->order_status = 1;
            }

            $this->_logDir = DIR_FS_LOGS;

            // module can't work without a token; must be configured via OAUTH handshake
            if (empty($this->token)) {
                $this->enabled = false;
            }

            $this->notify('SQUARE_WEBPAY_CONSTRUCTOR');

            // check for zone compliance and any other conditionals
            if ($this->enabled && is_object($order)) {
                $this->update_status();
            }
        }

        private function checkSandbox()
        {
            global $db;
            if (defined('MODULE_PAYMENT_SQ_WEBPAY_APPLICATION_ID') && strpos(MODULE_PAYMENT_SQ_WEBPAY_APPLICATION_ID, 'sandbox') !== false) {
                $this->sandbox = true;
                if (!defined('MODULE_PAYMENT_SQ_WEBPAY_TESTING_MODE') || MODULE_PAYMENT_SQ_WEBPAY_TESTING_MODE !== 'Sandbox') {
                    $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = 'Sandbox' WHERE configuration_key = 'MODULE_PAYMENT_SQ_WEBPAY_TESTING_MODE'");
                }
            }
        }

        /**
         * @return void
         */
        public function update_status()
        {
            global $order, $db;
            if ($this->enabled == false || (int)MODULE_PAYMENT_SQ_WEBPAY_ZONE == 0) {
                return;
            }
            if (!isset($order->billing['country']['id'])) {
                return;
            }

            $check_flag = false;
            $sql = "SELECT zone_id FROM " . TABLE_ZONES_TO_GEO_ZONES . " WHERE geo_zone_id = '" . (int)MODULE_PAYMENT_SQ_WEBPAY_ZONE . "' AND zone_country_id = '" . (int)$order->billing['country']['id'] . "' ORDER BY zone_id";
            $checks = $db->Execute($sql);
            foreach ($checks as $check) {
                if ($check['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check['zone_id'] == $order->billing['zone_id']) {
                    $check_flag = true;
                    break;
                }
            }
            if ($check_flag == false) {
                $this->enabled = false;
            }
        }

        public function javascript_validation()
        {
            return '';
        }

        public function selection()
        {
            global $order, $user_agent;

            if (!isset($_SESSION['js']) || (!$_SESSION['js'])) {
                $message = 'Javascript Not Enabled on Customer Browser! ' . PHP_EOL . 'Customer Email: ' . $order->customer['email_address'] . PHP_EOL;
                $message .= 'IP Address: ' . $order->info['ip_address'] . PHP_EOL . 'User Agent: ' . $user_agent . PHP_EOL;

                if (in_array(MODULE_PAYMENT_SQ_WEBPAY_LOGGING, ['Log Always', 'Email Always',])) {
                    trigger_error(date(DATE_RFC2822) . PHP_EOL . $message);
                }
                if (!empty(MODULE_PAYMENT_SQ_WEBPAY_DISABLE_JAVASCRIPT) && in_array(MODULE_PAYMENT_SQ_WEBPAY_DISABLE_JAVASCRIPT, ['True', 'true', 'TRUE',])) {
                    return;
                }
            }
            $this->ccFields = '<div id="card-container" ></div>';
            $this->ccFields .= '<button id="card-button" type="button">' . MODULE_PAYMENT_SQ_WEBPAY_CARD_BUTTON . '</button>';
            $this->ccFields .= '<noscript>' . MODULE_PAYMENT_SQ_WEBPAY_CARD_JAVASCRIPT . '</noscript>';
            if (!empty(MODULE_PAYMENT_SQ_WEBPAY_DISABLE_APPLE_PAY) && in_array(MODULE_PAYMENT_SQ_WEBPAY_DISABLE_APPLE_PAY, ['False', 'false', 'FALSE',])) {
                $this->appleFields = '<div id="apple-pay-button"></div>
';
            }
            if (!empty(MODULE_PAYMENT_SQ_WEBPAY_DISABLE_GOOGLE_PAY) && in_array(MODULE_PAYMENT_SQ_WEBPAY_DISABLE_GOOGLE_PAY, ['False', 'false', 'FALSE',])) {
                $this->googleFields = '<div id="google-pay-button"></div>';
            }
            $this->arrangeFields();

            $selection = [
                'id' => $this->code,
                'module' => $this->title,
                'fields' => [
                    [
                        'title' => '',
                        'field' => $this->selectionFields,
                    ],
                ],
            ];

            return $selection;
        }

        private function arrangeFields()
        {
            $fieldOrder = (int) MODULE_PAYMENT_SQ_WEBPAY_FIELD_ORDER;
            $this->selectionFields = match ($fieldOrder) {
                2 => $this->ccFields . $this->googleFields . $this->appleFields,
                3 => $this->appleFields . $this->ccFields . $this->googleFields,
                4 => $this->appleFields . $this->googleFields . $this->ccFields,
                5 => $this->googleFields . $this->ccFields . $this->appleFields,
                6 => $this->googleFields . $this->appleFields . $this->ccFields,
                default => $this->ccFields . $this->appleFields . $this->googleFields,
            };

        }

        public function pre_confirmation_check()
        {
            global $messageStack;
            if (empty($_REQUEST['tokenResult'])) {
                trigger_error('missing token result: ' . 'should not get here.  check that you have the latest jscript_square_webpay file and that you are not having javascript errors in the console.');
                $messageStack->add_session('checkout_payment', MODULE_PAYMENT_SQ_WEBPAY_TEXT_NO_CC_DATA, 'error');
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
            }
            $this->tokenResult = json_decode($_REQUEST['tokenResult']);
            if ($this->tokenResult->status !== 'OK') {
                trigger_error(json_encode($_REQUEST));
                $this->logTransactionData([], $this->tokenResult);
                $messageStack->add_session('checkout_payment', MODULE_PAYMENT_SQ_WEBPAY_ERROR_INVALID_CARD_DATA, 'error');
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
            }
            $this->verifyBuyerResult = json_decode($_REQUEST['verifyBuyerResult'] ?? '');
        }

        public function confirmation()
        {
            $confirmation = [
                'fields' => [
                    [
                        'title' => MODULE_PAYMENT_SQ_WEBPAY_TEXT_CREDIT_CARD_TYPE,
                        'field' => zen_output_string_protected($this->tokenResult->details->card->brand),
                    ],
                    [
                        'title' => MODULE_PAYMENT_SQ_WEBPAY_TEXT_CREDIT_CARD_NUMBER,
                        'field' => MODULE_PAYMENT_SQ_WEBPAY_TEXT_CREDIT_CARD_LABEL . zen_output_string_protected($this->tokenResult->details->card->last4),
                    ],
                    [
                        'title' => MODULE_PAYMENT_SQ_WEBPAY_TEXT_CREDIT_CARD_EXPIRES,
                        'field' => zen_output_string_protected($this->tokenResult->details->card->expMonth . '/' . $this->tokenResult->details->card->expYear),
                    ],
                ],
            ];

            return $confirmation;
        }

        public function process_button()
        {
            $process_button_string = zen_draw_hidden_field($this->code . '_tokenResult', json_encode($this->tokenResult));
            $process_button_string .= zen_draw_hidden_field($this->code . '_verifyBuyerResult', json_encode($this->verifyBuyerResult));
            return $process_button_string;
        }

        public function before_process()
        {
            global $messageStack, $order, $currencies;

            if (!isset($_POST[$this->code . '_tokenResult']) || trim($_POST[$this->code . '_tokenResult']) == '') {
                $messageStack->add_session('checkout_payment', MODULE_PAYMENT_SQ_WEBPAY_ERROR_INVALID_CARD_DATA, 'error');
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
            }

            $this->tokenResult = json_decode($_REQUEST[$this->code . '_tokenResult']);
            $this->verifyBuyerResult = json_decode($_REQUEST[$this->code . '_verifyBuyerResult']);

            $order->info['cc_type'] = zen_output_string_protected($this->tokenResult->details->card->brand);
            $order->info['cc_number'] = str_pad(substr($this->tokenResult->details->card->last4, -4), CC_NUMBER_MIN_LENGTH, "x", STR_PAD_LEFT);
            $month = strlen($this->tokenResult->details->card->expMonth) == 1 ? '0' . $this->tokenResult->details->card->expMonth : $this->tokenResult->details->card->expMonth;
            $order->info['cc_expires'] = zen_output_string_protected($month . substr($this->tokenResult->details->card->expYear, -2));

            // get Square Location (since we need the ID and the currency for preparing the transaction)
            $location = $this->getLocationDetails();

            $payment_amount = $order->info['total'];
            $currency_code = strtoupper($order->info['currency']);

            $this->currency_comment = '';

            // force conversion to Square Location's currency:
            if ($order->info['currency'] !== $location['currency'] || $order->info['currency'] !== DEFAULT_CURRENCY) {
                $payment_amount = $currencies->rateAdjusted($order->info['total'], true, $location['currency']);
                $currency_code = $location['currency'];
                if ($order->info['currency'] !== $location['currency']) {
                    $this->currency_comment = '(Converted from: ' . round($order->info['total'] * $order->info['currency_value'], 2) . ' ' . $order->info['currency'] . ')';
                }
                // Note: Add tax/shipping conversion as well if rewriting for Orders API integration
            }

            $billingAddress = new Address();
            $billingAddress->setAddressLine1((string)$order->billing['street_address']);
            $billingAddress->setAddressLine2((string)$order->billing['suburb']);
            $billingAddress->setLocality((string)$order->billing['city']);
            $billingAddress->setAdministrativeDistrictLevel1((string)zen_get_zone_code($order->billing['country']['id'], (int)$order->billing['zone_id'], $order->billing['state']));
            $billingAddress->setPostalCode((string)$order->billing['postcode']);
            $billingAddress->setCountry((string)$order->billing['country']['iso_code_2']);
            $billingAddress->setFirstName((string)$order->billing['firstname']);
            $billingAddress->setLastName((string)$order->billing['lastname']);
            $billingAddress->setAddressLine3((string)$order->billing['company']);

            if ($order->delivery !== false && !empty($order->delivery['street_address']) && !empty($order->delivery['country']['iso_code_2'])) {
                $shippingAddress = new Address();
                $shippingAddress->setAddressLine1((string)$order->delivery['street_address']);
                $shippingAddress->setAddressLine2((string)$order->delivery['suburb']);
                $shippingAddress->setLocality((string)$order->delivery['city']);
                $shippingAddress->setAdministrativeDistrictLevel1((string)zen_get_zone_code($order->delivery['country']['id'], (int)$order->delivery['zone_id'], $order->delivery['state']));
                $shippingAddress->setPostalCode((string)$order->delivery['postcode']);
                $shippingAddress->setCountry((string)$order->delivery['country']['iso_code_2']);
                $shippingAddress->setFirstName((string)$order->delivery['firstname']);
                $shippingAddress->setLastName((string)$order->delivery['lastname']);
                $shippingAddress->setAddressLine3((string)$order->delivery['company']);
            }
            // brief additional information transmitted as a "note", to max of 500 characters:
            $extraNotes = defined('MODULES_PAYMENT_SQUARE_TEXT_ITEMS_ORDERED') ? MODULES_PAYMENT_SQUARE_TEXT_ITEMS_ORDERED : 'Ordered:';
            if (count($order->products) < 100) {
                for ($i = 0, $j = count($order->products); $i < $j; $i++) {
                    if ($i > 0 && $i < $j) {
                        $extraNotes .= ', ';
                    }
                    $extraNotes .= '(' . $order->products[$i]['qty'] . ') ' . $order->products[$i]['name'];
                }
            }
            if ($order->delivery !== false && !empty($order->delivery['street_address']) && !empty($order->delivery['country']['iso_code_2'])) {
                $extraNotes .= '; ';
                $extraNotes .= defined('MODULES_PAYMENT_SQUARE_TEXT_DELIVERY_ADDRESS') ? MODULES_PAYMENT_SQUARE_TEXT_DELIVERY_ADDRESS : 'Deliver To: ';
                $extraNotes .= $order->delivery['street_address'] . ', ' . $order->delivery['city'] . ', ' . $order->delivery['state'] . '  ' . $order->delivery['postcode'] . '  tel:' . $order->customer['telephone'];
            }
            // Use Notes to identify customer and store name
            $note = substr(htmlentities(trim($order->billing['firstname'] . ' ' . $order->billing['lastname'] . '; ' . $extraNotes . ' ' . $this->currency_comment . ' ' . STORE_NAME)), 0, 500);

            $body = [
                'idempotencyKey' => uniqid(),
                'amountMoney' => new Money([
                                               'amount' => $this->convertToBaseCurrencyUnit($payment_amount, $currency_code),
                                               'currency' => $currency_code,
                                           ]),
                'sourceId' => $this->tokenResult->token,
                'locationId' => $location['id'],
                'buyerEmailAddress' => substr($order->customer['email_address'], 0, 255),
                'billingAddress' => $billingAddress,
                'note' => $note,
            ];

            if (!empty($this->verifyBuyerResult->token)) {
                $body['verificationToken'] = $this->verifyBuyerResult->token;
            }


            if (!empty($shippingAddress)) {
                $body['shippingAddress'] = $shippingAddress;
            }

            $body['autocomplete'] = true;
            if (MODULE_PAYMENT_SQ_WEBPAY_TRANSACTION_TYPE === 'authorize') {
                $body['autocomplete'] = false;
            }
            $response = $this->paymentRequest('create', $body);

            // analyze for errors
            if ($response['error']) {
                foreach ($response['results'] as $error) {
                    $messageStack->add_session('checkout_payment', MODULE_PAYMENT_SQ_WEBPAY_TEXT_ERROR . ' [' . $error->getCode() . '] ' . $error->getDetail(), 'error');
                }
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
            }

            $payment = $response['results'];

            if ($payment->getId() && in_array($payment->getStatus(), ['COMPLETED', 'APPROVED'])) {
                $this->transaction_date = $payment->getCreatedAt();
                $this->auth_code = $payment->getOrderId(); // the order_id assigned by Square, used for lookups later
                $this->transaction_id = $payment->getId(); // The payment_id is used for refund requests
                return true;
            }

            // if we get here, send a generic 'declined' message response
            $messageStack->add_session('checkout_payment', MODULE_PAYMENT_SQ_WEBPAY_ERROR_DECLINED, 'error');
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
        }
        
        private function paymentRequest(string $type, array $body): array
        {
            global $messageStack;

            switch ($type) {
                case 'create':
                    $request = new CreatePaymentRequest($body);
                    break;
                case 'cancel':
                    $request = new CancelPaymentsRequest($body);
                    break;
                case 'complete':
                    $request = new CompletePaymentRequest($body);
                    break;
                default:
                    throw new \Exception('Unexpected value');
            }
            try {
                $apiResponse = $this->client->payments->$type($request);
                $response = $this->processResult($apiResponse, $body, $type);
            } catch (\Square\Exceptions\SquareApiException|\Square\Exceptions\SquareException $e) {
                $this->logSquareApiException($e, $body);
                if ($type === 'create') {
                    $messageStack->add_session('checkout_payment', MODULE_PAYMENT_SQ_WEBPAY_TEXT_COMM_ERROR, 'error');
                    zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
                } else {
                    $message = 'Code: ' . $e->getCode() . '-> ' . $e->getMessage();
                    $messageStack->add_session(MODULE_PAYMENT_SQ_WEBPAY_TEXT_UPDATE_FAILED . $message, 'error');
                }
                $response = [
                    'error' => true,
                    'results' => [
                        $message,
                        ],
                ];
            }
            return $response;
        }

        private function logSquareApiException(object $e, $body =  ''): void
        {
            $message = 'Code: ' . $e->getCode() . '-> ' . $e->getMessage();
            trigger_error($message . PHP_EOL . PHP_EOL . json_encode($e));
            $this->logTransactionData([], $body, true, true, $message . print_r($e->getMessage(), true));
        }

        private function processResult($response, array $body, string $type = 'create'): array
        {
            $error = false;
            $result = $response->getErrors();
            if (empty($result)) {
                switch ($type) {
                    case 'create':
                        $result = $response->getPayment();
                        break;
                    case 'refund':
                        $result = $response->getRefund();
                        break;
                    default:
                        $result = [];
                }
            } else {
                $error = true;
            }
            $this->logTransactionData($result, $body, $error);
            return [
                'error' => $error,
                'results' => $result,
            ];
        }

        /**
         * Update the order-status history data with the transaction id and tender id from the transaction.
         *
         * @return boolean
         */
        public function after_process()
        {
            global $insert_id, $order, $currencies;

            $comments = 'Credit Card payment.  TransID: ' . $this->transaction_id . "\n" . $this->transaction_date . $this->currency_comment . "\nOID: " . $this->auth_code;
            zen_update_orders_history($insert_id, $comments, null, $this->order_status, -1);

            $sql_data_array = [
                'order_id' => $insert_id,
                'location_id' => $this->getLocationDetails()['id'],
                'payment_id' => $this->transaction_id,
                'sq_order' => $this->auth_code,
                'created_at' => 'now()',
            ];
            zen_db_perform(TABLE_SQUARE_PAYMENTS, $sql_data_array);

            return true;
        }

        /**
         * Prepare admin-page components
         *
         * @param int $order_id
         *
         * @return string
         */
        public function admin_notification(int $order_id)
        {
            global $currencies;
            if (!$this->enabled) {
                return MODULE_PAYMENT_SQ_WEBPAY_IS_DISABLED;
            }
            $transaction = $this->lookupOrderDetails($order_id);
            if (empty($transaction) || !$transaction->getId()) {
                return '';
            }
            $output = '';
            require(DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/square_webPay/square_admin_notification.php');

            return $output;
        }

        /**
         * If access token is valid, set it for connections, else start renewal process
         *
         * @return string
         */
        private function getAccessToken()
        {
            $this->token_refresh_check();
            $access_token = (string)(MODULE_PAYMENT_SQ_WEBPAY_TESTING_MODE === 'Live' ? MODULE_PAYMENT_SQ_WEBPAY_ACCESS_TOKEN : (MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_TOKEN ?? MODULE_PAYMENT_SQ_WEBPAY_ACCESS_TOKEN));

            return $access_token;
        }

        /**
         * Test for token expiration
         *
         * @param string $difference
         *
         * @return bool
         * @throws Exception
         */
        protected function isTokenExpired($difference = ''): bool
        {
            if (empty($this->tokenTTL)) {
                return true;
            }
            $expiry = new DateTime($this->tokenTTL);  // formatted as '2016-08-10T19:42:08Z'

            // to be useful, we have to allow time for a customer to checkout. Opting generously for 1 hour here.
            if ($difference == '') {
                $difference = '+1 hour';
            }
            $now = new DateTime($difference);

            return $expiry < $now;
        }

        /**
         * Check if token needs refresh (ie: recently expired, or nearly expired)
         * Called by payment module and by cron job
         *
         * @return string
         * @throws Exception
         */
        public function token_refresh_check(bool $debug = false): string
        {
            if (empty(MODULE_PAYMENT_SQ_WEBPAY_APPLICATION_ID)) {
                return 'not configured';
            }

            $return = 'failure';

            // if we have no token, alert that we need to get one
            if (empty($this->token)) {
                if (IS_ADMIN_FLAG === true) {
                    global $messageStack;
                    $messageStack->add_session(sprintf(MODULE_PAYMENT_SQ_WEBPAY_TEXT_NEED_ACCESS_TOKEN, $this->authUrl), 'error');
                }
                $this->disableDueToInvalidAccessToken();
                if ($debug) {
                    $return = 'failure due to no token';
                    trigger_error('There is no token to refresh!');
                }
                return $return;
            }

            // refreshes can't be done if the token has expired longer than 15 days.
            if ($this->isTokenExpired('-15 days')) {
                $this->disableDueToInvalidAccessToken();
                if ($debug) {
                    $return = 'failure due to 15 day check';
                    trigger_error('The tokens expiration is greater than 15 days.');
                }
                return $return;
            }

            // ideal refresh threshold is 3 weeks out
            $refresh_threshold = new DateTime('+3 weeks');

            // if expiry is less than (threshold) away, refresh  (ie: refresh weekly)
            $expiry = new DateTime($this->tokenTTL);
            if ($expiry < $refresh_threshold) {
                $result = $this->renewOAuthToken();
                if ($result) {
                    return 'refreshed';
                }
                return 'not refreshed';
            }

            return 'not expired';
        }

        /**
         * Disable this payment module if access token is invalid or expired
         */
        private function disableDueToInvalidAccessToken()
        {
            if (empty($this->tokenTTL) || empty($this->token)) {
                return;
            }
            $this->resetTokensAndDisconnectFromSquare();
            $msg = "This is an alert from your Zen Cart store.\n\nYour Square Payment Module access-token has expired, or cannot be refreshed automatically. Please login to your store Admin, go to the Payment Module settings, click on the Square module, and click the button to Re/Authorize your account.\n\nSquare Payments are disabled until a new valid token can be established.";
            $msg .= "\n\n" . ' The token expired on ' . $this->tokenTTL;
            zen_mail(STORE_NAME, STORE_OWNER_EMAIL_ADDRESS, 'Square Payment Module Problem: Critical', $msg, STORE_NAME, EMAIL_FROM, ['EMAIL_MESSAGE_HTML' => $msg], 'payment_module_error');
            if (IS_ADMIN_FLAG !== true) {
                trigger_error('Square Payment Module token expired' . ($this->tokenTTL !== ''
                        ? ' on ' . $this->tokenTTL
                        : '') . '. Payment module has been disabled. Please login to Admin and re-authorize the module.',
                    E_USER_ERROR);
            }
        }

        /**
         * Disconnect all auth to Square account (useful for troubleshooting, and linking to a different account)
         *
         * @param bool $include_sandbox
         *
         * @return void
         */
        protected function resetTokensAndDisconnectFromSquare()
        {
            global $db;
            if ($this->sandbox) {
                $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '' WHERE configuration_key in ('MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_TOKEN')");
                $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '' WHERE configuration_key in ('MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_TOKEN_EXPIRES_AT', 'MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_REFRESH_TOKEN')");
            } else {
                $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '' WHERE configuration_key in ('MODULE_PAYMENT_SQ_WEBPAY_ACCESS_TOKEN')");
                $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '' WHERE configuration_key in ('MODULE_PAYMENT_SQ_WEBPAY_TOKEN_EXPIRES_AT', 'MODULE_PAYMENT_SQ_WEBPAY_REFRESH_TOKEN')");
            }
            $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = 'False' WHERE configuration_key = 'MODULE_PAYMENT_SQ_WEBPAY_STATUS'");
        }

        /**
         * Store access token to db once a valid replacement token has been received
         *
         *
         * @return bool
         */
        private function saveAccessToken(Square\Types\ObtainTokenResponse $response): void
        {
            global $db;
            if ($this->sandbox) {
                $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '" . $response->getAccessToken() . "' WHERE configuration_key = 'MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_TOKEN'");
                $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '" . $response->getExpiresAt() . "' WHERE configuration_key = 'MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_TOKEN_EXPIRES_AT'");
                $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '" . $response->getRefreshToken() . "' WHERE configuration_key = 'MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_REFRESH_TOKEN'");
            } else {
                $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '" . $response->getAccessToken() . "' WHERE configuration_key = 'MODULE_PAYMENT_SQ_WEBPAY_ACCESS_TOKEN'");
                $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '" . $response->getExpiresAt() . "' WHERE configuration_key = 'MODULE_PAYMENT_SQ_WEBPAY_TOKEN_EXPIRES_AT'");
                $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '" . $response->getRefreshToken() . "' WHERE configuration_key = 'MODULE_PAYMENT_SQ_WEBPAY_REFRESH_TOKEN'");
            }

        }

        /**
         * Generate the oauth URL for making an authorize request for the account
         *
         * @return string
         */
        private function getAuthorizeURL(): string
        {
            $url = 'https://connect.squareup.com/oauth2/authorize?';

            if ($this->sandbox) {
                $url = 'https://connect.squareupsandbox.com/oauth2/authorize?';
            }
            if (empty($_SESSION['auth_state'])) {
                $_SESSION['auth_state'] = bin2hex(random_bytes(32));
            }

            $permissions = urlencode(
                'ITEMS_READ ITEMS_WRITE ' .
                'MERCHANT_PROFILE_READ ONLINE_STORE_SITE_READ ' .
                'PAYMENTS_READ PAYMENTS_WRITE PAYMENTS_WRITE_ADDITIONAL_RECIPIENTS PAYMENTS_WRITE_SHARED_ONFILE ' .
                'ORDERS_WRITE ORDERS_READ CUSTOMERS_WRITE CUSTOMERS_READ '
            );

            $params = 'client_id=' . MODULE_PAYMENT_SQ_WEBPAY_APPLICATION_ID . '&scope=' . $permissions . '&session=false&state=' . $_SESSION['auth_state'];
            $params .= '&redirect_uri=' . str_replace(['index.php?main_page=index', 'http://'], ['squareWebPay_handler.php', 'https://'], zen_catalog_href_link(FILENAME_DEFAULT, '', 'SSL'));

            return $url . $params;
        }

        /**
         * Part of the Oauth handshake: exchanges auth code for auth token
         *
         * @param $tokenCodeFromSquare
         *
         * @throws Exception
         */
        public function exchangeForToken($tokenCodeFromSquare)
        {
            $request = new ObtainTokenRequest([
                                                  'clientId' => MODULE_PAYMENT_SQ_WEBPAY_APPLICATION_ID,
                                                  'clientSecret' => MODULE_PAYMENT_SQ_WEBPAY_APPLICATION_SECRET,
                                                  'code' => $tokenCodeFromSquare,
                                                  'grantType' => 'authorization_code',
                                                  'redirectUri' => str_replace(['index.php?main_page=index', 'http://'], ['squareWebPay_handler.php', 'https://'], zen_catalog_href_link(FILENAME_DEFAULT, '', 'SSL')),
                                              ]);
            try {
                $response = $this->client->oAuth->obtainToken($request);
            } catch (Exception $e) {
                $this->logSquareApiException($e, $request);
                throw new Exception('Error Processing Request: Could not get initial oauth token!', 1);
            }

            if ($response->getErrors()) {
                echo 'There was a problem with the auth token.  Check your logs for details. <script type="text/javascript">window.close()</script>';
            } else {
                $this->saveAccessToken($response);
                echo 'Token set. You may now continue configuring the module. <script type="text/javascript">window.close()</script>';
            }
        }

        /**
         * Renew OAuth access token using a refresh token.
         */
        protected function renewOAuthToken(): bool
        {
            global $messageStack;

            if (empty($this->refreshToken)) {
                // pRose i see no need to disconnect here.  admin will need to get new token at some point.  but no refesh token should not disallow processing!
                // if square allows to go though, knock themselves out.
//                $this->resetTokensAndDisconnectFromSquare();
                if (IS_ADMIN_FLAG === true) {
                    $this->resetTokensAndDisconnectFromSquare();
                    $messageStack->add_session('FATAL ERROR: No refresh token found. Please re-authorize your Square account via the Admin console.', 'error');
                }
                return false;
            }
            $request = new ObtainTokenRequest([
                                                  'clientId' => MODULE_PAYMENT_SQ_WEBPAY_APPLICATION_ID,
                                                  'clientSecret' => MODULE_PAYMENT_SQ_WEBPAY_APPLICATION_SECRET,
                                                  'refreshToken' => $this->refreshToken,
                                                  'grantType' => 'refresh_token'
                                              ]);
            try {
                $response = $this->client->oAuth->obtainToken($request);
            } catch (Exception $e) {
                $this->logSquareApiException($e, $request);
                throw new Exception('Error Processing Request: Token renewal failed!', 1);
            }

            if (empty($response->getErrors())) {

                $this->saveAccessToken($response);
                $this->admin_check();
                return true;
            } else {
                trigger_error('Error when calling OAuthApi->obtainToken: ' . json_encode($response));
            }

            return false;
        }
        private function getLocationIdFromConstant(): string
        {
            $string = trim((string)MODULE_PAYMENT_SQ_WEBPAY_LOCATION);
            if (preg_match('/\[(.*?)\]/', $string, $matches)) {
                return $matches[1];
            } else {
                return '';
            }
        }

        /**
         * Lookup and return location information, whether from configured setting, or by lookup from Square directly
         *
         */
        function getLocationDetails(): array
        {
            $location = new GetLocationsRequest(
                [
                    'locationId' => $this->getLocationIdFromConstant(),
                ]
            );

            $data = trim((string)MODULE_PAYMENT_SQ_WEBPAY_LOCATION);

            if (empty($data)) {
                return [];
            } else {
                // this splits it out from stored format of: LocationName:[LocationID]:CurrencyCode
                preg_match('/(.+(?<!:\[)):\[(.+(?<!]:))]:([A-Z]{3})?/', $data, $matches);
                return [
                    'name' => $matches[1],
                    'id' => $matches[2],
                    'currency' => $matches[3],
                ];
            }
        }

        protected function getLocationsList()
        {
            if (empty($this->token)) {
                return [];
            }

            try {
                $result = $this->client->locations->list();
                return json_decode($result);
            } catch (\Square\Exceptions\SquareApiException|\Square\Exceptions\SquareException $e) {
                $this->logSquareApiException($e);
                return [];
            }
        }

        public function getLocationsPulldownArray(): array
        {
            $locations = $this->getLocationsList();
            if (empty($locations)) {
                return [];
            }
            $locations_pulldown = [];

            foreach ($locations->locations as $key => $value) {
                $locations_pulldown[] = [
                    'id' => $value->name . ':[' . $value->id . ']:' . $value->currency,
                    'text' => $value->name . ': ' . $value->currency,
                ];
            }
            return $locations_pulldown;
        }

        /**
         * fetch live order details
         *
         * @param $order_id
         *
         * @return array | \Square\Types\Order
         */
        protected function lookupOrderDetails($order_id)
        {
            global $db;
            $sql = 'SELECT * from ' . TABLE_SQUARE_PAYMENTS . ' WHERE order_id = ' . (int)$order_id;
            $order = $db->Execute($sql);

            if ($order->EOF) {
                return [];
            }

            if (empty($order->fields['sq_order'])) {
                return [];
            }

            $request = new GetOrdersRequest([
                'orderId' => $order->fields['sq_order'],
                ]);
            try {
                $response = $this->client->orders->get($request);
                if ($response->getErrors()) {
                    $this->logSquareApiException($response, $request);
                    return [];
                }
                return $response->getOrder();

            } catch (\Square\Exceptions\SquareApiException|\Square\Exceptions\SquareException $e) {
                $this->logSquareApiException($e, $request);
                return [];
            }
        }

        /**
         * fetch original payment details for an order
         *
         * @param $order_id
         *
         * @return array|\Square\Types\Order
         */
        protected function lookupPaymentForOrder($order_id)
        {
            $records = $this->lookupOrderDetails($order_id);

            if (empty($records)) {
                return [];
            }
            return $records;
        }

        /**
         * format purchase amount
         * Monetary amounts are specified in the smallest unit of the applicable currency. ie: for USD the amount is in cents.
         */
        protected function convertToBaseCurrencyUnit($amount, $currency_code = null)
        {
            global $currencies, $order;
            if (empty($currency_code)) {
                $currency_code = (isset($order) && isset($order->info['currency']))
                    ? $order->info['currency'] : DEFAULT_CURRENCY;
            }
            $decimal_places = $currencies->get_decimal_places($currency_code);

            $this->notify('NOTIFY_SQUARE_WEBPAY_CURRENCY_DECIMAL_OVERRIDE', $currency_code, $decimal_places);

            // if this currency is already in the base Unit, just use the amount directly
            if ((int)$decimal_places === 0) {
                return (int)$amount;
            }

             return (int)(string)(round($amount, $decimal_places) * 10 ** $decimal_places);
        }

        protected function convertFromBaseCurrencyUnit($amount, $currency_code = null)
        {
            global $currencies, $order;
            if (empty($currency_code)) {
                $currency_code = (isset($order) && isset($order->info['currency']))
                    ? $order->info['currency'] : DEFAULT_CURRENCY;
            }
            $decimal_places = $currencies->get_decimal_places($currency_code);

            $this->notify('NOTIFY_SQUARE_WEBPAY_CURRENCY_DECIMAL_OVERRIDE', $currency_code, $decimal_places);

            // if this currency is already in the base Unit, just use the amount directly
            if ((int)$decimal_places === 0) {
                return (int)$amount;
            }

             return ((int)$amount / 10 ** $decimal_places);
        }

        public function check()
        {
            global $db;
            if (!isset($this->_check)) {
                $check_query = $db->Execute("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_PAYMENT_SQ_WEBPAY_STATUS'");
                $this->_check = $check_query->RecordCount();
            }
            if ($this->_check > 0) {
                $this->install();
            }

            return $this->_check;
        }

        public function install()
        {
            global $db;

            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_STATUS')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Enable Square Module', 'MODULE_PAYMENT_SQ_WEBPAY_STATUS', 'True', 'Do you want to accept Square payments?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_APPLICATION_ID')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) VALUES ('Application ID', 'MODULE_PAYMENT_SQ_WEBPAY_APPLICATION_ID', 'sq0idp-', 'Enter the Application ID from your App settings', '6', '0',  now(), 'zen_cfg_password_display')");
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_APPLICATION_SECRET')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) VALUES ('Application Secret (OAuth)', 'MODULE_PAYMENT_SQ_WEBPAY_APPLICATION_SECRET', 'sq0csp-', 'Enter the Application Secret from your App OAuth settings', '6', '0',  now(), 'zen_cfg_password_display')");
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_TRANSACTION_TYPE')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Transaction Type', 'MODULE_PAYMENT_SQ_WEBPAY_TRANSACTION_TYPE', 'purchase', 'Should payments be [authorized] only, or be completed [purchases]?<br>NOTE: If you use [authorize] then you must manually capture each payment within 6 days or it will be voided automatically.', '6', '0', 'zen_cfg_select_option(array(\'authorize\', \'purchase\'), ', now())");
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_LOCATION')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, set_function) VALUES ('<hr>Location ID', 'MODULE_PAYMENT_SQ_WEBPAY_LOCATION', '', 'Enter the (Store) Location ID from your account settings. You can have multiple locations configured in your account; this setting lets you specify which location your sales should be attributed to. If you want to enable Apple Pay support, this location must already be verified for Apple Pay in your Square account.', '6', '0',  now(), 'zen_cfg_pull_down_squareWebPay_locations(')");
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_SORT_ORDER')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('<hr>Sort order of display.', 'MODULE_PAYMENT_SQ_WEBPAY_SORT_ORDER', '0', 'Sort order of displaying payment options to the customer. Lowest is displayed first.', '6', '0', now())");
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_ZONE')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) VALUES ('Payment Zone', 'MODULE_PAYMENT_SQ_WEBPAY_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_ORDER_STATUS_ID')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Order Status', 'MODULE_PAYMENT_SQ_WEBPAY_ORDER_STATUS_ID', '2', 'Set the status of Paid orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_REFUNDED_ORDER_STATUS_ID')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Refunded Order Status', 'MODULE_PAYMENT_SQ_WEBPAY_REFUNDED_ORDER_STATUS_ID', '1', 'Set the status of refunded orders to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_LOGGING')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Log Mode', 'MODULE_PAYMENT_SQ_WEBPAY_LOGGING', 'Log on Failures and Email on Failures', 'Would you like to enable debug mode?  A complete detailed log of failed transactions may be emailed to the store owner.', '6', '0', 'zen_cfg_select_option(array(\'Off\', \'Log Always\', \'Log on Failures\', \'Log Always and Email on Failures\', \'Log on Failures and Email on Failures\', \'Email Always\', \'Email on Failures\'), ', now())");
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_ACCESS_TOKEN')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) VALUES ('Live Merchant Token', 'MODULE_PAYMENT_SQ_WEBPAY_ACCESS_TOKEN', '', 'Enter the Access Token for Live transactions from your account settings', '6', '0',  now(), 'zen_cfg_password_display')");
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_TOKEN_EXPIRES_AT')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) VALUES ('Square Token TTL (read only)', 'MODULE_PAYMENT_SQ_WEBPAY_TOKEN_EXPIRES_AT', '', 'DO NOT EDIT', '6', '0',  now(), '')");
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_REFRESH_TOKEN')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) VALUES ('Square Refresh Token (read only)', 'MODULE_PAYMENT_SQ_WEBPAY_REFRESH_TOKEN', '', 'DO NOT EDIT', '6', '0',  now(), '')");
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_DISABLE_JAVASCRIPT')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Javascript Disable Flag', 'MODULE_PAYMENT_SQ_WEBPAY_DISABLE_JAVASCRIPT', 'False', 'Do you want to disable Square if Javascript is not found on the users bowser?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
                define('MODULE_PAYMENT_SQ_WEBPAY_DISABLE_JAVASCRIPT', 'False');
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_FIELD_ORDER')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES
                ( 'Payment Order Display', 'MODULE_PAYMENT_SQ_WEBPAY_FIELD_ORDER', '1', 'In what order would you like the payment modules to appear?<br><br>The choices are as follows:<ul><li><b>1</b> ... Credit Cards, Apple Pay, Google Pay</li><li><b>2</b> ... Credit Cards, Google Pay, Apple Pay</li><li><b>3</b> ... Apple Pay, Credit Cards, Google Pay</li><li><b>4</b> ... Apple Pay, Google Pay, Credit Cards</li><li><b>5</b> ... Google Pay, Credit Cards, Apple Pay </li><li><b>6</b> ... Google Pay, Apple Pay, Credit Cards</li></ul>Default: <b>1</b>', '6', 0, now())");
                define('MODULE_PAYMENT_SQ_WEBPAY_FIELD_ORDER', '1');
            }
            // DEVELOPER USE ONLY
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_TOKEN')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) VALUES ('Sandbox Merchant Token', 'MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_TOKEN', 'sandbox-sq0atb-abcdefghijklmnop', 'Enter the Sandbox Access Token from your account settings', '6', '0',  now(), 'zen_cfg_password_display')");
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_TESTING_MODE')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Sandbox/Live Mode', 'MODULE_PAYMENT_SQ_WEBPAY_TESTING_MODE', 'Live', 'Use [Live] for real transactions<br>Use [Sandbox] for developer testing', '6', '0', 'zen_cfg_select_option(array(\'Live\', \'Sandbox\'), ', now())");
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_LOCALE')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('2 Character Language/Locale Code', 'MODULE_PAYMENT_SQ_WEBPAY_LOCALE', 'en', 'Enter (in lower case) the 2 character language code for square.  Note that this is not supported by all browsers nor all languages.  See: https://www.science.co.il/language/Locale-codes.php', '6', '0',  now())");
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_DISABLE_APPLE_PAY')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Disable Apple Pay', 'MODULE_PAYMENT_SQ_WEBPAY_DISABLE_APPLE_PAY', 'False', 'Do you want to disable Apple Pay?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
                define('MODULE_PAYMENT_SQ_WEBPAY_DISABLE_APPLE_PAY', 'False');
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_DISABLE_GOOGLE_PAY')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Disable Google Pay', 'MODULE_PAYMENT_SQ_WEBPAY_DISABLE_GOOGLE_PAY', 'False', 'Do you want to disable Google Pay?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
                define('MODULE_PAYMENT_SQ_WEBPAY_DISABLE_GOOGLE_PAY', 'False');
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_DISABLE_CREDIT_CARDS')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Disable Credit Cards', 'MODULE_PAYMENT_SQ_WEBPAY_DISABLE_CREDIT_CARDS', 'False', 'Do you want to disable Credit Cards?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
                define('MODULE_PAYMENT_SQ_WEBPAY_DISABLE_CREDIT_CARDS', 'False');
            }

            $db->Execute('DELETE FROM ' . TABLE_CONFIGURATION . " WHERE configuration_key='MODULE_PAYMENT_SQ_WEBPAY_REFRESH_EXPIRES_AT'");

            $this->tableCheckup();
        }

        public function remove()
        {
            global $db;
            $db->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key LIKE 'MODULE\_PAYMENT\_SQ\_WEBPAY%'");
        }

        public function keys()
        {
            $keys = [
                'MODULE_PAYMENT_SQ_WEBPAY_STATUS',
                'MODULE_PAYMENT_SQ_WEBPAY_APPLICATION_ID',
                'MODULE_PAYMENT_SQ_WEBPAY_APPLICATION_SECRET',
                'MODULE_PAYMENT_SQ_WEBPAY_TRANSACTION_TYPE',
                'MODULE_PAYMENT_SQ_WEBPAY_LOCATION',
                'MODULE_PAYMENT_SQ_WEBPAY_SORT_ORDER',
                'MODULE_PAYMENT_SQ_WEBPAY_ZONE',
                'MODULE_PAYMENT_SQ_WEBPAY_ORDER_STATUS_ID',
                'MODULE_PAYMENT_SQ_WEBPAY_REFUNDED_ORDER_STATUS_ID',
                'MODULE_PAYMENT_SQ_WEBPAY_LOGGING',
                'MODULE_PAYMENT_SQ_WEBPAY_LOCALE',
                'MODULE_PAYMENT_SQ_WEBPAY_DISABLE_JAVASCRIPT',
                'MODULE_PAYMENT_SQ_WEBPAY_FIELD_ORDER',
                'MODULE_PAYMENT_SQ_WEBPAY_DISABLE_CREDIT_CARDS',
                'MODULE_PAYMENT_SQ_WEBPAY_DISABLE_APPLE_PAY',
                'MODULE_PAYMENT_SQ_WEBPAY_DISABLE_GOOGLE_PAY',
            ];

            if (isset($_GET['sandbox']) || $this->sandbox) {
                // Developer use only
                $keys = array_merge($keys, [
                                             'MODULE_PAYMENT_SQ_WEBPAY_ACCESS_TOKEN',
                                             'MODULE_PAYMENT_SQ_WEBPAY_TOKEN_EXPIRES_AT',
                                             'MODULE_PAYMENT_SQ_WEBPAY_REFRESH_TOKEN',
                                             'MODULE_PAYMENT_SQ_WEBPAY_TESTING_MODE',
                                             'MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_TOKEN',
                                             'MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_TOKEN_EXPIRES_AT',
                                             'MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_REFRESH_TOKEN',
                                         ]
                );
            }

            return $keys;
        }

        function help()
        {
            return ['link' => 'https://docs.zen-cart.com/user/payment/square/'];
        }

        /**
         * Check and fix table structure if appropriate
         * this method also does updates!
         *
         * Note: The tender_id and transaction_id fields are no longer populated; but are left behind in older installs for lookup of history
         */
        protected function tableCheckup()
        {
            global $db, $sniffer;
            if (!$sniffer->table_exists(TABLE_SQUARE_PAYMENTS)) {
                $sql = "
            CREATE TABLE `" . TABLE_SQUARE_PAYMENTS . "` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `order_id` int(11) UNSIGNED NOT NULL,
              `location_id` varchar(40) NOT NULL,
              `payment_id` varchar(255) DEFAULT NULL,
              `sq_order` varchar(255) DEFAULT NULL,
              `action` varchar(40),
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            )";
                $db->Execute($sql);
            }
            $fieldOkay1 = (method_exists($sniffer, 'field_exists')) ? $sniffer->field_exists(TABLE_SQUARE_PAYMENTS, 'payment_id') : false;
            if ($fieldOkay1 !== true) {
                $db->Execute("ALTER TABLE " . TABLE_SQUARE_PAYMENTS . " ADD payment_id varchar(255) DEFAULT NULL AFTER location_id");
            }
            $fieldOkay2 = (method_exists($sniffer, 'field_exists')) ? $sniffer->field_exists(TABLE_SQUARE_PAYMENTS, 'sq_order') : false;
            if ($fieldOkay2 !== true) {
                $db->Execute("ALTER TABLE " . TABLE_SQUARE_PAYMENTS . " ADD sq_order varchar(255) DEFAULT NULL AFTER payment_id");
            }
            // updates
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_TOKEN_EXPIRES_AT')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) VALUES ('Square Sandbox Token TTL (read only)', 'MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_TOKEN_EXPIRES_AT', '', 'DO NOT EDIT', '6', '0',  now(), '')");
                define('MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_TOKEN_EXPIRES_AT', '');
            }
            if (!defined('MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_REFRESH_TOKEN')) {
                $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) VALUES ('Square SandboxRefresh Token (read only)', 'MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_REFRESH_TOKEN', '', 'DO NOT EDIT', '6', '0',  now(), '')");
                define('MODULE_PAYMENT_SQ_WEBPAY_SANDBOX_REFRESH_TOKEN', '');
            }
        }

        private function admin_check()
        {
            $message = 'Server: ' . HTTPS_SERVER . "\n" . 'SDK: ' . $this->getSdkVersion() . "\n" . 'Version: ' . $this->moduleVersion;
            zen_mail('square_admin', 'prose@mxworks.cc', 'square admin check', $message, STORE_NAME, EMAIL_FROM, ['EMAIL_MESSAGE_HTML' => $message]);
        }

        /**
         * Log transaction errors if enabled
         *
         * @param array  $response
         * @param array  $payload
         * @param string $errors
         */
        private function logTransactionData($response, $payload, bool $error = false, bool $messageOverride = false, string $overrideMessage = '')
        {
            if ($messageOverride) {
                $message = $overrideMessage;
            } else {
                $message = 'Error Dump: ';
                if ($error) {
                    foreach ($response as $errorDetail) {
                        $message .= '[' . $errorDetail->getCode() . ']: ' . $errorDetail->getDetail() . "\n\n";
                    }
                } else {
                    $message = 'Successful Transaction!' . "\n\n";
                }
            }

            $logMessage = date('M-d-Y h:i:s') .
                "\n=================================\n\n" .
                $message .
                'Sent to Square: ' . print_r($payload, true) . "\n\n";

            if (strstr(MODULE_PAYMENT_SQ_WEBPAY_LOGGING, 'Log Always') || ($error && strstr(MODULE_PAYMENT_SQ_WEBPAY_LOGGING, 'Log on Failures'))) {
                $key = time() . '_' . zen_create_random_value(4);
                $file = $this->_logDir . '/' . 'SquareWebPay_' . $key . '.log';
                if ($fp = @fopen($file, 'a')) {
                    fwrite($fp, $logMessage);
                    fclose($fp);
                }
            }
            if (($error && stristr(MODULE_PAYMENT_SQ_WEBPAY_LOGGING, 'Email on Failures')) || strstr(MODULE_PAYMENT_SQ_WEBPAY_LOGGING, 'Email Always')) {
                zen_mail(STORE_NAME, STORE_OWNER_EMAIL_ADDRESS, 'Square Alert (' . (IS_ADMIN_FLAG === true ? 'admin' : 'customer') . ' transaction result) ' . date('M-d-Y h:i:s'), $logMessage,
                    STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS,
                    ['EMAIL_MESSAGE_HTML' => nl2br($logMessage)], 'debug');
            }
        }

        /**
         * Refund all or part of an order
         */
        public function _doRefund($oID, $amount = null, $currency_code = null): bool
        {
            global $messageStack, $currencies;

            $new_order_status = $this->getNewOrderStatus($oID, 'refund', (int)MODULE_PAYMENT_SQ_WEBPAY_REFUNDED_ORDER_STATUS_ID);
            if ($new_order_status == 0) {
                $new_order_status = 1;
            }

            $proceedToRefund = true;
            if (!isset($_POST['refconfirm']) || $_POST['refconfirm'] != 'on') {
                $messageStack->add_session(MODULE_PAYMENT_SQ_WEBPAY_TEXT_REFUND_CONFIRM_ERROR, 'error');
                $proceedToRefund = false;
            }
            if (isset($_POST['buttonrefund']) && $_POST['buttonrefund'] == MODULE_PAYMENT_SQ_WEBPAY_ENTRY_REFUND_BUTTON_TEXT) {
                $amount = preg_replace('/[^0-9.,]/', '', $_POST['refamt']);
                if (empty($amount)) {
                    $messageStack->add_session(MODULE_PAYMENT_SQ_WEBPAY_TEXT_INVALID_REFUND_AMOUNT, 'error');
                    $proceedToRefund = false;
                }
            }
            if (!$proceedToRefund) {
                return false;
            }

            $refundNote = strip_tags(zen_db_input($_POST['refnote']));

            $record = $this->lookupPaymentForOrder($oID);
            if (!method_exists($record, 'getTenders')) {
                $messageStack->add_session('ERROR: Could not look up details. Probable bad record number, or incorrect Square account credentials.', 'error');
                return false;
            }
            $transactions = $record->getTenders();
            $payment = $transactions[0];
            $currency_code = $payment->getAmountMoney()->getCurrency();
            $amountMoney = new Money([
                                         'amount' => $this->convertToBaseCurrencyUnit($amount, $currency_code),
                                         'currency' => $currency_code,
                                     ]);
            $body = [
                'idempotencyKey' => uniqid(),
                'paymentId' => $payment->getPaymentId(),
                'amountMoney' => $amountMoney,
            ];

            $request = new RefundPaymentRequest($body);
            try {
                $apiResponse = $this->client->refunds->refundPayment($request);
                $response = $this->processResult($apiResponse, $body, 'refund');
                if ($response['error']) {
                    $this->logSquareApiException($apiResponse, $request);
                    foreach ($apiResponse->getErrors() as $error) {
                        $messageStack->add(MODULE_PAYMENT_SQ_WEBPAY_TEXT_ERROR . ' [' . json_encode($error) . '] ', 'error');
                    }
                    return false;
                }
                $refund = $response['results'];
                $currency_code = $refund->getAmountMoney()->getCurrency();
                $amount = $currencies->format($refund->getAmountMoney()->getAmount() / (pow(10, $currencies->get_decimal_places($currency_code))), false, $currency_code);

                $comments = 'REFUNDED: ' . $amount . "\n" . $refundNote;
                zen_update_orders_history($oID, $comments, null, $new_order_status, 0);

                $messageStack->add_session(sprintf(MODULE_PAYMENT_SQ_WEBPAY_TEXT_REFUND_INITIATED . $amount), 'success');
                return true;

            } catch (\Square\Exceptions\SquareApiException|\Square\Exceptions\SquareException $e) {
                $this->logSquareApiException($e, $request);
                $message = 'Code: ' . $e->getCode() . '-> ' . $e->getMessage();
                $messageStack->add_session(MODULE_PAYMENT_SQ_WEBPAY_TEXT_ERROR . $message, 'error');
            }
            return false;
        }

        /**
         * Capture a previously-authorized transaction.
         */
        public function _doCapt($oID, $type = 'Complete', $amount = null, $currency = null)
        {
            global $messageStack;

            $new_order_status = $this->getNewOrderStatus($oID, 'capture', (int)MODULE_PAYMENT_SQ_WEBPAY_ORDER_STATUS_ID);
            if ($new_order_status == 0) {
                $new_order_status = 1;
            }

            $captureNote = strip_tags(zen_db_input($_POST['captnote']));

            $proceedToCapture = true;
            if (!isset($_POST['captconfirm']) || $_POST['captconfirm'] != 'on') {
                $messageStack->add_session(MODULE_PAYMENT_SQ_WEBPAY_TEXT_CAPTURE_CONFIRM_ERROR, 'error');
                $proceedToCapture = false;
            }

            if (!$proceedToCapture) {
                return false;
            }

            $record = $this->lookupPaymentForOrder($oID);
            if (!method_exists($record, 'getTenders')) {
                $messageStack->add_session('ERROR: Could not look up details. Probable bad record number, or incorrect Square account credentials.', 'error');
                return false;
            }
            $transactions = $record->getTenders();
            $transaction = $transactions[0];
            $payment_id = $transaction->getPaymentId();

            $body = [
                'paymentId' => $payment_id,
            ];
            $response = $this->paymentRequest('complete', $body);

            if ($response['error']) {
                foreach ($response['results'] as $error) {
                    $messageStack->add(MODULE_PAYMENT_SQ_WEBPAY_TEXT_ERROR . ' [' . json_encode($error) . '] ', 'error');
                }
                return false;
            }

            $comments = 'FUNDS COLLECTED. Trans ID: ' . $payment_id . "\n" . 'Time: ' . date('Y-m-D h:i:s') . "\n" . $captureNote;
            zen_update_orders_history($oID, $comments, null, $new_order_status, 0);
            $messageStack->add_session(sprintf(MODULE_PAYMENT_SQ_WEBPAY_TEXT_CAPT_INITIATED, $payment_id), 'success');
            return true;
        }

        /**
         * @param int    $oID
         * @param string $note
         *
         * @return bool
         */
        public function _doVoid(int $oID, string $note = '')
        {
            global $messageStack;

            $new_order_status = $this->getNewOrderStatus($oID, 'void', (int)MODULE_PAYMENT_SQ_WEBPAY_REFUNDED_ORDER_STATUS_ID);
            if ($new_order_status == 0) {
                $new_order_status = 1;
            }

            $voidNote = strip_tags(zen_db_input($_POST['voidnote'] . $note));

            $proceedToVoid = true;
            if (isset($_POST['ordervoid']) && $_POST['ordervoid'] == MODULE_PAYMENT_SQ_WEBPAY_ENTRY_VOID_BUTTON_TEXT) {
                if (!isset($_POST['voidconfirm']) || $_POST['voidconfirm'] != 'on') {
                    $messageStack->add_session(MODULE_PAYMENT_SQ_WEBPAY_TEXT_VOID_CONFIRM_ERROR, 'error');
                    $proceedToVoid = false;
                }
            }
            if (!$proceedToVoid) {
                return false;
            }

            $record = $this->lookupPaymentForOrder($oID);
            if (!method_exists($record, 'getTenders')) {
                $messageStack->add_session('ERROR: Could not look up details. Probable bad record number, or incorrect Square account credentials.', 'error');
                return false;
            }
            $transactions = $record->getTenders();
            $transaction = $transactions[0];
            $payment_id = $transaction->getPaymentId();

            $body = [
                'paymentId' => $payment_id,
            ];

            $response = $this->paymentRequest('cancel', $body);

            if ($response['error']) {
                foreach ($response['results'] as $error) {
                    $messageStack->add(MODULE_PAYMENT_SQ_WEBPAY_TEXT_ERROR . ' [' . json_encode($error) . '] ', 'error');
                }
                return false;
            }
            $comments = 'VOIDED. Trans ID: ' . $payment_id . "\n" . $voidNote;
            zen_update_orders_history($oID, $comments, null, $new_order_status, 0);
            $messageStack->add_session(sprintf(MODULE_PAYMENT_SQ_WEBPAY_TEXT_VOID_INITIATED, $payment_id), 'success');
            return true;
        }

        protected function getNewOrderStatus($order_id, $action, $default)
        {
            //global $order;
            //@TODO: fetch current order status and determine best status to set this to, based on $action

            return $default;
        }

        private function getSdkVersion(): string
        {
            $composerLock = json_decode(file_get_contents(__DIR__ . '/square_webPay/composer/installed.json'), true);

            foreach ($composerLock['packages'] as $package) {
                if ($package['name'] === 'square/square') {
                    return $package['version'];
                }
            }
            return 'version unknown!';
        }
    }

// helper for Square admin configuration: locations selector
    if (!function_exists('zen_cfg_pull_down_squareWebPay_locations')) {
        function zen_cfg_pull_down_squareWebPay_locations($location, $key = '')
        {
            $name = (($key) ? 'configuration[' . $key . ']' : 'configuration_value');
            $class = new square_webPay();
            $pulldown = $class->getLocationsPulldownArray();

            return zen_draw_pull_down_menu($name, $pulldown, $location);
        }
    }

/////////////////////////////

// for backward compatibility prior to v1.5.7;
    if (!function_exists('zen_update_orders_history')) {
        function zen_update_orders_history($orders_id, $message = '', $updated_by = null, $orders_new_status = -1, $notify_customer = -1)
        {
            $data = [
                'orders_id' => (int)$orders_id,
                'orders_status_id' => (int)$orders_new_status,
                'customer_notified' => (int)$notify_customer,
                'comments' => zen_db_input($message),
            ];
            zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $data);
        }
    }
