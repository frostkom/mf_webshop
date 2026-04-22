(function()
{
	var el = wp.element.createElement,
		registerBlockType = wp.blocks.registerBlockType,
		SelectControl = wp.components.SelectControl,
		InspectorControls = wp.blockEditor.InspectorControls;

	registerBlockType('mf/webshoptimeline',
	{
		title: script_webshop_block_wp.block_title_timeline,
		description: script_webshop_block_wp.block_description_timeline,
		icon: 'cart',
		category: 'widgets',
		'attributes':
		{
			'align':
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
						'strong',
						{className: props.className},
						script_webshop_block_wp.block_title_timeline
					)
				]
			);
		},
		save: function()
		{
			return null;
		}
	});

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
			'webshop_search':
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
						el(
							SelectControl,
							{
								label: script_webshop_block_wp.webshop_search_label,
								value: props.attributes.webshop_search,
								options: convert_php_array_to_block_js(script_webshop_block_wp.yes_no_for_select),
								onChange: function(value)
								{
									props.setAttributes({webshop_search: value});
								}
							}
						)
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

	registerBlockType('mf/webshopcart',
	{
		title: script_webshop_block_wp.block_title4,
		description: script_webshop_block_wp.block_description4,
		icon: 'cart',
		category: 'widgets',
		'attributes':
		{
			'align':
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
						'strong',
						{className: props.className},
						script_webshop_block_wp.block_title4
					)
				]
			);
		},
		save: function()
		{
			return null;
		}
	});

	registerBlockType('mf/webshopmoreimages',
	{
		title: script_webshop_block_wp.block_title6,
		description: script_webshop_block_wp.block_description6,
		icon: 'format-gallery',
		category: 'widgets',
		'attributes':
		{
			'align':
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
						'strong',
						{className: props.className},
						script_webshop_block_wp.block_title6
					)
				]
			);
		},
		save: function()
		{
			return null;
		}
	});

	registerBlockType('mf/webshopbuybutton',
	{
		title: script_webshop_block_wp.block_title5,
		description: script_webshop_block_wp.block_description5,
		icon: 'cart',
		category: 'widgets',
		'attributes':
		{
			'align':
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
						'strong',
						{className: props.className},
						script_webshop_block_wp.block_title5
					)
				]
			);
		},
		save: function()
		{
			return null;
		}
	});

	registerBlockType('mf/webshopconfirmation',
	{
		title: script_webshop_block_wp.block_title_confirmation,
		description: script_webshop_block_wp.block_description_confirmation,
		icon: 'cart',
		category: 'widgets',
		'attributes':
		{
			'align':
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
						'strong',
						{className: props.className},
						script_webshop_block_wp.block_title_confirmation
					)
				]
			);
		},
		save: function()
		{
			return null;
		}
	});
})();