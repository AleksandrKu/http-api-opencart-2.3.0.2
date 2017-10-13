<?php

class ControllerApiUniorder extends Controller
{
    public function add()
    {
        $this->load->language('api/order');

        $json = array();

        if (!isset($this->session->data['api_id'])) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            // Customer
            if (!isset($this->session->data['customer'])) {
                $json['error'] = $this->language->get('error_customer');
            }

            // Payment Address
            if (!isset($this->session->data['payment_address'])) {
                $json['error'] = $this->language->get('error_payment_address');
            }

            // Payment Method
            if (!$json && !empty($this->request->post['payment_method'])) {
                if (empty($this->session->data['payment_methods'])) {
                    $json['error'] = $this->language->get('error_no_payment');
                } elseif (!isset($this->session->data['payment_methods'][$this->request->post['payment_method']])) {
                    $json['error'] = $this->language->get('error_payment_method');
                }

                if (!$json) {
                    $this->session->data['payment_method'] = $this->session->data['payment_methods'][$this->request->post['payment_method']];
                }
            }

            if (!isset($this->session->data['payment_method'])) {
                $json['error'] = $this->language->get('error_payment_method');
            }

            // Shipping
            if ($this->cart->hasShipping()) {
                // Shipping Address
                if (!isset($this->session->data['shipping_address'])) {
                    $json['error'] = $this->language->get('error_shipping_address');
                }

                // Shipping Method
                if (!$json && !empty($this->request->post['shipping_method'])) {
                    if (empty($this->session->data['shipping_methods'])) {
                        $json['error'] = $this->language->get('error_no_shipping');
                    } else {
                        $shipping = explode('.', $this->request->post['shipping_method']);

                        if (!isset($shipping[0]) || !isset($shipping[1]) || !isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {
                            $json['error'] = $this->language->get('error_shipping_method');
                        }
                    }

                    if (!$json) {
                        $this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
                    }
                }

                // Shipping Method
                if (!isset($this->session->data['shipping_method'])) {
                    $json['error'] = $this->language->get('error_shipping_method');
                }
            } else {
                unset($this->session->data['shipping_address']);
                unset($this->session->data['shipping_method']);
                unset($this->session->data['shipping_methods']);
            }

            // Cart
            if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
                $json['error'] = $this->language->get('error_stock');
            }

            // Validate minimum quantity requirements.
            $products = $this->cart->getProducts();

            foreach ($products as $product) {
                $product_total = 0;

                foreach ($products as $product_2) {
                    if ($product_2['product_id'] == $product['product_id']) {
                        $product_total += $product_2['quantity'];
                    }
                }

                if ($product['minimum'] > $product_total) {
                    $json['error'] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);

                    break;
                }
            }

