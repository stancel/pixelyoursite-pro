<?php

namespace PixelYourSite;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * @property int    post_id
 * @property string title
 * @property bool   enabled
 *
 * @property int    delay
 * @property array  triggers
 * @property array  url_filters
 * @property string trigger_type
 * @property int    time_window
 * @property bool   enable_time_window
 *
 * @property bool   facebook_enabled
 * @property string facebook_event_type
 * @property string facebook_custom_event_type
 * @property bool   facebook_params_enabled
 * @property array  facebook_params
 * @property array  facebook_custom_params
 *
 * @property bool   pinterest_enabled
 * @property string pinterest_event_type
 * @property string pinterest_custom_event_type
 * @property bool   pinterest_params_enabled
 * @property array  pinterest_custom_params
 *
 * @property bool   ga_enabled
 * @property string ga_event_action
 * @property string ga_custom_event_action
 * @property string ga_event_category
 * @property string ga_event_label
 * @property string ga_event_value
 * @property bool   ga_non_interactive
 *
 * @property bool   google_ads_enabled
 * @property string google_ads_conversion_id
 * @property string google_ads_conversion_label
 * @property string google_ads_event_action
 * @property string google_ads_custom_event_action
 * @property string google_ads_event_category
 * @property string google_ads_event_label
 * @property string google_ads_event_value
 * @property array  google_ads_custom_params
 *
 * @property bool   bing_enabled
 * @property string bing_event_action
 * @property string bing_event_category
 * @property string bing_event_label
 * @property string bing_event_value
 */
class CustomEvent {

	private $post_id;

	private $title = 'Untitled';

	private $enabled = true;
	
	private $data = array(
		'delay'        => null,
		'trigger_type' => 'page_visit',
		'triggers'     => array(),
		'url_filters'  => array(),
        'enable_time_window' => false,
        'time_window' => 24,
		
		'facebook_enabled'           => false,
		'facebook_event_type'        => 'ViewContent',
		'facebook_custom_event_type' => null,
		'facebook_params_enabled'    => false,
		'facebook_params'            => array(),
		'facebook_custom_params'     => array(),
		
		'pinterest_enabled'           => false,
		'pinterest_event_type'        => 'ViewContent',
		'pinterest_custom_event_type' => null,
		'pinterest_params_enabled'    => false,
		'pinterest_custom_params'     => array(),
		
		'ga_enabled'             => false,
		'ga_event_action'        => '_custom',
		'ga_custom_event_action' => null,
		'ga_event_category'      => null,
		'ga_event_label'         => null,
		'ga_event_value'         => null,
		'ga_non_interactive'     => true,
		
		'google_ads_enabled'             => false,
		'google_ads_conversion_id'       => '_all',
		'google_ads_conversion_label'    => null,
		'google_ads_event_action'        => 'conversion',
		'google_ads_custom_event_action' => null,
		'google_ads_event_category'      => null,
		'google_ads_event_label'         => null,
		'google_ads_event_value'         => null,
		'google_ads_custom_params'       => array(),

        'bing_enabled' => false,
        'bing_event_action' => null,
        'bing_event_category' => null,
        'bing_event_label' => null,
        'bing_event_value' => null,
	);

	public function __construct( $post_id = null ) {
		$this->initialize( $post_id );
	}

	public function __get( $key ) {

		if ( $key == 'post_id' ) {
			return $this->post_id;
		}

		if ( $key == 'title' ) {
			return $this->title;
		}

		if ( $key == 'enabled' ) {
			return $this->enabled;
		}

		if ( isset( $this->data[ $key ] ) ) {
			return $this->data[ $key ];
		} else {
			return null;
		}

	}

	private function initialize( $post_id ) {

		if ( $post_id ) {

			$this->post_id = $post_id;
			$this->title   = get_the_title( $post_id );
			
			$data = get_post_meta( $post_id, '_pys_event_data', true );
			$this->data = is_array( $data ) ? $data : array();

			$state = get_post_meta( $post_id, '_pys_event_state', true );
			$this->enabled = $state == 'active' ? true : false;

		}

	}

