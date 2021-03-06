<?php
/*
Template Name: Webshop Search
*/

get_header();

	$obj_webshop = new mf_webshop();

	echo "<form action='".get_form_url(get_option('setting_quote_form'))."' method='post' id='product_form' class='mf_form product_search'>
		<div class='aside'><div>".$obj_webshop->get_webshop_map()."</div></div>
		<article".(IS_ADMIN ? " class='template_webshop_search'" : "").">"
			."<section>"
				.$obj_webshop->get_search_result_info(array('type' => 'filter'))
				.$obj_webshop->get_webshop_search()
				.$obj_webshop->get_search_result_info(array('type' => 'matches'))
				."<ul id='product_result_search' class='product_list webshop_item_list'><li class='loading'><i class='fa fa-spinner fa-spin fa-3x'></i></li></ul>"
				.$obj_webshop->get_quote_button()
				.$obj_webshop->get_form_fields_passthru()
			."</section>
		</article>
	</form>"
	.$obj_webshop->get_templates(array('type' => 'products'));

get_footer();