            if (!$json) {
                $json['success'] = $this->language->get('text_success');

                $order_data = array();

                // Store Details
                $order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
                $order_data['store_id'] = $this->config->get('config_store_id');
                $order_data['store_name'] = $this->config->get('config_name');
                $order_data['store_url'] = $this->config->get('config_url');

                // Customer Details
                $order_data['customer_id'] = $this->session->data['customer']['customer_id'];
                $order_data['customer_group_id'] = $this->session->data['customer']['customer_group_id'];
                $order_data['firstname'] = $this->session->data['customer']['firstname'];
                $order_data['lastname'] = $this->session->data['customer']['lastname'];
                $order_data['email'] = $this->session->data['customer']['email'];
                $order_data['telephone'] = $this->session->data['customer']['telephone'];
                $order_data['fax'] = $this->session->data['customer']['fax'];
                $order_data['custom_field'] = $this->session->data['customer']['custom_field'];

                // Payment Details
                $order_data['payment_firstname'] = $this->session->data['payment_address']['firstname'];
                $order_data['payment_lastname'] = $this->session->data['payment_address']['lastname'];
                $order_data['payment_company'] = $this->session->data['payment_address']['company'];
                $order_data['payment_address_1'] = $this->session->data['payment_address']['address_1'];
                $order_data['payment_address_2'] = $this->session->data['payment_address']['address_2'];
                $order_data['payment_city'] = $this->session->data['payment_address']['city'];
                $order_data['payment_postcode'] = $this->session->data['payment_address']['postcode'];
                $order_data['payment_zone'] = $this->session->data['payment_address']['zone'];
                $order_data['payment_zone_id'] = $this->session->data['payment_address']['zone_id'];
                $order_data['payment_country'] = $this->session->data['payment_address']['country'];
                $order_data['payment_country_id'] = $this->session->data['payment_address']['country_id'];
                $order_data['payment_address_format'] = $this->session->data['payment_address']['address_format'];
                $order_data['payment_custom_field'] = (isset($this->session->data['payment_address']['custom_field']) ? $this->session->data['payment_address']['custom_field'] : array());

                if (isset($this->session->data['payment_method']['title'])) {
                    $order_data['payment_method'] = $this->session->data['payment_method']['title'];
                } else {
                    $order_data['payment_method'] = '';
                }

                if (isset($this->session->data['payment_method']['code'])) {
                    $order_data['payment_code'] = $this->session->data['payment_method']['code'];
                } else {
                    $order_data['payment_code'] = '';
                }

                // Shipping Details
                if ($this->cart->hasShipping()) {
                    $order_data['shipping_firstname'] = $this->session->data['shipping_address']['firstname'];
                    $order_data['shipping_lastname'] = $this->session->data['shipping_address']['lastname'];
                    $order_data['shipping_company'] = $this->session->data['shipping_address']['company'];
                    $order_data['shipping_address_1'] = $this->session->data['shipping_address']['address_1'];
                    $order_data['shipping_address_2'] = $this->session->data['shipping_address']['address_2'];
                    $order_data['shipping_city'] = $this->session->data['shipping_address']['city'];
                    $order_data['shipping_postcode'] = $this->session->data['shipping_address']['postcode'];
                    $order_data['shipping_zone'] = $this->session->data['shipping_address']['zone'];
                    $order_data['shipping_zone_id'] = $this->session->data['shipping_address']['zone_id'];
                    $order_data['shipping_country'] = $this->session->data['shipping_address']['country'];
                    $order_data['shipping_country_id'] = $this->session->data['shipping_address']['country_id'];
                    $order_data['shipping_address_format'] = $this->session->data['shipping_address']['address_format'];
                    $order_data['shipping_custom_field'] = (isset($this->session->data['shipping_address']['custom_field']) ? $this->session->data['shipping_address']['custom_field'] : array());

                    if (isset($this->session->data['shipping_method']['title'])) {
                        $order_data['shipping_method'] = $this->session->data['shipping_method']['title'];
                    } else {
                        $order_data['shipping_method'] = '';
                    }

                    if (isset($this->session->data['shipping_method']['code'])) {
                        $order_data['shipping_code'] = $this->session->data['shipping_method']['code'];
                    } else {
                        $order_data['shipping_code'] = '';
                    }
                } else {
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
                }

                // Products
                $order_data['products'] = array();

                foreach ($this->cart->getProducts() as $product) {
                    $option_data = array();

                    foreach ($product['option'] as $option) {
                        $option_data[] = array(
                            'product_option_id' => $option['product_option_id'],
                            'product_option_value_id' => $option['product_option_value_id'],
                            'option_id' => $option['option_id'],
                            'option_value_id' => $option['option_value_id'],
                            'name' => $option['name'],
                            'value' => $option['value'],
                            'type' => $option['type']
                        );
                    }

                    $order_data['products'][] = array(
                        'product_id' => $product['product_id'],
                        'name' => $product['name'],
                        'model' => $product['model'],
                        'option' => $option_data,
                        'download' => $product['download'],
                        'quantity' => $product['quantity'],
                        'subtract' => $product['subtract'],
                        'price' => $product['price'],
                        'total' => $product['total'],
                        'tax' => $this->tax->getTax($product['price'], $product['tax_class_id']),
                        'reward' => $product['reward']
                    );
                }

                // Gift Voucher
                $order_data['vouchers'] = array();

                if (!empty($this->session->data['vouchers'])) {
                    foreach ($this->session->data['vouchers'] as $voucher) {
                        $order_data['vouchers'][] = array(
                            'description' => $voucher['description'],
                            'code' => token(10),
                            'to_name' => $voucher['to_name'],
                            'to_email' => $voucher['to_email'],
                            'from_name' => $voucher['from_name'],
                            'from_email' => $voucher['from_email'],
                            'voucher_theme_id' => $voucher['voucher_theme_id'],
                            'message' => $voucher['message'],
                            'amount' => $voucher['amount']
                        );
                    }
                }

                // Order Totals
                $this->load->model('extension/extension');

                $totals = array();
                $taxes = $this->cart->getTaxes();
                $total = 0;

                // Because __call can not keep var references so we put them into an array.
                $total_data = array(
                    'totals' => &$totals,
                    'taxes' => &$taxes,
                    'total' => &$total
                );

                $sort_order = array();

                $results = $this->model_extension_extension->getExtensions('total');

                foreach ($results as $key => $value) {
                    $sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
                }

                array_multisort($sort_order, SORT_ASC, $results);

                foreach ($results as $result) {
                    if ($this->config->get($result['code'] . '_status')) {
                        $this->load->model('extension/total/' . $result['code']);

                        // We have to put the totals in an array so that they pass by reference.
                        $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                    }
                }

                $sort_order = array();

                foreach ($total_data['totals'] as $key => $value) {
                    $sort_order[$key] = $value['sort_order'];
                }

                array_multisort($sort_order, SORT_ASC, $total_data['totals']);

                $order_data = array_merge($order_data, $total_data);

                if (isset($this->request->post['comment'])) {
                    $order_data['comment'] = $this->request->post['comment'];
                } else {
                    $order_data['comment'] = '';
                }

                if (isset($this->request->post['affiliate_id'])) {
                    $subtotal = $this->cart->getSubTotal();

                    // Affiliate
                    $this->load->model('affiliate/affiliate');

                    $affiliate_info = $this->model_affiliate_affiliate->getAffiliate($this->request->post['affiliate_id']);

                    if ($affiliate_info) {
                        $order_data['affiliate_id'] = $affiliate_info['affiliate_id'];
                        $order_data['commission'] = ($subtotal / 100) * $affiliate_info['commission'];
                    } else {
                        $order_data['affiliate_id'] = 0;
                        $order_data['commission'] = 0;
                    }

                    // Marketing
                    $order_data['marketing_id'] = 0;
                    $order_data['tracking'] = '';
                } else {
                    $order_data['affiliate_id'] = 0;
                    $order_data['commission'] = 0;
                    $order_data['marketing_id'] = 0;
                    $order_data['tracking'] = '';
                }

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

                $json['order_id'] = $this->model_checkout_order->addOrder($order_data);

                // Set the order history
                if (isset($this->request->post['order_status_id'])) {
                    $order_status_id = $this->request->post['order_status_id'];
                } else {
                    $order_status_id = $this->config->get('config_order_status_id');
                }

                $this->model_checkout_order->addOrderHistory($json['order_id'], $order_status_id);

                // clear cart since the order has already been successfully stored.
                //$this->cart->clear();
            }
        }

        if (isset($this->request->server['HTTP_ORIGIN'])) {
            $this->response->addHeader('Access-Control-Allow-Origin: ' . $this->request->server['HTTP_ORIGIN']);
            $this->response->addHeader('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
            $this->response->addHeader('Access-Control-Max-Age: 1000');
            $this->response->addHeader('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function edit()
    {
        $this->load->language('api/order');

        $json = array();

        if (!isset($this->session->data['api_id'])) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('checkout/order');

            if (isset($this->request->get['order_id'])) {
                $order_id = $this->request->get['order_id'];
            } else {
                $order_id = 0;
            }

            $order_info = $this->model_checkout_order->getOrder($order_id);

            if ($order_info) {
                // Customer
                if (!isset($this->session->data['customer'])) {
                    $json['error'] = $this->language->get('error_customer');
                }

                // Payment Address
                if (!isset($this->session->data['payment_address'])) {
                    $json['error'] = $this->language->get('error_payment_address');
                }

                // Payment Method
                if (!$json && !empty($this->request->post['payment_method'])) {
                    if (empty($this->session->data['payment_methods'])) {
                        $json['error'] = $this->language->get('error_no_payment');
                    } elseif (!isset($this->session->data['payment_methods'][$this->request->post['payment_method']])) {
                        $json['error'] = $this->language->get('error_payment_method');
                    }

                    if (!$json) {
                        $this->session->data['payment_method'] = $this->session->data['payment_methods'][$this->request->post['payment_method']];
                    }
                }

                if (!isset($this->session->data['payment_method'])) {
                    $json['error'] = $this->language->get('error_payment_method');
                }

                // Shipping
                if ($this->cart->hasShipping()) {
                    // Shipping Address
                    if (!isset($this->session->data['shipping_address'])) {
                        $json['error'] = $this->language->get('error_shipping_address');
                    }

                    // Shipping Method
                    if (!$json && !empty($this->request->post['shipping_method'])) {
                        if (empty($this->session->data['shipping_methods'])) {
                            $json['error'] = $this->language->get('error_no_shipping');
                        } else {
                            $shipping = explode('.', $this->request->post['shipping_method']);

                            if (!isset($shipping[0]) || !isset($shipping[1]) || !isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {
                                $json['error'] = $this->language->get('error_shipping_method');
                            }
                        }

                        if (!$json) {
                            $this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
                        }
                    }

                    if (!isset($this->session->data['shipping_method'])) {
                        $json['error'] = $this->language->get('error_shipping_method');
                    }
                } else {
                    unset($this->session->data['shipping_address']);
                    unset($this->session->data['shipping_method']);
                    unset($this->session->data['shipping_methods']);
                }

                // Cart
                if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
                    $json['error'] = $this->language->get('error_stock');
                }

                // Validate minimum quantity requirements.
                $products = $this->cart->getProducts();

                foreach ($products as $product) {
                    $product_total = 0;

                    foreach ($products as $product_2) {
                        if ($product_2['product_id'] == $product['product_id']) {
                            $product_total += $product_2['quantity'];
                        }
                    }

                    if ($product['minimum'] > $product_total) {
                        $json['error'] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);

                        break;
                    }
                }

                if (!$json) {
                    $json['success'] = $this->language->get('text_success');

                    $order_data = array();

                    // Store Details
                    $order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
                    $order_data['store_id'] = $this->config->get('config_store_id');
                    $order_data['store_name'] = $this->config->get('config_name');
                    $order_data['store_url'] = $this->config->get('config_url');

                    // Customer Details
                    $order_data['customer_id'] = $this->session->data['customer']['customer_id'];
                    $order_data['customer_group_id'] = $this->session->data['customer']['customer_group_id'];
                    $order_data['firstname'] = $this->session->data['customer']['firstname'];
                    $order_data['lastname'] = $this->session->data['customer']['lastname'];
                    $order_data['email'] = $this->session->data['customer']['email'];
                    $order_data['telephone'] = $this->session->data['customer']['telephone'];
                    $order_data['fax'] = $this->session->data['customer']['fax'];
                    $order_data['custom_field'] = $this->session->data['customer']['custom_field'];

                    // Payment Details
                    $order_data['payment_firstname'] = $this->session->data['payment_address']['firstname'];
                    $order_data['payment_lastname'] = $this->session->data['payment_address']['lastname'];
                    $order_data['payment_company'] = $this->session->data['payment_address']['company'];
                    $order_data['payment_address_1'] = $this->session->data['payment_address']['address_1'];
                    $order_data['payment_address_2'] = $this->session->data['payment_address']['address_2'];
                    $order_data['payment_city'] = $this->session->data['payment_address']['city'];
                    $order_data['payment_postcode'] = $this->session->data['payment_address']['postcode'];
                    $order_data['payment_zone'] = $this->session->data['payment_address']['zone'];
                    $order_data['payment_zone_id'] = $this->session->data['payment_address']['zone_id'];
                    $order_data['payment_country'] = $this->session->data['payment_address']['country'];
                    $order_data['payment_country_id'] = $this->session->data['payment_address']['country_id'];
                    $order_data['payment_address_format'] = $this->session->data['payment_address']['address_format'];
                    $order_data['payment_custom_field'] = $this->session->data['payment_address']['custom_field'];

                    if (isset($this->session->data['payment_method']['title'])) {
                        $order_data['payment_method'] = $this->session->data['payment_method']['title'];
                    } else {
                        $order_data['payment_method'] = '';
                    }

                    if (isset($this->session->data['payment_method']['code'])) {
                        $order_data['payment_code'] = $this->session->data['payment_method']['code'];
                    } else {
                        $order_data['payment_code'] = '';
                    }

                    // Shipping Details
                    if ($this->cart->hasShipping()) {
                        $order_data['shipping_firstname'] = $this->session->data['shipping_address']['firstname'];
                        $order_data['shipping_lastname'] = $this->session->data['shipping_address']['lastname'];
                        $order_data['shipping_company'] = $this->session->data['shipping_address']['company'];
                        $order_data['shipping_address_1'] = $this->session->data['shipping_address']['address_1'];
                        $order_data['shipping_address_2'] = $this->session->data['shipping_address']['address_2'];
                        $order_data['shipping_city'] = $this->session->data['shipping_address']['city'];
                        $order_data['shipping_postcode'] = $this->session->data['shipping_address']['postcode'];
                        $order_data['shipping_zone'] = $this->session->data['shipping_address']['zone'];
                        $order_data['shipping_zone_id'] = $this->session->data['shipping_address']['zone_id'];
                        $order_data['shipping_country'] = $this->session->data['shipping_address']['country'];
                        $order_data['shipping_country_id'] = $this->session->data['shipping_address']['country_id'];
                        $order_data['shipping_address_format'] = $this->session->data['shipping_address']['address_format'];
                        $order_data['shipping_custom_field'] = $this->session->data['shipping_address']['custom_field'];

                        if (isset($this->session->data['shipping_method']['title'])) {
                            $order_data['shipping_method'] = $this->session->data['shipping_method']['title'];
                        } else {
                            $order_data['shipping_method'] = '';
                        }

                        if (isset($this->session->data['shipping_method']['code'])) {
                            $order_data['shipping_code'] = $this->session->data['shipping_method']['code'];
                        } else {
                            $order_data['shipping_code'] = '';
                        }
                    } else {
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
                    }

                    // Products
                    $order_data['products'] = array();

                    foreach ($this->cart->getProducts() as $product) {
                        $option_data = array();

                        foreach ($product['option'] as $option) {
                            $option_data[] = array(
                                'product_option_id' => $option['product_option_id'],
                                'product_option_value_id' => $option['product_option_value_id'],
                                'option_id' => $option['option_id'],
                                'option_value_id' => $option['option_value_id'],
                                'name' => $option['name'],
                                'value' => $option['value'],
                                'type' => $option['type']
                            );
                        }

                        $order_data['products'][] = array(
                            'product_id' => $product['product_id'],
                            'name' => $product['name'],
                            'model' => $product['model'],
                            'option' => $option_data,
                            'download' => $product['download'],
                            'quantity' => $product['quantity'],
                            'subtract' => $product['subtract'],
                            'price' => $product['price'],
                            'total' => $product['total'],
                            'tax' => $this->tax->getTax($product['price'], $product['tax_class_id']),
                            'reward' => $product['reward']
                        );
                    }

                    // Gift Voucher
                    $order_data['vouchers'] = array();

                    if (!empty($this->session->data['vouchers'])) {
                        foreach ($this->session->data['vouchers'] as $voucher) {
                            $order_data['vouchers'][] = array(
                                'description' => $voucher['description'],
                                'code' => token(10),
                                'to_name' => $voucher['to_name'],
                                'to_email' => $voucher['to_email'],
                                'from_name' => $voucher['from_name'],
                                'from_email' => $voucher['from_email'],
                                'voucher_theme_id' => $voucher['voucher_theme_id'],
                                'message' => $voucher['message'],
                                'amount' => $voucher['amount']
                            );
                        }
                    }

                    // Order Totals
                    $this->load->model('extension/extension');

                    $totals = array();
                    $taxes = $this->cart->getTaxes();
                    $total = 0;

                    // Because __call can not keep var references so we put them into an array.
                    $total_data = array(
                        'totals' => &$totals,
                        'taxes' => &$taxes,
                        'total' => &$total
                    );

                    $sort_order = array();

                    $results = $this->model_extension_extension->getExtensions('total');

                    foreach ($results as $key => $value) {
                        $sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
                    }

                    array_multisort($sort_order, SORT_ASC, $results);

                    foreach ($results as $result) {
                        if ($this->config->get($result['code'] . '_status')) {
                            $this->load->model('extension/total/' . $result['code']);

                            // We have to put the totals in an array so that they pass by reference.
                            $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                        }
                    }

                    $sort_order = array();

                    foreach ($total_data['totals'] as $key => $value) {
                        $sort_order[$key] = $value['sort_order'];
                    }

                    array_multisort($sort_order, SORT_ASC, $total_data['totals']);

                    $order_data = array_merge($order_data, $total_data);

                    if (isset($this->request->post['comment'])) {
                        $order_data['comment'] = $this->request->post['comment'];
                    } else {
                        $order_data['comment'] = '';
                    }

                    if (isset($this->request->post['affiliate_id'])) {
                        $subtotal = $this->cart->getSubTotal();

                        // Affiliate
                        $this->load->model('affiliate/affiliate');

                        $affiliate_info = $this->model_affiliate_affiliate->getAffiliate($this->request->post['affiliate_id']);

                        if ($affiliate_info) {
                            $order_data['affiliate_id'] = $affiliate_info['affiliate_id'];
                            $order_data['commission'] = ($subtotal / 100) * $affiliate_info['commission'];
                        } else {
                            $order_data['affiliate_id'] = 0;
                            $order_data['commission'] = 0;
                        }
                    } else {
                        $order_data['affiliate_id'] = 0;
                        $order_data['commission'] = 0;
                    }

                    $this->model_checkout_order->editOrder($order_id, $order_data);

                    // Set the order history
                    if (isset($this->request->post['order_status_id'])) {
                        $order_status_id = $this->request->post['order_status_id'];
                    } else {
                        $order_status_id = $this->config->get('config_order_status_id');
                    }

                    $this->model_checkout_order->addOrderHistory($order_id, $order_status_id);
                }
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }

        if (isset($this->request->server['HTTP_ORIGIN'])) {
            $this->response->addHeader('Access-Control-Allow-Origin: ' . $this->request->server['HTTP_ORIGIN']);
            $this->response->addHeader('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
            $this->response->addHeader('Access-Control-Max-Age: 1000');
            $this->response->addHeader('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function delete()
    {
        $this->load->language('api/order');

        $json = array();

        if (!isset($this->session->data['api_id'])) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('checkout/order');

            if (isset($this->request->get['order_id'])) {
                $order_id = $this->request->get['order_id'];
            } else {
                $order_id = 0;
            }

            $order_info = $this->model_checkout_order->getOrder($order_id);

            if ($order_info) {
                $this->model_checkout_order->deleteOrder($order_id);

                $json['success'] = $this->language->get('text_success');
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }

        if (isset($this->request->server['HTTP_ORIGIN'])) {
            $this->response->addHeader('Access-Control-Allow-Origin: ' . $this->request->server['HTTP_ORIGIN']);
            $this->response->addHeader('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
            $this->response->addHeader('Access-Control-Max-Age: 1000');
            $this->response->addHeader('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function returnJson($order_info, $orders_count = 1, $order_id = 0) {
        $products = [];
        /* foreach ($order_info["product_ids"] as $prod) {
            $products[] = [
                "product_id" => $prod["product_id"], //string,
                "order_product_id" => $prod["order_product_id"], //string,
                "model" => $prod["model"], //string,
                "name" => $prod["name"], //string,
                "price" => $prod["price"], //decimal,
                "quantity" => $prod["quantity"], //int,
                "total_price" => $prod["total"], //decimal,
                "tax_percent" => $prod["tax"], //decimal,
                "tax_value" => "!!!!!", //decimal,
                "variant_id" => "!!!!!", //string
            ];
        }*/
        $json = [
            "return_code" => 1, // int
            "return_message" => "!!!!!", // string
            "result" => [
                "orders_count" => $orders_count, //int
                "order" => [
                    "id" => "!!! 1", // string,
                    "order_id" => "$order_id", // string,
                    "customer" => [
                        "id" => $order_info["customer_id"], //string
                        "email" => $order_info["email"], //string
                        "first_name" => $order_info["firstname"], // string
                        "last_name" => $order_info["lastname"], // string
                    ],
                    "create_at" => "!!!!!", // string
                    "currency" => [
                        "name" => $order_info["currency_code"], //string
                        "iso3" => "!!!!!", //string
                        "symbol_left" => "!!!!!", //string
                        "symbol_right" => "!!!!!", // string
                        "rate" => "!!!!!" // decimal

                    ],
                    "billing_address" => [
                        "id" => "!!!!!", //string
                        "type" => "!!! type", //string
                        "first_name" => $order_info["payment_firstname"], // string
                        "last_name" => $order_info["payment_lastname"], //string,
                        "postcode" => $order_info["payment_postcode"], //string,
                        "address1" => $order_info["payment_address_1"], //string,
                        "address2" => $order_info["payment_address_2"], //string,
                        "phone" => $order_info["telephone"], //string,
                        "city" => $order_info["payment_city"], //string,
                        "country" => [
                            "code2" => $order_info["payment_iso_code_2"], //string,
                            "code3" => $order_info["payment_iso_code_3"], //string,
                            "name" => $order_info["payment_country"], //string
                        ],
                        "state" => [
                            "code" => "!!!!!", //string,
                            "name" => "!!!!!", //string
                        ],
                        "company" => $order_info["payment_company"], // string,
                        "fax" => "!!!!!", //string,
                        "website" => "!!!!!", //string,
                        "gender" => "!!!!!", //string,
                        "region" => "!!!!!", //string,
                        "default" => "!!!!!", //string,
                        "tax_id" => "!!!!!", //string
                    ],
                    "shipping_address" => [

                        "id" => "!!!!!", //string,
                        "type" => $order_info["shipping_code"], //string,
                        "first_name" => $order_info["shipping_firstname"], //string,
                        "last_name" => $order_info["shipping_lastname"], //string,
                        "postcode" => $order_info["shipping_postcode"], //string,
                        "address1" => $order_info["shipping_address_1"], //string,
                        "address2" => $order_info["shipping_address_2"], //string,
                        "phone" => "!!!!!", //string,
                        "city" => $order_info["shipping_city"], //string,
                        "country" => [
                            "code2" => $order_info["shipping_iso_code_2"], //string,
                            "code3" => $order_info["shipping_iso_code_3"], //string,
                            "name" => $order_info["shipping_country"], //string
                        ],
                        "state" => [
                            "code" => "!!!!!", //string,
                            "name" => "!!!!!", //string
                        ],
                        "company" => "!!!!!", //string,
                        "fax" => "!!!!!", //string,
                        "website" => "!!!!!", //string,
                        "gender" => "!!!!!", //string,
                        "region" => "!!!!!", //string,
                        "default" => "!!!!!", //string,
                        "tax_id" => "!!!!!", //string
                    ],
                    "payment_method" => [
                        "name" => $order_info["payment_method"] //string
                    ],
                    "shipping_method" => [
                        "name" => $order_info["shipping_method"] // string
                    ],
                    "status" => [
                        "id" => "!!!!!", //string,
                        "name" => "!!!!!", //string,
                        "history" => [
                            "history" => [
                                "id" => "!!!!!", //string,
                                "name" => "!!!!!", //string,
                                "modified_time" => "!!!!!", //string,
                                "notify" => "!!!!!", //string,
                                "comment" => "!!!!!", //string
                            ]
                        ],
                        "refund_info" => [
                            "shipping" => "!!!!!", //decimal,
                            "fee" => "!!!!!", //decimal,
                            "total_refunded" => "!!!!!", //decimal,
                            "time" => "!!!!!", //string,
                            "comment" => "!!!!!", //string,
                            "refunded_items" => [
                                "items" => [
                                    "product_id" => "!!!!!", //string,
                                    "variant_id" => "!!!!!", //string,
                                    "qty" => "!!!!!", //int,
                                    "refund" => "!!!!!", //decimal
                                ]
                            ]

                        ],

                    ],
                    "totals" => [
                        "total" => $order_info["total"], // decimal,
                        "subtotal" => $order_info["total"], // decimal,
                        "shipping" => "!!!!!", // decimal,
                        "tax" => "!!!!!", //decimal,
                        "discount" => "!!!!!" //decimal
                    ],
                    "total" => [
                        "subtotal_ex_tax" => "!!!!!", //decimal,
                        "wrapping_ex_tax" => "!!!!!", //decimal,
                        "shipping_ex_tax" => "!!!!!", //decimal,
                        "total_discount" => "!!!!!", //decimal,
                        "total_tax" => "!!!!!", //decimal,
                        "total" => "!!!!!", //decimal,
                        "additional_attributes" => [
                            "shipping_discount_ex_tax" => "!!!!!", //decimal,
                            "subtotal_discount_ex_tax" => "!!!!!", //decimal,
                            "subtotal_tax" => "!!!!!", //decimal,
                            "wrapping_tax" => "!!!!!", //decimal,
                            "shipping_tax" => "!!!!!", //decimal
                        ]
                    ],
                    "order_products" => $products,
                    "modified_at" => "!!!!!", // string,
                    "finished_time" => "!!!!!", //string,
                    "comment" => $order_info["comment"], //string,
                    "store_id" => $order_info["store_id"], //string
                ]
            ]
        ];
        return $json;
    }

    public function order()
    {
        $this->load->language('api/order');
        $json = array();

        /* if (!isset($this->session->data['api_id'])) {
             $json['error'] = $this->language->get('error_permission');
         } else {*/
        $this->load->model('checkout/uniorder');
        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } else {
            $order_id = 0;
        }
        $order_info = $this->model_checkout_uniorder->getOrder($order_id);
       /* $this->load->model('catalog/product');
        $product_info = $this->model_catalog_product->getProduct($product_id);*/

        /* if ($order_info) {
             $json['order'] = $order_info;

             $json['success'] = $this->language->get('text_success');
         } else {
             $json['error'] = $this->language->get('error_not_found');
         }*/
        /*}*/

       /* $products = [];
        foreach ($order_info["product_ids"] as $prod) {
            $products[] = [
                "product_id" => $prod["product_id"], //string,
                "order_product_id" => $prod["order_product_id"], //string,
                "model" => $prod["model"], //string,
                "name" => $prod["name"], //string,
                "price" => $prod["price"], //decimal,
                "quantity" => $prod["quantity"], //int,
                "total_price" => $prod["total"], //decimal,
                "tax_percent" => $prod["tax"], //decimal,
                "tax_value" => "!!!!!", //decimal,
                "variant_id" => "!!!!!", //string
            ];

        }
        $json = [
            "return_code" => 1, // int
            "return_message" => "!!!!!", // string
            "result" => [
                "orders_count" => 1, //int
                "order" => [
                    "id" => "!!! 1", // string,
                    "order_id" => "$order_id", // string,
                    "customer" => [
                        "id" => $order_info["customer_id"], //string
                        "email" => $order_info["email"], //string
                        "first_name" => $order_info["firstname"], // string
                        "last_name" => $order_info["lastname"], // string
                    ],
                    "create_at" => "!!!!!", // string
                    "currency" => [
                        "name" => $order_info["currency_code"], //string
                        "iso3" => "!!!!!", //string
                        "symbol_left" => "!!!!!", //string
                        "symbol_right" => "!!!!!", // string
                        "rate" => "!!!!!" // decimal

                    ],
                    "billing_address" => [
                        "id" => "!!!!!", //string
                        "type" => "!!! type", //string
                        "first_name" => $order_info["payment_firstname"], // string
                        "last_name" => $order_info["payment_lastname"], //string,
                        "postcode" => $order_info["payment_postcode"], //string,
                        "address1" => $order_info["payment_address_1"], //string,
                        "address2" => $order_info["payment_address_2"], //string,
                        "phone" => $order_info["telephone"], //string,
                        "city" => $order_info["payment_city"], //string,
                        "country" => [
                            "code2" => $order_info["payment_iso_code_2"], //string,
                            "code3" => $order_info["payment_iso_code_3"], //string,
                            "name" => $order_info["payment_country"], //string
                        ],
                        "state" => [
                            "code" => "!!!!!", //string,
                            "name" => "!!!!!", //string
                        ],
                        "company" => $order_info["payment_company"], // string,
                        "fax" => "!!!!!", //string,
                        "website" => "!!!!!", //string,
                        "gender" => "!!!!!", //string,
                        "region" => "!!!!!", //string,
                        "default" => "!!!!!", //string,
                        "tax_id" => "!!!!!", //string
                    ],
                    "shipping_address" => [

                        "id" => "!!!!!", //string,
                        "type" => $order_info["shipping_code"], //string,
                        "first_name" => $order_info["shipping_firstname"], //string,
                        "last_name" => $order_info["shipping_lastname"], //string,
                        "postcode" => $order_info["shipping_postcode"], //string,
                        "address1" => $order_info["shipping_address_1"], //string,
                        "address2" => $order_info["shipping_address_2"], //string,
                        "phone" => "!!!!!", //string,
                        "city" => $order_info["shipping_city"], //string,
                        "country" => [
                            "code2" => $order_info["shipping_iso_code_2"], //string,
                            "code3" => $order_info["shipping_iso_code_3"], //string,
                            "name" => $order_info["shipping_country"], //string
                        ],
                        "state" => [
                            "code" => "!!!!!", //string,
                            "name" => "!!!!!", //string
                        ],
                        "company" => "!!!!!", //string,
                        "fax" => "!!!!!", //string,
                        "website" => "!!!!!", //string,
                        "gender" => "!!!!!", //string,
                        "region" => "!!!!!", //string,
                        "default" => "!!!!!", //string,
                        "tax_id" => "!!!!!", //string
                    ],
                    "payment_method" => [
                        "name" => $order_info["payment_method"] //string
                    ],
                    "shipping_method" => [
                        "name" => $order_info["shipping_method"] // string
                    ],
                    "status" => [
                        "id" => "!!!!!", //string,
                        "name" => "!!!!!", //string,
                        "history" => [
                            "history" => [
                                "id" => "!!!!!", //string,
                                "name" => "!!!!!", //string,
                                "modified_time" => "!!!!!", //string,
                                "notify" => "!!!!!", //string,
                                "comment" => "!!!!!", //string
                            ]
                        ],
                        "refund_info" => [
                            "shipping" => "!!!!!", //decimal,
                            "fee" => "!!!!!", //decimal,
                            "total_refunded" => "!!!!!", //decimal,
                            "time" => "!!!!!", //string,
                            "comment" => "!!!!!", //string,
                            "refunded_items" => [
                                "items" => [
                                    "product_id" => "!!!!!", //string,
                                    "variant_id" => "!!!!!", //string,
                                    "qty" => "!!!!!", //int,
                                    "refund" => "!!!!!", //decimal
                                ]
                            ]

                        ],

                    ],
                    "totals" => [
                        "total" => $order_info["total"], // decimal,
                        "subtotal" => $order_info["total"], // decimal,
                        "shipping" => "!!!!!", // decimal,
                        "tax" => "!!!!!", //decimal,
                        "discount" => "!!!!!" //decimal
                    ],
                    "total" => [
                        "subtotal_ex_tax" => "!!!!!", //decimal,
                        "wrapping_ex_tax" => "!!!!!", //decimal,
                        "shipping_ex_tax" => "!!!!!", //decimal,
                        "total_discount" => "!!!!!", //decimal,
                        "total_tax" => "!!!!!", //decimal,
                        "total" => "!!!!!", //decimal,
                        "additional_attributes" => [
                            "shipping_discount_ex_tax" => "!!!!!", //decimal,
                            "subtotal_discount_ex_tax" => "!!!!!", //decimal,
                            "subtotal_tax" => "!!!!!", //decimal,
                            "wrapping_tax" => "!!!!!", //decimal,
                            "shipping_tax" => "!!!!!", //decimal
                        ]
                    ],
                    "order_products" => $products,
                    "modified_at" => "!!!!!", // string,
                    "finished_time" => "!!!!!", //string,
                    "comment" => $order_info["comment"], //string,
                    "store_id" => $order_info["store_id"], //string
                ]
            ]
        ];*/
        $json = $this->returnJson($order_info, 1, $order_id);


        if (isset($this->request->server['HTTP_ORIGIN'])) {
            $this->response->addHeader('Access-Control-Allow-Origin: ' . $this->request->server['HTTP_ORIGIN']);
            $this->response->addHeader('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
            $this->response->addHeader('Access-Control-Max-Age: 1000');
            $this->response->addHeader('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        }

        $this->response->addHeader('Content-Type: application/json');
        /*   $this->response->setOutput(json_encode($order_info)); */
        $this->response->setOutput(json_encode($json));
        /*$this->response->setOutput(json_encode($products));*/
    }

    public function orders()
    {
        $this->load->language('api/order');
        $json = array();
        /* if (!isset($this->session->data['api_id'])) {
             $json['error'] = $this->language->get('error_permission');
         } else {*/
        $this->load->model('checkout/uniorder');
        if (isset($this->request->get['limit'])) {
            $limit = $this->request->get['limit'];
        } else {
            $limit = 0;
        }
        $order_info = $this->model_checkout_uniorder->getOrders($limit);
        if ($order_info) {
            $json['order'] = $order_info;
            foreach ($json['order'] as $order) {
                /* $result["order_id"] = $order["order_id"];
                $result["store_url"] = $order["store_url"];
                $result["firstname"] = $order["firstname"];
                $result["total"] = $order["total"];
                $result["date_added"] = $order["date_added"];
                $result["order_status"] = $order["order_status"];*/
                $result = $this->returnJson($order_info);
                $result_array[] = $result;
            }
            /* $json['success'] = $this->language->get('text_success');*/
        } else {
            $json['error'] = $this->language->get('error_not_found');
        }
        /*}*/
        /*  if (isset($this->request->server['HTTP_ORIGIN'])) {
              $this->response->addHeader('Access-Control-Allow-Origin: ' . $this->request->server['HTTP_ORIGIN']);
              $this->response->addHeader('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
              $this->response->addHeader('Access-Control-Max-Age: 1000');
              $this->response->addHeader('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
          }*/
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($result_array));
    }

    public function ordersbydate()
    {
        $this->load->language('api/order');
        $json = array();
        /* if (!isset($this->session->data['api_id'])) {
             $json['error'] = $this->language->get('error_permission');
         } else {*/
        $this->load->model('checkout/uniorder');
        if (isset($this->request->get['startdate']) && isset($this->request->get['enddate'])) {
            $startdate = $this->request->get['startdate'];
            $enddate = $this->request->get['enddate'];

            $order_info = $this->model_checkout_uniorder->getOrdersByDate($startdate, $enddate);
        } else {
        }


        if ($order_info) {
            $json['order'] = $order_info;
            foreach ($json['order'] as $order) {
                $result["order_id"] = $order["order_id"];
                /*$result["store_url"] = $order["store_url"];*/
                /*$result["firstname"] = $order["firstname"];*/
                $result["total"] = $order["total"];
                $result["date_added"] = $order["date_added"];
                /*$result["order_status"] = $order["order_status"];*/
                $result_array[] = $result;
            }
            /* $json['success'] = $this->language->get('text_success');*/
        } else {
            $json['error'] = $this->language->get('error_not_found');
        }
        /*}*/
        /*  if (isset($this->request->server['HTTP_ORIGIN'])) {
              $this->response->addHeader('Access-Control-Allow-Origin: ' . $this->request->server['HTTP_ORIGIN']);
              $this->response->addHeader('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
              $this->response->addHeader('Access-Control-Max-Age: 1000');
              $this->response->addHeader('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
          }*/
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($result_array));
    }

    public function product()
    {
        $this->load->language('api/order');

        $json = array();
        /* ************************************************************************         */
        if (isset($this->request->get['product_id'])) {
            $product_id = (int)$this->request->get['product_id'];
        } else {
            $product_id = 0;
        }

        $this->load->model('catalog/product');
        $product_info = $this->model_catalog_product->getProduct($product_id);

        if ($product_info) {
            $this->load->model('catalog/review');
            $this->load->model('tool/image');
            $data['images'] = array();
            $results = $this->model_catalog_product->getProductImages($this->request->get['product_id']);
            $discounts = $this->model_catalog_product->getProductDiscounts($this->request->get['product_id']);
            $data['discounts'] = array();
            foreach ($discounts as $discount) {
                $data['discounts'][] = array(
                    'quantity' => $discount['quantity'],
                    'price' => $this->currency->format($this->tax->calculate($discount['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])
                );
            }

            $data['options'] = array();
            foreach ($this->model_catalog_product->getProductOptions($this->request->get['product_id']) as $option) {
                $product_option_value_data = array();

                foreach ($option['product_option_value'] as $option_value) {
                    if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {
                        if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
                            $price = $this->currency->format($this->tax->calculate($option_value['price'], $product_info['tax_class_id'], $this->config->get('config_tax') ? 'P' : false), $this->session->data['currency']);
                        } else {
                            $price = false;
                        }

                        $product_option_value_data[] = array(
                            'product_option_value_id' => $option_value['product_option_value_id'],
                            'option_value_id' => $option_value['option_value_id'],
                            'name' => $option_value['name'],
                            'image' => $this->model_tool_image->resize($option_value['image'], 50, 50),
                            'price' => $price,
                            'price_prefix' => $option_value['price_prefix']
                        );
                    }
                }

                $data['options'][] = array(
                    'product_option_id' => $option['product_option_id'],
                    'product_option_value' => $product_option_value_data,
                    'option_id' => $option['option_id'],
                    'name' => $option['name'],
                    'type' => $option['type'],
                    'value' => $option['value'],
                    'required' => $option['required']
                );
            }
            $data['attribute_groups'] = $this->model_catalog_product->getProductAttributes($this->request->get['product_id']);
            $data['products'] = array();
            $results = $this->model_catalog_product->getProductRelated($this->request->get['product_id']);
            foreach ($results as $result) {
                if ($result['image']) {
                    $image = $this->model_tool_image->resize($result['image'], $this->config->get($this->config->get('config_theme') . '_image_related_width'), $this->config->get($this->config->get('config_theme') . '_image_related_height'));
                } else {
                    $image = $this->model_tool_image->resize('placeholder.png', $this->config->get($this->config->get('config_theme') . '_image_related_width'), $this->config->get($this->config->get('config_theme') . '_image_related_height'));
                }

                if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                    $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                } else {
                    $price = false;
                }

                if ((float)$result['special']) {
                    $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                } else {
                    $special = false;
                }

                if ($this->config->get('config_tax')) {
                    $tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
                } else {
                    $tax = false;
                }

                if ($this->config->get('config_review_status')) {
                    $rating = (int)$result['rating'];
                } else {
                    $rating = false;
                }

                $data['products'][] = array(
                    'product_id' => $result['product_id'],
                    'thumb' => $image,
                    'name' => $result['name'],
                    'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get($this->config->get('config_theme') . '_product_description_length')) . '..',
                    'price' => $price,
                    'special' => $special,
                    'tax' => $tax,
                    'minimum' => $result['minimum'] > 0 ? $result['minimum'] : 1,
                    'rating' => $rating,
                    'href' => $this->url->link('product/product', 'product_id=' . $result['product_id'])
                );
            }
            $data['recurrings'] = $this->model_catalog_product->getProfiles($this->request->get['product_id']);
            $this->model_catalog_product->updateViewed($this->request->get['product_id']);
            /* $this->response->setOutput($this->load->view('product/product', $data));*/
            $this->response->addHeader('Content-Type: application/json');
            $date_now = date('Y-m-d H:i');
            $http_server = HTTP_SERVER;
            $xmlstr = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<yml_catalog date="$date_now">
    <shop>
    <name>$http_server</name>
    <company>$http_server</company>
    <url>$http_server</url>
    <currencies>
      <currency id="RUR" rate="1"/>
      <currency id="USD" rate="60"/>
    </currencies>
    <categories>
      <category id="1">Бытовая техника</category>
      <category id="10" parentId="1">Мелкая техника для кухни</category>
      <category id="101" parentId="10">Сэндвичницы и приборы для выпечки</category>
      <category id="102" parentId="10">Мороженицы</category>
    </categories>
    <offers>
      <offer id="12346" available="true" bid="80" cbid="90" fee="325">
        <url>http://best.seller.ru/product_page.asp?pid=12348</url>
        <price>1490</price>
        <oldprice>1620</oldprice>
        <currencyId>RUR</currencyId>
        <categoryId>101</categoryId>
        <picture>http://best.seller.ru/img/large_12348.jpg</picture>
        <store>false</store>
        <pickup>true</pickup>
        <delivery>true</delivery>
        <delivery-options>
          <option cost="300" days="0" order-before="12"/>
        </delivery-options>
        <name>Вафельница First FA-5300</name>
        <vendor>First</vendor>
        <vendorCode>A1234567B</vendorCode>
        <description>
        <![CDATA[
          <p>Отличный подарок для любителей венских вафель.</p>
        ]]>
        </description>
        <sales_notes>Необходима предоплата.</sales_notes>
        <manufacturer_warranty>true</manufacturer_warranty>
        <country_of_origin>Россия</country_of_origin>
        <barcode>0156789012</barcode>
        <cpa>1</cpa>
        <rec>123,456</rec>
      </offer>
      <offer id="9012" type="vendor.model" available="true" bid="80" cbid="90" fee="325">
        <url>http://best.seller.ru/product_page.asp?pid=12345</url>
        <price>8990</price>
        <oldprice>9900</oldprice>
        <currencyId>RUR</currencyId>
        <categoryId>102</categoryId>
        <picture>http://best.seller.ru/img/model_12345.jpg</picture>
        <store>false</store>
        <pickup>false</pickup>
        <delivery>true</delivery>
        <delivery-options>
          <option cost="300" days="1" order-before="18"/>
        </delivery-options>
        <outlets> 
          <outlet id="1" instock="50"/>
          <outlet id="2" instock="20"/>
        </outlets>
        <typePrefix>Мороженица</typePrefix>
        <vendor>Brand</vendor>
        <model>3811</model>
        <description>
        <![CDATA[
          <h3>Мороженица Brand 3811</h3>
          <p>Это прибор, который придётся по вкусу всем любителям десертов и сладостей, ведь с его помощью вы сможете делать вкусное домашнее мороженое из натуральных ингредиентов.</p>
        ]]>
        </description>
        <param name="Цвет">белый</param>
        <sales_notes>Необходима предоплата.</sales_notes>
        <manufacturer_warranty>true</manufacturer_warranty>
        <country_of_origin>Китай</country_of_origin>
        <barcode>0123456789379</barcode>
        <cpa>1</cpa>
        <rec>345,678</rec>
      </offer>
    </offers>
  </shop>
</yml_catalog>
XML;

            /* $this->response->setOutput(json_encode($product_info)); */
            $this->response->setOutput($xmlstr);

        } else {
            /*$this->response->setOutput($this->load->view('error/not_found', $data));*/
            $this->response->setOutput(json_encode("No product whis  product_id = {$product_id}!"));
        }
        /* **************************************************************      */
        /*$this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));*/
    }

    public function history()
    {
        $this->load->language('api/order');

        $json = array();

        if (!isset($this->session->data['api_id'])) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            // Add keys for missing post vars
            $keys = array(
                'order_status_id',
                'notify',
                'override',
                'comment'
            );

            foreach ($keys as $key) {
                if (!isset($this->request->post[$key])) {
                    $this->request->post[$key] = '';
                }
            }

            $this->load->model('checkout/order');

            if (isset($this->request->get['order_id'])) {
                $order_id = $this->request->get['order_id'];
            } else {
                $order_id = 0;
            }

            $order_info = $this->model_checkout_order->getOrder($order_id);

            if ($order_info) {
                $this->model_checkout_order->addOrderHistory($order_id, $this->request->post['order_status_id'], $this->request->post['comment'], $this->request->post['notify'], $this->request->post['override']);

                $json['success'] = $this->language->get('text_success');
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        }

        if (isset($this->request->server['HTTP_ORIGIN'])) {
            $this->response->addHeader('Access-Control-Allow-Origin: ' . $this->request->server['HTTP_ORIGIN']);
            $this->response->addHeader('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
            $this->response->addHeader('Access-Control-Max-Age: 1000');
            $this->response->addHeader('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}