	public function update( $args = null ) {

		if ( ! is_array( $args ) ) {
			$args = $this->data;
		}

		/**
		 * GENERAL
		 */

		// title
		wp_update_post( array(
			'ID'         => $this->post_id,
			'post_title' => empty( $args['title'] ) ? $this->title : sanitize_text_field( $args['title'] )
		) );

		// state
		$state = isset( $args['enabled'] ) && $args['enabled'] ? 'active' : 'paused';
		$this->enabled = $state == 'active' ? true : false;
		update_post_meta( $this->post_id, '_pys_event_state', $state );

		$trigger_types = array( 'page_visit', 'url_click', 'css_click', 'css_mouseover', 'scroll_pos' );

		// trigger type
		$this->data['trigger_type'] = isset( $args['trigger_type'] ) && in_array( $args['trigger_type'], $trigger_types )
			? $args['trigger_type']
			: 'page_visit';

		// delay
		$this->data['delay'] = $this->trigger_type == 'page_visit' && isset( $args['delay'] ) && $args['delay']
			? (int) $args['delay']
			: null;

        $this->data['enable_time_window'] = isset( $args['enable_time_window']) ? (bool)$args['enable_time_window'] : false;
        $this->data['time_window'] = isset( $args['time_window']) ? (int)$args['time_window'] : 24;

		/**
		 * TRIGGERS
		 */

		// reset old triggers
		$this->data['triggers'] = array();

		// page visit triggers
		if ( $this->trigger_type == 'page_visit' && isset( $args['page_visit_triggers'] )
		     && is_array( $args['page_visit_triggers'] ) ) {

			foreach ( $args['page_visit_triggers'] as $trigger ) {

				if ( ! empty( $trigger['value'] ) ) {

					$this->data['triggers'][] = array(
						'rule'  => $trigger['rule'] == 'contains' ? 'contains' : 'match',
						'value' => $trigger['value'],
					);

				}

			}

		}

		// url click triggers
		if ( $this->trigger_type == 'url_click' && isset( $args['url_click_triggers'] )
		     && is_array( $args['url_click_triggers'] ) ) {

			foreach ( $args['url_click_triggers'] as $trigger ) {

				if ( ! empty( $trigger['value'] ) ) {
					
					$this->data['triggers'][] = array(
						'rule'  => $trigger['rule'] == 'contains' ? 'contains' : 'match',
						'value' => $trigger['value'],
					);

				}

			}

		}

		// css click triggers
		if ( $this->trigger_type == 'css_click' && isset( $args['css_click_triggers'] )
		     && is_array( $args['css_click_triggers'] ) ) {

			foreach ( $args['css_click_triggers'] as $trigger ) {

				if ( ! empty( $trigger['value'] ) ) {

					$this->data['triggers'][] = array(
						'rule'  => null,
						'value' => sanitize_text_field( $trigger['value'] ),
					);

				}

			}

		}

		// css mouseover triggers
		if ( $this->trigger_type == 'css_mouseover' && isset( $args['css_mouseover_triggers'] )
		     && is_array( $args['css_mouseover_triggers'] ) ) {

			foreach ( $args['css_mouseover_triggers'] as $trigger ) {

				if ( ! empty( $trigger['value'] ) ) {

					$this->data['triggers'][] = array(
						'rule'  => null,
						'value' => sanitize_text_field( $trigger['value'] ),
					);

				}

			}

		}

		// scroll pos triggers
		if ( $this->trigger_type == 'scroll_pos' && isset( $args['scroll_pos_triggers'] )
		     && is_array( $args['scroll_pos_triggers'] ) ) {

			foreach ( $args['scroll_pos_triggers'] as $trigger ) {

				if ( ! empty( $trigger['value'] ) ) {
					
					$this->data['triggers'][] = array(
						'rule'  => null,
						'value' => (int) $trigger['value'],
					);

				}

			}

		}

		// reset old url filters
		$this->data['url_filters'] = array();

		if ( in_array( $this->trigger_type, array( 'url_click', 'css_click', 'css_mouseover', 'scroll_pos' ) ) &&
		     isset( $args['url_filter_triggers'] ) && is_array( $args['url_filter_triggers'] ) ) {

			foreach ( $args['url_filter_triggers'] as $trigger ) {

				if ( ! empty( $trigger['value'] ) ) {
					
					$this->data['url_filters'][] = array(
						'rule'  => null,
						'value' => $trigger['value'],
					);

				}

			}

		}

		/**
		 * FACEBOOK
		 */

		$facebook_event_types = array(
			'ViewContent',
			'AddToCart',
			'AddToWishlist',
			'InitiateCheckout',
			'AddPaymentInfo',
			'Purchase',
			'Lead',
			'CompleteRegistration',
			
			'Subscribe',
			'CustomizeProduct',
			'FindLocation',
			'StartTrial',
			'SubmitApplication',
			'Schedule',
			'Contact',
			'Donate',
			
			'CustomEvent'
		);

		// enabled
		$this->data['facebook_enabled'] = isset( $args['facebook_enabled'] ) && $args['facebook_enabled'] ? true : false;

		// event type
		$this->data['facebook_event_type'] = isset( $args['facebook_event_type'] ) && in_array( $args['facebook_event_type'], $facebook_event_types )
			? sanitize_text_field( $args['facebook_event_type'] )
			: 'ViewContent';

		// custom event type
		$this->data['facebook_custom_event_type'] = $this->facebook_event_type == 'CustomEvent' && ! empty( $args['facebook_custom_event_type'] )
			? sanitizeKey( $args['facebook_custom_event_type'] )
			: null;

		// params enabled
		$this->data['facebook_params_enabled'] = isset( $args['facebook_params_enabled'] ) && $args['facebook_params_enabled'] ? true : false;

		// params
		if ( $this->facebook_params_enabled && isset( $args['facebook_params'] ) && $this->facebook_event_type !== 'CustomEvent' ) {

			$this->data['facebook_params'] = array(
				'value'            => ! empty( $args['facebook_params']['value'] ) ? sanitize_text_field( $args['facebook_params']['value'] ) : null,
				'currency'         => ! empty( $args['facebook_params']['currency'] ) ? sanitize_text_field( $args['facebook_params']['currency'] ) : null,
				'content_name'     => ! empty( $args['facebook_params']['content_name'] ) ? sanitize_text_field( $args['facebook_params']['content_name'] ) : null,
				'content_ids'      => ! empty( $args['facebook_params']['content_ids'] ) ? sanitize_text_field( $args['facebook_params']['content_ids'] ) : null,
				'content_type'     => ! empty( $args['facebook_params']['content_type'] ) ? sanitize_text_field( $args['facebook_params']['content_type'] ) : null,
				'content_category' => ! empty( $args['facebook_params']['content_category'] ) ? sanitize_text_field( $args['facebook_params']['content_category'] ) : null,
				'num_items'        => ! empty( $args['facebook_params']['num_items'] ) ? (int) $args['facebook_params']['num_items'] : null,
				'order_id'         => ! empty( $args['facebook_params']['order_id'] ) ? sanitize_text_field( $args['facebook_params']['order_id'] ) : null,
				'search_string'    => ! empty( $args['facebook_params']['search_string'] ) ? sanitize_text_field( $args['facebook_params']['search_string'] ) : null,
				'status'           => ! empty( $args['facebook_params']['status'] ) ? sanitize_text_field( $args['facebook_params']['status'] ) : null,
				'predicted_ltv'    => ! empty( $args['facebook_params']['predicted_ltv'] ) ? sanitize_text_field( $args['facebook_params']['predicted_ltv'] ) : null,
			);

			// custom currency
			if ( $this->data['facebook_params']['currency'] == 'custom' && ! empty( $args['facebook_params']['custom_currency'] )) {
				$this->data['facebook_params']['custom_currency'] = sanitize_text_field( $args['facebook_params']['custom_currency'] );
			} else {
				$this->data['facebook_params']['custom_currency'] = null;
			}

		} else {
			
			$this->data['facebook_params'] = array(
				'value'            => null,
				'currency'         => null,
				'custom_currency'  => null,
				'content_name'     => null,
				'content_ids'      => null,
				'content_type'     => null,
				'content_category' => null,
				'num_items'        => null,
				'order_id'         => null,
				'search_string'    => null,
				'status'           => null,
				'predicted_ltv'    => null,
			);

		}

		// reset old custom params
		$this->data['facebook_custom_params'] = array();

		// custom params
		if ( $this->facebook_params_enabled && isset( $args['facebook_custom_params'] ) ) {

			foreach ( $args['facebook_custom_params'] as $custom_param ) {

				if ( ! empty( $custom_param['name'] ) && ! empty( $custom_param['value'] ) ) {

					$this->data['facebook_custom_params'][] = array(
						'name'  => sanitize_text_field( $custom_param['name'] ),
						'value' => sanitize_text_field( $custom_param['value'] ),
					);

				}

			}

		}

		/**
		 * PINTEREST
		 */
		
		$pinterest_event_types = array(
			'pagevisit',
			'viewcategory',
			'search',
			'addtocart',
			'checkout',
			'watchvideo',
			'signup',
			'lead',
			'custom',
			'CustomEvent',
		);
		
		// enabled
		$this->data['pinterest_enabled'] = isset( $args['pinterest_enabled'] ) && $args['pinterest_enabled'] ? true
			: false;
		
		// event type
		$this->data['pinterest_event_type'] = isset( $args['pinterest_event_type'] ) && in_array( $args['pinterest_event_type'],
			$pinterest_event_types )
			? sanitize_text_field( $args['pinterest_event_type'] )
			: 'pagevisit';
		
		// custom event type
		$this->data['pinterest_custom_event_type'] = $this->pinterest_event_type == 'CustomEvent' && ! empty( $args['pinterest_custom_event_type'] )
			? sanitizeKey( $args['pinterest_custom_event_type'] )
			: null;
		
		// params enabled
		$this->data['pinterest_params_enabled'] = isset( $args['pinterest_params_enabled'] ) && $args['pinterest_params_enabled']
			? true : false;
		
		// reset old custom params
		$this->data['pinterest_custom_params'] = array();
		
		// custom params
		if ( $this->pinterest_params_enabled && isset( $args['pinterest_custom_params'] ) ) {
			
			foreach ( $args['pinterest_custom_params'] as $custom_param ) {
				
				if ( ! empty( $custom_param['name'] ) && ! empty( $custom_param['value'] ) ) {
					
					$this->data['pinterest_custom_params'][] = array(
						'name'  => sanitize_text_field( $custom_param['name'] ),
						'value' => sanitize_text_field( $custom_param['value'] ),
					);
					
				}
				
			}
			
		}

		/**
		 * GOOGLE ANALYTICS
		 */

		$this->data['ga_enabled'] = isset( $args['ga_enabled'] ) && $args['ga_enabled'] ? true : false;
		
		$ga_event_actions = array(
			'_custom',
			'add_payment_info',
			'add_to_cart',
			'add_to_wishlist',
			'begin_checkout',
			'checkout_progress',
			'generate_lead',
			'login',
			'purchase',
			'refund',
			'remove_from_cart',
			'search',
			'select_content',
			'set_checkout_option',
			'share',
			'sign_up',
			'view_item',
			'view_item_list',
			'view_promotion',
			'view_search_results',
		);

		// event action
		$this->data['ga_event_action'] = isset( $args['ga_event_action'] ) && in_array( $args['ga_event_action'], $ga_event_actions )
			? sanitize_text_field( $args['ga_event_action'] )
			: 'view_item';

		// custom event type
		$this->data['ga_custom_event_action'] = $this->ga_event_action == '_custom' && !empty( $args['ga_custom_event_action'] )
			? sanitizeKey( $args['ga_custom_event_action'] )
			: null;

		$this->data['ga_event_category']  = ! empty( $args['ga_event_category'] ) ? sanitize_text_field( $args['ga_event_category'] ) : null;
		$this->data['ga_event_label']     = ! empty( $args['ga_event_label'] ) ? sanitize_text_field( $args['ga_event_label'] ) : null;
		$this->data['ga_event_value']     = ! empty( $args['ga_event_value'] ) ? sanitize_text_field( $args['ga_event_value'] ) : null;
		$this->data['ga_non_interactive'] = isset( $args['ga_non_interactive'] ) && $args['ga_non_interactive'] ? true : false;
		
		/**
		 * GOOGLE ADS
		 */
		
		$this->data['google_ads_enabled'] = isset( $args['google_ads_enabled'] ) && $args['google_ads_enabled'] ? true : false;
		
		// conversion id
		$this->data['google_ads_conversion_id'] = isset( $args['google_ads_conversion_id'] ) && in_array( $args['google_ads_conversion_id'],
				Ads()->getPixelIDs() ) ? $args['google_ads_conversion_id'] : '_all';

		$sanitizeGoogleAdsConversionLabel = function ($label) {
            return wp_kses_post( trim( stripslashes( $label ) ) );
        };
		
		// conversion label
		$this->data['google_ads_conversion_label'] = ! empty( $args['google_ads_conversion_label'] )
			? $sanitizeGoogleAdsConversionLabel( $args['google_ads_conversion_label'] )
			: null;

		$google_ads_event_actions = array(
			'_custom',
			'add_payment_info',
			'add_to_cart',
			'add_to_wishlist',
			'begin_checkout',
			'checkout_progress',
			'conversion',
			'generate_lead',
			'login',
			'purchase',
			'refund',
			'remove_from_cart',
			'search',
			'select_content',
			'set_checkout_option',
			'share',
			'sign_up',
			'view_item',
			'view_item_list',
			'view_promotion',
			'view_search_results',
		);

		// event action
		$this->data['google_ads_event_action'] = isset( $args['google_ads_event_action'] ) && in_array( $args['google_ads_event_action'],
			$google_ads_event_actions )
			? $args['google_ads_event_action']
			: 'conversion';

		// custom event type
		$this->data['google_ads_custom_event_action'] = $this->google_ads_event_action == '_custom' && ! empty( $args['google_ads_custom_event_action'] )
			? sanitizeKey( $args['google_ads_custom_event_action'] )
			: null;

		// default params
		$this->data['google_ads_event_category'] = ! empty( $args['google_ads_event_category'] )
			? sanitize_text_field( $args['google_ads_event_category'] ) : null;
		$this->data['google_ads_event_label'] = ! empty( $args['google_ads_event_label'] )
			? sanitize_text_field( $args['google_ads_event_label'] ) : null;
		$this->data['google_ads_event_value'] = ! empty( $args['google_ads_event_value'] )
			? sanitize_text_field( $args['google_ads_event_value'] ) : null;
		
		// reset old custom params
		$this->data['google_ads_custom_params'] = array();
		
		// custom params
		if ( isset( $args['google_ads_custom_params'] ) ) {
			
			foreach ( $args['google_ads_custom_params'] as $custom_param ) {
				
				if ( ! empty( $custom_param['name'] ) && ! empty( $custom_param['value'] ) ) {
					
					$this->data['google_ads_custom_params'][] = array(
						'name'  => sanitizeKey( $custom_param['name'] ),
						'value' => sanitize_text_field( $custom_param['value'] ),
					);
					
				}
				
			}
			
		}

        /**
         * BING
         */

        $this->data['bing_enabled'] = isset($args['bing_enabled']) && $args['bing_enabled'] ? true : false;
        $this->data['bing_event_action'] = !empty($args['bing_event_action']) ? sanitize_text_field($args['bing_event_action']) : null;
        $this->data['bing_event_category'] = !empty($args['bing_event_category']) ? sanitize_text_field($args['bing_event_category']) : null;
        $this->data['bing_event_label'] = !empty($args['bing_event_label']) ? sanitize_text_field($args['bing_event_label']) : null;
        $this->data['bing_event_value'] = !empty($args['bing_event_value']) ? sanitize_text_field($args['bing_event_value']) : null;
		
		update_post_meta( $this->post_id, '_pys_event_data', $this->data );

	}

