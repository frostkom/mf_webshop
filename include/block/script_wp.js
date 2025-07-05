(function()
{
	var el = wp.element.createElement,
		registerBlockType = wp.blocks.registerBlockType,
		SelectControl = wp.components.SelectControl,
		TextControl = wp.components.TextControl,
		InspectorControls = wp.blockEditor.InspectorControls;

	/*registerBlockType('mf/webshoplist',
	{
		title: script_webshop_block_wp.block_title,
		description: script_webshop_block_wp.block_description,
		icon: 'forms',
		category: 'widgets',
		'attributes':
		{
			'align':
			{
				'type': 'string',
				'default': ''
			},
			'webshop_location':
			{
                'type': 'string',
                'default': ''
            }
		},
		'supports':
		{
			'html': false,
			'multiple': false,
			'align': true,
			'spacing':
			{
				'margin': true,
				'padding': true
			},
			'color':
			{
				'background': true,
				'gradients': false,
				'text': true
			},
			'defaultStylePicker': true,
			'typography':
			{
				'fontSize': true,
				'lineHeight': true
			},
			"__experimentalBorder":
			{
				"radius": true
			}
		},
		edit: function(props)
		{
			return el(
				'div',
				{className: 'wp_mf_block_container'},
				[
					/*el(
						InspectorControls,
						'div',
						el(
							SelectControl,
							{
								label: script_webshop_block_wp.webshop_location_label,
								value: props.attributes.webshop_location,
								options: convert_php_array_to_block_js(script_webshop_block_wp.webshop_location),
								onChange: function(value)
								{
									props.setAttributes({webshop_location: value});
								}
							}
						)
					),*/

					/*.show_select(array('data' => $arr_data, 'name' => $this->get_field_name('webshop_action'), 'text' => __("Go to on click", 'lang_webshop'), 'value' => $instance['webshop_action']))
					.show_select(array('data' => $arr_data_locations, 'name' => $this->get_field_name('webshop_locations')."[]", 'text' => __("Locations", 'lang_webshop'), 'value' => $instance['webshop_locations']))*/

					/*el(
						'strong',
						{className: props.className},
						script_webshop_block_wp.block_title
					)
				]
			);
		},
		save: function()
		{
			return null;
		}
	});*/

	registerBlockType('mf/webshopsearch',
	{
		title: script_webshop_block_wp.block_title2,
		description: script_webshop_block_wp.block_description2,
		icon: 'cart',
		category: 'widgets',
		'attributes':
		{
			'align':
			{
				'type': 'string',
				'default': ''
			},
			'webshop_option_type':
			{
                'type': 'string',
                'default': ''
            }
		},
		'supports':
		{
			'html': false,
			'multiple': false,
			'align': true,
			'spacing':
			{
				'margin': true,
				'padding': true
			},
			'color':
			{
				'background': true,
				'gradients': false,
				'text': true
			},
			'defaultStylePicker': true,
			'typography':
			{
				'fontSize': true,
				'lineHeight': true
			},
			"__experimentalBorder":
			{
				"radius": true
			}
		},
		edit: function(props)
		{
			return el(
				'div',
				{className: 'wp_mf_block_container'},
				[
					el(
						InspectorControls,
						'div',
						/*el(
							SelectControl,
							{
								label: script_webshop_block_wp.webshop_option_type_label,
								value: props.attributes.webshop_option_type,
								options: convert_php_array_to_block_js(script_webshop_block_wp.webshop_option_type),
								onChange: function(value)
								{
									props.setAttributes({webshop_option_type: value});
								}
							}
						)*/
					),
					el(
						'strong',
						{className: props.className},
						script_webshop_block_wp.block_title2
					)
				]
			);
		},
		save: function()
		{
			return null;
		}
	});

	/*registerBlockType('mf/webshopproducts',
	{
		title: script_webshop_block_wp.block_title3,
		description: script_webshop_block_wp.block_description3,
		icon: 'forms',
		category: 'widgets',
		'attributes':
		{
			'align':
			{
				'type': 'string',
				'default': ''
			},
			'webshop_option_type':
			{
                'type': 'string',
                'default': ''
            }
		},
		'supports':
		{
			'html': false,
			'multiple': false,
			'align': true,
			'spacing':
			{
				'margin': true,
				'padding': true
			},
			'color':
			{
				'background': true,
				'gradients': false,
				'text': true
			},
			'defaultStylePicker': true,
			'typography':
			{
				'fontSize': true,
				'lineHeight': true
			},
			"__experimentalBorder":
			{
				"radius": true
			}
		},
		edit: function(props)
		{
			return el(
				'div',
				{className: 'wp_mf_block_container'},
				[
					/*el(
						InspectorControls,
						'div',
						el(
							SelectControl,
							{
								label: script_webshop_block_wp.webshop_option_type_label,
								value: props.attributes.webshop_option_type,
								options: convert_php_array_to_block_js(script_webshop_block_wp.webshop_option_type),
								onChange: function(value)
								{
									props.setAttributes({webshop_option_type: value});
								}
							}
						)
					),*/

					/*$name_category = get_option_or_default('setting_webshop_replace_category', __("Category", 'lang_webshop'));

					$this->option_type = ($instance['webshop_option_type'] != '' ? "_".$instance['webshop_option_type'] : '');

					.show_textfield(array('name' => $this->get_field_name('webshop_heading'), 'text' => __("Heading", 'lang_webshop'), 'value' => $instance['webshop_heading'], 'xtra' => " id='".$this->widget_ops['classname']."-title'"))
					.show_select(array('data' => $this->get_filters_for_select(), 'name' => $this->get_field_name('webshop_filters')."[]", 'text' => __("Display Filters", 'lang_webshop'), 'value' => $instance['webshop_filters']));

					if(in_array('order_by', $instance['webshop_filters']))
					{
						echo show_select(array('data' => $this->get_order_by_for_select(), 'name' => $this->get_field_name('webshop_filters_order_by'), 'text' => __("Order by", 'lang_webshop')." (".__("Default", 'lang_webshop').")", 'value' => $instance['webshop_filters_order_by']))
						.show_textfield(array('name' => $this->get_field_name('webshop_filters_order_by_text'), 'text' => __("Order by Text", 'lang_webshop'), 'value' => $instance['webshop_filters_order_by_text'], 'placeholder' => __("Order by", 'lang_webshop')));
					}

					echo show_textarea(array('name' => $this->get_field_name('webshop_text'), 'text' => __("Text", 'lang_webshop'), 'value' => $instance['webshop_text'], 'placeholder' => sprintf(__("There are %s events", 'lang_webshop'), "[amount]")))
					.show_select(array('data' => $this->get_option_types_for_select(), 'name' => $this->get_field_name('webshop_option_type'), 'text' => __("Type", 'lang_webshop'), 'value' => $instance['webshop_option_type']))
					.show_textfield(array('type' => 'number', 'name' => $this->get_field_name('webshop_amount'), 'text' => __("Amount", 'lang_webshop'), 'value' => $instance['webshop_amount']))
					.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('webshop_link_product'), 'text' => __("Link Product", 'lang_webshop'), 'value' => $instance['webshop_link_product']))
					.show_select(array('data' => $this->get_categories_for_select(array('include_on' => 'products')), 'name' => $this->get_field_name('webshop_category')."[]", 'text' => $name_category, 'value' => $instance['webshop_category'], 'required' => true))
					.show_textfield(array('name' => $this->get_field_name('webshop_button_text'), 'text' => __("Button Text", 'lang_webshop'), 'value' => $instance['webshop_button_text']))*/

					/*el(
						'strong',
						{className: props.className},
						script_webshop_block_wp.block_title3
					)
				]
			);
		},
		save: function()
		{
			return null;
		}
	});*/
})();