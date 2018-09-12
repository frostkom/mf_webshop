<?php

$paged = check_var('paged', 'int', true, '0');
$strSearch = check_var('s', 'char', true);

$intOrderID = check_var('intOrderID');

$intLimitAmount = 20;
$intLimitStart = $paged * $intLimitAmount;

if(isset($_REQUEST['btnOrderDelete']) && wp_verify_nonce($_REQUEST['_wpnonce_order_delete'], 'order_delete_'.$intOrderID))
{
	$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."webshop_product2user WHERE orderID = '%d'", $intOrderID));
	$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."webshop_order WHERE orderID = '%d'", $intOrderID));

	$done_text = __("The order was deleted", 'lang_webshop');
}

else if(isset($_REQUEST['btnOrderInvoice']) && wp_verify_nonce($_REQUEST['_wpnonce_order_invoice'], 'order_invoice_'.$intOrderID))
{
	$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."webshop_order SET orderInvoice = NOW() WHERE orderID = '%d'", $intOrderID));
}

else if(isset($_REQUEST['btnOrderDelivery']) && wp_verify_nonce($_REQUEST['_wpnonce_order_delivery'], 'order_delivery_'.$intOrderID))
{
	$result = $wpdb->get_results($wpdb->prepare("SELECT productID, webshopAmount FROM ".$wpdb->prefix."webshop_product2user WHERE orderID = '%d'", $intOrderID));

	foreach($result as $r)
	{
		$intProductID = $r->productID;
		$intWebshopAmount = $r->webshopAmount;

		update_product_amount($intProductID2, $intWebshopAmount);
	}

	$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."webshop_order SET orderDelivery = NOW() WHERE orderID = '%d'", $intOrderID));

	$strOrderEmail = $wpdb->get_var($wpdb->prepare("SELECT orderEmail FROM ".$wpdb->prefix."webshop_order WHERE orderID = '%d'", $intOrderID));

	if(1 == 2 && $strOrderEmail != '')
	{
		$strEmail = $strOrderEmail;
		$strFromEmail = get_bloginfo('admin_email');
		$strSubject = __("Shipping info", 'lang_webshop')." (".date("Y-m-d").")";
		$strText = sprintf(__("Your order from %s is now ready for pickup. Thanks for your order and welcome back!", 'lang_webshop'), get_bloginfo('name'));

		sendEmail();
	}
}

echo "<div class='wrap'>
	<h2>".__("Orders", 'lang_webshop')."</h2>"
	.get_notification();

	$query_join = $query_xtra = "";

	if($strSearch != '')
	{
		$query_xtra .= ($query_xtra != '' ? " AND " : " WHERE ")."(orderName LIKE '%".esc_sql($strSearch)."%')";
	}

	$resultPagination = $wpdb->get_results("SELECT orderID FROM ".$wpdb->prefix."webshop_order".$query_join.$query_xtra);

	echo get_list_navigation($resultPagination)
	."<table class='widefat striped'>";

		$arr_header[] = __("Invoice sent", 'lang_webshop');
		$arr_header[] = __("Delivered", 'lang_webshop');
		$arr_header[] = __("Ordered by", 'lang_webshop');
		$arr_header[] = __("Created", 'lang_webshop');

		echo show_table_header($arr_header)
		."<tbody>";

			$result = $wpdb->get_results("SELECT orderID, orderInvoice, orderDelivery, customerID, orderName, userID, orderCreated FROM ".$wpdb->prefix."webshop_order".$query_join.$query_xtra." ORDER BY orderCreated DESC LIMIT ".esc_sql($intLimitStart).", ".esc_sql($intLimitAmount));

			foreach($result as $r)
			{
				$intOrderID2 = $r->orderID;
				$strOrderInvoice = $r->orderInvoice;
				$strOrderDelivery = $r->orderDelivery;
				$intCustomerID = $r->customerID;
				$strOrderName = $r->orderName;
				$intUserID = $r->userID;
				$strOrderCreated = $r->orderCreated;

				$strCustomerName = $wpdb->get_var($wpdb->prepare("SELECT post_title FROM ".$wpdb->posts." WHERE post_type = 'mf_customers' AND post_status = 'publish' AND ID = '%d'", $intCustomerID));

				//$user_data = get_userdata($intUserID);

				echo "<tr>
					<td>";

						if(!($strOrderInvoice > 0))
						{
							echo "<i class='fa fa-ban fa-lg red'></i>
							<div class='row-actions'>
								<a href='".wp_nonce_url("?post_type=mf_products&page=mf_webshop/orders/index.php&btnOrderInvoice&intOrderID=".$intOrderID2, 'order_invoice_'.$intOrderID2, '_wpnonce_order_invoice')."'>".__("Invoice sent", 'lang_webshop')."</a>
							</div>";
						}

						else
						{
							echo "<i class='fa fa-check fa-lg green'></i>";
						}

					echo "</td>
					<td>";

						if(!($strOrderDelivery > 0))
						{
							echo "<i class='fa fa-ban fa-lg red'></i>
							<div class='row-actions'>
								<a href='".wp_nonce_url("?post_type=mf_products&page=mf_webshop/orders/index.php&btnOrderDelivery&intOrderID=".$intOrderID2, 'order_delivery_'.$intOrderID2, '_wpnonce_order_delivery')."'>".__("Delivered", 'lang_webshop')."</a>
							</div>";
						}

						else
						{
							echo "<i class='fa fa-check fa-lg green'></i>";
						}

					echo "</td>
					<td>
						<a href='?post_type=mf_products&page=mf_webshop/orders/index.php&btnOrderShow&intOrderID=".$intOrderID2."'>".$strCustomerName."</a>
						<div class='row-actions'>";

							if($intUserID > 0)
							{
								echo get_user_info(array('id' => $intUserID))." | ";
							}

							echo $strOrderName
						."</div>
					</td>
					<td>"
						.format_date($strOrderCreated)
						."<div class='row-actions'>
							<a href='".wp_nonce_url("?post_type=mf_products&page=mf_webshop/orders/index.php&btnOrderDelete&intOrderID=".$intOrderID2, 'order_delete_'.$intOrderID2, '_wpnonce_order_delete')."'>".__("Delete", 'lang_webshop')."</a>
						</div>
					</td>
				</tr>";

				if(isset($_REQUEST['btnOrderShow']) && $intOrderID2 == $intOrderID)
				{
					echo "<tr".($class != '' ? " class='".$class."'" : "").">
						<td colspan='2'></td>
						<td colspan='".(count($arr_header) - 2)."'>
							<table>";

								$result2 = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, productAmount FROM ".$wpdb->posts." INNER JOIN ".$wpdb->prefix."webshop_product2user ON ".$wpdb->posts.".ID = ".$wpdb->prefix."webshop_product2user.productID WHERE post_type = 'mf_products' AND orderID = '%d'", $intOrderID2));

								foreach($result2 as $r)
								{
									$product_id = $r->ID;
									$product_title = $r->post_title;
									$product_amount = $r->productAmount;

									echo "<tr>
										<td>".$product_title."</td>
										<td>".$product_amount."</td>
									</tr>";
								}

							echo "</table>
						</td>
					</tr>";
				}
			}

		echo "</tbody>
	</table>
</div>";

update_user_meta(get_current_user_id(), 'meta_orders_viewed', date("Y-m-d H:i:s"));