	public function enable() {

		$this->enabled = true;
		update_post_meta( $this->post_id, '_pys_event_state', 'active' );

	}

	public function disable() {

		$this->enabled = false;
		update_post_meta( $this->post_id, '_pys_event_state', 'paused' );

	}

	/**
	 * @return int
	 */
	public function getPostId() {
	    return $this->post_id;
    }

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	public function isEnabled() {
		return $this->enabled;
	}

	public function getTriggerType() {
		return $this->trigger_type;
	}

	public function getDelay() {
		return $this->delay;
	}

	public function hasTimeWindow() {
	    return $this->enable_time_window;
    }

    public function getTimeWindow() {
        return $this->time_window;
    }

	/**
	 * @return array
	 */
	public function getPageVisitTriggers() {
		return $this->trigger_type == 'page_visit' ? $this->triggers : array();
	}

	/**
	 * @return array
	 */
	public function getURLClickTriggers() {
		return $this->trigger_type == 'url_click' ? $this->triggers : array();
	}

	/**
	 * @return array
	 */
	public function getCSSClickTriggers() {
		return $this->trigger_type == 'css_click' ? $this->triggers : array();
	}

	/**
	 * @return array
	 */
	public function getCSSMouseOverTriggers() {
		return $this->trigger_type == 'css_mouseover' ? $this->triggers : array();
	}

