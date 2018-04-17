<?php
/**
 * @package WordPress
 * @subpackage MF Webshop

Template Name: Webshop Favorites
*/

get_header();

	if(have_posts())
	{
		$post_amount = $wp_query->found_posts;

		while(have_posts())
		{
			the_post();

			$post_id = $post->ID;
			$post_title = $post->post_title;
			$post_content = $post->post_content;

			$obj_webshop = new mf_webshop();

			echo "<form action='".get_form_url(get_option('setting_quote_form'))."' method='post' id='product_form' class='mf_form product_search product_favorites'>
				<div class='aside'><div>".get_webshop_map()."</div></div>
				<article>
					<h1>".$post_title."</h1>
					<section>
						<div class='favorite_result'>"
							.get_search_result_info(array('type' => 'favorites'))
							.get_quote_button(array('include' => array('quote', 'print', 'email')))
							."<ul id='product_result_search' class='product_list webshop_item_list'><li class='loading'><i class='fa fa-spinner fa-spin fa-3x'></i></li></ul>
						</div>
						<div class='favorite_fallback hide'>".apply_filters('the_content', $post_content)."</div>"
					."</section>
				</article>
			</form>"
			.$obj_webshop->get_templates();
		}
	}

get_footer();