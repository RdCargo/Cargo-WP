<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$cslfw_cargo_autoloader = true;

require CSLFW_PATH . '/includes/CSLFW_Helpers.php';
require CSLFW_PATH . '/includes/CargoApi/Helpers.php';
require CSLFW_PATH . '/includes/CargoApi/CSLFW_Order.php';
require CSLFW_PATH . '/includes/CargoApi/Cargo.php';
require CSLFW_PATH . '/includes/CargoApi/CargoAPIV2.php';
require CSLFW_PATH . '/includes/CargoApi/Webhook.php';
require CSLFW_PATH . '/includes/CSLFW_ShipmentsPage.php';
require CSLFW_PATH . '/includes/cslfw-logs.php';
require CSLFW_PATH . '/includes/cslfw-contact.php';
require CSLFW_PATH . '/includes/cslfw-settings.php';
require CSLFW_PATH . '/includes/cslfw-admin.php';
require CSLFW_PATH . '/includes/cslfw-front.php';
require CSLFW_PATH . '/includes/cslfw-cargo.php';
// processes
require CSLFW_PATH . '/includes/processes/cslfw-cargo-job.php';
require CSLFW_PATH . '/includes/processes/cslfw-cargo-process-shipment-create.php';
//include_once __DIR__ . '/blocks/cargo-shipping.php';

/**
 * @param CSLFW_Cargo_Job $job
 * @param int $delay
 * @return false|int
 */
function cslfw_push_cargo_job(CSLFW_Cargo_Job $job, $delay = 0 )  {
    $logs = new CSLFW_Logs();
    $current_page = isset($job->current_page) && $job->current_page >= 0 ? $job->current_page : false;
    $job_id = isset($job->id) ? $job->id : ($current_page ? $job->current_page : get_class($job));
    $message = ($job_id != get_class($job)) ? ' :: '. (isset($job->current_page) ? 'page ' : 'obj_id ') . $job_id : '';
    $attempts = $job->get_attempts() > 0 ? ' attempt:' . $job->get_attempts() : '';

    if ($job->get_attempts() <= 5) {
        $existing_actions =  function_exists('as_get_scheduled_actions') ? as_get_scheduled_actions(array(
                'hook' => get_class($job),
                'status' => ActionScheduler_Store::STATUS_PENDING,
                'args' => array(
                    'obj_id' => isset($job->id) ? $job->id : null),
                'group' => 'cslfw-cargo-shipping-location'
            )
        ) : null;

        if (!empty($existing_actions)) {
            try {
                as_unschedule_action(get_class($job), array('obj_id' => $job->id), 'cslfw-cargo-shipping-location');
            } catch (Exception $e) {}
        }

        $action_args = $job->toArgsArray();

        if ($current_page !== false) {
            $action_args['page'] = $current_page;
        }

        $action = as_schedule_single_action( strtotime( '+'.$delay.' seconds' ), get_class($job), $action_args, "cslfw-cargo-shipping-location");

        if (!empty($existing_actions)) {
            $logs->add_debug_message('action_scheduler.reschedule_job::' . get_class($job) . ($delay > 0 ? ' restarts in '.$delay. ' seconds' : ' re-queued' ) . $message . $attempts);
        } else {
            $logs->add_debug_message('action_scheduler.queue_job::' . get_class($job) . ($delay > 0 ? ' starts in '.$delay. ' seconds' : ' queued' ) . $message . $attempts);
        }

        return $action;
    } else {
        $job->set_attempts(0);
        $logs->add_log_message('action_scheduler.fail_job::' . get_class($job) . ' cancelled. Too many attempts' . $message . $attempts);
        return false;
    }
}

/**
 * @param CSLFW_Cargo_Job $job
 * @param int $delay
 */
function cslfw_handle_or_queue(CSLFW_Cargo_Job $job, $delay = 0)
{
    $logs = new CSLFW_Logs();
    if ($job instanceof CSLFW_Cargo_Process_Shipment_Create && isset($job->id) && empty($job->gdpr_fields)) {
        // if this is a order process already queued - just skip this
        if (get_site_transient("cslfw_order_shipment_being_processed_{$job->id}") == true) {
            $logs->add_debug_message("queue:: Not queuing up order {$job->id} because it's already queued");
            return;
        }
        // tell the system the order is already queued for processing in this saving process - and we don't need to process it again.
        set_site_transient("cslfw_order_shipment_being_processed_{$job->id}", true, 30);
    }

    $as_job_id = cslfw_push_cargo_job($job, $delay);

    if (!is_int($as_job_id)) {
        $logs->add_debug_message('action_scheduler.queue_fail::' . get_class($job) .' FAILED :: as_job_id: '.$as_job_id);
    }
}


/**
 * @param $key
 * @param $value
 * @param int $seconds
 * @return bool
 */
function cslfw_cargo_set_transient($key, $value, $seconds = 60) {
    cslfw_cargo_delete_transient($key);
    return set_site_transient("mailchimp-woocommerce.{$key}", array(
        'value' => $value,
        'expires' => time()+$seconds,
    ), $seconds);
}

/**
 * @param $key
 * @return bool
 */
function cslfw_cargo_delete_transient($key) {
    return delete_site_transient("mailchimp-woocommerce.{$key}");
}
