<?php
class ControllerCheckoutExpressConfirm extends Controller {

	public function index() {
		$redirect = '';

		if ($this->cart->hasShipping()) {
			// Validate if shipping address has been set
			$this->load->model('account/address');

			if ($this->customer->isLogged() && isset($this->session->data['shipping_address_id'])) {
				$shipping_address = $this->model_account_address->getAddress($this->session->data['shipping_address_id']);
			}

			if (empty($shipping_address)) {
				$redirect = $this->url->link('checkout_express/checkout', '', 'SSL');
			}

			// Validate if shipping method has been set
			if (!isset($this->session->data['shipping_method'])) {
				$redirect = $this->url->link('checkout_express/checkout', '', 'SSL');
			}

		} else {
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
		}

		// Validate if payment address has been set
		$this->load->model('account/address');

		if ($this->customer->isLogged() && isset($this->session->data['payment_address_id'])) {
			$payment_address = $this->model_account_address->getAddress($this->session->data['payment_address_id']);
		}

		if (empty($payment_address) && !$this->config->get('config_express_billing')) {
			if (isset($this->session->data['shipping_country_id'])) {
				$payment_address['country_id'] = $this->session->data['shipping_country_id'];
			} else {
				$payment_address['country_id'] = $this->config->get('config_country_id');
			}

			if (isset($this->session->data['shipping_zone_id'])) {
				$payment_address['zone_id'] = $this->session->data['shipping_zone_id'];
			} else {
				$payment_address['zone_id'] = '';
			}
        }

		if (empty($payment_address)) {
			$redirect = $this->url->link('checkout_express/checkout', '', 'SSL');
		}

		// Validate if payment method has been set
		if (!isset($this->session->data['payment_method'])) {
			$redirect = $this->url->link('checkout_express/checkout', '', 'SSL');
		}

		// Validate cart has products and has stock
		if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
			$redirect = $this->url->link('checkout/cart', '', 'SSL');
		}

		// Validate minimum quantity requirements
		$products = $this->cart->getProducts();

		foreach ($products as $product) {
			$product_total = 0;

			foreach ($products as $product_2) {
				if ($product_2['product_id'] == $product['product_id']) {
					$product_total += $product_2['quantity'];
				}
			}

			if ($product['minimum'] > $product_total) {
				$redirect = $this->url->link('checkout/cart', '', 'SSL');
				break;
			}

			// Validate minimum age
			if ($this->config->get('config_customer_dob') && ($product['age_minimum'] > 0)) {
				if (!$this->customer->isLogged() || !$this->customer->isSecure()) {
					$redirect = $this->url->link('checkout/login', '', 'SSL');
					break;
				}
			}
		}

