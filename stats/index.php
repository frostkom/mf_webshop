<?php

$obj_webshop = new mf_webshop();

$result = $wpdb->get_results("SELECT MIN(answerCreated) AS answerMin, MAX(answerCreated) AS answerMax FROM ".$wpdb->prefix."webshop_sent INNER JOIN ".$wpdb->base_prefix."form2answer USING (answerID)");

foreach($result as $r)
{
	$dteAnswerMin = $r->answerMin;
	$dteAnswerMax = $r->answerMax;
}

$intAnswerMin_year = date("Y", strtotime($dteAnswerMin));
$intAnswerMax_year = date("Y", strtotime($dteAnswerMax));

$intStatsLimit = check_var('intStatsLimit', 'int', true, '10');
$dteStatsDateStart = check_var('dteStatsDateStart', 'date', true, date("Y-m-d", strtotime($dteAnswerMin)));
$dteStatsDateEnd = check_var('dteStatsDateEnd', 'date', true, date("Y-m-d", strtotime($dteAnswerMax)));

$arr_data_limit = array(
	'' => "-- ".__("Choose here", 'lang_webshop')." --"
);

for($i = 5; $i <= 100; $i += 5)
{
	$arr_data_limit[$i] = $i;
}

$arr_data_limit[0] = "-- ".__("All", 'lang_webshop')." --";

$name_products = get_option_or_default('setting_webshop_replace_products', __("Products", 'lang_webshop'));

echo "<div class='wrap'>
	<h2>".__("Statistics", 'lang_webshop')."</h2>
	<div id='poststuff'>
		<div id='post-body' class='columns-2'>
			<div id='post-body-content'>
				<div class='postbox'>
					<h3 class='hndle'><span>".$name_products."</span></h3>
					<div class='inside'>
						<ul>";

							$result = $wpdb->get_results($wpdb->prepare("SELECT productID, COUNT(answerID) AS productAmount FROM ".$wpdb->prefix."webshop_sent INNER JOIN ".$wpdb->base_prefix."form2answer USING (answerID) WHERE answerCreated BETWEEN %s AND %s GROUP BY productID ORDER BY productAmount DESC".($intStatsLimit > 0 ? " LIMIT 0, ".$intStatsLimit : ""), $dteStatsDateStart, $dteStatsDateEnd));

							foreach($result as $r)
							{
								$intProductID = $r->productID;
								$intProductAmount = $r->productAmount;

								$strProductName = get_the_title($intProductID);

								if($strProductName == '')
								{
									$strProductName = "(".__("unknown", 'lang_webshop').")";
								}

								echo "<li>".$intProductAmount.". ".$strProductName."</li>";
							}

						echo "</ul>
					</div>
				</div>

				<div class='postbox'>
					<h3 class='hndle'><span>".__("Monthly", 'lang_webshop')."</span></h3>
					<div class='inside'>
						<ul>";

							$arr_flot_info = $arr_flot_data = array();;

							for($i = $intAnswerMin_year; $i <= $intAnswerMax_year; $i++)
							{
								$intAnswerYear = 0;

								for($j = 1; $j <= 12; $j++)
								{
									$dteAnswerMonth = $i."-".zeroise($j, 2);

									$intAnswerAmount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(answerID) FROM ".$wpdb->prefix."webshop_sent INNER JOIN ".$wpdb->base_prefix."form2answer USING (answerID) WHERE SUBSTRING(answerCreated, 1, 7) = %s AND answerCreated BETWEEN %s AND %s", $dteAnswerMonth, $dteStatsDateStart, $dteStatsDateEnd));

									if($intAnswerAmount > 0)
									{
										$intAnswerYear += $intAnswerAmount;

										$arr_flot_data['month'][] = array(
											'date' => $dteAnswerMonth."-01",
											'value' => $intAnswerAmount,
										);
									}
								}

								if($intAnswerYear > 0)
								{
									$arr_flot_data['year'][] = array(
										'date' => $i."-01-01",
										'value' => $intAnswerYear,
									);
								}
							}

							if($flot_data_month != "")
							{
								$arr_flot_info['months'] = array(
									'label' => __("Months", 'lang_webshop'),
									'data' => $arr_flot_data['month'],
								);

								$arr_flot_info['years'] = array(
									'label' => __("Years", 'lang_webshop'),
									'data' => $arr_flot_data['year'],
								);

								$out .= show_flot_graph(array('data' => $arr_flot_info, 'type' => 'lines', 'height' => 300)); //, 'width' => 600
							}

						echo "</ul>
					</div>
				</div>
			</div>
			<div id='postbox-container-1'>
				<div class='postbox'>
					<h3 class='hndle'><span>".__("Filter", 'lang_webshop')."</span></h3>
					<form method='post' class='inside mf_form'>"
						.show_select(array('data' => $arr_data_limit, 'name' => 'intStatsLimit', 'text' => __("Limit", 'lang_webshop'), 'value' => $intStatsLimit, 'xtra' => "rel='submit_change' disabled"))
						."<div class='flex_flow'>"
							.show_textfield(array('type' => 'date', 'name' => 'dteStatsDateStart', 'text' => __("From", 'lang_webshop'), 'value' => $dteStatsDateStart, 'xtra' => "rel='submit_change' disabled"))
							.show_textfield(array('type' => 'date', 'name' => 'dteStatsDateEnd', 'text' => __("To", 'lang_webshop'), 'value' => $dteStatsDateEnd, 'xtra' => "rel='submit_change' min='".$dteStatsDateStart."' disabled"))
						."</div>
					</form>
				</div>

				<div class='postbox'>
					<h3 class='hndle'><span>".__("Location", 'lang_webshop')."</span></h3>
					<div class='inside'>
						<ul>";

							$location_post_name = $obj_webshop->get_post_name_for_type('location');

							$result = $wpdb->get_results($wpdb->prepare("SELECT locations.post_title, COUNT(products_meta.meta_value) AS locationAmount FROM ".$wpdb->base_prefix."form2answer INNER JOIN ".$wpdb->prefix."webshop_sent USING (answerID) INNER JOIN ".$wpdb->posts." AS products ON ".$wpdb->prefix."webshop_sent.productID = products.ID AND products.post_type = 'mf_products' INNER JOIN ".$wpdb->postmeta." AS products_meta ON products.ID = products_meta.post_id AND products_meta.meta_key = '".$obj_webshop->meta_prefix.$location_post_name."' INNER JOIN ".$wpdb->posts." AS locations ON products_meta.meta_value = locations.ID WHERE answerCreated BETWEEN %s AND %s GROUP BY products_meta.meta_value ORDER BY locationAmount DESC".($intStatsLimit > 0 ? " LIMIT 0, ".$intStatsLimit : ""), $dteStatsDateStart, $dteStatsDateEnd));

							foreach($result as $r)
							{
								$post_title = $r->post_title;
								$intLocationAmount = $r->locationAmount;

								echo "<li>".$intLocationAmount.". ".$post_title."</li>";
							}

						echo "</ul>
					</div>
				</div>";

				if(isset($dteAnswerMin))
				{
					echo "<div class='postbox'>
						<h3 class='hndle'><span>".__("Overall", 'lang_webshop')."</span></h3>
						<div class='inside'>
							<p>".__("First answer", 'lang_webshop').": ".format_date($dteAnswerMin)."</p>
							<p>".__("Latest answer", 'lang_webshop').": ".format_date($dteAnswerMax)."</p>
						</div>
					</div>";
				}

				echo "
			</div>
		</div>
	</div>
</div>";