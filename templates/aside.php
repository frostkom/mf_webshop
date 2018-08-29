<?php

function get_webshop_cart()
{
	global $wpdb, $sesWebshopCookie, $intCustomerID, $intCustomerNo, $strOrderName, $emlOrderEmail, $strOrderText, $intDeliveryTypeID, $error_text, $done_text;

	$out = get_notification();

	$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, productAmount FROM ".$wpdb->posts." INNER JOIN ".$wpdb->prefix."webshop_product2user ON ".$wpdb->posts.".ID = ".$wpdb->prefix."webshop_product2user.productID WHERE post_type = 'mf_products' AND webshopDone = '0' AND (userID = '%d' OR webshopCookie = %s)", get_current_user_id(), $sesWebshopCookie));

	if($wpdb->num_rows > 0)
	{
		$out .= "<h4>".__("Cart", 'lang_webshop')."</h4>
		<ul>";

			foreach($result as $r)
			{
				$post_id = $r->ID;
				$post_title = $r->post_title;
				$product_amount = $r->productAmount;

				$post_url = get_permalink($post_id);

				$out .= "<li>
					<a href='".$post_url."'>
						<span>".$post_title."</span>
						<em>(".$product_amount.")</em>
					</a>
				</li>";
			}

		$out .= "</ul>
		<form method='post' action='' id='order_proceed' class='mf_form".(isset($_POST['btnOrderConfirm']) ? " hide" : "")."'>
			<div class='form_button'>"
				.show_button(array('name' => 'btnOrderProceed', 'text' => __("Proceed to Checkout", 'lang_webshop'), 'type' => 'button'))
			."</div>
		</form>";

		if(get_current_user_id() > 0 && !($intCustomerID > 0))
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT customerID, orderName, orderEmail FROM ".$wpdb->prefix."webshop_order WHERE userID = '%d' ORDER BY orderCreated DESC LIMIT 0, 1", get_current_user_id()));

			foreach($result as $r)
			{
				$intCustomerID = $r->customerID;
				$strOrderName = $r->orderName;
				$emlOrderEmail = $r->orderEmail;
			}
		}

		$out .= "<form method='post' action='' id='order_confirm' class='mf_form".(isset($_POST['btnOrderConfirm']) ? "" : " hide")."'>
			<h4>".__("Checkout", 'lang_webshop')."</h4>";

			$arr_data = get_posts_for_select(array('post_type' => 'mf_customers', 'order' => "post_title ASC", 'add_choose_here' => true));

			if(count($arr_data) > 0)
			{
				$out .= show_select(array('data' => $arr_data, 'name' => 'intCustomerID', 'text' => __("Customer", 'lang_webshop'), 'value' => $intCustomerID))
				.show_textfield(array('name' => 'intCustomerNo', 'text' => __("Customer No", 'lang_webshop'), 'value' => $intCustomerNo, 'type' => 'number'));
			}

			$out .= show_textfield(array('name' => 'strOrderName', 'text' => __("Name", 'lang_webshop'), 'value' => $strOrderName, 'required' => true))
			.show_textfield(array('name' => 'emlOrderEmail', 'text' => __("E-mail", 'lang_webshop'), 'value' => $emlOrderEmail, 'required' => true))
			.show_textarea(array('name' => 'strOrderText', 'text' => __("Text", 'lang_webshop'), 'value' => $strOrderText));

			$arr_data = get_posts_for_select(array('post_type' => 'mf_delivery_type', 'order' => "post_title ASC"));

			if(count($arr_data) > 0)
			{
				$out .= show_select(array('data' => $arr_data, 'name' => 'intDeliveryTypeID', 'text' => __("Delivery Type", 'lang_webshop'), 'value' => $intDeliveryTypeID));
			}

			$out .= "<div class='form_button'>"
				.show_button(array('name' => 'btnOrderConfirm', 'text' => __("Confirm Order", 'lang_webshop')))
			."</div>"
			.wp_nonce_field('order_confirm', '_wpnonce_order_confirm', true, false)
		."</form>";
	}

	return $out;
}

