<?php

$obj_webshop = new mf_webshop();

//do_action('load_font_awesome');

echo "<div class='wrap'>
	<h2>".__("Statistics", 'lang_webshop')."</h2>";

	$arr_total_products_type = [];
	$shipping_cost_total = $invoice_cost_total = [];

	$result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s", $obj_webshop->post_type_orders, 'publish'));

	foreach($result as $r)
	{
		$order_id = $r->ID;

		$order_status = get_post_meta($order_id, $obj_webshop->meta_prefix.'order_status', true);

		if(in_array($order_status, array('paid', 'sent', 'finalized')))
		{
			$payment_method = get_post_meta($order_id, $obj_webshop->meta_prefix.'payment_method', true);
			$test_mode = get_post_meta($order_id, $obj_webshop->meta_prefix.'test_mode', true);
			$shipping_cost = get_post_meta($order_id, $obj_webshop->meta_prefix.'shipping_cost', true);

			if($payment_method == 'invoice')
			{
				$invoice_cost = get_post_meta($order_id, $obj_webshop->meta_prefix.'invoice_cost', true);
				$total_sum_invoice = get_post_meta($order_id, $obj_webshop->meta_prefix.'total_sum_invoice', true);
			}

			$total_sum = get_post_meta($order_id, $obj_webshop->meta_prefix.'total_sum', true);
			//$total_tax = get_post_meta($order_id, $obj_webshop->meta_prefix.'total_tax', true);
			$paid_currency = get_post_meta($order_id, $obj_webshop->meta_prefix.'paid_currency', true);
			$paid_tax_display = get_post_meta_or_default($order_id, $obj_webshop->meta_prefix.'paid_tax_display', true, get_option('setting_webshop_tax_display', 'yes'));

			if($shipping_cost > 0)
			{
				if(!isset($shipping_cost_total[$test_mode]))
				{
					$shipping_cost_total[$test_mode] = 0;
				}

				$shipping_cost_total[$test_mode] += $shipping_cost;
			}

			if($payment_method == 'invoice')
			{
				if($invoice_cost > 0)
				{
					if(!isset($invoice_cost_total[$test_mode]))
					{
						$invoice_cost_total[$test_mode] = 0;
					}

					$invoice_cost_total[$test_mode] += $invoice_cost;
				}

				else if(($total_sum_invoice - $total_sum) > 0)
				{
					if(!isset($invoice_cost_total[$test_mode]))
					{
						$invoice_cost_total[$test_mode] = 0;
					}

					$invoice_cost_total[$test_mode] += ($total_sum_invoice - $total_sum);
				}
			}

			$arr_products = get_post_meta($order_id, $obj_webshop->meta_prefix.'products', true);

			if(is_array($arr_products))
			{
				foreach($arr_products as $key => $arr_product)
				{
					if(!isset($arr_total_products_type[$test_mode][$arr_product['id']]))
					{
						$arr_total_products_type[$test_mode][$arr_product['id']] = array(
							'total_amount' => 0,
							'total_price' => 0,
							'paid_currency' => $paid_currency,
							'paid_tax_display' => $paid_tax_display,
						);
					}

					$arr_total_products_type[$test_mode][$arr_product['id']]['total_amount'] += $arr_product['amount'];
					$arr_total_products_type[$test_mode][$arr_product['id']]['total_price'] += ($arr_product['price'] * $arr_product['amount']);

					if($paid_currency != $arr_total_products_type[$test_mode][$arr_product['id']]['paid_currency'])
					{
						$error_text = __("Paid currency is not the same on all orders!", 'lang_webshop')." (".$paid_currency." != ".$arr_total_products_type[$test_mode][$arr_product['id']]['paid_currency'].")";
					}

					else if($paid_tax_display != $arr_total_products_type[$test_mode][$arr_product['id']]['paid_tax_display'])
					{
						$error_text = __("Paid tax is not the same on all orders!", 'lang_webshop')." (".$paid_tax_display." != ".$arr_total_products_type[$test_mode][$arr_product['id']]['paid_tax_display'].")";
					}
				}
			}
		}
	}

	echo get_notification();

	if(count($arr_total_products_type) > 0)
	{
		foreach($arr_total_products_type as $test_mode => $arr_total_products)
		{
			$total_price = 0;
			$paid_currency = $paid_tax_display_prefix = "";

			echo "<h3>".__("Test Mode", 'lang_webshop').": ".$test_mode."</h3>";

			echo "<table".apply_filters('get_table_attr', "").">";

				$arr_header = [];
				$arr_header[] = __("Product", 'lang_webshop');
				$arr_header[] = __("Amount", 'lang_webshop');
				$arr_header[] = __("Total Price", 'lang_webshop');

				echo show_table_header($arr_header)
				."<tbody>";

					foreach($arr_total_products as $product_id => $arr_product_total)
					{
						$paid_currency = $arr_product_total['paid_currency'];
						$paid_tax_display_prefix = ($arr_product_total['paid_tax_display'] == 'yes' ? __("excl. tax", 'lang_webshop') : __("incl. tax", 'lang_webshop'));

						echo "<tr>
							<td>".get_the_title($product_id)."</td>
							<td>".$arr_product_total['total_amount']."</td>
							<td>".$arr_product_total['total_price']." ".$paid_currency." ".$paid_tax_display_prefix."</td>
						</tr>";

						$total_price += $arr_product_total['total_price'];
					}

					echo "<tr>
						<th></th>
						<th></th>
						<th>".$total_price." ".$paid_currency." ".$paid_tax_display_prefix."</th>
					</tr>";

				echo "</tbody>
			</table>";

			if(isset($shipping_cost_total[$test_mode]))
			{
				echo "<p>".__("Shipping Cost", 'lang_webshop').": ".$shipping_cost_total[$test_mode]." ".$paid_currency." ".$paid_tax_display_prefix."</p>";
			}

			if(isset($invoice_cost_total[$test_mode]))
			{
				echo "<p>".__("Invoice Cost", 'lang_webshop').": ".$invoice_cost_total[$test_mode]." ".$paid_currency." ".$paid_tax_display_prefix."</p>";
			}
		}
	}

echo "</div>";