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
   *  06/2025  project: square_webPay v3.0.2 file: jscript_square_webPay.php
   */
   
    if (!defined('MODULE_PAYMENT_SQ_WEBPAY_STATUS') || MODULE_PAYMENT_SQ_WEBPAY_STATUS != 'True' || !defined('MODULE_PAYMENT_SQ_WEBPAY_APPLICATION_ID')) {
        return false;
    }
    if ($payment_modules->in_special_checkout() || empty($square_webPay) || !$square_webPay->enabled) {
        return false;
    }

    $jsurl = 'https://web.squarecdn.com/v1/square.js';
    if (MODULE_PAYMENT_SQ_WEBPAY_TESTING_MODE === 'Sandbox') {
        $jsurl = 'https://sandbox.web.squarecdn.com/v1/square.js';
    }
    $locale = 'en';
    if (defined('MODULE_PAYMENT_SQ_WEBPAY_LOCALE')) {
        $locale = strtolower(substr((MODULE_PAYMENT_SQ_WEBPAY_LOCALE), 0, 2));
    }
    $google = $applePay = $creditCards = false;
    if (in_array(MODULE_PAYMENT_SQ_WEBPAY_DISABLE_APPLE_PAY, ['False', 'false', 'FALSE',])) {
        $applePay = true;
    }
    if (in_array(MODULE_PAYMENT_SQ_WEBPAY_DISABLE_CREDIT_CARDS, ['False', 'false', 'FALSE',])) {
        $creditCards = true;
    }
    if (in_array(MODULE_PAYMENT_SQ_WEBPAY_DISABLE_GOOGLE_PAY, ['False', 'false', 'FALSE',])) {
        $google = true;
    }

    $calculatedTotal = $order_total_modules->pre_confirmation_check(true);
    $values = json_encode([
                              'orderInfo' => $order->info,
                              'orderBilling' => $order->billing,
                              'orderCustomer' => $order->customer,
                              'appId' => MODULE_PAYMENT_SQ_WEBPAY_APPLICATION_ID,
                              'locationId' => $square_webPay->getLocationDetails()['id'],
                              'squareCurrency' => $square_webPay->getLocationDetails()['currency'],
                              'handler' => DIR_WS_HTTPS_CATALOG . 'squareWebPay_handler.php',
                              'textTotal' => TEXT_YOUR_TOTAL ?? 'Total:',
                              'orderTotal' => zen_round($calculatedTotal, 2),
                              'locale' => $locale,
                              'google' => $google,
                              'apple' => $applePay,
                              'creditCards' => $creditCards,
                          ]);