	/**
	 * @return array
	 */
	public function getScrollPosTriggers() {
		return $this->trigger_type == 'scroll_pos' ? $this->triggers : array();
	}

	/**
	 * @return array
	 */
	public function getURLFilters() {
		return in_array( $this->trigger_type, array( 'url_click', 'css_click', 'css_mouseover', 'scroll_pos' ) )
			? $this->url_filters
			: array();
	}
	
	public function isFacebookEnabled() {
		return (bool) $this->facebook_enabled;
	}
	
	public function getFacebookEventType() {
		return $this->facebook_event_type == 'CustomEvent' ? $this->facebook_custom_event_type : $this->facebook_event_type;
	}
	
	public function isFacebookParamsEnabled() {
		return (bool) $this->facebook_params_enabled;
	}
	
	public function getFacebookParam( $key ) {
		return isset( $this->facebook_params[ $key ] ) ? $this->facebook_params[ $key ] : null;
	}
	
	public function getFacebookParams() {
		return $this->facebook_params_enabled ? $this->facebook_params : array();
	}
	
	public function getFacebookCustomParams() {
		return $this->facebook_params_enabled ? $this->facebook_custom_params : array();
	}
	
	public function isPinterestEnabled() {
		return (bool) $this->pinterest_enabled;
	}
	
	public function getPinterestEventType() {
		return $this->pinterest_event_type == 'CustomEvent'
			? $this->pinterest_custom_event_type
			: $this->pinterest_event_type;
	}
	
	public function isPinterestParamsEnabled() {
		return (bool) $this->pinterest_params_enabled;
	}
	
	public function getPinterestCustomParams() {
		return $this->pinterest_params_enabled ? $this->pinterest_custom_params : array();
	}

	public function isGoogleAnalyticsEnabled() {
		return (bool) $this->ga_enabled;
	}

	public function getGoogleAnalyticsAction() {
		return $this->ga_event_action == '_custom' ? $this->ga_custom_event_action : $this->ga_event_action;
	}
	
	public function isGoogleAdsEnabled() {
		return (bool) $this->google_ads_enabled;
	}
	
	public function getGoogleAdsAction() {
		return $this->google_ads_event_action == '_custom' ? $this->google_ads_custom_event_action : $this->google_ads_event_action;
	}
	
	public function getGoogleAdsCustomParams() {
		return (array) $this->google_ads_custom_params;
	}

    public function isBingEnabled() {
        return (bool) $this->bing_enabled;
    }
}