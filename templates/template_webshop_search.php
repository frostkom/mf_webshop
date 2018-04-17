<?php
/**
 * @package WordPress
 * @subpackage MF Webshop

Template Name: Webshop Search
*/

get_header();

	$obj_webshop = new mf_webshop();

	echo "<form action='".get_form_url(get_option('setting_quote_form'))."' method='post' id='product_form' class='mf_form product_search'>
		<div class='aside'><div>".get_webshop_map()."</div></div>
		<article>"
			."<section>"
				.get_search_result_info(array('type' => 'filter'))
				.$obj_webshop->get_webshop_search()
				.get_search_result_info(array('type' => 'matches'))
				."<ul id='product_result_search' class='product_list webshop_item_list'><li class='loading'><i class='fa fa-spinner fa-spin fa-3x'></i></li></ul>"
				.get_quote_button()
				.$obj_webshop->get_form_fields_passthru()
			."</section>
		</article>
	</form>"
	.$obj_webshop->get_templates();

get_footer();