?>
    <style>
        #apple-pay-button {
            height: 48px;
            width: 77%;
            display: inline-block;
            -webkit-appearance: -apple-pay-button;
            -apple-pay-button-type: check-out;
            -apple-pay-button-style: white-outline;
        }
    </style>

    <script type="text/javascript" src="<?= $jsurl; ?>" title="square js"></script>

    <script type="text/javascript" title="square">
        const squareOrderValues = <?= $values; ?>;

        const appId = squareOrderValues.appId;
        const locationId = squareOrderValues.locationId;

        async function initializeCard(payments) {

            const card = await payments.card();
            await card.attach('#card-container');
            return card;
        }

        // Call this function to send a payment token, buyer name, and other details
        // to the project server code so that a payment can be created with
        // Payments API
        async function createPayment(token) {
            const body = JSON.stringify({
                locationId,
                sourceId: token,
                idempotencyKey: window.crypto.randomUUID(),
            });
            const paymentResponse = await fetch(squareOrderValues.handler, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body,
            });
            if (paymentResponse.ok) {
                return paymentResponse.json();
            }
            const errorBody = await paymentResponse.text();
            throw new Error(errorBody);
        }

        function buildPaymentRequest(payments) {
            return payments.paymentRequest({
                countryCode: squareOrderValues.orderBilling.country.iso_code_2,
                currencyCode: squareOrderValues.orderInfo.currency,
                total: {
                    amount: squareOrderValues.orderInfo.total.toFixed(2),
                    label: squareOrderValues.textTotal,
                },
            });
        }

        async function initializeApplePay(payments) {
            const paymentRequest = buildPaymentRequest(payments);
            const applePay = await payments.applePay(paymentRequest);
            // Note: You do not need to `attach` applePay.
            return applePay;
        }

        async function initializeGooglePay(payments) {
            const paymentRequest = buildPaymentRequest(payments)

            const googlePay = await payments.googlePay(paymentRequest);
            await googlePay.attach('#google-pay-button');

            return googlePay;
        }

        // This function tokenizes a payment method.
        // The ‘error’ thrown from this async function denotes a failed tokenization,
        // which is due to buyer error (such as an expired card). It is up to the
        // developer to handle the error and provide the buyer the chance to fix
        // their mistakes.
        async function tokenize(paymentMethod) {
            const verificationDetails = {
                amount: squareOrderValues.orderInfo.total.toFixed(2),
                billingContact: {
                    addressLines: [JSON.stringify(squareOrderValues.orderBilling.street_address), JSON.stringify(squareOrderValues.orderBilling.suburb)],
                    familyName: squareOrderValues.orderBilling.lastname,
                    givenName: squareOrderValues.orderBilling.firstname,
                    email: squareOrderValues.orderCustomer.email_address,
                    country: squareOrderValues.orderBilling.country.iso_code_2,
                    phone: squareOrderValues.orderCustomer.telephone,
                    state: JSON.stringify(squareOrderValues.orderBilling.state),
                    city: JSON.stringify(squareOrderValues.orderBilling.city),
                },
                currencyCode: squareOrderValues.squareCurrency,
                intent: 'CHARGE',
                customerInitiated: true,
                sellerKeyedIn: false,
            };
            const tokenResult = await paymentMethod.tokenize(verificationDetails);
            $.ajax({
                url: squareOrderValues.handler,
                method: "POST",
                data: {"token result": tokenResult}
            })
            if (tokenResult.status === 'OK') {
                return tokenResult;
            } else {
                let errorMessage = `Tokenization failed-status: ${tokenResult.status}`;
                if (tokenResult.errors) {
                    errorMessage += ` and errors: ${JSON.stringify(
                        tokenResult.errors
                    )}`;
                }
                throw new Error(errorMessage);
            }
        }

        document.addEventListener('DOMContentLoaded', async function () {
            if (!window.Square) {
                throw new Error('Square.js failed to load properly');
            }

            const payments = window.Square.payments(appId, locationId);
            payments.setLocale(squareOrderValues.locale);
            let card;
            try {
                card = await initializeCard(payments);
                document.querySelector("#card-button").style.visibility = 'hidden';
            } catch (e) {
                console.error('Initializing Card failed', e);
                return;
            }
            card.addEventListener("cardBrandChanged", async (cardInputEvent) => {
                document.querySelector("#pmt-square_webPay").checked = true;
            });
            if (squareOrderValues.creditCards === false) {
                document.querySelector("#card-container").style.visibility = 'hidden';
            }

            let applePay;
            const appleButton = document.querySelector("#apple-pay-button");
            if (window.ApplePaySession && ApplePaySession.canMakePayments()) {
                if (appleButton) {
                    try {
                        applePay = await initializeApplePay(payments);
                    } catch (e) {
                        console.error('Initializing Apple Pay failed', e);
                        document.querySelector("#apple-pay-button").style.height = '0px;';
                    }
                }
            }

            let googlePay;
            const googleButton = document.querySelector("#google-pay-button")
            if (googleButton) {
                try {
                    googlePay = await initializeGooglePay(payments);
                } catch (e) {
                    console.error('Initializing Google Pay failed', e);
                    document.querySelector("#google-pay-button").style.height = '0px;';
                }
            }
            if (squareOrderValues.creditCards === false && !googleButton && !appleButton) {
                document.querySelector("#pmt-square_webPay").style.visibility = 'hidden';
                document.querySelector('label[for="pmt-square_webPay"]').style.visibility = 'hidden';
            }

            // Checkpoint 2.
            const applePayButton = document.getElementById('apple-pay-button');
            if (applePayButton) {
                applePayButton.addEventListener('click', async function (event) {
                    await handlePaymentMethodSubmission(event, applePay);
                });
            }

            const googlePayButton = document.getElementById('google-pay-button');
            if (googlePayButton) {
                googlePayButton.addEventListener('click', async function (event) {
                    await handlePaymentMethodSubmission(event, googlePay);
                });
            }

            async function handlePaymentMethodSubmission(event, paymentMethod) {
                event.preventDefault();

                try {
                    // disable the submit button as we await tokenization and make a
                    // payment request.
                    cardButton.disabled = true;
                    const token = await tokenize(paymentMethod);

                    var tokenResult = document.createElement("input");
                    tokenResult.setAttribute("type", "hidden");
                    tokenResult.setAttribute("name", "tokenResult");
                    tokenResult.setAttribute("value", JSON.stringify(token));
                    document.querySelector('[name="checkout_payment"]').appendChild(tokenResult);

                    cardButton.disabled = false;
                    let isFormValid = document.forms["checkout_payment"].checkValidity();
                    if (isFormValid) {
                        document.forms["checkout_payment"].submit();
                    } else {
                        document.forms["checkout_payment"].reportValidity();
                    }
                } catch (e) {
                    cardButton.disabled = false;
                    console.error(e.message);
                }
            }

            const cardButton = document.getElementById('card-button');
            if (document.querySelector("#checkoutOneSubmit")) {
                formButton = document.querySelector("#checkoutOneSubmit");
                zcLog2Console('found checkoutOneSubmit button!');
            } else {
                console.log('using standard paymentSubmit button');
                formButton = document.querySelector("#paymentSubmit");
            }

            cardButton.addEventListener('click', async function (event) {
                await handlePaymentMethodSubmission(event, card);
            });
            try {
                formButton.addEventListener('click', function (event) {
                    if ((document.querySelector('#pmt-square_webPay').checked) || (document.querySelector('#pmt-square_webPay').getAttribute('type') == 'hidden')) {
                        event.preventDefault();
                        jQuery("#card-button").click();
                    }
                });
            } catch (e) {
                console.log('square is not going to work!');
                console.log('form button not found! removing square');
                document.querySelector('#card-container').remove();
                document.querySelector('#pmt-square_webPay').parentElement.remove();
            }
        });
    </script>
<?php
