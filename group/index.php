<?php

$intGroupID = 0;

$obj_group = new mf_group();
$obj_webshop = new mf_webshop();

$name_webshop = get_option_or_default('setting_webshop_replace_webshop', __("Webshop", 'lang_webshop'));

$result = $obj_group->get_groups(array('where' => " AND post_title LIKE '%".$name_webshop."%'", 'order' => "post_title ASC", 'limit' => 0, 'amount' => 1));

if($wpdb->num_rows > 0)
{
	foreach($result as $r)
	{
		$intGroupID = $r->ID;
	}

	$type = "updated";
}

else
{
	$post_data = array(
		'post_type' => $obj_group->post_type,
		'post_status' => 'draft',
		'post_title' => $name_webshop,
	);

	$intGroupID = wp_insert_post($post_data);

	$type = "created";
}

if($intGroupID > 0)
{
	$email_post_name = $obj_webshop->get_post_name_for_type('email');

	if($email_post_name != '')
	{
		$obj_group->remove_all_address($intGroupID);

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, meta_value FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = %s WHERE post_type = %s AND post_status = 'publish' AND meta_value != '' GROUP BY meta_value", $obj_webshop->meta_prefix.$email_post_name, $obj_webshop->post_type_products));

		foreach($result as $r)
		{
			$product_id = $r->ID;
			$product_title = $r->post_title;
			$product_email = $r->meta_value;

			$intAddressID = $obj_group->check_if_address_exists("addressEmail = '".esc_sql($product_email)."'");

			if($intAddressID > 0)
			{
				$wpdb->query($wpdb->prepare("UPDATE ".get_address_table_prefix()."address SET addressFirstName = %s WHERE addressID = '%d'", $product_title, $intAddressID));
			}

			else
			{
				$wpdb->query($wpdb->prepare("INSERT INTO ".get_address_table_prefix()."address SET addressPublic = '0', addressFirstName = %s, addressEmail = %s, addressCreated = NOW(), userID = '%d'", $product_title, $product_email, get_current_user_id()));

				$intAddressID = $wpdb->insert_id;
			}

			if($intAddressID > 0)
			{
				$obj_group->add_address(array('address_id' => $intAddressID, 'group_id' => $intGroupID));
			}

			else
			{
				$error_text = __("The address was not able to be created", 'lang_webshop');
			}
		}

		if(!isset($error_text))
		{
			mf_redirect(admin_url("admin.php?page=mf_group/list/index.php&s=".$name_webshop."&".$type));
		}
	}

	else
	{
		$error_text = __("You have not set a field for e-mails", 'lang_webshop');
	}
}

else
{
	$error_text = __("Could not find an existing group or create a new one", 'lang_webshop');
}

echo "<div class='wrap'>
	<h2>".__("Loading", 'lang_webshop')."...</h2>"
	.get_notification()
."</div>";