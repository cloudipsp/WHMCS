<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once dirname(__FILE__) . "/fondy/fondy_cls.php";

function fondy_MetaData()
{
    return array(
        'DisplayName' => 'FONDY - Online payments',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}


function fondy_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'FONDY - Online payments',
        ),
        // a text field type allows for single line text input
        'accountID' => array(
            'FriendlyName' => 'Merchant ID',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter your Merchant ID here',
        ),
        // a password field type allows for masked text input
        'secretKey' => array(
            'FriendlyName' => 'Secret Key',
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter secret key here',
        ),

        // the dropdown field type renders a select menu of options
        'lang' => array(
            'FriendlyName' => 'Select Language',
            'Type' => 'dropdown',
            'Options' => array(
                'ru' => 'RU',
                'uk' => 'UK',
                'en' => 'EN',
                'lv' => 'LV',
                'fr' => 'FR',
            ),
            'Description' => 'Choose language',
        ),
        'cur' => array(
            'FriendlyName' => 'Select Default Currency',
            'Type' => 'dropdown',
            'Options' => array(
                'UAH' => 'Ukrainian Hryvnia',
                'RUB' => 'Russian Rouble',
                'USD' => 'US Dollar',
                'EUR' => 'Euro',
                'GBP' => 'Pound sterling',
            ),
            'Description' => 'Choose currency',
        ),

    );
}


function fondy_link($params)
{
    // Gateway Configuration Parameters
    $accountId = $params['accountID'];
    $secretKey = $params['secretKey'];

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];
    if (!$currencyCode)
        $currencyCode = $params['cur'];

    // Client Parameters
    $email = $params['clientdetails']['email'];

    // System Parameters
    $systemUrl = $params['systemurl'];
    $langPayNow = $params['langpaynow'];
    $moduleName = $params['paymentmethod'];

    $url = 'https://api.fondy.eu/api/checkout/redirect/';

    $postfields = array();
    $postfields['order_id'] = $invoiceId . Fondy_Cls::ORDER_SEPARATOR . time();
    $postfields['merchant_id'] = $accountId;
    $postfields['order_desc'] = $description;
    $postfields['amount'] = round($amount * 100, 2);
    $postfields['currency'] = $currencyCode;
    $postfields['lang'] = $params['lang'];
    $postfields['sender_email'] = $email;
    $postfields['server_callback_url'] = $systemUrl . '/modules/gateways/callback/' . $moduleName . '.php';
    $postfields['response_url'] = $systemUrl . '/modules/gateways/callback/' . $moduleName . '.php';
    $postfields['signature'] = Fondy_Cls::getSignature($postfields, $secretKey);

    $htmlOutput = '<form method="post" action="' . $url . '">';
    foreach ($postfields as $k => $v) {
        $htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
    }
    $htmlOutput .= '<input type="submit" class="btn btn-action" value="' . $langPayNow . '" />';
    $htmlOutput .= '</form>';
    //
    return $htmlOutput;
}

function fondy_refund($params)
{
    // Gateway Configuration Parameters
    $accountId = $params['accountID'];
    $secretKey = $params['secretKey'];

    // Transaction Parameters
    $transactionIdToRefund = $params['transid'];
    $refundAmount = $params['amount'];
    $currencyCode = $params['currency'];
    $reason = 'Return ' . $params['description'];

    $result = doFondyRefund($refundAmount, $reason, $transactionIdToRefund, $secretKey, $accountId, $currencyCode);
    // perform API call to initiate refund and interpret result
    return $result;
}

function doFondyRefund($refundAmount, $reason, $transactionIdToRefund, $secretKey, $accountId, $currencyCode)
{
    $refund_args = array(
        'request' => array(
            'amount' => round($refundAmount * 100),
            'order_id' => $transactionIdToRefund,
            'currency' => $currencyCode,
            'merchant_id' => $accountId,
            'comment' => $reason
        )
    );
    $refund_args['request']['signature'] = Fondy_Cls::getSignature($refund_args['request'], $secretKey);
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.fondy.eu/api/reverse/order_id');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($refund_args));
        $result = json_decode(curl_exec($ch));
        if ($result->response->response_status == 'failure') {
            $out = array(
                'status' => 'error',
                'rawdata' => $result->response->error_message
            );
        } else {
            $out = array(
                'status' => 'success',
                'rawdata' => (array)$result->response,
                'transid' => $result->response->transaction_id,
                'fees' => 0,
            );
        }
        return $out;
    } catch (Exception $e) {
        $out = array(
            'status' => 'error',
            'rawdata' => $e
        );
    }
    return $out;
}