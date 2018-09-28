<?php

function confirm_payment_webshop($data)
{
	$obj_webshop = new mf_webshop();
	$obj_webshop->confirm_payment($data);
}

if(!function_exists('get_list_navigation'))
{
	function get_list_navigation($resultPagination)
	{
		global $wpdb, $intLimitAmount, $strSearch;

		$out = "";

		$rowsPagination = $wpdb->num_rows;

		if($rowsPagination > $intLimitAmount || $strSearch != '')
		{
			$out .= "<form method='post' action='".preg_replace("/\&paged\=\d+/", "", $_SERVER['REQUEST_URI'])."'>
				<p class='search-box'>"
					//."<input type='search' name='s' value='".$strSearch."'>"
					.show_textfield(array('type' => 'search', 'name' => 's', 'value' => $strSearch, 'placeholder' => __("Search for", 'lang_webshop'), 'xtra' => " autocomplete='off'"))
					.show_button(array('text' => __("Search", 'lang_webshop'), 'class' => "button"))
				."</p>
			</form>";
		}

		if($rowsPagination > 0)
		{
			$pagination_obj = new pagination();

			$out .= $pagination_obj->show(array('result' => $resultPagination));
		}

		return $out;
	}
}

function update_product_amount($intProductID2, $intProductAmount2)
{
	global $wpdb;

	$obj_webshop = new mf_webshop();

	$error_text = "";

	$result = $obj_webshop->get_document_types(array('select' => "ID, post_name", 'where_key' => "ID = '%d'", 'where_value' => $intProductID2, 'order' => "menu_order ASC"));

	foreach($result as $r)
	{
		$post_id = $r->ID;
		$post_name = $r->post_name;

		$post_custom_type = get_post_meta($post_id, $obj_webshop->meta_prefix.'document_type', true);

		if($post_custom_type == 'price')
		{
			$post_meta = get_post_meta($intProductID2, $obj_webshop->meta_prefix.$post_name, true);

			if($post_meta > 0 && $intProductAmount2 > 0)
			{
				$intProductAmount_result = $post_meta - $intProductAmount2 > 0 ? $post_meta - $intProductAmount2 : 0;

				update_post_meta($intProductID2, $obj_webshop->meta_prefix.$post_name, $intProductAmount_result);
			}

			else
			{
				$error_text = __("The amount in stock and in the order is wrong", 'lang_webshop')." (".$post_meta." - ".$intProductAmount2.")";
			}
		}
	}

	return $error_text;
}

function get_product_list_item($post_id = 0, $current_post_id = 0)
{
	global $wpdb;
	
	$obj_webshop = new mf_webshop();

	$out = "";
	$is_ancestor = false;

	$result = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = 'publish' AND post_parent = '%d' ORDER BY menu_order ASC", $obj_webshop->post_type_categories, $post_id));

	if($wpdb->num_rows > 0)
	{
		$out .= "<ul".($post_id > 0 ? " class='children'" : "").">";

			foreach($result as $r)
			{
				$post_id = $r->ID;
				$post_title = $r->post_title;

				$post_url = get_permalink($r);

				list($list_output, $is_parent) = get_product_list_item($post_id, $current_post_id);

				$class = "";

				if($post_id == $current_post_id)
				{
					$class = "current_page_item";

					$is_ancestor = true;
				}

				else if($is_parent == true)
				{
					$class = "current_page_parent";

					$is_ancestor = true;
				}

				$out .= "<li".($class != '' ? " class='".$class."'" : "").">
					<a href='".$post_url."'>
						<i class='fa fa-caret-right'></i>"
						.$post_title
					."</a>"
					.$list_output
				."</li>";
			}

		$out .= "</ul>";
	}

	return array($out, $is_ancestor);
}