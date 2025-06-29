<?php

get_header();

	if(have_posts())
	{
		if(!isset($obj_base))
		{
			$obj_base = new mf_base();
		}

		if(!isset($obj_webshop))
		{
			$obj_webshop = new mf_webshop();
		}

		echo "<article".(IS_ADMINISTRATOR ? " class='single-mf_product'" : "").">";

			while(have_posts())
			{
				the_post();

				if(is_active_sidebar('widget_after_heading') && $obj_base->is_post_password_protected($post->ID) == false)
				{
					ob_start();

					dynamic_sidebar('widget_after_heading');

					$widget_content = ob_get_clean();

					if($widget_content != '')
					{
						$obj_webshop->template_shortcodes['after_heading']['html'] .= "<div class='aside after_heading'>"
							.$widget_content
						."</div>";
					}
				}

				echo $obj_webshop->get_single_info($post);

				$setting_webshop_product_template = get_option('setting_webshop_product_template'.$obj_webshop->option_type);

				if($setting_webshop_product_template > 0)
				{
					$template = mf_get_post_content($setting_webshop_product_template);
				}

				else
				{
					$template = $obj_webshop->default_template;
				}

				//This adds a bunch of empty p tags
				//$template = apply_filters('the_content', $template);
				$template = str_replace("[product_id]", $post->ID, $template);
				$template = do_shortcode($template);

				foreach($obj_webshop->template_shortcodes as $key => $value)
				{
					$html = ($value['html'] != '' ? str_replace("[html]", $value['html'], $value['formatting']) : '');

					$template = str_replace("[".$key."]", $html, $template);
				}

				echo $template;
			}

		echo "</article>";
	}

get_footer();