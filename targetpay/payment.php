<?php
/**
 * @file	Provides support for TargetPay iDEAL, Mister Cash and Sofort Banking
 * @author	Yellow Melon B.V.
 * @url		http://www.idealplugins.nl
 */

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
require_once(dirname(__FILE__).'/targetpay.php');

if (!$cookie->isLogged(true)) {
	Tools::redirect('authentication.php?back=order.php');
} elseif (!Customer::getAddressesTotalById((int)($cookie->id_customer))) {
	Tools::redirect('address.php?back=order.php?step=1');
} else if(!isset($_POST)) {
	Tools::redirect('order.php?step=3');
}
$tp = new Targetpay();
if(!$tp->retrievePaymentUrl()) {
	$error = '&error='.urlencode(end($tp->_errors));
	$paymethod = '&method='.urlencode($tp->payMethod);
	Tools::redirect('order.php?step=3'.$error.$paymethod);
}

include_once(dirname(__FILE__).'/../../footer.php');

