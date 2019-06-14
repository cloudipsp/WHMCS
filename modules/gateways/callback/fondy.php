<?php

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
require_once __DIR__ . '/../../../modules/gateways/fondy/fondy_cls.php';

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}
if (empty($_POST)) {
    $fap = json_decode(file_get_contents("php://input"));
    $_POST = array();
    foreach ($fap as $key => $val) {
        $_POST[$key] = $val;
    }
}

// Retrieve data returned in payment gateway callback
// Varies per payment gateway
$success = $_POST["response_status"];
$order_id = explode("#", $_POST["order_id"]);
$invoiceId = $order_id[0];
$transactionId = $_POST["order_id"];
$paymentAmount = $_POST["amount"] / 100;


/**
 * Validate callback authenticity.
 *
 * Most payment gateways provide a method of verifying that a callback
 * originated from them. In the case of our example here, this is achieved by
 * way of a shared secret which is used to build and compare a hash.
 */

$fondy = new Fondy_Cls();

$fondySettings['MERCHANT'] = $gatewayParams['accountID'];
$fondySettings['SECURE_KEY'] = $gatewayParams['secretKey'];
$fondyResult = $fondy->isPaymentValid($fondySettings, $_POST);
if ($_POST['order_status'] == $fondy->ORDER_DECLINED) {
    $transactionStatus = 'failure';
} elseif ($fondyResult === 1 and $_POST['order_status'] == $fondy->ORDER_APPROVED) {
    $transactionStatus = 'success';
} else {
    $transactionStatus = $fondyResult;
}

$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

checkCbTransID($transactionId);

logTransaction($gatewayParams['name'], $_POST, $transactionStatus);

if ($transactionStatus) {

    addInvoicePayment(
        $invoiceId,
        $transactionId,
        $paymentAmount,
        0,
        $gatewayModuleName
    );

}
echo "<script>location.replace('/clientarea.php?action=invoices')</script>";

