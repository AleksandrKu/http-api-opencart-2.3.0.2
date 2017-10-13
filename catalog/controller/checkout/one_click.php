<?php
class ControllerCheckoutOneClick extends Controller {
	public function add() {
		$this->load->language('checkout/one_click');
		
		$json = array();

		if (isset($this->request->post['product_id'])) {
			$product_id = (int)$this->request->post['product_id'];
		} else {
			$product_id = 0;
		}

		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if ($product_info) {
            if (!$json) {
				if (empty($this->request->post['telephone']) || (utf8_strlen($this->request->post['telephone']) < 9)) {
					$json['error']['telephone'] = $this->language->get('error_telephone');
				} else {
					$order_data['products'] = array();
					$option_data = array();
                    $order_data['products'][] = array(
							'product_id' => $product_info['product_id'],
							'name'       => $product_info['name'],
							'model'      => $product_info['model'],
							'option'     => $option_data,
							'download'   => '',
							'quantity'   => 1,
							'subtract'   => $product_info['subtract'],
							'price'      => $product_info['price'],
							'total'      => $product_info['price'],
							'tax'        => '',
							'reward'     => $product_info['reward']
						);
					
                    $order_data['vouchers'] = array();
                    $order_data['totals'][] = array(
                        'code'          => 'total',
                        'title'         => 'Итого',
                        'value'         => $product_info['price'],
                        'sort_order'    => 9 
                    );
                    
                    $order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
                    $order_data['store_id'] = $this->config->get('config_store_id');
                    $order_data['store_name'] = $this->config->get('config_name');
                    
                    if ($order_data['store_id']) {
                        $order_data['store_url'] = $this->config->get('config_url');
                    } else {
                        $order_data['store_url'] = HTTP_SERVER;
                    }
					
                    $order_data['customer_id'] = 0;
                    $order_data['customer_group_id'] = 1;
                    $order_data['firstname'] = 'Заказ';
                    $order_data['lastname'] = 'в 1 клик';
                    $order_data['email'] = $this->config->get('config_email');
                    $order_data['telephone'] = $this->request->post['telephone'];
                    $order_data['fax'] = '';
                    $order_data['custom_field'] = array();
                    
					$order_data['payment_firstname'] = '';
                    $order_data['payment_lastname'] = '';
                    $order_data['payment_company'] = '';
                    $order_data['payment_address_1'] = '';
                    $order_data['payment_address_2'] = '';
                    $order_data['payment_city'] = '';
                    $order_data['payment_postcode'] = '';
                    $order_data['payment_zone'] = '';
                    $order_data['payment_zone_id'] = '';
                    $order_data['payment_country'] = '';
                    $order_data['payment_country_id'] = '';
                    $order_data['payment_address_format'] = '';
                    $order_data['payment_custom_field'] = array();
                    $order_data['payment_method'] = '';
					$order_data['payment_code'] = 'cod';
            
					$order_data['shipping_firstname'] = '';
                    $order_data['shipping_lastname'] = '';
                    $order_data['shipping_company'] = '';
                    $order_data['shipping_address_1'] = '';
                    $order_data['shipping_address_2'] = '';
                    $order_data['shipping_city'] = '';
                    $order_data['shipping_postcode'] = '';
                    $order_data['shipping_zone'] = '';
                    $order_data['shipping_zone_id'] = '';
                    $order_data['shipping_country'] = '';
                    $order_data['shipping_country_id'] = '';
                    $order_data['shipping_address_format'] = '';
                    $order_data['shipping_custom_field'] = array();
                    $order_data['shipping_method'] = '';
                    $order_data['shipping_code'] = '';
					
					$order_data['comment'] = '';
                    $order_data['total'] = $product_info['price'];
                    $order_data['affiliate_id'] = 0;
                    $order_data['commission'] = 0;
                    $order_data['marketing_id'] = 0;
                    $order_data['tracking'] = '';
                    $order_data['language_id'] = $this->config->get('config_language_id');
                    
                    $order_data['currency_id'] = $this->currency->getId($this->session->data['currency']);
					$order_data['currency_code'] = $this->session->data['currency'];
					$order_data['currency_value'] = $this->currency->getValue($this->session->data['currency']);
                    
                    $order_data['ip'] = $this->request->server['REMOTE_ADDR'];

					if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
						$order_data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
					} elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
						$order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
					} else {
						$order_data['forwarded_ip'] = '';
					}

					if (isset($this->request->server['HTTP_USER_AGENT'])) {
						$order_data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
					} else {
						$order_data['user_agent'] = '';
					}

					if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
						$order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
					} else {
						$order_data['accept_language'] = '';
					}
					
					$this->load->model('checkout/order');

					$data['order_id'] = $this->model_checkout_order->addOrder($order_data);
					
					if (empty($data['order_id'])) {
						$json['error']['order'] = $this->language->get('error_order');
					} else {
                        $this->model_checkout_order->addOrderHistory($data['order_id'], $this->config->get('config_order_status_id'));
                        
                        $json['success'] = sprintf($this->language->get('text_success'), $data['order_id'], $this->request->post['telephone']);
					}
				}
			} else {
				$json['redirect'] = str_replace('&amp;', '&', $this->url->link('product/product', 'product_id=' . $this->request->post['product_id']));
			}
		} else {
			$json['error']['product'] = $this->language->get('error_product');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}