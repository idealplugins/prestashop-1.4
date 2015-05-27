<?php
/**
 * @file	Provides support for TargetPay iDEAL, Mister Cash and Sofort Banking
 * @author	Yellow Melon B.V.
 * @url		http://www.idealplugins.nl
 */
class targetpay extends PaymentModule {
	
	protected $_html = '';
	
	public $_errors	= array();
	
	public $context;
	public $paymethod;
	
	function __construct()    {
		$this->name = 'targetpay';
		$this->tab = 'payments_gateways';
		$this->author = 'idealplugins.nl';
		$this->version = 1;
		
		$this->ps_versions_compliancy = array('min' => '1.4', 'max' => '1.4'); 
		
		$this->currencies = true;
		$this->currencies_mode = 'radio';
		
		parent::__construct();
		
		/* The parent construct is required for translations */
		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('TargetPay Bank Payments');
		$this->description = $this->l('Let the customer pay with popular payment services such as iDEAL (The Netherlands), MrCash (Belgium), SOFORT Banking (Germany)');

	}
	
	/* Install / uninstall stuff */
	function install() {
		
		if (!parent::install() || !$this->createTargetpayIdealTable() || !$this->registerHook('invoice') || !$this->registerHook('payment') || !$this->registerHook('paymentReturn')) {
			return false;
		}
		
		return true;
	}
	
	
	function uninstall() {
		if (!$this->removeTables() || !parent::uninstall()) {
			return false;
		}
		return true;
	}
	
	function removeTables () {
		
		$db = Db::getInstance(); 
		$query = "DROP TABLE IF EXISTS `"._DB_PREFIX_."targetpay_ideal`";
		$db->Execute($query);
		return true;
	}
	
