<?php
namespace PixelYourSite;


defined('ABSPATH') or die('Direct access not allowed');


class PYSServerEventAsyncTask extends \WP_Async_Task {
    protected $action = 'pys_send_server_event';

    protected function prepare_data($data) {
        try {
            if (!empty($data)) {
                return array('data' => base64_encode(serialize($data)));
            }
        } catch (\Exception $ex) {
            error_log($ex);
        }

        return array();
    }

    protected function run_action() {
        try {
            $events = unserialize(base64_decode($_POST['data']));
            if (empty($events)) {
                return;
            }

            Facebook::sendServerRequest($events);
        }
        catch (\Exception $ex) {
            error_log($ex);
        }
    }
}