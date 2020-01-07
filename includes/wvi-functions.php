<?php
require_once('class-woo-vtiger-integrate.php');
function wvi_after_woo_order($order_id) {
	
	$woo_vtiger_int = new woo_vtiger_int;
	$woo_vtiger_order = $woo_vtiger_int->get_order_data($order_id);
	if($woo_vtiger_order) {
		$vtigerConn = $woo_vtiger_int->vtiger_connect();
		if($vtigerConn) {
			$woo_vtiger_int->vtiger_add_contact();
		} else {
			return;
		}
	}
	
}

add_action('woocommerce_thankyou', 'wvi_after_woo_order');

//if a failed order is changed to processing, add to vtiger
function failed_order_change($order_id, $old_status, $new_status) {
	
	if($new_status == 'processing' && $old_status == 'failed') {
		$woo_vtiger_int = new woo_vtiger_int;
		$woo_vtiger_order = $woo_vtiger_int->get_order_data($order_id);
		if($woo_vtiger_order) {
			$vtigerConn = $woo_vtiger_int->vtiger_connect();
			if($vtigerConn) {
				$woo_vtiger_int->vtiger_add_contact();
			} else {
				return;
			}
		}
	}
	
}

add_action('woocommerce_order_status_changed', 'failed_order_change', 10, 3);

//add a success or error notice to woocommerce orders screen
function wvi_error_check() {
	//check if screen is currently woocommerce orders in admin
	$screen = get_current_screen();

	
	if($screen->id == 'edit-shop_order') {
	$wooOrderUrl = admin_url() .'edit.php?post_type=shop_order';
	//=====check if there was a problem getting the order number
		if(get_transient('wvi-order-error')) {
			?>
			<div class="notice notice-error is-dismissible">
				<p>Unable to get order info from woocommerce to vtiger. <a href="<?php echo $wooOrderUrl;?>&vtiger-ignore-notice">Dismiss</a></p>
			</div>
			<?php
			if(isset($_GET['vtiger-ignore-notice'])) {
				delete_transient('wvi-order-error');
			}
		}

	//get the 10 most recent orders
	$query = new WC_Order_Query(array(
		'limit' =>10,
		'orderby' => 'date',
		'return' => 'ids'
		));
	$mostRecentOrders = $query->get_orders();
	//print_r($mostRecentOrders);
	foreach($mostRecentOrders as $mostRecentOrderKey => $mostRecentOrderValue) {
		
		$mostRecentOrderId = $mostRecentOrderValue;
		//echo $mostRecentOrderNumber;
		//get vtiger_success meta, vtiger_notice_dismissed meta, and the order number
		$vtigerSuccess = get_post_meta($mostRecentOrderId, 'vtiger_success', true);
		$vtigerDismissed = get_post_meta($mostRecentOrderId, 'vtiger_notice_dismissed', true);
		$mostRecentOrderNumber = get_post_meta($mostRecentOrderId, '_wcj_order_number', true);
		
		//print_r($vtigerSuccess);
		//if vtiger_success is true and the notice is not dismissed, show the success notice
		if($vtigerSuccess == 'true' && !$vtigerDismissed == 'true') {
		
			?>
			<!--<div class="notice notice-success is-dismissible">
				<p>Order #<?php echo $mostRecentOrderNumber; ?> successfully added to vtiger. Remember to update Lead Source and add Class Dates. <a href="<?php echo $wooOrderUrl;?>&vtiger-ignore-notice=<?php echo $mostRecentOrderId;?>">Dismiss</a></p>
			</div>-->
			<?php
			//if the user has dismissed the notice, the $_GET variable will be set. Add dismissed meta data to order
			
			if(isset($_GET['vtiger-ignore-notice']) && $_GET['vtiger-ignore-notice'] == $mostRecentOrderId) {

				update_post_meta($mostRecentOrderId, 'vtiger_notice_dismissed', 'true');
				wp_redirect(admin_url('/edit.php?post_type=shop_order'));
				return;
			}
		//if vtiger_success is false and the notice is not dismissed, show the error notice
		} elseif($vtigerSuccess == 'false' && !$vtigerDismissed == 'true') {
			?>
			<div class="notice notice-error is-dismissible">

				<p>Order #<?php echo $mostRecentOrderNumber; ?> was not added to vtiger. <a href="<?php echo $wooOrderUrl;?>&vtiger-ignore-notice-error=<?php echo $mostRecentOrderId;?>">Dismiss</a></p>
			</div>

			<?php
			//if the user has dismissed the notice, the $_GET variable will be set. Add dismissed meta data to order
			if(isset($_GET['vtiger-ignore-notice-error']) && $_GET['vtiger-ignore-notice-error'] == $mostRecentOrderId) {
				update_post_meta($mostRecentOrderId, 'vtiger_notice_dismissed', 'true');
				wp_redirect(admin_url('/edit.php?post_type=shop_order'));
				return;
			}
		}
	}

  } //end if screen
	
}

add_action('admin_head', 'wvi_error_check');