	function createTargetpayIdealTable() {
		$db = Db::getInstance(); 
		$query = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."targetpay_ideal` (
		`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`order_id` int(11) NULL DEFAULT '0',
		`cart_id` int(11) NOT NULL DEFAULT '0',
		`rtlo` int(11) NOT NULL,
		`paymethod` varchar(8) NOT NULL DEFAULT 'IDE',
		`transaction_id` varchar(255) NOT NULL,
		`bank_id` varchar(8) NOT NULL,
		`description` varchar(32) NOT NULL,
		`amount` decimal(11,2) NOT NULL,
		`bankaccount` varchar(25) NULL,
		`name` varchar(35) NULL,
		`city` varchar(25) NULL,
		`status` int(5) NOT NULL,
		`via` varchar(10) NULL
		) ENGINE = MYISAM ";
		$db->Execute($query);
		Configuration::updateValue('RTLO', 93929); // Default TargetPay
		return true;
	}
	
	/* admin configuration settings */
	
		
	public function getContent() {
			$output = '<h2>'.$this->displayName.'</h2>';
			if (Tools::isSubmit('submitTargetpayValues')) {
				$rtlo = (int)(Tools::getValue('rtlo'));
				if (!$rtlo OR $rtlo <= 0 OR !Validate::isInt($rtlo)) {
					$errors[] = $this->l('Invalid number of products');
				} else {
					Configuration::updateValue('RTLO', (int)($rtlo));
				}
			}
			if (isset($errors) AND sizeof($errors)) {
				$output .= $this->displayError(implode('<br />', $errors));
			} else {
				$output .= $this->displayConfirmation($this->l('Settings updated'));
			}
		return $output.$this->displayForm();
	}
	public function displayForm() {
	  $output = '
	  <form action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'" method="post">
	   <fieldset><legend><img src="'.$this->_path.'logo.gif" alt="" title="" />'.$this->l('Settings').'</legend>
		<p>'.$this->l('').'</p><br />
		<label>'.$this->l('RTLO').'</label>
		<div class="margin-form">
			 <input type="text" size="5" name="rtlo" value="'.Tools::safeOutput(Tools::getValue('RTLO', (int)(Configuration::get('RTLO')))).'" />
			 <p class="clear">'.$this->l('').'</p>
		</div>
		<center><input type="submit" name="submitTargetpayValues" value="'.$this->l('Save').'" class="button" /></center>
	   </fieldset>
	  </form>';
	  return $output;
	}

	
	
	
	
	
	
	
	
	/* hooks */
	/**
	* hookPayment($params)
	* Called in Front Office at Payment Screen - displays user this module as payment option
	*/
	function hookPayment($params) {
		global $currency;
		/* Only Euro's */
		if($currency->iso_code != 'EUR') {
			return;
		}

		$rtlo = Configuration::get('RTLO');

		require_once(realpath(dirname(__FILE__) . '/targetpay.class.php'));


		$idealOBJ = new TargetPayCore("IDE",$rtlo);
		$mrCashOBJ = new TargetPayCore("MRC",$rtlo);
		$directEBankingOBJ = new TargetPayCore("DEB",$rtlo);
		
		$idealBankListArr = $this->setPaymethodInKey("IDE",$idealOBJ->getBankList());
		$mrCashOBJBankListArr = $this->setPaymethodInKey("",$mrCashOBJ->getBankList());
		$directEBankingBankListArr = $this->setPaymethodInKey("",$directEBankingOBJ->getBankList());

		

		global $smarty;

		$smarty->assign(array(
				'error' => (isset($_GET["error"]) && strlen(trim(strip_tags($_GET["error"]))) != 0 ? $_GET["error"] : false),
				'method' => (isset($_GET["method"]) && $this->validatePayMethod(trim(strip_tags($_GET["method"]))) && strlen(trim(strip_tags($_GET["method"]))) != 0 ? trim(strip_tags($_GET["method"])) : false),
				'rtlo' => $rtlo,
				'idealBankListArr' => $idealBankListArr,
				'mrCashOBJBankListArr' => $mrCashOBJBankListArr,
				'directEBankingBankListArr' => $directEBankingBankListArr,
				'this_path' => $this->_path,
				'this_path_ssl' => Tools::getShopDomainSsl(true).__PS_BASE_URI__.'modules/'.$this->name.'/'));
		return $this->display(__FILE__, 'views/templates/front/payment_options_selection.tpl');
	}
	
	function hookInvoice($params)
	{
		
		$sql = sprintf("SELECT * FROM `"._DB_PREFIX_."targetpay_ideal` WHERE `order_id` = %d",$params["id_order"]);
		$result = Db::getInstance()->getRow($sql);
		
		$var = '
		<br/><br/>
		<fieldset style="width:400px">
			<legend><img src="../img/admin/charged_ok.gif" /> '.$this->l('External Payment Information').'</legend>';
			if(count($result) == 0) {
				$var .= 'No information found';
			} else {
				$var .= '
				<table>
					<tr>
						<td>RTLO</td>
						<td>'.$result["rtlo"].'</td>
					</tr>
					<tr>
						<td>Order Id</td>
						<td>'.$result["order_id"].'</td>
					</tr>
					<tr>
						<td>Cart id</td>
						<td>'.$result["cart_id"].'</td>
					</tr>
					<tr>
						<td>Paymethod</td>
						<td>'.$result["paymethod"].'</td>
					</tr>
					<tr>
						<td>External transaction id</td>
						<td>'.$result["transaction_id"].'</td>
					</tr>
					<tr>
						<td>Choosen bank id</td>
						<td>'.$result["bank_id"].'</td>
					</tr>
					<tr>
						<td>Amount</td>
						<td>'.$result["amount"].'</td>
					</tr>
					<tr>
						<td>Bank account</td>
						<td>'.$result["bankaccount"].'</td>
					</tr>
					<tr>
						<td>Name</td>
						<td>'.$result["name"].'</td>
					</tr>
					<tr>
						<td>City</td>
						<td>'.$result["city"].'</td>
					</tr>
				</table>
				';
			}
			
		$var .= '</fieldset>';
		return $var;
	}
	
	/* support functions */
	
	function setPaymethodInKey($paymethod,$BankListArray){
		$newArr = array();
		foreach($BankListArray AS $key => $value) {
			$newArr[strtoupper($paymethod).$key] = $value;
		}
		return $newArr;
	}
	
	/* test in order to check if the object is available in the script you're requesting this function */
	function objectAvailable(){
		return true;
	}
	
	function selectTransaction($trxid) {
		$trxid = preg_replace("/[^a-z\d]/i", "", $trxid);
		$sql = sprintf("SELECT `id`, `rtlo`, `cart_id`,`order_id`, `paymethod`, `transaction_id`, `bank_id`, `description`, `amount`, `status`
						FROM `"._DB_PREFIX_."targetpay_ideal`
						WHERE `transaction_id`= '%s'",
						$trxid);
		$result = Db::getInstance()->getRow($sql);
		return $result;
	}
	
	function updateTransaction($updateArr,$trxid, $via) {
		$trxid = preg_replace("/[^a-z\d]/i", "", $trxid);
		$fields = '';
		foreach($updateArr AS $key => $value) {
			$fields .= "`".$key."` = '".$value."',";
		}
		
		$sql = sprintf("UPDATE `"._DB_PREFIX_."targetpay_ideal` SET
						".$fields."
						`via` = '".$via."'
						WHERE `transaction_id`= '%s'",
						$trxid);
		Db::getInstance()->execute($sql);
		return;
	}
	
	
	function validatePayMethod($payMethod){
		require_once(realpath(dirname(__FILE__) . '/targetpay.class.php'));
		$methodAllowed = new TargetPayCore($payMethod);
		if($methodAllowed !== false) {
			return true;
		}
		return false;
	}
	
	function retrievePaymentUrl ($bankID = false){
	
		
		require_once(realpath(dirname(__FILE__) . '/targetpay.class.php'));
		global $cart;
		
		$bankID = Tools::getValue('bankID');
		$cartID = $cart->id;
		$rtlo = Configuration::get('RTLO');

		$appId = 'c92eeeaf8911cdc97d53652cfe4c64a2';
		$targetpayObj = new TargetPayCore("AUTO",$rtlo,$appId);
		
		$url = Tools::getShopDomainSsl(true).__PS_BASE_URI__.'modules/'.$this->name.'/';

		$targetpayObj->setBankId($bankID);
		$this->paymethod = $targetpayObj->getPayMethod();
		$targetpayObj->setAmount(($cart->getOrderTotal()*100));
		$targetpayObj->setDescription('Cart id: '.$cart->id);
		
		$returnUrl = $url.'returnUrl.php?cartid='.$cart->id;
		$targetpayObj->setReturnUrl($returnUrl);
		$reportUrl = $url.'notifyUrl.php?cartid='.$cart->id;
		$targetpayObj->setReportUrl($reportUrl);
				
		$result = @$targetpayObj->startPayment();

		if($result !== false) {

			$sql = sprintf("INSERT INTO `"._DB_PREFIX_."targetpay_ideal`
					SET
					`cart_id` = %d,
					`rtlo` = %d,
					`paymethod` = '%s',
					`transaction_id` = '%s',
					`bank_id` = '%s',
					`description` = '%s',
					`amount` = '%s',
					`status` = %d,
					`via` = '%s'
					",
					$cart->id,
					$rtlo,
					$targetpayObj->getPayMethod(),
					$targetpayObj->getTransactionId(),
					$targetpayObj->getBankId(),
					$targetpayObj->getDescription(),
					($targetpayObj->getAmount()/100),
					0,
					'payment'
					);
					
			Db::getInstance()->Execute($sql);

			Tools::redirectLink($result);
		}
		$this->_errors[] = $targetpayObj->getErrorMessage();
		return false;
	}
}
?>
