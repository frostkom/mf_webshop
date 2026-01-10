(function()
{
	var el = wp.element.createElement,
		registerBlockType = wp.blocks.registerBlockType,
		SelectControl = wp.components.SelectControl,
		TextControl = wp.components.TextControl,
		InspectorControls = wp.blockEditor.InspectorControls;

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
})();