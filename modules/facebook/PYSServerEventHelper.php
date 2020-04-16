<?php
namespace PixelYourSite;

use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\UserData;
use PixelYourSite;

defined('ABSPATH') or die('Direct access not allowed');

class ServerEventHelper {
    public static function newEvent($event_name,$eventId) {
        $user_data = ServerEventHelper::getUserData()
            ->setClientIpAddress(self::getIpAddress())
            ->setClientUserAgent(self::getHttpUserAgent())
            ->setFbp(self::getFbp())
            ->setFbc(self::getFbc());

        $event = (new Event())
            ->setEventName($event_name)
            ->setEventTime(time())
            ->setEventId($eventId)
            ->setEventSourceUrl(self::getRequestUri())
            ->setUserData($user_data);

        return $event;
    }

    private static function getIpAddress() {
        $ip_address = null;

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }

        return $ip_address;
    }

    private static function getHttpUserAgent() {
        $user_agent = null;

        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
        }

        return $user_agent;
    }

    private static function getRequestUri() {
        $request_uri = null;

        if (!empty($_SERVER['REQUEST_URI'])) {
            $request_uri = $_SERVER['REQUEST_URI'];
        }

        return $request_uri;
    }

    private static function getFbp() {
        $fbp = null;

        if (!empty($_COOKIE['_fbp'])) {
            $fbp = $_COOKIE['_fbp'];
        }

        return $fbp;
    }

    private static function getFbc() {
        $fbc = null;

        if (!empty($_COOKIE['_fbc'])) {
            $fbc = $_COOKIE['_fbc'];
        }

        return $fbc;
    }

    private static function getUserData() {
        $userData = new UserData();

        /**
         * Add purchase WooCommerce Advanced Matching params
         */

        if ( is_order_received_page() && isset( $_REQUEST['key'] ) ) {

            $order_key = sanitize_key($_REQUEST['key']);
            $order_id = wc_get_order_id_by_order_key( $order_key );
            $order    = wc_get_order( $order_id );

            if ( $order ) {

                if ( PixelYourSite\isWooCommerceVersionGte( '3.0.0' ) ) {

                    $userData->setEmail($order->get_billing_email());
                    $userData->setPhone($order->get_billing_phone());
                    $userData->setFirstName($order->get_billing_first_name());
                    $userData->setLastName($order->get_billing_last_name());
                    $userData->setCity($order->get_billing_city());
                    $userData->setState($order->get_billing_state());

                } else {
                    $userData->setEmail($order->billing_email);
                    $userData->setPhone($order->billing_phone);
                    $userData->setFirstName($order->billing_first_name);
                    $userData->setLastName($order->billing_last_name);
                    $userData->setCity($order->billing_city);
                    $userData->setState($order->billing_state);
                }
            } else {
                return ServerEventHelper::getRegularUserData();
            }

        } else {
            return ServerEventHelper::getRegularUserData();
        }

        return $userData;
    }

    private static function getRegularUserData() {
        $user = wp_get_current_user();
        $userData = new UserData();
        if ( $user->ID ) {
            // get user regular data
            $userData->setFirstName($user->get( 'user_firstname' ));
            $userData->setLastName($user->get( 'user_lastname' ));
            $userData->setEmail($user->get( 'user_email' ));

            /**
             * Add common WooCommerce Advanced Matching params
             */
            if ( PixelYourSite\isWooCommerceActive() && PixelYourSite\PYS()->getOption( 'woo_enabled' ) ) {
                // if first name is not set in regular wp user meta
                if (empty($userData->getFirstName())) {
                    $userData->setFirstName($user->get('billing_first_name'));
                }

                // if last name is not set in regular wp user meta
                if (empty($userData->getLastName())) {
                    $userData->setLastName($user->get('billing_last_name'));
                }

                $userData->setPhone($user->get('billing_phone'));
                $userData->setCity($user->get('billing_city'));
                $userData->setState($user->get('billing_state'));
            }
        }
        return $userData;
    }

}