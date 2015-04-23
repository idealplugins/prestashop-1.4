<?php
/**
 * @file	Provides support for TargetPay iDEAL, Mister Cash and Sofort Banking
 * @author	Yellow Melon B.V.
 * @url		http://www.idealplugins.nl
 *
 * 11-09-2014 -> Removed checkReportValidity
 */

	include(dirname(__FILE__).'/../../config/config.inc.php');
	require_once(dirname(__FILE__).'/targetpay.php');
	require_once(dirname(__FILE__).'/targetpay.class.php');
	$targetpay = new Targetpay();

	$rtlo = (int)Configuration::get('RTLO');
	$trxid = Tools::getValue('trxid');
	
	$transactionInfoArr = $targetpay->selectTransaction($trxid);
	$targetpayObj = new TargetPayCore($transactionInfoArr["paymethod"],$rtlo);
	$targetpayObj->checkPayment($trxid);
	
	if($targetpayObj->getPaidStatus()) {
		$state = Configuration::get('PS_OS_PAYMENT');
		$cart = new Cart($transactionInfoArr["cart_id"]);
		$targetpay->validateOrder(intval($cart->id), $state, $transactionInfoArr["amount"], $targetpay->displayName."(".$transactionInfoArr["paymethod"].")",NULL,array("transaction_id" => $transactionInfoArr["transaction_id"]));
		$order = new Order(intval($targetpay->currentOrder));
		$updateArr = $targetpayObj->getConsumerInfo();
		$updateArr["order_id"] = $order->id;
		$updateArr["status"] = 1;
		$targetpay->updateTransaction($updateArr,$trxid, 'notify');
		echo "Paid... ";
	} else {
		echo "Not paid... ";
	}
	echo "(Prestashop-1.4, 23-04-2015)";
	die();


