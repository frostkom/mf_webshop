<?php

get_header();

	if(have_posts())
	{
		while(have_posts())
		{
			the_post();

			$obj_webshop = new mf_webshop();

			$cat_id = $post->ID;
			$cat_title = $post->post_title;
			$cat_content = $post->post_content;

			include_once("aside.php");

			echo "<article>";

				$result = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND post_status = 'publish' AND post_parent = '0' AND meta_key = '".$obj_webshop->meta_prefix."category' AND meta_value = '%d' ORDER BY menu_order ASC", $obj_webshop->post_type_products, $cat_id));

				if($wpdb->num_rows == 0)
				{
					echo "<h1>".$cat_title."</h1>
					<div>".$cat_content."</div>";
				}

				else
				{
					foreach($result as $r)
					{
						$post_id = $r->ID;
						$post_title = $r->post_title;
						$post_excerpt = $r->post_excerpt;

						$post_url = get_permalink($r);

						$post_product_image_id = get_post_meta($post_id, $obj_webshop->meta_prefix.'product_image_image', true);

						echo "<h1><a href='".$post_url."'>".$post_title."</a></h1>
						<section>";

							if($post_product_image_id > 0)
							{
								echo render_image_tag(array('id' => $post_product_image_id));
							}

							echo "<p>".$post_excerpt."</p>
							<p><a href='".$post_url."'>".__("Read More", 'lang_webshop')."&hellip;</a></p>
						</section>";
					}
				}

			echo "</article>";
		}
	}

get_footer();