if(get_option('setting_show_categories') == 'yes')
{
	if(isset($_SESSION['sesWebshopCookie']) && $_SESSION['sesWebshopCookie'] != '')
	{
		$sesWebshopCookie = check_var('sesWebshopCookie', 'char', true);
	}

	else
	{
		$_SESSION['sesWebshopCookie'] = $sesWebshopCookie = md5("mf_webshop".$_SERVER['REMOTE_ADDR'].date("Y-m-d H:i:s"));
	}

	$intProductID = check_var('intProductID');
	$intProductAmount = check_var('intProductAmount', '', true, '1');

	$intCustomerID = check_var('intCustomerID');
	$intCustomerNo = check_var('intCustomerNo');

	$strOrderName = check_var('strOrderName');
	$emlOrderEmail = check_var('emlOrderEmail');
	$strOrderText = check_var('strOrderText');
	$intDeliveryTypeID = check_var('intDeliveryTypeID');

	if(isset($_POST['btnProductBuy']) && wp_verify_nonce($_POST['_wpnonce_product_buy'], 'product_buy_'.$intProductID))
	{
		$wpdb->get_results($wpdb->prepare("SELECT productID FROM ".$wpdb->prefix."webshop_product2user WHERE productID = '%d' AND webshopDone = '0' AND (userID = '%d' OR webshopCookie = %s) LIMIT 0, 1", $intProductID, get_current_user_id(), $sesWebshopCookie));

		if($wpdb->num_rows > 0)
		{
			if($intProductAmount > 0)
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."webshop_product2user SET productAmount = '%d' WHERE productID = '%d' AND webshopDone = '0' AND (userID = '%d' OR webshopCookie = %s)", $intProductAmount, $intProductID, get_current_user_id(), $sesWebshopCookie));
			}

			else
			{
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."webshop_product2user WHERE productID = '%d' AND webshopDone = '0' AND (userID = '%d' OR webshopCookie = %s)", $intProductID, get_current_user_id(), $sesWebshopCookie));
			}
		}

		else if($intProductAmount > 0)
		{
			$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."webshop_product2user SET productID = '%d', userID = '%d', webshopCookie = %s, productAmount = '%d', webshopCreated = NOW()", $intProductID, get_current_user_id(), $sesWebshopCookie, $intProductAmount));
		}

		//$done_text = __("The cart has been updated", 'lang_webshop');
	}

	else if(isset($_POST['btnOrderConfirm']) && wp_verify_nonce($_POST['_wpnonce_order_confirm'], 'order_confirm'))
	{
		if($strOrderName != '' && $emlOrderEmail != '')
		{
			$accepted = false;

			$result	= $wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'mf_customers' AND post_status = 'publish' ORDER BY post_title ASC");

			if($wpdb->num_rows > 0)
			{
				foreach($result as $r)
				{
					$customer_id = $r->ID;

					if($intCustomerID == $customer_id)
					{
						$customer_no = get_post_meta($customer_id, $meta_prefix.'customer_no', true);

						if($intCustomerNo == $customer_no)
						{
							$accepted = true;
						}
					}
				}
			}

			else
			{
				$accepted = true;
			}

			if($accepted == true)
			{
				$result = $wpdb->get_results($wpdb->prepare("SELECT productID, productAmount FROM ".$wpdb->prefix."webshop_product2user WHERE webshopDone = '0' AND (userID = '%d' OR webshopCookie = %s)", get_current_user_id(), $sesWebshopCookie));

				if($wpdb->num_rows > 0)
				{
					$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."webshop_order SET customerID = '%d', orderName = %s, orderEmail = %s, orderText = %s, deliveryTypeID = '%d', userID = '%d', orderCreated = NOW()", $intCustomerID, $strOrderName, $emlOrderEmail, $strOrderText, $intOrderDeliveryType, get_current_user_id()));

					$intOrderID = $wpdb->insert_id;

					foreach($result as $r)
					{
						$intProductID2 = $r->productID;
						$intProductAmount2 = $r->productAmount;

						$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."webshop_product2user SET orderID = '%d', webshopDone = '1' WHERE productID = '%d' AND webshopDone = '0' AND (userID = '%d' OR webshopCookie = %s)", $intOrderID, $intProductID2, get_current_user_id(), $sesWebshopCookie));

						//update_product_amount($intProductID2, $intProductAmount2)
					}

					$done_text = __("The order has been completed", 'lang_webshop');

					$intCustomerID = $strOrderName = $emlOrderEmail = $strOrderText = $intOrderDeliveryType = "";
					unset($_POST['btnOrderConfirm']);

					/*if($emlOrderEmail != '')
					{
						$strEmail = $emlOrderEmail;
						$strFromEmail = get_bloginfo('admin_email');
						$strSubject = __("Order info", 'lang_webshop')." (".date("Y-m-d").")";
						$strText = "";

						sendEmail();
					}*/
				}
			}

			else
			{
				$error_text = __("You have to select the customer and correct customer number", 'lang_webshop');
			}
		}

		else
		{
			$error_text = __("You have to enter customer, customer number, name and e-mail", 'lang_webshop');
		}
	}

	if(!isset($cat_id)){	$cat_id = 0;}

	list($list_output, $is_parent) = get_product_list_item(0, $cat_id);

	$cart_output = get_webshop_cart();

	if($list_output != '' || $cart_output != '')
	{
		echo "<div class='aside'>"
			.$list_output
			.$cart_output
		."</div>";
	}
}