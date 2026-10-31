var style = {
	base: {
		border: '.1em solid #4a90e2',
		color: '#32325d',
		fontFamily: 'inherit',
		fontSize: '1em',
		'::placeholder': {
			color: '#aab7c4'
		}
	},
	invalid: {
		color: '#fa755a',
		iconColor: '#fa755a'
	}
};

var order_cart_hash = '',
	stripe_obj = Stripe(script_webshop_stripe.public_key),
	card_obj = stripe_obj.elements().create('card', { style: style }),
	form_obj = document.getElementById('form_stripe');

fetch(script_webshop_stripe.ajax_url, {
	method: 'POST',
	headers: {
		'Content-Type': 'application/x-www-form-urlencoded',
	},
	body: new URLSearchParams({
		action: 'api_webshop_get_order_cart_hash'
	})
})
.then(response => response.json())
.then(data => {
	if(data.success)
	{
		order_cart_hash = data.order_cart_hash;
		document.querySelector('#form_stripe .wp-block-button__link').disabled = false;
	}
});

card_obj.mount('.card_details');

form_obj.addEventListener('submit', function(event)
{
	document.querySelector('#form_stripe .wp-block-button__link .total_sum_stripe').innerHTML = script_webshop_stripe.loading_animation;

	event.preventDefault();

	stripe_obj.createPaymentMethod('card', card_obj).then(function(result)
	{
		if(result.error)
		{
			document.querySelector('.notification').classList.remove('hide');
			document.querySelector('.notification p').textContent = result.error.message;
		}

		else
		{
			sendPaymentRequest({
				order_id: order_cart_hash,
				payment_method_id: result.paymentMethod.id,
				test_mode: script_webshop_stripe.test_mode
			});
		}
	});
});

function sendPaymentRequest(payload)
{
	fetch('/wp-json/' + script_webshop_stripe.CLASS + '/process_stripe_payment',
	{
		method: 'POST',
		headers:
		{
			'Content-Type': 'application/json'
		},
		body: JSON.stringify(payload)
	})
	.then(response => response.json())
	.then(handlePaymentResponse)
	.catch(function(error)
	{
		document.querySelector('.notification').classList.remove('hide');
		document.querySelector('.notification p').textContent = script_webshop_stripe.error_text;
	});
}

function handlePaymentResponse(data)
{
	if(data.success)
	{
		location.href = data.return_url;
	}

	else if(data.requires_action)
	{
		stripe_obj.confirmCardPayment(data.payment_intent_client_secret).then(function(result)
		{
			if(result.error)
			{
				document.querySelector('.notification').classList.remove('hide');
				document.querySelector('.notification p').textContent = result.error.message;
			}

			else
			{
				// result.paymentIntent.status is now 'succeeded' (or possibly 'requires_action' again in rare multi-step 3DS cases)
				sendPaymentRequest({
					order_id: order_cart_hash,
					payment_intent_id: result.paymentIntent.id,
					test_mode: script_webshop_stripe.test_mode
				});
			}
		});
	}

	else
	{
		document.querySelector('.notification').classList.remove('hide');
		document.querySelector('.notification p').textContent = data.error;
	}
}