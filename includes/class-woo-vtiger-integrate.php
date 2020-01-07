<?php
require_once('db.php');

//====main class
	

	class woo_vtiger_int {



		function __construct() {
			//get options entered into admin page. neeed api and database credentials. Some queries could not be done through api.
			$this->vtigerOptions = get_option('wvi_api_settings');
			$this->vtigerUrl = $this->vtigerOptions['wvi_api_vtiger_url'];
			$this->vtigerUsername = $this->vtigerOptions['wvi_api_username'];
			$this->vtigerAccessKey = $this->vtigerOptions['wvi_api_accesskey'];
			$this->vtigerDbUsername = $this->vtigerOptions['wvi_db_username'];
			$this->vtigerDbPassword = $this->vtigerOptions['wvi_db_pw'];
			$this->vtigerDbName = $this->vtigerOptions['wvi_db_name'];
			$this->logFile = WOO_VTIGER_INT . 'error_log.txt';
		}



		public function get_order_data($order_id) {

			if(empty($this->vtigerUrl)) {
				return;
			}

			$this->order_id = $order_id;
			$order = wc_get_order($order_id);
			
			//check if order exists
			 if($order) {
			 	//if order failed do not add to vtiger
			 	if ($order->has_status('failed')) {
			 		return;
			 	}

			 	//get current user email for setting Assigned To in vtiger
			 	$this->currentUser = wp_get_current_user();
			 	$this->currentUserEmail = $this->currentUser->user_email;
			 	
			 	//get all order data
			 	$order_data = $order->get_data();
			 	

			 	if($order_data) {

			 			//turns order array into variables
				 	 	extract($order_data);

				 	 	//set date created and date modified
				 		date_default_timezone_set('America/Denver');
				 		$this->date_created = date('Y-m-d H:i:s', strtotime($date_created));
				 		$this->date_modified = date('Y-m-d H:i:s', strtotime($date_modified));

				 		

				 		//set notes 
				 		$this->notes = "Order Note: ".$customer_note;
				 		$this->customer_note = $customer_note;

				 		

				 		//convert billing array into variables
				 	 	extract($billing, EXTR_PREFIX_ALL, "billing");
				 	 	$this->billing_first_name = $billing_first_name;
				 	 	$this->billing_last_name = $billing_last_name;
				 	 	$this->billing_phone = $billing_phone;
				 	 	$this->billing_email = $billing_email;
				 	 	$this->billing_company = $billing_company;
				 	 	$this->billing_address_1 = $billing_address_1;
				 	 	$this->billing_address_2 = $billing_address_2;
				 	 	$this->billing_city = $billing_city;
				 	 	if(!empty($billing_state)) {
				 	 		$this->billing_state = $billing_state;
				 	 	} else {
				 	 		if(empty($billing_state)) {
				 	 			$this->billing_state = $billing_country;
				 	 		}
				 	 	}
				 	 	$this->billing_postcode = $billing_postcode;
				 	 	

				 	 	//convert shipping array into variables
				 		extract($shipping, EXTR_PREFIX_ALL, "shipping");
				 		$this->shipping_first_name = $shipping_first_name;
				 		$this->shipping_last_name = $shipping_last_name;
				 		$this->shipping_address_1 = $shipping_address_1;
				 		$this->shipping_address_2 = $shipping_address_2;
				 		$this->shipping_city = $shipping_city;
				 		if(!empty($shipping_state)) {
				 			$this->shipping_state = $shipping_state;
				 		} else {
				 			if(empty($shipping_state)) {
				 				$this->shipping_state = $shipping_country;
				 			}
				 		}
				 		
				 		$this->shipping_postcode = $shipping_postcode;

				 		//get full name of country except abbreviate US
				 		$this->billing_country = WC()->countries->countries[$order->get_billing_country()];
				 		
				 		if(strpos($this->billing_country, 'United States') !== false) {
				 			$this->billing_country = substr($this->billing_country, 0, -5);
				 			if($this->billing_country == 'United States') {
				 				$this->billing_country = 'US';
				 			}
				 		} else {
				 			//if shipping country is not us, state should not be abbbreviated
				 			if(!empty(WC()->countries->get_states($order->get_billing_country()))) {
				 				$this->billing_state = WC()->countries->get_states($order->get_billing_country())[$this->billing_state];
				 			} else {
				 				$this->billing_state = $this->billing_country;
				 			}
				 			
				 			
				 		}
				 		$this->shipping_country = WC()->countries->countries[$order->get_shipping_country()];
				 		if(strpos($this->shipping_country, 'United States') !== false) {
				 			$this->shipping_country = substr($this->shipping_country, 0, -5);
				 			if($this->shipping_country == 'United States') {
				 				$this->shipping_country = 'US';
				 			}
				 		} else {
				 			if(!empty(WC()->countries->get_states($order->get_shipping_country()))) {
				 				$this->shipping_state = WC()->countries->get_states($order->get_shipping_country())[$this->shipping_state];

				 			} else {
				 				$this->shipping_state = $this->shipping_country;
				 			}
				 			
				 			
				 		}
				 		

				 	 	//format phone number for vtiger. If US number, convert to 000-000-0000 format for vtiger
				 	 	if($billing_country == "US") {
				 	 		$this->billing_phone_numbers_only = preg_replace("/[^\d]/", "", $this->billing_phone);
				 	 		$this->billing_phone_formatted = preg_replace("/^1?(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", $this->billing_phone_numbers_only);

				 	 	} else {
				 	 		//does not format international phone numbers
				 	 		$this->billing_phone_formatted = $this->billing_phone;
				 	 	}

				 	 	//if billing address and shipping address are the same, set billing address variables to null to leave other address (billing) blank in vtiger
				 	 	if($this->billing_address_1 == $this->shipping_address_1) {
				 	 		$this->billing_address_1 = null;
				 	 		$this->billing_address_2 = null;
				 	 		$this->billing_city = null;
				 	 		$this->billing_state = null;
				 	 		$this->billing_country = null;
				 	 		$this->billing_postcode = null;

				 	 	}

				 	 	//if zip code is empty make it '000000', for international
				 	 	if(empty($this->shipping_postcode)) {
				 	 		$this->shipping_postcode = '00000';
				 	 	}

				 	 	//get value from "How Did You Hear About Us" dropdown and change to match options in vtiger
				 		$this->howDidYouHearFormatted = null;
				 			foreach($meta_data as $metakey => $metavalue) {
				 				
				 				if($metavalue->key == '_billing_wcj_checkout_field_2') {

				 					$this->howDidYouHear = $metavalue->value;
				 					$this->howDidYouHearChange = 'CHANGE THIS';
				 					
				 					$this->howDidYouHearFormatted = "How Did You Hear About Us: ". $this->howDidYouHear;
				 					
				 					
				 				}


				 				//get value of "Tell Us About Yourself"
				 				if($metavalue->key == '_billing_wcj_checkout_field_1') {
				 					$this->tellUsAboutYourself = $metavalue->value;
				 				}
				 				//get invoice number value
				 				if($metavalue->key == '_wcj_invoicing_invoice_number_id') {
				 					$this->invoiceNumberId = $metavalue->value;
				 				}
				 				//get "Name on Certificate" value
				 				if($metavalue->key == '_order_wcj_checkout_field_3') {
				 					$this->nameOnCert = $metavalue->value;
				 				}

				 				
				 			}


				 			//check if HowDidYouHear missing for error checking. If missing set error notice on woocomerce order page, add info to log file, stop running
				 			if(is_null($this->howDidYouHearFormatted)) {
				 				update_post_meta($order_id, "vtiger_success", 'false');
				 				$logMessage = date('Y-m-d h:i:sa').': Missing How Did You Hear Field from woocommerce order #' .$order_id  .PHP_EOL;
				 				error_log($logMessage, 3, $this->logFile);
				 				return false;
				 			}
				 		
				 		//get skus from ordered products
				 		//get lifestage meta if it exists

				 		$allProducts = array();
				 		foreach($line_items as $item_key => $item):


				 			//product name to add to vtiger comment for order
				 			$product = $item->get_product();
				 			$this->productName = $product->get_name();
				 			array_push($allProducts, $this->productName);

				 			$this->productSku = $product->get_sku();
				 			//get date from sku
				 			$classYear = substr($this->productSku, -2);
				 			$classMonth = substr($this->productSku, -6, 2);
				 			$classDay = substr($this->productSku, -4, 2);
				 			$this->classDate = '20' .$classYear . '-' . $classMonth . '-' . $classDay;

				 			//get order item meta
				 			$metaData = $item->get_meta_data();

				 			//create variable for ACCT time
				 			$this->acttTime = null;
				 			//create variable for Life Stage selected in Master Certification products
				 			$this->metaLifeStage = '';

				 			//if order item has metadata such as selected life stage or class time
				 			if($metaData) {
				 				
				 				foreach($metaData as $metaDataKey => $metaDataValue) {
				 					//if life stage mata data is set, get value of life stage selected from attribute
				 					if($metaDataValue->key == 'pa_twolifestages' || $metaDataValue->key == 'pa_onelifestage') {
				 						$this->metaLifeStage = $metaDataValue->value; 

				 						//get selected life stage label and name to add to order comment in vtiger
				 						$lifestageData = get_term_by('slug', $metaDataValue->value, $metaDataValue->key);
				 						$this->lifestageName = wc_attribute_label($lifestageData->taxonomy).": ";
				 						$this->lifestageValue = $lifestageData->name;
				 						
				 					}
				 					//check if class time value is set
				 					if($metaDataValue->key == 'pa_mandatory-live-class-time') {
				 						//check if daytime is the slug amd set it to AM in vtiger, if not set it to pm in vtiger
				 						if(strpos($metaDataValue->value, 'daytime') !==false) {
				 							$this->acttTime = 'AM';
				 						} else {
				 							$this->acttTime = 'PM';
				 						}
				 						// if(strpos($metaDataValue->value, 'am')) {
				 						// 	$this->acttTime = 'AM';
				 						// } else {
				 						// 	$this->acttTime = 'PM';
				 						// }

				 						//get selected class time to add to order comment in vtiger
				 						$classTimeData = get_term_by('slug', $metaDataValue->value, $metaDataValue->key);
				 						$this->classTimeName = wc_attribute_label($classTimeData->taxonomy).": ";
				 						$this->classTimeValue = $classTimeData->name;
				 						
				 						
				 					}

				 					
				 				}
				 					
				 			}
				 			
				 			//===get class type from sku
				 			//NOTE - if new skus added or changed, update this section

				 			//get letters from sku bhy removing date at the end
				 			$this->classType = substr($this->productSku, 0, -7);

				 			$this->classStart = null;
				 			$this->classExpect = null;
				 			$this->classPrime = null;
				 			$this->classACTT = null;
				 			$this->paymentPlan = null;

				 			//check value of class type and set variable for each class
				 			switch ($this->classType) {
				 				case "A":
				 					$this->classStart = '0';
				 					$this->classExpect = '0';
				 					$this->classPrime = '1';
				 					$this->classACTT = '0';
				 					$this->paymentPlan = '0';
				 					break;
				 				case "PA":
				 				case "P4A":
				 					$this->classStart = '0';
				 					$this->classExpect = '0';
				 					$this->classPrime = '1';
				 					$this->classACTT = '0';
				 					$this->paymentPlan = '1';
				 					break;
				 				case "F":
				 					$this->classStart = '1';
				 					$this->classExpect = '0';
				 					$this->classPrime = '0';
				 					$this->classACTT = '0';
				 					$this->paymentPlan = '0';
				 					break;
				 				case "PF":
				 				case "P4F":
				 					$this->classStart = '1';
				 					$this->classExpect = '0';
				 					$this->classPrime = '0';
				 					$this->classACTT = '0';
				 					$this->paymentPlan = '1';
				 					break;
				 				case "P":
				 					$this->classStart = '0';
				 					$this->classExpect = '1';
				 					$this->classPrime = '0';
				 					$this->classACTT = '0';
				 					$this->paymentPlan = '0';
				 					break;
				 				case "PP":
				 				case "P4P":
				 					$this->classStart = '0';
				 					$this->classExpect = '1';
				 					$this->classPrime = '0';
				 					$this->classACTT = '0';
				 					$this->paymentPlan = '1';
				 					break;
				 				case "F-P":
				 					$this->classStart = '1';
				 					$this->classExpect = '1';
				 					$this->classPrime = '0';
				 					$this->classACTT = '0';
				 					$this->paymentPlan = '0';
				 					break;
				 				case "PF-P":
				 				case "P4F-P":
				 					$this->classStart = '1';
				 					$this->classExpect = '1';
				 					$this->classPrime = '0';
				 					$this->classACTT = '0';
				 					$this->paymentPlan = '1';
				 					break;
				 				case "F-A":
				 					$this->classStart = '1';
				 					$this->classExpect = '0';
				 					$this->classPrime = '1';
				 					$this->classACTT = '0';
				 					$this->paymentPlan = '0';
				 					break;
				 				case "PF-P":
				 				case "P4F-P":
				 					$this->classStart = '1';
				 					$this->classExpect = '0';
				 					$this->classPrime = '1';
				 					$this->classACTT = '0';
				 					$this->paymentPlan = '1';
				 					break;
				 				case "F-A":
				 					$this->classStart = '1';
				 					$this->classExpect = '0';
				 					$this->classPrime = '1';
				 					$this->classACTT = '0';
				 					$this->paymentPlan = '0';
				 					break;
				 				case "PF-A":
				 				case "P4F-A":
				 					$this->classStart = '1';
				 					$this->classExpect = '0';
				 					$this->classPrime = '1';
				 					$this->classACTT = '0';
				 					$this->paymentPlan = '1';
				 					break;
				 				case "A-P":
				 					$this->classStart = '0';
				 					$this->classExpect = '1';
				 					$this->classPrime = '1';
				 					$this->classACTT = '0';
				 					$this->paymentPlan = '0';
				 					break;
				 				case "PA-P":
				 				case "P4A-P":
				 					$this->classStart = '0';
				 					$this->classExpect = '1';
				 					$this->classPrime = '1';
				 					$this->classACTT = '0';
				 					$this->paymentPlan = '1';
				 					break;
				 				case "F-P-A":
				 					$this->classStart = '1';
				 					$this->classExpect = '1';
				 					$this->classPrime = '1';
				 					$this->classACTT = '0';
				 					$this->paymentPlan = '0';
				 					break;
				 				case "PF-P-A":
				 				case "P4F-P-A":
				 					$this->classStart = '1';
				 					$this->classExpect = '1';
				 					$this->classPrime = '1';
				 					$this->classACTT = '0';
				 					$this->paymentPlan = '1';
				 					break;
				 				case "ACTT-ONE":
				 				 {

				 					switch ($this->metaLifeStage) {
				 						case "adultsseniors":
				 							$this->classStart = '0';
				 							$this->classExpect = '0';
				 							$this->classPrime = '1';
				 							$this->paymentPlan = '0';
				 							break;
				 						case "families":
				 							$this->classStart = '1';
				 							$this->classExpect = '0';
				 							$this->classPrime = '0';
				 							$this->paymentPlan = '0';
				 							break;
				 						case "pregnancy":
				 							$this->classStart = '0';
				 							$this->classExpect = '1';
				 							$this->classPrime = '0';
				 							$this->paymentPlan = '0';
				 							break;
				 					}
				 					$this->classACTT = '1';
				 					
				 				}
				 				break;
				 				case "ACTT-TWO":
				 				{
				 					switch ($this->metaLifeStage) {
				 						case "adultsseniors-pregnancy":
				 							$this->classStart = '0';
				 							$this->classExpect = '1';
				 							$this->classPrime = '1';
				 							$this->paymentPlan = '0';
				 							break;
				 						case "families-adultsseniors":
				 							$this->classStart = '1';
				 							$this->classExpect = '0';
				 							$this->classPrime = '1';
				 							$this->paymentPlan = '0';
				 							break;
				 						case "families-pregnancy":
				 							$this->classStart = '1';
				 							$this->classExpect = '1';
				 							$this->classPrime = '0';
				 							$this->paymentPlan = '0';
				 							break;

				 					}
				 					$this->classACTT = '1';
				 				}
				 				break;
				 				case "ACTT-THREE":
				 					$this->classStart = '1';
				 					$this->classExpect = '1';
				 					$this->classPrime = '1';
				 					$this->classACTT = '1';
				 					$this->paymentPlan = '0';
				 					break;
				 				case "ACTT-ONE-PP3": {
				 					switch ($this->metaLifeStage) {
				 						case "adultsseniors":
				 							$this->classStart = '0';
				 							$this->classExpect = '0';
				 							$this->classPrime = '1';
				 							$this->paymentPlan = '1';
				 							break;
				 						case "families":
				 							$this->classStart = '1';
				 							$this->classExpect = '0';
				 							$this->classPrime = '0';
				 							$this->paymentPlan = '1';
				 							break;
				 						case "pregnancy":
				 							$this->classStart = '0';
				 							$this->classExpect = '1';
				 							$this->classPrime = '0';
				 							$this->paymentPlan = '1';
				 							break;
				 					}
				 					$this->classACTT = '1';
				 				}
				 				break;
				 				case "ACTT-ONE-PP4": {
				 					switch ($this->metaLifeStage) {
				 						case "adultsseniors":
				 							$this->classStart = '0';
				 							$this->classExpect = '0';
				 							$this->classPrime = '1';
				 							$this->paymentPlan = '1';
				 							break;
				 						case "families":
				 							$this->classStart = '1';
				 							$this->classExpect = '0';
				 							$this->classPrime = '0';
				 							$this->paymentPlan = '1';
				 							break;
				 						case "pregnancy":
				 							$this->classStart = '0';
				 							$this->classExpect = '1';
				 							$this->classPrime = '0';
				 							$this->paymentPlan = '1';
				 							break;
				 					}
				 					$this->classACTT = '1';
				 				}
				 				case "ACTT-TWO-PP3":
				 				{
				 					switch ($this->metaLifeStage) {
				 						case "adultsseniors-pregnancy":
				 							$this->classStart = '0';
				 							$this->classExpect = '1';
				 							$this->classPrime = '1';
				 							$this->paymentPlan = '1';
				 							break;
				 						case "families-adultsseniors":
				 							$this->classStart = '1';
				 							$this->classExpect = '0';
				 							$this->classPrime = '1';
				 							$this->paymentPlan = '1';
				 							break;
				 						case "families-pregnancy":
				 							$this->classStart = '1';
				 							$this->classExpect = '1';
				 							$this->classPrime = '0';
				 							$this->paymentPlan = '1';
				 							break;

				 					}
				 					$this->classACTT = '1';
				 				}
				 				break;
				 				case "ACTT-TWO-PP4":
				 				{
				 					switch ($this->metaLifeStage) {
				 						case "adultsseniors-pregnancy":
				 							$this->classStart = '0';
				 							$this->classExpect = '1';
				 							$this->classPrime = '1';
				 							$this->paymentPlan = '1';
				 							break;
				 						case "families-adultsseniors":
				 							$this->classStart = '1';
				 							$this->classExpect = '0';
				 							$this->classPrime = '1';
				 							$this->paymentPlan = '1';
				 							break;
				 						case "families-pregnancy":
				 							$this->classStart = '1';
				 							$this->classExpect = '1';
				 							$this->classPrime = '0';
				 							$this->paymentPlan = '1';
				 							break;

				 					}
				 					$this->classACTT = '1';
				 				}
				 				break;
				 				case "ACTT-THREE-PP3":
				 				case "ACTT-THREE-PP4":
				 					$this->classStart = '1';
				 					$this->classExpect = '1';
				 					$this->classPrime = '1';
				 					$this->classACTT = '1';
				 					$this->paymentPlan = '1';
				 					break;
				 			}

				 		
				 	 	endforeach;

				 	 	//check if any skus missing
				 	 	if(is_null($this->classStart) || is_null($this->classExpect) || is_null($this->classPrime) || is_null($this->classACTT) || is_null($this->paymentPlan)) {
				 	 		update_post_meta($order_id, "vtiger_success", 'false');
				 	 		$logMessage = date('Y-m-d h:i:sa').': Missing sku data or payments plan data from woocommerce order #' .$order_id  .PHP_EOL;
				 	 		error_log($logMessage, 3, $this->logFile);
				 	 		return false;
				 	 	}
				 
				 		//get coupon code
				 		if(!empty($coupon_lines)) {
				 			foreach($coupon_lines as $coupon_item_key => $coupon_item) {
				 				$this->coupon = $coupon_item->get_code();
				 			}

				 		

				 	 	} //end coupons lines


				 	 	//get woocommerce order number
				 	 	if($number) {
				 	 		$this->orderNumber = $number;
				 	 	} else {
				 	 		$this->orderNumber = '0000';
				 	 	}
				 		

				 		//shipping info to add to vtiger comment for order
				 		foreach($shipping_lines as $shippingLinesKey => $shippingLinesValue) {
				 			$shippingLinesData = $shippingLinesValue->get_data();
				 			$this->shippingMethod = $shippingLinesData['name'];
				 			$this->shippingTotal = $shippingLinesData['total'];
				 		}

				 		//order total to add to vtiger comment for order
				 		$this->orderTotal = $total;

				 		//all info to be added to order comment in vtiger
				 		$this->orderInfo = "Order Details \r\n";
				 		$this->orderInfo .= "Date: " .$this->date_created ."\r\n";
				 		$this->orderInfo .= "Order Number: " .$this->orderNumber . "\r\n";
				 		foreach($allProducts as $singleProduct) {
				 			$this->orderInfo .= "Product: " .$singleProduct ."\r\n";
				 			$this->orderInfo .= "Sku: " .$this->productSku . "\r\n";
				 		} 
				 		
				 		if(isset($this->lifestageValue)) {
				 			$this->orderInfo .= $this->lifestageName ." ".$this->lifestageValue ."\r\n";
				 		}
				 		if(isset($this->classTimeValue)) {
				 			$this->orderInfo .= $this->classTimeName . " " .$this->classTimeValue. "\r\n";
				 		}

				 		if($this->paymentPlan == '1') {
				 			$this->orderInfo .= "Payment Plan : Yes" ."\r\n";
				 		}

				 		$this->orderInfo .= $this->shippingMethod . ": " .$this->shippingTotal . "\r\n";
				 		$this->orderInfo .= "Order Total: " .$this->orderTotal;

				 		return true;
				 	} //end if $order_data

				 	else {
				 		//if there is no order data, set error notice on woocomerce order page and add info to log file.
				 		update_post_meta($order_id, 'vtiger_success', 'false');
				 		$logMessage = date('Y-m-d h:i:sa').': Missing all order data from woocommerce order #' .$order_id  .PHP_EOL;
				 		error_log($logMessage, 3, $this->logFile);
				 		return false;
				 	}
			 
			} //end if $order
			else {
				//if there is no order id provided, add transient. Will set error notice on woocommerce orders page
				set_transient('wvi-order-error', 'unable to get order number', 259200);
				$logMessage = date('Y-m-d h:i:sa').': Unable to get order woocommerce order id' .PHP_EOL;
				error_log($logMessage, 3, $this->logFile);
				return false;
			} //end else $order
		} //end function



		//======connect to vtiger using setting in plugin admin page
		public function vtiger_connect() {
			//send username to vtiger. It will return a token
			$vtigerChallenge = wp_remote_get($this->vtigerUrl .'/webservice.php?operation=getchallenge&username=' .$this->vtigerUsername);
			 
			if( is_wp_error( $vtigerChallenge ) ) {
				$logMessage = date('Y-m-d h:i:sa').': ' .$vtigerChallenge->get_error_message .PHP_EOL;
				error_log($logMessage, 3, $this->logFile);
			}
			

			$challengeBody = wp_remote_retrieve_body( $vtigerChallenge );
			$challengeData = json_decode( $challengeBody );

			//if challenge is returned, set vtgier access key. Access key is token plus user key with md5 hash
			if( !empty( $challengeData)){
			    
				$userKey = $this->vtigerAccessKey;
				$challengeResult = $challengeData->result;
				$accessToken = $challengeResult->token;
				$accessKey = md5($accessToken.$userKey);

			} else {
				update_post_meta($order_id, 'vtiger_success', 'false');
				$logMessage = date('Y-m-d h:i:sa').': vTiger Challenge did not return response. Check your username and access key.' .PHP_EOL;
				error_log($logMessage, 3, $this->logFile);
				
				return false;
			}

			//login to vtiger with username and access key. Will return a session
			$urlLogin = $this->vtigerUrl .'/webservice.php?operation=login&username='.$this->vtigerUsername.'&accessKey='.$accessKey;
			
			$vtigerLogin = wp_remote_post($urlLogin, array(
			    'headers' => array(
			        'Content-type' => 'application/x-www-form-urlencoded',

			    ),
			     'body' => array(
			            'operation' => 'login',
			            'username' => 'mspurr',
			            'accessKey' => $accessKey
			    )
			));

			$loginBody = wp_remote_retrieve_body( $vtigerLogin );
			$loginData = json_decode($loginBody);
			
			if($loginData->success == false || empty($loginData->success)) {
				//print_r($loginData);
				$logMessage = date('Y-m-d h:i:sa').': Could not login to vtiger. Error: ' .$loginData->error->message .PHP_EOL;
				error_log($logMessage, 3, $this->logFile);
				return false;
			}
			$loginResult = $loginData->result;
			$this->sessionName = $loginResult->sessionName;
			if($loginData->success == true) {

				return true;
			}

		}

		public function vtiger_add_contact() {

			if(empty($this->vtigerUrl)) {
				return;
			}

			//set varaibles 
			$vtigerUrl = $this->vtigerUrl;
			$sessionName = $this->sessionName;
			$order_id = $this->order_id;
			$userId = $this->vtiger_user($this->currentUserEmail);
			//used in all webservice functions
			$webserviceUserId = '19x'.$userId;

			
			//check if order is existing lead by checking email and phone number in leads
			$queryExistsLead = "SELECT * FROM Leads where email='$this->billing_email' OR phone='$this->billing_phone_formatted';";
			$urlExistsLead = $vtigerUrl.'/webservice.php?operation=query&sessionName='.$sessionName.'&query='.$queryExistsLead;

			$vtigerExistsLead = wp_remote_get($urlExistsLead);
			if(is_wp_error($vtigerExistsLead)) {
				$logMessage = date('Y-m-d h:i:sa').': ' .$vtigerExistsLead->get_error_message .PHP_EOL;
				error_log($logMessage, 3, $this->logFile);
			}
			

				$existsBodyLead = wp_remote_retrieve_body($vtigerExistsLead);
				$existsDataLead = json_decode($existsBodyLead);
				

				if($existsDataLead->success == false) {
					update_post_meta($order_id, 'vtiger_success', 'false');
					$logMessage =  date('Y-m-d h:i:sa').': Unable to check if lead exists. vTiger Error Message: ' . $existsDataLead->error->message .PHP_EOL;
					error_log($logMessage, 3, $this->logFile);
					return false;
				}

				if($existsDataLead->success == true && !empty($existsDataLead->result)) {
					//lead already exists
					$existsResultLead = $existsDataLead->result;


					//get data from lead to transfer to the new contact
					$leadMobile = $existsResultLead[0]->mobile;
					$leadCompany = $existsResultLead[0]->company;
					$leadFax = $existsResultLead[0]->fax;
					$leadWebsite = $existsResultLead[0]->website;
					$leadDescription = $existsResultLead[0]->description;
					$leadJuicePlus = $existsResultLead[0]->cf_673;
					$leadICEA = $existsResultLead[0]->cf_674;
					$leadACE = $existsResultLead[0]->cf_675;
					$leadProfession = $existsResultLead[0]->cf_676;
					$leadEducation = $existsResultLead[0]->cf_677;
					if(isset($existsResultLead[0]->leadsource ) && !empty($existsResultLead[0]->leadsource)) {
											$leadSource = $existsResultLead[0]->leadsource;	
										} else {
											$leadSource = $this->howDidYouHearChange;
										}		
					$leadWebinarDate = $existsResultLead[0]->cf_678;
					$leadAttendedWebinar = $existsResultLead[0]->cf_679;
					$leadEmailOptOut = $existsResultLead[0]->emailoptout;
					$leadJpTitle = $existsResultLead[0]->cf_1120;
					$leadId = $existsResultLead[0]->id;
					$leadMarketingAudience = $existsResultLead[0]->cf_1312;

					$convertLeadParams = array(
						'id' => $leadId,
						'firstname' => $this->billing_first_name,
						'lastname' => $this->billing_last_name,
						'cf_663' => $this->billing_phone_formatted,
						'email' => $this->billing_email,
						'assigned_user_id' => $webserviceUserId,
						'mailingstreet' => $this->shipping_address_1 ."\r\n" .$this->shipping_address_2,
						'mailingcity' => $this->shipping_city,
						'mailingstate' => $this->shipping_state,
						'mailingzip' => $this->shipping_postcode,
						'mailingcountry' => $this->shipping_country,
						'otherstreet' => $this->billing_address_1 . "\r\n" . $this->billing_address_2,
						'othercity' => $this->billing_city,
						'otherstate' => $this->billing_state,
						'otherzip' => $this->billing_postcode,
						'othercountry' => $this->billing_country,
						'description' => $this->howDidYouHearFormatted ."\r\n" . "Tell Us About Yourself: ". $this->tellUsAboutYourself ."\r\n" .$leadDescription,
						'cf_1067' => $this->nameOnCert,
						'cf_662' => 'Candidates',
						'cf_821' => $this->orderNumber,
						'cf_781' => $this->classDate,
						'cf_622' => $this->classDate,
						'cf_626' => $this->classStart,
						'cf_628' => $this->classExpect,
						'cf_630' => $this->classPrime,
						'cf_809' => $this->classACTT,
						'cf_621' => $this->paymentPlan,
						'cf_686' => '0',
						'cf_687' => '0',
						'cf_688' => '0',
						'cf_697' => '0',
						'mobile' => $leadMobile,
						'fax' => $leadFax,
						'cf_623' => $leadJuicePlus,
						'cf_704' => $leadJpTitle,
						'cf_625' => $leadICEA,
						'cf_618' => $leadEducation,
						'cf_619' => $leadProfession,
						'leadsource' => $leadSource,
						'cf_1106' => $leadWebinarDate,
						'cf_1124' => $leadAttendedWebinar,
						'cf_1320' => $this->acttTime, 
						'isconvertedfromlead' => '1',
						'emailoptout' => $leadEmailOptOut,
						'createdtime' => $this->date_created,
						'modifiedtime' => $this->date_modified,
						'cf_1310' => $leadMarketingAudience
					);

				//if billing name is different from shipping name, create conact with shipping name
				if(!empty($this->shipping_last_name) && $this->billing_last_name != $this->shipping_last_name) {
					$createContactParams['firstname'] = $this->shipping_first_name;
					$createContactParams['lastname'] = $this->shipping_last_name;
					$this->orderInfo .= "\r\n Billing Name: " . $this->billing_first_name." ".$this->billing_last_name;
				}

					//if there is no Company Name set on the woocommerce order, use company name from leads
					if(empty($this->billing_company)) {
						$convertLeadParams['cf_617'] = $leadCompany;
					} else {
						$convertLeadParams['cf_617'] = $this->billing_company;
					}

					//create contact from lead
					$createLeadData = json_encode($convertLeadParams);
					

					$urlCreateLeadContact = $vtigerUrl.'/webservice.php';
					$vtigerCreateLeadContact = wp_remote_post($urlCreateLeadContact, array(
								    'headers' => array(
								        'Content-type' => 'application/x-www-form-urlencoded',

								    ),
								     'body' => array(
								            'operation' => 'create',
								            'sessionName' => $sessionName,
								            'element' => $createLeadData,
								            'elementType' => 'Contacts'
								    )
								));

								if(is_wp_error($vtigerCreateLeadContact)) {
									$logMessage = date('Y-m-d h:i:sa').': ' .$vtigerCreateLeadContact->get_error_message .PHP_EOL;
									error_log($logMessage, 3, $this->logFile);
								}

								$createLeadContactBody = wp_remote_retrieve_body($vtigerCreateLeadContact);
								

								$createLeadContactData = json_decode($createLeadContactBody);
						
								
								//if lead was converted to contact, add success notice in wordpress admin. If not add error notice and message to log
								if($createLeadContactData->success == true && !empty($createLeadContactData->result)) {
										
										update_post_meta($order_id, 'vtiger_success', 'true');
										//get new contact id, lead id and use to convert lead to contact
										$newContactId = $createLeadContactData->result->id;
										$newContactId = substr($newContactId, 2);
										$leadId = substr($leadId, 2);	
										$this->transfer_comments($leadId, $newContactId);
										
										$this->lead_convert($leadId);
										$newContactId = $createLeadContactData->result->id;
										$newContactId = substr($newContactId, 2);
											
										
										
										//add How Did You Hear as a comment
										$this->order_note_create_comment($userId, $newContactId, $this->date_created, $this->date_modified, $this->howDidYouHearFormatted);

										//add order info as a comment
										$this->order_note_create_comment($userId, $newContactId, $this->date_created, $this->date_modified, $this->orderInfo);

										//add note as a comment if it exists
										if(isset($this->customer_note) && !empty($this->customer_note)) {
											
											$this->order_note_create_comment($userId, $newContactId, $this->date_created, $this->date_modified, $this->notes);
										}		
									
								} elseif($createLeadContactData->success == true && empty($createLeadContactData->result)) {
										update_post_meta($order_id, 'vtiger_success', 'false');
										$logMessage = date('Y-m-d h:i:sa').': vTiger Success: ' .$createLeadContactData->success .PHP_EOL;
										error_log($logMessage, 3, $this->logFile);
										return false;
								} elseif($createLeadContactData->success == false) {
										update_post_meta($order_id, 'vtiger_success', 'false');
										$logMessage = date('Y-m-d h:i:sa').': Could not create new contact from lead in vTiger. vtiger Error Message: ' .$createLeadContactData->error->message .PHP_EOL;
										error_log($logMessage, 3, $this->logFile);
										return false;
								} 
					



				} elseif ($existsDataLead->success == true && empty($existsResultLead->result)) {
					//lead doesnt exist
				
						//check if billing email is already an existing contact
						//use existing data for Original Training Date, Training Date, Create Time. Get existing description to add to new description from order
						$queryExistsContact = "SELECT email, cf_622, cf_781, cf_662, createdtime, description FROM Contacts where email='$this->billing_email';";

						$urlExistsContact = $vtigerUrl.'/webservice.php?operation=query&sessionName='.$sessionName.'&query='.$queryExistsContact;

						$vtigerExistsContact = wp_remote_get($urlExistsContact);

							if(is_wp_error($vtigerExistsContact)) {
								$logMessage = date('Y-m-d h:i:sa').': ' .$vtigerExistsContact->get_error_message .PHP_EOL;
								error_log($logMessage, 3, $this->logFile);
							}
							
							$existsBodyContact = wp_remote_retrieve_body($vtigerExistsContact);
							$existsDataContact = json_decode($existsBodyContact);
							
								if($existsDataContact->success == true && !empty($existsDataContact->result)) {
									//is existing contact
								
									$existsResultContact = $existsDataContact->result;
								
									$existsId = $existsResultContact[0]->id;
									
									//set correct Account Type
									$existsAccountType = $existsResultContact[0]->cf_662;
									
									$accountType = null;
									switch ($existsAccountType) {
										case 'Candidates':
										$accountType = 'Candidates';
										break;
										case 'Certified L.E.A.N. Coach':
										$accountType = 'Certified L.E.A.N. Coach';
										break;
										case 'Outstanding Balance':
										$accountType = 'Outstanding Balance';
										break;
										case 'Transfer Pending':
										$accountType = 'Transfer Pending';
										break;
										case 'Incomplete':
										$accountType = 'Candidates';
										break;
										case 'Withdrawn':
										$accountType = 'Candidates';
										break;
										case 'Inactive':
										$accountType = 'Candidates';
										break;
										case 'Expired':
										$accountType = 'Candidates';
										break;
										default:
										$accountType = 'Candidates';
									}



								//	$existsResult = $existsData->result;

									if(isset($existsResultContact[0]->cf_781)) {
										$originalTrainingDate = $existsResultContact[0]->cf_781;
									} else {
										$originalTrainingDate = $this->classDate;
									}

									$createContactParams = array(
										'id' => $existsId,
										'firstname' => $this->billing_first_name,
										'lastname' => $this->billing_last_name,
										'cf_663' => $this->billing_phone_formatted,
										'leadsource' => $this->howDidYouHearChange,
										'email' => $this->billing_email,
										'cf_617' => $this->billing_company,
										'assigned_user_id' => $webserviceUserId,
										'mailingstreet' => $this->shipping_address_1 ."\r\n" .$this->shipping_address_2,
										'mailingcity' => $this->shipping_city,
										'mailingstate' => $this->shipping_state,
										'mailingzip' => $this->shipping_postcode,
										'mailingcountry' => $this->shipping_country,
										'otherstreet' => $this->billing_address_1 . "\r\n" . $this->billing_address_2,
										'othercity' => $this->billing_city,
										'otherstate' => $this->billing_state,
										'otherzip' => $this->billing_postcode,
										'othercountry' => $this->billing_country,
										'cf_1067' => $this->nameOnCert,
										'cf_662' => $accountType,
										'cf_821' => $this->orderNumber,
										'cf_781' => $existsResultContact[0]->cf_781,
										'cf_622' => $this->classDate,
										'cf_621' => $this->paymentPlan,
										'cf_686' => '0',
										'cf_687' => '0',
										'cf_688' => '0',
										'cf_697' => '0',
										'isconvertedfromlead' => '0',
										'modifiedtime' => $this->date_modified,
										'createdtime' => $existsResultContact[0]->createdtime,
										'description' =>  $this->howDidYouHearFormatted ."\r\n". "Tell Us About Yourself: " .$this->tellUsAboutYourself . "\r\n" .$existsResultContact[0]->description
										
									);

									//if billing name is different from shipping name, create conact with shipping name
									if(!empty($this->shipping_last_name) && $this->billing_last_name != $this->shipping_last_name) {
										$createContactParams['firstname'] = $this->shipping_first_name;
										$createContactParams['lastname'] = $this->shipping_last_name;
										$this->orderInfo .= "\r\n Billing Name: " . $this->billing_first_name." ".$this->billing_last_name;
									}


									//check if value is set for each class and add it if it is
									if($this->classStart == '1') {
										$createContactParams['cf_626'] = $this->classStart;
									}

									if($this->classExpect == '1') {
										$createContactParams['cf_628'] = $this->classExpect;
									}

									if($this->classPrime == '1') {
										$createContactParams['cf_630'] = $this->classPrime;
									}

									if($this->classACTT == '1') {
										$createContactParams['cf_809'] = $this->classACTT;
										$createContactParams['cf_1320'] = $this->acttTime;
									}

									$updateContactData = json_encode($createContactParams);

									$urlUpdateContact = $vtigerUrl.'/webservice.php';
									$vtigerUpdateContact = wp_remote_post($urlUpdateContact, array(
										    'headers' => array(
										        'Content-type' => 'application/x-www-form-urlencoded',

										    ),
										     'body' => array(
										            'operation' => 'update',
										            'sessionName' => $sessionName,
										            'element' => $updateContactData,
										    )
										));

									if(is_wp_error($vtigerUpdateContact)) {
										$logMessage = date('Y-m-d h:i:sa').': ' .$vtigerUpdateContact->get_error_message .PHP_EOL;
										error_log($logMessage, 3, $this->logFile);
									}

									$updateContactBody = wp_remote_retrieve_body($vtigerUpdateContact);
									
									$updateContactData = json_decode($updateContactBody);

									//if existing contact was updated, add success notice in wordpress admin. If not add error notice and message to log
									if($updateContactData->success == true && !empty($updateContactData->result)) {
										update_post_meta($order_id, 'vtiger_success', 'true');
										$newContactId = $updateContactData->result->id;
										$newContactId = substr($newContactId, 2);
											

										//add How Did You Hear as a comment
										$this->order_note_create_comment($userId, $newContactId, $this->date_created, $this->date_modified, $this->howDidYouHearFormatted);

										//add order info as a comment
										$this->order_note_create_comment($userId, $newContactId, $this->date_created, $this->date_modified, $this->orderInfo);
										
										//if order notes exists add it as a comment
										if(isset($this->customer_note) && !empty($this->customer_note)) {
											$this->order_note_create_comment($userId, $newContactId, $this->date_created, $this->date_modified, $this->notes);
										}
										
									}  elseif($updateContactData->success == true && empty($updateContactData->result)) {
										update_post_meta($order_id, 'vtiger_success', 'false');
										$logMessage = date('Y-m-d h:i:sa').': Could not update contact in vTiger. vtiger Error Message: ' .$updateContactData->success .PHP_EOL;
										error_log($logMessage, 3, $this->logFile);
										return false;
								 
									} elseif($updateContactData->success == false) {
										update_post_meta($order_id, 'vtiger_success', 'false');
										$logMessage = date('Y-m-d h:i:sa').': Could not update contact in vTiger. vtiger Error Message: ' .$updateContactData->error->message .PHP_EOL;
										error_log($logMessage, 3, $this->logFile);
										return false;
									} 
								
							} elseif ($existsDataContact->success == true && empty($existsDataContact->result)) {
								
								$existsResultContact = $existsDataContact->result;
								//is not existing contact
							
								
								//======add contact to vtiger========
								$createContactParams = array(
									'firstname' => $this->billing_first_name,
									'lastname' => $this->billing_last_name,
									'cf_663' => $this->billing_phone_formatted,
									'leadsource' => $this->howDidYouHearChange,
									'email' => $this->billing_email,
									'cf_617' => $this->billing_company,
									'assigned_user_id' => $webserviceUserId,
									'mailingstreet' => $this->shipping_address_1 ."\r\n" .$this->shipping_address_2,
									'mailingcity' => $this->shipping_city,
									'mailingstate' => $this->shipping_state,
									'mailingzip' => $this->shipping_postcode,
									'mailingcountry' => $this->shipping_country,
									'otherstreet' => $this->billing_address_1 . "\r\n" . $this->billing_address_2,
									'othercity' => $this->billing_city,
									'otherstate' => $this->billing_state,
									'otherzip' => $this->billing_postcode,
									'othercountry' => $this->billing_country,
									'description' =>  $this->howDidYouHearFormatted . "\r\n"."Tell Us About Yourself: " .$this->tellUsAboutYourself,
									'cf_1067' => $this->nameOnCert,
									'cf_662' => 'Candidates',
									'cf_821' => $this->orderNumber,
									'cf_781' => $this->classDate,
									'cf_622' => $this->classDate,
									'cf_626' => $this->classStart,
									'cf_628' => $this->classExpect,
									'cf_630' => $this->classPrime,
									'cf_809' => $this->classACTT,
									'cf_621' => $this->paymentPlan,
									'cf_686' => '0',
									'cf_687' => '0',
									'cf_688' => '0',
									'cf_697' => '0',
									'isconvertedfromlead' => '0',
									'cf_1320' => $this->acttTime,
									'createdtime' => $this->date_created,
									'modifiedtime' => $this->date_modified
								);	

								//if billing name is different from shipping name, create conact with shipping name
								if(!empty($this->shipping_last_name) && $this->billing_last_name != $this->shipping_last_name) {
									$createContactParams['firstname'] = $this->shipping_first_name;
									$createContactParams['lastname'] = $this->shipping_last_name;
									$this->orderInfo .= "\r\n Billing Name: " . $this->billing_first_name." ".$this->billing_last_name;
								}


								$createContactData = json_encode($createContactParams);

								$urlCreateContact = $vtigerUrl.'/webservice.php';
								$vtigerCreateContact = wp_remote_post($urlCreateContact, array(
									    'headers' => array(
									        'Content-type' => 'application/x-www-form-urlencoded',

									    ),
									     'body' => array(
									            'operation' => 'create',
									            'sessionName' => $sessionName,
									            'element' => $createContactData,
									            'elementType' => 'Contacts'
									    )
									));

									if(is_wp_error($vtigerCreateContact)) {
										$logMessage = date('Y-m-d h:i:sa').': ' .$vtigerCreateContact->get_error_message .PHP_EOL;
										error_log($logMessage, 3, $this->logFile);
									}

									$createContactBody = wp_remote_retrieve_body($vtigerCreateContact);
									$createContactData = json_decode($createContactBody);
									
									//if new contact was added, add success notice in wordpress admin
									if($createContactData->success == true && !empty($createContactData->result)) {
										
											update_post_meta($order_id, 'vtiger_success', 'true');

											$newContactId = $createContactData->result->id;
											$newContactId = substr($newContactId, 2);

											//add How Did You Hear as a comment
											$this->order_note_create_comment($userId, $newContactId, $this->date_created, $this->date_modified, $this->howDidYouHearFormatted);

											//add order info as a comment
											$this->order_note_create_comment($userId, $newContactId, $this->date_created, $this->date_modified, $this->orderInfo);
											
											//if order notes exists add it as a comment
											if(isset($this->customer_note) && !empty($this->customer_note)) {
												
												$this->order_note_create_comment($userId, $newContactId, $this->date_created, $this->date_modified, $this->notes);
											}

											
										
									} elseif($createContactData->success == true && empty($createContactData->result)) {
										
										update_post_meta($order_id, 'vtiger_success', 'false');
										$logMessage = date('Y-m-d h:i:sa').': Could not create new contact in vTiger. vtiger Error Message: ' .$createContactData->success .PHP_EOL;
										error_log($logMessage, 3, $this->logFile);
										return false;
									} elseif ($createContactData->success == false) {
										
										update_post_meta($order_id, 'vtiger_success', 'false');
										$logMessage = date('Y-m-d h:i:sa').': Could not create new contact in vTiger. vtiger Error Message: ' .$createContactData->error->message .PHP_EOL;
										error_log($logMessage, 3, $this->logFile);
										return false;
									}
								
							} //end check if contact exists

					
				} //end check if lead exists
			
		}

		//====transfer comments from lead to contact
		function transfer_comments($currentParentId, $targetParentId) {

			$db = new db;
			$connection = $db->connect();
			
		
			 $transferCommentsQuery = "UPDATE vtiger_modcomments SET related_to=? WHERE related_to=?;";
			 $transferCommentsStmt = mysqli_prepare($connection, $transferCommentsQuery);
			 mysqli_stmt_bind_param($transferCommentsStmt, 'ss', $targetParentId, $currentParentId);
			 $transferCommentsExecute = mysqli_stmt_execute($transferCommentsStmt);
			
			if($transferCommentsExecute) {
				mysqli_close($connection);
			} else {
				$logMessage = date('Y-m-d h:i:sa').': Comments transfer query has error: '.mysqli_error($connection) .PHP_EOL;
				error_log($logMessage, 3, $this->logFile);
			}

		}

		//===convert lead to contact. Set "converted" to 1 on leaddetails table
		function lead_convert($leadId) {
			$db = new db;
			$connection = $db->connect();

			$leadConvertQuery = "UPDATE vtiger_leaddetails SET converted=1 WHERE leadid=?;";
			$leadConvertStmt = mysqli_prepare($connection, $leadConvertQuery);
			mysqli_stmt_bind_param($leadConvertStmt, 's', $leadId);
			$leadConvertExecute = mysqli_stmt_execute($leadConvertStmt);
			
			if($leadConvertExecute) {
				mysqli_close($connection);
			} else {
				
				$logMessage = date('Y-m-d h:i:sa').': Failed to convert to lead: '.mysqli_error($connection) .PHP_EOL;
				error_log($logMessage, 3, $this->logFile);
				
			}
		}

		//===make any order notes into a comment
		//records in vtiger must be added to 3 different tables - vtiger_crmentity, vtiger_modcomments, vtiger_modcommentscf 
		function order_note_create_comment($userId, $newContactId, $comCreatedTime, $comModifiedTime, $commentText) {

			$db = new db;
			$connection = $db->connect();

			$crmidForEntity = $this->create_new_crmid();

			//need to create crmentity using newCrmid;
			$crmEntityQuery = "INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid, modifiedby, setype, createdtime, modifiedtime, version, presence, deleted, label, smgroupid, source) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

			$crmEntityStmt = mysqli_prepare($connection, $crmEntityQuery);

			$modComments = 'ModComments';
			$version = '0';
			$presence = '1';
			$deleted = '0';
			$smgroupid = '0';
			$source = 'WEBSERVICE';
			mysqli_stmt_bind_param($crmEntityStmt, "sssssssssssss", $crmidForEntity, $userId, $userId, $userId, $modComments, $comCreatedTime, $comModifiedTime, $version, $presence, $deleted, $commentText, $smgroupid, $source);
			$crmEntityExecute = mysqli_stmt_execute($crmEntityStmt);
			
			if(!$crmEntityExecute) {
				$logMessage = date('Y-m-d h:i:sa').': Unable to insert new row in vtiger_crmentity table: '.mysqli_error($connection) .PHP_EOL;
				error_log($logMessage, 3, $this->logFile);
			} 

			//create comment
			$newCommentQuery = "INSERT INTO vtiger_modcomments(modcommentsid, commentcontent, related_to, parent_comments, customer, userid, is_private, filename, related_email_id) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?);";

			$newCommentStmt = mysqli_prepare($connection, $newCommentQuery);

			$parentComments = '0';
			$customer = '0';
			$isPrivate = '0';
			$filename = '0';
			$relatedEmailId = '0';

			mysqli_stmt_bind_param($newCommentStmt, "sssssssss", $crmidForEntity, $commentText, $newContactId, $parentComments, $customer, $userId, $isPrivate, $filename, $relatedEmailId);

			$newCommentExecute = mysqli_stmt_execute($newCommentStmt);

			if(!$newCommentExecute) {
				$logMessage = date('Y-m-d h:i:sa').': Unable to insert new row into vtiger_modcommnets table: '.mysqli_error($connection) .PHP_EOL;
				error_log($logMessage, 3, $this->logFile);
					
				} 

			//add to modcommentscf
			$modCommentsCfQuery = "INSERT INTO vtiger_modcommentscf(modcommentsid) VALUES(?);";
			$modCommentsCfStmt = mysqli_prepare($connection, $modCommentsCfQuery);
			mysqli_stmt_bind_param($modCommentsCfStmt, 's', $crmidForEntity);
			$modCommentsCfExecute = mysqli_stmt_execute($modCommentsCfStmt);

			if(!$modCommentsCfExecute) {
						$logMessage = date('Y-m-d h:i:sa').': Unable to insert new row into vtgier_modcommentscf table: '.mysqli_error($connection) .PHP_EOL;
				error_log($logMessage, 3, $this->logFile);

					} else {
						mysqli_close($connection);
					}
		}

		//must create crmid for crmentity table in vtiger
		function create_new_crmid() {
			$peardb = new PearDatabase;
			$connection = $peardb->connect();
			
			$newCrmid = $peardb->getUniqueID("vtiger_crmentity");
			if(!$newCrmid) {
				$logMessage = date('Y-m-d h:i:sa').': Failed to insert new crmentity: '.mysqli_error($connection) .PHP_EOL;
								error_log($logMessage, 3, $this->logFile);
			} 
			return $newCrmid;
		}

		//match vtiger user with wordpress user
		function vtiger_user($currentUserEmail) {
			$db = new db;
			$connection = $db->connect();

			$vtigerUserId = '1';

			$userQuery = "SELECT email1, id, status FROM vtiger_users WHERE email1=?;";

			$stmt = mysqli_prepare($connection, $userQuery);
			mysqli_stmt_bind_param($stmt, "s", $currentUserEmail);
			mysqli_stmt_execute($stmt);
			$userResult = mysqli_stmt_get_result($stmt);

			if($userResult) {
				while($row = $userResult->fetch_assoc()) {
					
					//if the wordpress email and vtiger email match anf vtiger user is active, then set vtiger user id, else make it default to admin
					if($row['status'] == 'Active') {
						$vtigerUserId = $row['id'];
						
					} else {
						
						$vtigerUserId = '1';
					}
				}
			} 
			return $vtigerUserId;
		}

	}