		if (!$redirect) {
			// Totals
			$total_data = array();
			$total = 0;
			$taxes = $this->cart->getTaxes();

			$this->load->model('setting/extension');

			$sort_order = array();

			$results = $this->model_setting_extension->getExtensions('total');

			foreach ($results as $key => $value) {
				$sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
			}

			array_multisort($sort_order, SORT_ASC, $results);

			foreach ($results as $result) {
				if ($this->config->get($result['code'] . '_status')) {
					$this->load->model('total/' . $result['code']);

					$this->{'model_total_' . $result['code']}->getTotal($total_data, $total, $taxes);
				}
			}

			$sort_order = array();

			foreach ($total_data as $key => $value) {
				$sort_order[$key] = $value['sort_order'];
			}

			array_multisort($sort_order, SORT_ASC, $total_data);

			$data = array();

			$data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
			$data['store_id'] = $this->config->get('config_store_id');
			$data['store_name'] = $this->config->get('config_name');

			if ($data['store_id']) {
				$data['store_url'] = $this->config->get('config_url');
			} else {
				$data['store_url'] = HTTP_SERVER;
			}

			if ($this->customer->isLogged()) {
				$data['customer_id'] = $this->customer->getId();
				$data['customer_group_id'] = $this->customer->getCustomerGroupId();
				$data['firstname'] = $this->customer->getFirstName();
				$data['lastname'] = $this->customer->getLastName();
				$data['email'] = $this->customer->getEmail();
				$data['telephone'] = $this->customer->getTelephone();
				$data['fax'] = $this->customer->getFax();

				if ($this->config->get('config_express_billing')) {
					$this->load->model('account/address');

					$payment_address = $this->model_account_address->getAddress($this->session->data['payment_address_id']);

					$data['payment_firstname'] = $payment_address['firstname'];
					$data['payment_lastname'] = $payment_address['lastname'];
					$data['payment_company'] = $payment_address['company'];
					$data['payment_company_id'] = $payment_address['company_id'];
					$data['payment_tax_id'] = $payment_address['tax_id'];
					$data['payment_address_1'] = $payment_address['address_1'];
					$data['payment_address_2'] = $payment_address['address_2'];
					$data['payment_city'] = $payment_address['city'];
					$data['payment_postcode'] = $payment_address['postcode'];
					$data['payment_zone'] = $payment_address['zone'];
					$data['payment_zone_id'] = $payment_address['zone_id'];
					$data['payment_country'] = $payment_address['country'];
					$data['payment_country_id'] = $payment_address['country_id'];
					$data['payment_address_format'] = $payment_address['address_format'];
				} else {
					$data['payment_firstname'] = '';
					$data['payment_lastname'] = '';
					$data['payment_company'] = '';
					$data['payment_company_id'] = '';
					$data['payment_tax_id'] = '';
					$data['payment_address_1'] = '';
					$data['payment_address_2'] = '';
					$data['payment_city'] = '';
					$data['payment_postcode'] = '';
					$data['payment_zone'] = '';
					$data['payment_zone_id'] = '';
					$data['payment_country'] = '';
					$data['payment_country_id'] = '';
					$data['payment_address_format'] = '';
				}
			}

			if (isset($this->session->data['payment_method']['title'])) {
				$data['payment_method'] = $this->session->data['payment_method']['title'];
			} else {
				$data['payment_method'] = '';
			}

			if (isset($this->session->data['payment_method']['code'])) {
				$data['payment_code'] = $this->session->data['payment_method']['code'];
			} else {
				$data['payment_code'] = '';
			}

			if ($this->cart->hasShipping()) {
				if ($this->customer->isLogged()) {
					$this->load->model('account/address');

					$shipping_address = $this->model_account_address->getAddress($this->session->data['shipping_address_id']);
				}

				$data['shipping_firstname'] = $shipping_address['firstname'];
				$data['shipping_lastname'] = $shipping_address['lastname'];
				$data['shipping_company'] = $shipping_address['company'];
				$data['shipping_address_1'] = $shipping_address['address_1'];
				$data['shipping_address_2'] = $shipping_address['address_2'];
				$data['shipping_city'] = $shipping_address['city'];
				$data['shipping_postcode'] = $shipping_address['postcode'];
				$data['shipping_zone'] = $shipping_address['zone'];
				$data['shipping_zone_id'] = $shipping_address['zone_id'];
				$data['shipping_country'] = $shipping_address['country'];
				$data['shipping_country_id'] = $shipping_address['country_id'];
				$data['shipping_address_format'] = $shipping_address['address_format'];

				if (isset($this->session->data['shipping_method']['title'])) {
					$data['shipping_method'] = $this->session->data['shipping_method']['title'];
				} else {
					$data['shipping_method'] = '';
				}

				if (isset($this->session->data['shipping_method']['code'])) {
					$data['shipping_code'] = $this->session->data['shipping_method']['code'];
				} else {
					$data['shipping_code'] = '';
				}

			} else {
				$data['shipping_firstname'] = '';
				$data['shipping_lastname'] = '';
				$data['shipping_company'] = '';
				$data['shipping_address_1'] = '';
				$data['shipping_address_2'] = '';
				$data['shipping_city'] = '';
				$data['shipping_postcode'] = '';
				$data['shipping_zone'] = '';
				$data['shipping_zone_id'] = '';
				$data['shipping_country'] = '';
				$data['shipping_country_id'] = '';
				$data['shipping_address_format'] = '';
				$data['shipping_method'] = '';
				$data['shipping_code'] = '';
			}

			$product_data = array();

			foreach ($this->cart->getProducts() as $product) {
				$option_data = array();

				foreach ($product['option'] as $option) {
					if ($option['type'] != 'file') {
						$value = $option['option_value'];
					} else {
						$value = $this->encryption->decrypt($option['option_value']);
					}

					$option_data[] = array(
						'product_option_id'       => $option['product_option_id'],
						'product_option_value_id' => $option['product_option_value_id'],
						'option_id'               => $option['option_id'],
						'option_value_id'         => $option['option_value_id'],
						'name'                    => $option['name'],
						'value'                   => $value,
						'type'                    => $option['type']
					);
				}

				$product_data[] = array(
					'product_id' => $product['product_id'],
					'name'       => $product['name'],
					'model'      => $product['model'],
					'option'     => $option_data,
					'download'   => $product['download'],
					'quantity'   => $product['quantity'],
					'subtract'   => $product['subtract'],
					'price'      => $product['price'],
					'cost'       => $product['cost'],
					'total'      => $product['total'],
					'tax'        => $this->tax->getTax($product['price'], $product['tax_class_id']),
					'reward'     => $product['reward']
				);
			}

			// Gift Voucher
			$voucher_data = array();

			if (!empty($this->session->data['vouchers'])) {
				foreach ($this->session->data['vouchers'] as $voucher) {
					$voucher_data[] = array(
						'description'      => $voucher['description'],
						'code'             => substr(hash_rand('md5'), 0, 10),
						'to_name'          => $voucher['to_name'],
						'to_email'         => $voucher['to_email'],
						'from_name'        => $voucher['from_name'],
						'from_email'       => $voucher['from_email'],
						'voucher_theme_id' => $voucher['voucher_theme_id'],
						'message'          => $voucher['message'],
						'amount'           => $voucher['amount']
					);
				}
			}

			$data['products'] = $product_data;
			$data['vouchers'] = $voucher_data;
			$data['totals'] = $total_data;
			$data['comment'] = $this->session->data['comment'];
			$data['total'] = $total;

			if (isset($this->request->cookie['tracking'])) {
				$this->load->model('affiliate/affiliate');

				$affiliate_info = $this->model_affiliate_affiliate->getAffiliateByCode($this->request->cookie['tracking']);

				$subtotal = $this->cart->getSubTotal();

				if ($affiliate_info) {
					$data['affiliate_id'] = $affiliate_info['affiliate_id'];
					$data['commission'] = ($subtotal / 100) * $affiliate_info['commission'];
				} else {
					$data['affiliate_id'] = 0;
					$data['commission'] = 0;
				}

			} else {
				$data['affiliate_id'] = 0;
				$data['commission'] = 0;
			}

			$data['language_id'] = $this->config->get('config_language_id');
			$data['currency_id'] = $this->currency->getId();
			$data['currency_code'] = $this->currency->getCode();
			$data['currency_value'] = $this->currency->getValue($this->currency->getCode());

			$data['ip'] = $this->request->server['REMOTE_ADDR'];

			if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
				$data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
			} elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
				$data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
			} else {
				$data['forwarded_ip'] = '';
			}

			if (isset($this->request->server['HTTP_USER_AGENT'])) {
				$data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
			} else {
				$data['user_agent'] = '';
			}

			if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
				$data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
			} else {
				$data['accept_language'] = '';
			}

			$this->language->load('checkout/checkout_express');

			$this->load->model('checkout/order');

			$this->session->data['order_id'] = $this->model_checkout_order->addOrder($data);

			// Language
			$this->data['text_checkout_confirm'] = $this->language->get('text_checkout_confirm');

			if ($this->customer->isLogged()) {
				$this->load->model('account/address');

				if (isset($this->session->data['shipping_address_id'])) {
					$ship_address = $this->model_account_address->getAddress($this->session->data['shipping_address_id']);
				} elseif (isset($this->session->data['payment_address_id'])) {
					$ship_address = $this->model_account_address->getAddress($this->session->data['payment_address_id']);
				} else {
					$customer_id = $this->customer->getId();

					$address_id = $this->model_account_address->getDefaultAddressId($customer_id);

					$ship_address = $this->model_account_address->getAddress($address_id);
				}

				$this->data['shipping_firstname'] = $ship_address['firstname'];
				$this->data['shipping_lastname'] = $ship_address['lastname'];
				$this->data['shipping_company'] = $ship_address['company'];
				$this->data['shipping_address_1'] = $ship_address['address_1'];
				$this->data['shipping_address_2'] = $ship_address['address_2'];
				$this->data['shipping_city'] = $ship_address['city'];
				$this->data['shipping_postcode'] = $ship_address['postcode'];
				$this->data['shipping_zone'] = $ship_address['zone'];
				$this->data['shipping_country'] = $ship_address['country'];

			} else {
				$this->data['shipping_firstname'] = '';
				$this->data['shipping_lastname'] = '';
				$this->data['shipping_company'] = '';
				$this->data['shipping_address_1'] = '';
				$this->data['shipping_address_2'] = '';
				$this->data['shipping_city'] = '';
				$this->data['shipping_postcode'] = '';
				$this->data['shipping_zone'] = '';
				$this->data['shipping_country'] = '';
			}

			if (isset($this->session->data['shipping_method']['title'])) {
				$this->data['shipping_method_selected'] = $this->session->data['shipping_method']['title'];
			} else {
				$this->data['shipping_method_selected'] = '';
			}

			if (isset($this->session->data['payment_method']['title'])) {
				$this->data['payment_method_selected'] = $this->session->data['payment_method']['title'];
			} else {
				$this->data['payment_method_selected'] = '';
			}

			if (isset($this->session->data['comment'])) {
				$this->data['order_comment'] = $this->session->data['comment'];
			} else {
				$this->data['order_comment'] = '';
			}

			// Get tax breakdown
			if ($this->config->get('config_tax_breakdown')) {
				$this->data['tax_breakdown'] = true;
				$this->data['tax_colspan'] = 6;
			} else {
				$this->data['tax_breakdown'] = false;
				$this->data['tax_colspan'] = 4;
			}

			$this->data['column_name'] = $this->language->get('column_name');
			$this->data['column_model'] = $this->language->get('column_model');
			$this->data['column_quantity'] = $this->language->get('column_quantity');
			$this->data['column_price'] = $this->language->get('column_price');
			$this->data['column_tax_value'] = $this->language->get('column_tax_value');
			$this->data['column_tax_percent'] = $this->language->get('column_tax_percent');
			$this->data['column_total'] = $this->language->get('column_total');

			$this->data['text_recurring_item'] = $this->language->get('text_recurring_item');
			$this->data['text_payment_profile'] = $this->language->get('text_payment_profile');

			$this->data['products'] = array();

			foreach ($this->cart->getProducts() as $product) {
				$option_data = array();

				foreach ($product['option'] as $option) {
					if ($option['type'] != 'file') {
						$value = $option['option_value'];
					} else {
						$filename = $this->encryption->decrypt($option['option_value']);

						$value = utf8_substr($filename, 0, utf8_strrpos($filename, '.'));
					}

					$option_data[] = array(
						'name'  => $option['name'],
						'value' => (utf8_strlen($value) > 20) ? utf8_substr($value, 0, 20) . '..' : $value
					);
				}

				// Profile
				$profile_description = '';

				if ($product['recurring']) {
					$frequencies = array(
						'day'        => $this->language->get('text_day'),
						'week'       => $this->language->get('text_week'),
						'semi_month' => $this->language->get('text_semi_month'),
						'month'      => $this->language->get('text_month'),
						'year'       => $this->language->get('text_year')
					);

					if ($product['recurring_trial']) {
						$recurring_price = $this->currency->format($this->tax->calculate($product['recurring_trial_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')));

						$profile_description = sprintf($this->language->get('text_trial_description'), $recurring_price, $product['recurring_trial_cycle'], $frequencies[$product['recurring_trial_frequency']], $product['recurring_trial_duration']) . ' ';
					}

					$recurring_price = $this->currency->format($this->tax->calculate($product['recurring_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')));

					if ($product['recurring_duration']) {
						$profile_description .= sprintf($this->language->get('text_payment_description'), $recurring_price, $product['recurring_cycle'], $frequencies[$product['recurring_frequency']], $product['recurring_duration']);
					} else {
						$profile_description .= sprintf($this->language->get('text_payment_until_canceled_description'), $recurring_price, $product['recurring_cycle'], $frequencies[$product['recurring_frequency']], $product['recurring_duration']);
					}
				}

				$product_tax_value = ($this->tax->calculate(($product['price'] * $product['quantity']), $product['tax_class_id'], $this->config->get('config_tax')) - ($product['price'] * $product['quantity']));

				$this->data['products'][] = array(
					'product_id'          => $product['product_id'],
					'name'                => $product['name'],
					'model'               => $product['model'],
					'option'              => $option_data,
					'quantity'            => $product['quantity'],
					'subtract'            => $product['subtract'],
					'price'               => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'))),
					'tax_value'           => $this->currency->format($product_tax_value),
					'tax_percent'         => number_format((($product_tax_value * 100) / (($product['price'] > 0) ? ($product['price'] * $product['quantity']) : $product['quantity'])), 2, '.', ''),
					'age_minimum'         => ($product['age_minimum'] > 0) ? ' (' . $product['age_minimum'] . '+)' : '',
					'total'               => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity']),
					'href'                => $this->url->link('product/product', 'product_id=' . $product['product_id'], 'SSL'),
					'recurring'           => $product['recurring'],
					'profile_name'        => $product['profile_name'],
					'profile_description' => $profile_description
				);
			}

			// Gift Voucher
			$this->data['vouchers'] = array();

			if (!empty($this->session->data['vouchers'])) {
				foreach ($this->session->data['vouchers'] as $voucher) {
					$this->data['vouchers'][] = array(
						'description' => $voucher['description'],
						'amount'      => $this->currency->format($voucher['amount'])
					);
				}
			}

			$this->data['totals'] = $total_data;

			$this->data['payment'] = $this->getChild('payment/' . $this->session->data['payment_method']['code']);
		} else {
			$this->data['redirect'] = $redirect;
		}

		// Theme
		$this->data['template'] = $this->config->get('config_template');

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/checkout_express/confirm.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/checkout_express/confirm.tpl';
		} else {
			$this->template = 'default/template/checkout_express/confirm.tpl';
		}

		$this->response->setOutput($this->render());
	}
}
