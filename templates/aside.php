<?php

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