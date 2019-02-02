<?php
/*
Template Name: Webshop
*/

get_header();

	if(have_posts())
	{
		echo "<article>";

			while(have_posts())
			{
				the_post();

				$post_title = $post->post_title;
				$post_content = apply_filters('the_content', $post->post_content);

				echo "<h1>".$post_title."</h1>";

				if(is_active_sidebar('widget_after_heading') && !post_password_required())
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