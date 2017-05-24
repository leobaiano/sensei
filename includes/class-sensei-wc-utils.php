<?php

class Sensei_WC_Utils {

	/**
	 * Logger.
	 *
	 * @var WC_Logger
	 */
	private static $logger = null;

    /**
     * @param $order WC_Order
     * @return string
     */
    public static function get_order_status( $order ) {
        return self::wc_version_less_than('2.7.0') ? $order->post_status : 'wc-' . $order->get_status();
    }

    public static function wc_version_less_than($str) {
        return version_compare(WC()->version, $str, '<');
    }

    /**
     * @param $product_id int
     * @param $item array|WC_Order_Item_Product
     * @return bool
     */
    public static function has_user_bought_product( $product_id, $item ) {
        if (self::wc_version_less_than( '2.7.0' ) ) {
            return $item['product_id'] == $product_id || $item['variation_id'] == $product_id;
        }
        return $product_id === $item->get_variation_id() || $product_id === $item->get_product_id();
    }

    /**
     * @param $item array|WC_Order_Item_Product
     * @return bool
     */
    public static function is_wc_item_variation($item) {
        if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
            return $item->get_variation_id() ? true : false;
        }
        return isset( $item['variation_id'] ) && !empty( $item['variation_id'] );
    }

    /**
     * @param $product WC_Product
     * @return bool
     */
    public static function is_product_variation( $product ) {
        if ( self::wc_version_less_than('2.7.0') ) {
            return isset( $product->variation_id ) && 0 < intval( $product->variation_id );
        }
        return $product->is_type( 'variation' );
    }

    /**
     * @param $order WC_Order
     * @return mixed
     */
    public static function get_order_id($order) {
        return self::wc_version_less_than('2.7.0') ? $order->id : $order->get_id();
    }

    /**
     * Get the product id. Always return parent id in variations
     * @param $product WC_Product
     * @return int
     */
    public static function get_product_id( $product ) {
        if ( self::wc_version_less_than('2.7.0') ) {
            return $product->id;
        }
        return self::is_product_variation( $product ) ? $product->get_parent_id() : $product->get_id();
    }

    /**
     * @param $product WC_Product
     * @return int|null
     */
    public static function get_product_variation_id( $product ) {
        if ( !self::is_product_variation( $product ) ) {
            return null;
        }
        return self::wc_version_less_than('2.7.0') ? $product->variation_id : $product->get_id();
    }

    /**
     * @param $item array|WC_Order_Item_Product
     * @param bool $always_return_parent_product_id
     * @return mixed
     */
    public static function get_item_id_from_item($item, $always_return_parent_product_id = false)
    {
        if ( is_a( $item, 'WC_Order_Item_Product') ) {
            // 2.7: we get a WC_Order_Item_Product
            $variation_id = $item->get_variation_id();
            $product_id = $item->get_product_id();
        } else {
            // pre 2.7: we get an array
            $variation_id = isset( $item['variation_id'] ) ? $item['variation_id'] : null;
            $product_id = $item['product_id'];
        }
        if (false === $always_return_parent_product_id
            && $variation_id && 0 < $variation_id
        ) {
            return $variation_id;
        }

        return $product_id;
    }

    /**
     * @param $post_or_id WP_Post|int
     * @return null|WC_Product
     */
    public static function get_product( $post_or_id ) {
        return self::wc_version_less_than('2.7') ? get_product( $post_or_id ) : wc_get_product( $post_or_id );
    }

    /**
     * @param $product WC_Product
     * @return null|WC_Product
     */
    public static function get_parent_product($product ) {
        return self::get_product( self::get_product_id( $product ) );
    }

    /**
     * @param $product WC_Abstract_Legacy_Product
     * @return mixed
     */
    public static function get_variation_data( $product ) {
        if ( self::wc_version_less_than('2.7') ) {
            return $product->variation_data;
        }
        return $product->is_type( 'variation' ) ? wc_get_product_variation_attributes( $product->get_id() ) : '';
    }

    /**
     * @param string $variation
     * @param bool $flat
     * @return string
     */
    public static function get_formatted_variation( $variation = '', $flat = false ) {
        if ( self::wc_version_less_than('2.7') ) {
            return woocommerce_get_formatted_variation( $variation, $flat );
        }

        return wc_get_formatted_variation( $variation, $flat );
    }

    /*
     * @param $product WC_Product|WC_Abstract_Legacy_Product
     * @return array|mixed|string
     */
    public static function get_product_variation_data( $product ) {
        if ( self::wc_version_less_than('3.0.0') ) {
            return ( isset( $product->variation_data ) && is_array( $product->variation_data ) ) ? $product->variation_data : array();
        }

        return self::is_product_variation( $product ) ? wc_get_product_variation_attributes( $product->get_id() ) : '';
    }

	private static function get_logger() {
		if ( null === self::$logger ) {
			self::$logger = new WC_Logger();
		}

		return self::$logger;
	}

	public static function log( $message ) {
		if ( false === Sensei_WC::is_woocommerce_active() ) {
			return;
		}
		$debugging_enabled = (bool) Sensei()->settings->get( 'woocommerce_enable_sensei_debugging' );
		if ( ! $debugging_enabled ) {
			return;
		}
		self::get_logger()->log( 'notice', $message, array( 'source' => 'woothemes_sensei_core' ) );
	}
}