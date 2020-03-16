<?php
namespace Integrations;

if (!class_exists('JustWooCommerce')) {
    class JustWooCommerce
    {
        public function getVerboseData($data)
        {
            $date = isset($data['date']) ? $data['date'] : null;
            $limit = isset($data['limit']) ? $data['limit'] : 20;
            $page = isset($data['page']) ? $data['page'] : 1;
            $thumbSize = isset($data['thumb']) ? $data['thumb'] : 'medium';
            $args = [

                'limit' => $limit,
                'page' => $page,
                'orderby' => 'modified',
                'order' => 'DESC',
            ];

            if ($date !== null) {
                $args['date_modified'] = '>=' . strtotime($date);
            }

            $p = wc_get_products($args);
            if ($this->has_verbose('product')) {
                print_r($p);
            }
            foreach ($p as $product) {
                $photos = $product->get_gallery_image_ids();
                if ($this->has_verbose('imageIds')) {
                    print_r($photos);
                }
                foreach ($photos as $photo) {
                    if ($this->has_verbose('fullphoto')) {
                        print_r(wp_get_attachment_image_url($photo));
                    }
                }

                $categories = get_the_terms($product->get_id(), 'product_cat');
                if ($this->has_verbose('category')) {
                    print_r($terms);
                }
                foreach ($categories as $category) {
                    $thumb_id = get_term_meta($category->term_id, 'thumbnail_id', true);
                    if ($this->has_verbose('category')) {
                        print_r($thumb_id);
                    }
                }

                $terms = get_the_terms($product->get_id(), 'product_tag');
                if ($this->has_verbose('tags')) {
                    print_r($terms);
                }

                if ($this->has_verbose('attributes')) {
                    print_r($product->get_variation_attributes());
                }

                if ($this->has_verbose('variations')) {
                    print_r($product->get_available_variations());
                }

                if ($this->has_verbose('variations')) {
                    print_r($product->get_attributes());
                }

            }

            $orders = wc_get_orders($args);

            if ($this->has_verbose('orders')) {
                print_r($orders);
            }

            foreach ($orders as $order) {
                if ($this->has_verbose('orders')) {
                    print_r($order->get_items());
                }
            }

            global $_wp_additional_image_sizes;
            print_r($_wp_additional_image_sizes);
        }

        public function getProductData($data)
        {
            $date = isset($data['date']) ? $data['date'] : null;
            $limit = isset($data['limit']) ? $data['limit'] : 20;
            $page = isset($data['page']) ? $data['page'] : 1;
            $thumbSize = isset($data['thumb']) ? $data['thumb'] : 'medium';
            $args = [

                'limit' => $limit,
                'page' => $page,
                'orderby' => 'modified',
                'order' => 'DESC',
            ];

            if ($date !== null) {
                $args['date_modified'] = '>=' . strtotime($date);
            }

            $products = array();
            foreach (wc_get_products($args) as $product) {
                $products[] = $this->mapProductData($product, $thumbSize);
            }
            return $products;

        }

        public function mapProductData($product, $thumbSize)
        {
            $photos = $product->get_gallery_image_ids();
            if (count($photos) === 0) {
                $photos[] = get_post_thumbnail_id($product->get_id());
            } else {
                $id = get_post_thumbnail_id();
                if ($id != '') {
                    foreach ($photos as $key => $photo) {
                        if ($id === $photo) {
                            unset($photo[$key]);
                        }
                    }
                    array_unshift($photos, get_post_thumbnail_id($product->get_id()));
                }
            }
            $photos = $this->pickPhotos($photos, $thumbSize);
            $options = $this->pickOptions($product);
            $variations = [];
            $variations = $this->pickVariations($product);
            $pricing = $this->get_pricing($product->get_regular_price(), $product->get_price(), $product->get_sale_price());
            return array(
                "ID" => (string) $product->get_id(),
                "MSRP" => $pricing["MSRP"],
                "Price" => $pricing["Price"],
                "SalePrice" => $pricing["SalePrice"],
                "Title" => $product->get_title(),
                "ImageURL1" => isset($photos[0]) ? $photos[0] : null,
                "ImageURL2" => isset($photos[1]) ? $photos[1] : null,
                "ImageURL3" => isset($photos[2]) ? $photos[2] : null,
                "AddToCartURL" => $product->get_type() !== "variable" ? $product->add_to_cart_url() : null,
                "URL" => $product->get_permalink(),
                "OptionType1" => isset($options[0]) ? $options[0] : null,
                "OptionType2" => isset($options[1]) ? $options[1] : null,
                "OptionType3" => isset($options[2]) ? $options[2] : null,
                "Categories" => $this->pickCategories($product->get_id()),
                "Tags" => $this->pickTags($product->get_id()),
                "CreatedAt" => $product->get_date_created() !== null ? $product->get_date_created()->date("Y-m-d h:i:s.u") : date("Y-m-d h:i:s.u"),
                "UpdatedAt" => $product->get_date_modified() !== null ? $product->get_date_modified()->date("Y-m-d h:i:s.u") : date("Y-m-d h:i:s.u"),
                "ReviewsCount" => $product->get_review_count(),
                "ReviewsRatingAvg" => floatval($product->get_average_rating()),
                "Variants" => $variations,
            );
        }

        private function get_pricing($msrp, $price, $sale)
        {
            $msrp = $msrp === "" ? null : floatval($msrp);
            $price = $price === "" ? null : floatval($price);
            $sale = $sale === "" ? null : floatval($sale);

            return [
                "MSRP" => ($msrp !== null ? $msrp : ($price !== null ? $price : $sale)),
                "Price" => ($price !== null ? $price : ($sale !== null ? $sale : $msrp)),
                "SalePrice" => ($sale !== null ? $sale : ($price !== null ? $price : $msrp)),
            ];
        }

        private function pickPhotos($photos, $thumbSize)
        {
            $return = [];
            foreach ($photos as $photo) {
                $photo = wp_get_attachment_image_url($photo, $thumbSize);
                if ($photo !== "") {
                    $return[] = $photo;
                }
            }
            return $return;
        }

        private function pickCategories($ID)
        {
            $categories = [];
            $terms = get_the_terms($ID, 'product_cat');
            foreach ($terms as $term) {
                $thumb_id = get_term_meta($term->term_id, 'thumbnail_id', true);
                $categories[] = array(
                    "ID" => (string) $term->term_id,
                    "Name" => $term->name,
                    "Description" => $term->description,
                    "URL" => get_term_link($term->term_id),
                    "ImageURL" => $thumb_id !== "" ? wp_get_attachment_url($thumb_id) : null,
                    "Keywords" => null,
                );
            }
            return $categories;
        }

        private function pickTags($ID)
        {
            $tags = [];
            $terms = get_the_terms($ID, 'product_tag');
            if (is_array($terms)) {
                foreach ($terms as $term) {
                    $tags[] = array(
                        "ID" => (string) $term->term_id,
                        "Name" => $term->name,
                    );
                }
            }
            return $tags;
        }

        private function pickOptions($product)
        {
            $return = [];
            if ($product->get_type() === "variable") {
                foreach ($product->get_variation_attributes() as $key => $attribute) {
                    $return[] = str_replace("pa_", "", str_replace("Variation ", "", $key));
                }
            }
            return $return;
        }

        private function pickVariations($product)
        {
            $isEnabled = false;
            if ($product->get_status() === 'publish' && $product->get_catalog_visibility() === 'visible' && $product->get_post_password() === '') {
                $isEnabled = true;
            }
            $return = [];
            if ($product->get_type() === "variable") {
                foreach ($product->get_available_variations() as $key => $variation) {
                    $options = [];
                    foreach ($variation['attributes'] as $keyAttr => $attribute) {
                        $options[] = $attribute;
                    }
                    $isVariationEnabled = $variation["variation_is_active"] == true && $variation["variation_is_visible"] == true;
                    $return[] = [
                        "ID" => (string) (isset($variation["variation_id"]) ? $variation["variation_id"] : null),
                        "Title" => null,
                        "SKU" => isset($variation["sku"]) ? $variation["sku"] : null,
                        "MSRP" => isset($variation["display_regular_price"]) ? floatval($variation["display_regular_price"]) : null,
                        "Option1" => isset($options[0]) ? $options[0] : null,
                        "Option2" => isset($options[1]) ? $options[1] : null,
                        "Option3" => isset($options[2]) ? $options[2] : null,
                        "SalePrice" => isset($variation["display_price"]) ? $variation["display_price"] : null,
                        "InventoryQuantity" => $isEnabled && $isVariationEnabled ? (isset($variation["max_qty"]) && $variation["max_qty"] != null ? $variation["max_qty"] : 9999) : -9999,
                    ];
                }
            } else {
                $optionsNew = [];
                foreach ($product->get_attributes() as $key => $attribute) {
                    $title = wc_attribute_label($key);
                    if ($key !== $title) {
                        $optionsNew[] = [$title => $attribute['options']];
                    }
                }
                $msrp = $product->get_regular_price() !== "" ? floatval($product->get_regular_price()) : null;
                $sale = $product->get_sale_price() !== "" ? floatval($product->get_sale_price()) : null;
                $return[] = [
                    "ID" => (string) $product->get_id(),
                    "Title" => $product->get_title(),
                    "SKU" => $product->get_sku(),
                    "MSRP" => $msrp !== null ? $msrp : $sale,
                    "SalePrice" => $sale !== null ? $sale : $msrp,
                    "Option1" => null,
                    "Option2" => null,
                    "Option3" => null,
                    "InventoryQuantity" => $isEnabled ? ($product->get_max_purchase_quantity() === -1 ? 9999 : $product->get_max_purchase_quantity()) : -9999,
                ];
            }
            return $return;
        }

        public function getOrderData($data)
        {
            $date = isset($data['date']) ? $data['date'] : null;
            $limit = isset($data['limit']) ? $data['limit'] : 20;
            $page = isset($data['page']) ? $data['page'] : 1;
            $args = [
                'limit' => $limit,
                'page' => $page,
                'orderby' => 'modified',
                'order' => 'DESC',
            ];

            if ($date !== null) {
                $args['date_modified'] = '>=' . strtotime($date);
            }

            $orders = [];
            foreach (wc_get_orders($args) as $order) {
                if ($order instanceof \WC_Order) {
                    $orders[] = $this->mapOrderData($order);
                }
            }
            return $orders;
        }

        public function mapOrderData($order)
        {
            $items = $order->get_items();
            return array(
                "ID" => (string) $order->get_id(),
                "OrderNumber" => $order->get_order_number(),
                "CustomerID" => (string) $order->get_customer_id(),
                "Email" => $order->get_billing_email(),
                "CreatedAt" => $order->get_date_created()->date("Y-m-d h:i:s.u"),
                "UpdatedAt" => $order->get_date_modified()->date("Y-m-d h:i:s.u"),
                "TotalPrice" => floatval($order->get_total()),
                "SubtotalPrice" => floatval($order->get_subtotal()),
                "ShippingPrice" => floatval($order->get_shipping_total()),
                "TotalTax" => floatval($order->get_total_tax()),
                "TotalDiscounts" => floatval($order->get_total_discount()),
                "TotalItems" => count($items),
                "Currency" => $order->get_currency(),
                "Status" => $order->get_status(),
                "IP" => $order->get_customer_ip_address(),
                "CountryCode" => $order->get_billing_country(),
                "LineItems" => $this->get_items($items),
                "Customer" => [
                    "ID" => (string) $order->get_customer_id(),
                    "Email" => $order->get_billing_email(),
                    "CreatedAt" => $order->get_date_created()->date("Y-m-d h:i:s.u"),
                    "UpdatedAt" => $order->get_date_modified()->date("Y-m-d h:i:s.u"),
                    "FirstName" => $order->get_billing_first_name(),
                    "LastName" => $order->get_billing_last_name(),
                    "OrdersCount" => $order->get_customer_id() > 0 ? wc_get_customer_order_count($order->get_customer_id()) : 1,
                    "TotalSpend" => $order->get_customer_id() > 0 ? floatval(wc_get_customer_total_spent($order->get_customer_id())) : floatval($order->get_total()),
                    "Tags" => null,
                    "Currency" => $order->get_currency(),
                    "Address1" => $order->get_billing_address_1(),
                    "Address2" => $order->get_billing_address_2(),
                    "City" => $order->get_billing_city(),
                    "Zip" => $order->get_billing_postcode(),
                    "ProvinceCode" => $order->get_billing_state(),
                    "CountryCode" => $order->get_billing_country(),
                ],
            );
        }

        public function get_items($items)
        {
            $return = [];
            foreach ($items as $item) {
                $return[] = [
                    "ProductID" => (string) $item->get_product_id(),
                    "OrderID" => (string) $item->get_order_id(),
                    "VariantID" => (string) ($item->get_variation_id() > 0 ? $item->get_variation_id() : $item->get_product_id()),
                    "Price" => floatval($item->get_total()),
                    "TotalDiscount" => floatval($item->get_total() - $item->get_subtotal()),
                ];
            }
            return $return;
        }

        public function getConversionTrackingCodes()
        {
            $code = '';
            if (is_home()) {
                $code .= 'juapp("local","pageType","home");';
            } else if (is_product_category()) {
                $code .= 'juapp("local","pageType","category");';
            } else if (is_product()) {
                global $post;
                $code .= 'juapp("local","pageType","product");';
                $code .= 'juapp("local","prodId","' . $post->ID . '");';
            } else if (is_cart()) {
                $code .= 'juapp("local","pageType","cart");';
            } else if (is_checkout()) {
                $code .= 'juapp("local","pageType","checkout");';
            }

            if (is_user_logged_in()) {
                $current_user = wp_get_current_user();
                $code .= 'juapp("local","custId","' . $current_user->user_email . '");';
            }

            global $wp;
            $order_id = absint($wp->query_vars['order-received']);
            if ($order_id > 0) {
                $order = wc_get_order($order_id);
                $code .= '
juapp("order", "' . $order->get_id() . '", {
	total:' . floatval($order->get_total()) . ',
	subtotal:' . floatval($order->get_subtotal()) . ',
	tax:' . floatval($order->get_total_tax()) . ',
	shipping:' . floatval($order->get_shipping_total()) . ',
	currency: "' . $order->get_currency() . '"
});';
                foreach ($order->get_items() as $item) {
                    $tmpCode = '';
                    foreach ($item->get_formatted_meta_data() as $meta) {
                        $tmpCode .= str_replace("pa_", "", $meta->key) . ':"' . $meta->value . '",';
                    }
                    $code .= 'juapp("orderItem", {
	productid:' . $item->get_product_id() . ',
	variationid:' . ($item->get_variation_id() > 0 ? $item->get_variation_id() : $item->get_product_id()) . ',
	sku:"' . $item->get_product()->get_sku() . '",
	name:"' . $item->get_name() . '",
	quantity:' . floatval($item->get_quantity()) . ',
	' . $tmpCode . '
	price:' . floatval($item->get_total()) . '
});';
                }
            }

            $cart = \WC()->cart;
            $totals = $cart->get_totals();
            $code .= 'juapp("cart", {
	total:' . $totals['total'] . ',
	subtotal:' . $totals['subtotal'] . ',
	tax:' . $totals['total_tax'] . ',
	shipping:' . $totals['shipping_total'] . ',
	currency:"USD",
	}
);';
            $cartItems = $cart->get_cart_contents();
            if (count($cartItems) > 0) {
                $code .= "juapp('cartItems', [";
                foreach ($cart->get_cart() as $key => $item) {
                    $cartItem = $cart->get_cart_item($key);
                    $attrs = '';
                    if ($cartItem['variation_id'] > 0) {
                        foreach ($cartItem['variation'] as $key => $value) {
                            $attrs .= str_replace('attribute_', '', str_replace('pa_', '', $key)) . ": '{$value}', ";
                        }
                    }
                    $variationId = $cartItem['variation_id'] > 0 ? $cartItem['variation_id'] : $cartItem['product_id'];
                    $product = $cartItem['data'];
                    $code .= "
{ productid: '{$cartItem['product_id']}', variationid: '{$variationId}', sku:'{$product->get_sku()}', quantity: {$cartItem['quantity']}, price: {$cartItem['line_subtotal']}, {$attrs}name: '{$product->get_name()}'},";
                }
                $code = substr($code, 0, -1);
                $code .= "])";
            }
            return $code;
        }

        public function has_verbose($type)
        {
            if (isset($_GET['level']) && ($_GET['level'] === '*' || strpos($_GET['level'], $type) !== false)) {
                return true;
            }
            return false;
        }
    }
}
