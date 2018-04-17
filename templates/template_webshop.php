<?php
/**
 * @package WordPress
 * @subpackage MF Webshop
 */

 /*
Template Name: Webshop
*/

get_header();

	if(have_posts())
	{
		include_once("aside.php");

		echo "<article>";

			if(is_active_sidebar('top_widget'))
			{
				echo "<div id='top_widget'>";

					dynamic_sidebar('top_widget');

				echo "</div>";
			}

			while(have_posts())
			{
				the_post();

				$post_title = $post->post_title;
				$post_content = apply_filters('the_content', $post->post_content);

				echo "<h1>".$post_title."</h1>
				<section>".$post_content."</section>";
			}

		echo "</article>";
	}

get_footer();