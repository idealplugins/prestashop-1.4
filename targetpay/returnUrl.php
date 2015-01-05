<?php
/**
 * @file	Provides support for TargetPay iDEAL, Mister Cash and Sofort Banking
 * @author	Yellow Melon B.V.
 * @url		http://www.idealplugins.nl
 */

	include(dirname(__FILE__).'/../../config/config.inc.php');
	include(dirname(__FILE__).'/../../header.php');
	require_once(dirname(__FILE__).'/targetpay.php');
	$targetpay = new Targetpay();


	$gets = '';
	foreach($_GET AS $key => $value) {
		$gets .= $key.'='.urldecode(Tools::getValue($key)).'&';
	}
	$gets = substr($gets,0,-1);

	if (!$cookie->isLogged(true)) {
		Tools::redirect('authentication.php?back=/modules/'.$targetpay->name.'/returnUrl.php?'.$gets);
	} 

	$trxid = Tools::getValue('trxid');


	$paymentInfo = $targetpay->selectTransaction($trxid);
	$css = 'background-color:#FFA8A8; border-color : #c00;';

	if($paymentInfo === false) {
		$msg = 'transaction ('.htmlspecialchars($trxid).') not found';
	} else {
		require_once(dirname(__FILE__).'/targetpay.class.php');
		$targetpayObj = new TargetPayCore($paymentInfo["paymethod"],$paymentInfo["rtlo"]);
		$targetpayObj->checkPayment($trxid);
		if($targetpayObj->getPaidStatus() == false) {
			list($errorcode,$void) = explode(" ", $targetpayObj->getErrorMessage(),2);
			$showTryAgainLink = false;
			switch ($errorcode) {
				case "TP0010":
					$realstatus = "Open";
					break;
				case "TP0011":
					$realstatus = "Cancelled";
					$showTryAgainLink = true;
					break;
				case "TP0012":
					$realstatus = "Expired";
					$showTryAgainLink = true;
					break;
				case "TP0013":
					$realstatus = "Failure";
					$showTryAgainLink = true;
					break;
				default:
					$realstatus = "Open";
			}
			$msg = 'We have received the following state from paymentprocessor: '.$realstatus . 
					'<br/>The message from the paymentprocessor: '.$void .' (Error code:'. $errorcode .')';
			if($showTryAgainLink == true) {
				$msg .= '<br/><br/>Try again by clicking <a style="font-weight:bold;" href="'.Tools::getShopDomainSsl(true).__PS_BASE_URI__.'order.php?step=3">here</a>';
			}
		} else {
			$css = 'background-color:#ABFFA8; border-color : #05CC00;';
			$msg = ' success ';
		}
	}
	echo '<p style="padding:5px;'.$css.'">'.$msg.'</p>';

	include_once(dirname(__FILE__).'/../../footer.php');

