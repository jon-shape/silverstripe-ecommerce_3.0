<?php
/**
 * @description Customisations to {@link Payment} specifically for the ecommerce module.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: payment
 * @sub-package: ecommerce
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class EcommercePayment extends DataExtension {

	public static $summary_fields = array(
		"Order.Title",
		"ClassName" => "Type",
		"AmountValue" => "Amount",
		"Status" => "Status"
	);

	static $has_one = array(
		'Order' => 'Order' //redundant...should be using PaidObject
	);

	static $casting = array(
		'AmountValue' => 'Currency'
	);

	static $searchable_fields = array(
		'OrderID' => array(
			'field' => 'TextField',
			'title' => 'Order Number'
		),
		'Created' => array(
			'title' => 'Date (e.g. today)',
			'field' => 'TextField',
			//'filter' => 'PaymentFilters_AroundDateFilter', //TODO: this breaks the sales section of the CMS
		),
		/*
		'IP' => array(
			'title' => 'IP Address',
			'filter' => 'PartialMatchFilter'
		),
		*/
		'Status'
	);

	/**
	 * standard SS variable
	 * @return Array
	 */
	public static $field_labels = array(
		"Order.Title" => "Order"
	);

	/**
	 * Process payment form and return next step in the payment process.
	 * Steps taken are:
	 * 1. create new payment
	 * 2. save form into payment
	 * 3. return payment result
	 *
	 * @param Order $order - the order that is being paid
	 * @param Form $form - the form that is being submitted
	 * @param Array $data - Array of data that is submittted
	 * @return Boolean - if successful, this method will return TRUE
	 */
	public static function process_payment_form_and_return_next_step(Order $order, Form $form, Array $data) {
		if(!$order){
			$form->sessionMessage(_t('EcommercePayment.NOORDER','Order not found.'), 'bad');
			$form->controller->redirectBack();
			return false;
		}
		$paidBy = $order->Member();
		if(!$paidBy) {
			$paidBy = Member::currentUser();
		}
		$paymentClass = (!empty($data['PaymentMethod'])) ? $data['PaymentMethod'] : null;
		$payment = class_exists($paymentClass) ? new $paymentClass() : null;
		if(!($payment && $payment instanceof Payment)) {
			$form->sessionMessage(_t('EcommercePayment.NOPAYMENTOPTION','No Payment option selected.'), 'bad');
			$form->controller->redirectBack();
			return false;
		}
		// Save payment data from form and process payment
		$form->saveInto($payment);
		$payment->OrderID = $order->ID;
		if(is_object($paidBy)) {
			$payment->PaidByID = $paidBy->ID;
		}
		$payment->Amount = $order->TotalOutstandingAsMoneyObject();
		$payment->write();
		// Process payment, get the result back
		$result = $payment->processPayment($data, $form);
		if(!($result instanceof Payment_Result)) {
			return false;
		}
		else {
			if($result->isProcessing()) {
				//IMPORTANT!!!
				// isProcessing(): Long payment process redirected to another website (PayPal, Worldpay)
				//redirection is taken care of by payment processor
				return $result->getValue();
			}
			else {
				//payment is done, redirect to either returntolink
				//OR to the link of the order ....
				if(isset($data["returntolink"])) {
					$form->controller->redirect($data["returntolink"]);
				}
				else {
					$form->controller->redirect($order->Link());
				}
			}
			return true;
		}
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	function canCreate($member) {
		return EcommerceRole::current_member_is_shop_admin($member);
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	function canDelete($member) {
		return false;
	}


	/**
	 * standard SS method
	 * @param FieldList $fields
	 * @return $Fields
	 */
	function updateSettingsFields(FieldList $fields){
		$fields->replaceField("OrderID", new ReadonlyField("OrderID", "Order ID"));
		return $fields;
	}


	/**
	 * redirect to order action
	 */
	function redirectToOrder() {
		$order = $this->owner->Order();
		if($order) {
			Controller::curr()->redirect($order->Link());
		}
		else {
			user_error("No order found with this payment: ".$this->ID, E_USER_NOTICE);
		}
		return;
	}

	/**
	 * @param DataObject $do
	 *
	 */
	function setPaidObject(DataObject $do){
		$this->owner->PaidForID = $do->ID;
		$this->owner->PaidForClass = $do->ClassName;
	}

	/**
	 * @return float
	 **/
	function getAmountValue() {
		return $this->owner->Amount->getAmount();
	}

	/**
	 * @alias for AmountValue
	 **/
	function AmountValue(){return $this->getAmountValue();}


	/**
	 * Determine which properties on the DataObject are
	 * searchable, and map them to their default {@link FormField}
	 * representations. Used for scaffolding a searchform for {@link ModelAdmin}.
	 *
	 * Some additional logic is included for switching field labels, based on
	 * how generic or specific the field type is.
	 *
	 * Used by {@link SearchContext}.
	 *
	 * @param array $_params
	 * 	'fieldClasses': Associative array of field names as keys and FormField classes as values
	 * 	'restrictFields': Numeric array of a field name whitelist
	 * @return FieldList
	 */
	public function scaffoldSearchFields($_params = null) {
		$fields = parent::scaffoldSearchFields($_params);
		$fields->replaceField("OrderID", new NumericField("OrderID", "Order ID"));
		return $fields;
	}

	/**
	 * Standard SS method
	 */
	function onBeforeWrite() {
		parent::onBeforeWrite();
		//see issue 148
		if($this->owner->OrderID) {
			$this->owner->PaidForID = $this->owner->OrderID;
			$this->owner->PaidForClass = "Order";
		}
		if($this->owner->PaidForID && !$this->owner->OrderID) {
			$this->owner->OrderID = $this->owner->PaidForID;
			$this->owner->PaidForClass = "Order";
		}
	}

	/**
	 * standard SS method
	 * try to finalise order if payment has been made.
	 */
	function onAfterWrite() {
		parent::onAfterWrite();
		$order = $this->owner->PaidObject();
		if($order && $order instanceof Order && $order->IsSubmitted()) {
			$order->tryToFinaliseOrder();
		}
	}


	/**
	 *@return String
	 **/
	function Status() {
		return _t('Payment.'.strtoupper($this->owner->Status),$this->owner->Status);
	}


	/**
	 * checks if a credit card is a real credit card number
	 * @reference: http://en.wikipedia.org/wiki/Luhn_algorithm
	 *
	 * @param String | Int $number
	 * @return Boolean
	 */
	public function validCreditCard($number) {
		for ($sum = 0, $i = strlen($number) - 1; $i >= 0; $i--) {
			$digit = (int) $number[$i];
			$sum += (($i % 2) === 0) ? array_sum(str_split($digit * 2)) : $digit;
		}
		return (($sum % 10) === 0);
	}

	/**
	 * @todo: finish!
	 * valid expiry date
	 * @param String | Int $number
	 * @return Boolean
	 */
	public function validExpiryDate($number) {
		return true;
	}

	/**
	 * @todo: finish!
	 * valid CVC number
	 *
	 * @param String | Int $number
	 * @return Boolean
	 */
	public function validCVC($number) {
		return true;
	}


}
