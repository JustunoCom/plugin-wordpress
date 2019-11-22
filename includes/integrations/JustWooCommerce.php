<?php
namespace Integrations;

if (!class_exists('JustWooCommerce')) {
	class JustWooCommerce
	{
		public function getProductData($data)
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

			if($date !== null) {
				$args['date_modified'] = '>=' . $date;
			}

			$products = array();
			foreach (wc_get_products($args) as $product) {
				$products[] = $this->mapProductData($product);
			}
			return $products;

		}

		public function mapProductData($product)
		{
			$photos = $this->pickPhotos($product->get_gallery_image_ids());
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
				"CreatedAt" => $product->get_date_created()->date("Y-m-d h:i:s.u"),
				"UpdatedAt" => $product->get_date_modified()->date("Y-m-d h:i:s.u"),
				"ReviewsCount" => $product->get_review_count(),
				"ReviewsRatingSum" => $product->get_average_rating(),
				"Variations" => $variations,
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

		private function pickPhotos($photos)
		{
			$return = [];
			foreach ($photos as $photo) {
				$photo = wp_get_attachment_image_url($photo, 'medium');
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
					"ID" => $term->term_id,
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
					$return[] = str_replace("Variation ", "", $key);
				}
			}
			return $return;
		}

		private function pickVariations($product)
		{
			$isEnabled = false;
			if($product->get_status() === 'publish' && $product->get_catalog_visibility() === 'visible' && $product->get_post_password() === '') {
				$isEnabled = true;
			}
			$return = [];
			if ($product->get_type() === "variable") {
				foreach ($product->get_available_variations() as $key => $variation) {
					$options = [];
					foreach ($product->get_variation_attributes() as $keyAttr => $attribute) {
						$options[] = $attribute[$key];
					}
					$isVariationEnabled = $variation["variation_is_active"] == true && $variation["variation_is_visible"] == true;
					$return[] = [
						"ID" => (string) isset($variation["variation_id"]) ? $variation["variation_id"] : null,
						"Title" => null,
						"SKU" => isset($variation["sku"]) ? $variation["sku"] : null,
						"MSRP" => isset($variation["display_regular_price"]) ? floatval($variation["display_regular_price"]) : null,
						"Option1" => isset($options[0]) ? $options[0] : null,
						"Option2" => isset($options[1]) ? $options[1] : null,
						"Option3" => isset($options[2]) ? $options[2] : null,
						"SalePrice" => isset($variation["display_price"]) ? $variation["display_price"] : null,
						"InventoryQuantity" => $isEnabled && $isVariationEnabled ? (isset($variation["max_qty"]) ? $variation["max_qty"] : null) : -9999,
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
					"InventoryQuantity" => $isEnabled ? ($product->get_max_purchase_quantity() === -1 ? null : $product->get_max_purchase_quantity()) : -9999,
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

			if($date !== null) {
				$args['date_modified'] = '>=' . $date;
			}

			$orders = [];
			foreach (wc_get_orders($args) as $orders) {
				$products[] = $this->mapOrderData($orders);
			}
			return $products;
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
					"ProductID" => (string) $item->get_product()->get_id(),
					"OrderID" => (string) $item->get_order_id(),
					"VariantID" => (string) $item->get_variation_id(),
					"Price" => floatval($item->get_total()),
					"TotalDiscount" => floatval($item->get_total() - $item->get_subtotal()),
				];
			}
			return $return;
		}
	}
}
