<?php
/*
Template Name: Webshop
*/

get_header();

	if(have_posts())
	{
		if(!isset($obj_theme_core))
		{
			$obj_theme_core = new mf_theme_core();
		}

		echo "<article".(IS_ADMINISTRATOR ? " class='template_webshop'" : "").">";

			while(have_posts())
			{
				the_post();

				$post_title = $post->post_title;
				$post_content = apply_filters('the_content', $post->post_content);

				echo "<h1>".$post_title."</h1>";

				if(is_active_sidebar('widget_after_heading') && $obj_theme_core->is_post_password_protected($post->ID) == false)
				{
					ob_start();

					dynamic_sidebar('widget_after_heading');

					$widget_content = ob_get_clean();

					if($widget_content != '')
					{
						echo "<div class='aside after_heading'>"
							.$widget_content
						."</div>";
					}
				}

				echo "<section>".$post_content."</section>";
			}

		echo "</article>";
	}

get_footer();