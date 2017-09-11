<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function fondy_MetaData()
{
    return array(
        'DisplayName' => 'Fondy',
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
            'Value' => 'Fondy',
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
            'FriendlyName' => 'Select Language',
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
	include dirname(__FILE__) . "/fondy.cls.php";
	
    // Gateway Configuration Parameters
    $accountId = $params['accountID'];
    $secretKey = $params['secretKey'];
	//$lang = $params['lang']

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    $currencyCode = $params['cur'];

    // Client Parameters
    $email = $params['clientdetails']['email'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];

    $url = 'https://api.fondy.eu/api/checkout/redirect/';

    $postfields = array();
    $postfields['order_id'] =  $invoiceId. Fondy::ORDER_SEPARATOR . time();
    $postfields['merchant_id'] =  $accountId;
    $postfields['order_desc'] = $description;
    $postfields['amount'] = round($amount * 100, 2);;
    $postfields['currency'] = $currencyCode;
    $postfields['lang'] = $params['lang'];
    $postfields['sender_email'] = $email;
    $postfields['server_callback_url'] = $systemUrl . '/modules/gateways/callback/' . $moduleName . '.php';
	$postfields['response_url'] = $systemUrl . '/modules/gateways/callback/' . $moduleName . '.php';
	$postfields['signature'] = Fondy::getSignature($postfields,  $secretKey);

	
    $htmlOutput = '<form method="post" action="' . $url . '">';
    foreach ($postfields as $k => $v) {
        $htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
    }
    $htmlOutput .= '<input type="submit" class="btn btn-action" value="' . $langPayNow . '" />';
    $htmlOutput .= '</form>';
	//
    return $htmlOutput;
}

