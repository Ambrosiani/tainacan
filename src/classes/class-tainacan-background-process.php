<?php

namespace Tainacan;

/**
 * Abstract Tainacan_Background_Process class.
 *
 * Uses https://github.com/A5hleyRich/wp-background-processing to handle DB
 * updates in the background.
 *
 * @package WooCommerce/Classes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_Async_Request', false ) ) {
	include_once TAINACAN_CLASSES_DIR . '/lib/wp-async-request.php';
}

if ( ! class_exists( 'WP_Background_Process', false ) ) {
	include_once TAINACAN_CLASSES_DIR . '/lib/wp-background-process.php';
}

/**
 * Tainacan_Background_Process class.
 */
abstract class Background_Process extends \WP_Background_Process {

	/**
	 * Table name where the queue is stored
	 * @var string
	 */
	protected $table = '';

	/**
	 * ID of the process in the database
	 * @var false|int
	 */
	public $ID = false;
	
	/**
	 * Prefix
	 *
	 * (default value: 'wp')
	 *
	 * @var string
	 * @access protected
	 */
	protected $prefix = 'tnc-bg';
	
	/**
	 * Action
	 *
	 * (default value: 'background_process')
	 *
	 * @var string
	 * @access protected
	 */
	protected $action = 'process';
	
	/**
	 * Initiate new background process
	 */
	public function __construct() {
		parent::__construct();
		global $wpdb;
		$this->table = $wpdb->prefix . 'tnc_bg_process';
	}

	public function get_id() {
		return $this->ID;
	}
	
	
	/**
	 * Save queue
	 *
	 * @return $this
	 */
	public function save($priority = 10) {

		if ( ! empty( $this->data ) ) {
			global $wpdb;
			$wpdb->insert(
				$this->table, 
				[
					'data' => maybe_serialize($this->data),
					'user_id' => get_current_user_id(),
					'priority' => $priority,
					'action' => $this->action,
					'queued_on' => date('Y-m-d H:i:s')
				]
			);
			$this->ID = $wpdb->insert_id;
		}

		return $this;
	}
	
	/**
	 * Update queue
	 *
	 * @param string $key Key.
	 * @param array  $data Data.
	 *
	 * @return $this
	 */
	public function update( $key, $batch ) {
		$data = $batch->data;
		if ( ! empty( $data ) ) {
			global $wpdb;
			$wpdb->update(
				$this->table, 
				[
					'data' => maybe_serialize($data),
					'processed_last' => date('Y-m-d H:i:s'),
					'progress_label' => $batch->progress_label,
					'progress_value' => $batch->progress_value
				],
				['ID' => $key]
			);
		}

		return $this;
	}
	
	/**
	 * Mark a process as done
	 *
	 * @param string $key Key.
	 * @param array  $data Data.
	 *
	 * @return $this
	 */
	public function close( $key ) {
		global $wpdb;

		$wpdb->update(
			$this->table, 
			['done' => 1],
			['ID' => $key],
			['%d']
		);

		return $this;
	}

	/**
	 * Delete queue
	 *
	 * @param string $key Key.
	 *
	 * @return $this
	 */
	public function delete( $key ) {
		global $wpdb;
		$wpdb->delete(
			$this->table, 
			['ID' => $key]
		);
		return $this;
	}
	
	/**
	 * Is queue empty
	 *
	 * @return bool
	 */
	protected function is_queue_empty() {
		global $wpdb;

		$table  = $this->table;

		$count = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*)
			FROM {$table}
			WHERE action = %s AND
			done = false
		", $this->action ) );

		return ( $count > 0 ) ? false : true;
	}
	
	/**
	 * Get batch
	 *
	 * @return stdClass Return the first batch from the queue
	 */
	protected function get_batch() {
		global $wpdb;

		$table        = $this->table;

		$query = $wpdb->get_row( $wpdb->prepare( "
			SELECT *
			FROM {$table}
			WHERE action = %s
			AND done = FALSE
			ORDER BY priority DESC, queued_on ASC
			LIMIT 1
		", $this->action ) );

		$batch       = new \stdClass();
		$batch->key  = $query->ID;
		$batch->data = maybe_unserialize( $query->data );

		return $batch;
	}

	/**
	 * Handle
	 *
	 * Pass each queue item to the task handler, while remaining
	 * within server memory and time limit constraints.
	 *
	 * Tainacan comments: This is where we changed the mos from otiginal class.
	 * Each batch is a single array of data. There is no queue inside a batch.
	 */
	protected function handle() {
		$this->lock_process();
		//error_log('new request');
		do {
			$batch = $this->get_batch();
			
			// TODO: find a way to catch and log PHP errors as
			try {
				$task = $this->task( $batch );
			} catch (\Exception $e) {
				// TODO: Add Stacktrace
				$this->write_error_log($batch->key, ['Fatal Error: ' . $e->getMessage()]);
				$this->write_error_log($batch->key, ['Process aborted']);
				$task = false;
			}
			

			// Update or close current batch.
			if ( false !== $task )  {
				$this->update( $batch->key, $task );
			} else {
				$this->close( $batch->key );
			}
		} while ( ! $this->time_exceeded() && ! $this->memory_exceeded() && ! $this->is_queue_empty() );

		$this->unlock_process();

		// Start next batch or complete process.
		if ( ! $this->is_queue_empty() ) {
			$this->dispatch();
		} else {
			$this->complete();
			$this->write_log($batch->key, ['Process Finished']);
		}

		wp_die();
	}

	/**
	 * Delete all batches.
	 *
	 * @return WC_Background_Process
	 */
	public function delete_all_batches() {
		global $wpdb;

		$table  = $this->table;

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE done = FALSE AND action LIKE %s", $this->action ) ); // @codingStandardsIgnoreLine.

		return $this;
	}

	/**
	 * Kill process.
	 *
	 * Stop processing queue items, clear cronjob and delete all batches.
	 */
	public function kill_process() {
		if ( ! $this->is_queue_empty() ) {
			$this->delete_all_batches();
			wp_clear_scheduled_hook( $this->cron_hook_identifier );
		}
	}
	
	
	
	/**
	 * LOG
	 */
	
	protected function write_log_to_file($key, array $log, $type = '') {
		
		$upload_dir = wp_upload_dir();
		$upload_dir = trailingslashit( $upload_dir['basedir'] );
		$logs_folder = $upload_dir . 'tainacan';
		
		if (sizeof($log) < 1) {
			return false;
		}
		
		if (!is_dir($logs_folder)) {
			if (!mkdir($logs_folder)) {
				return false;
			}
		}
		
		$suffix = $type ? '-' . $type : '';
		
		$filename = 'bg-' . $this->action . '-' . $key . $suffix . '.log';
		
		$filepath = $logs_folder . '/' . $filename;
		
		file_put_contents($filepath, $this->recursive_stingify_log_array($log), FILE_APPEND);
		
		//$fh = fopen($filepath, 'a');
		//
		//foreach ($log as $message) {
		//	fwrite($fh, $message."\n");
		//}
		//
		//fclose($fh);
		
	}
	
	protected function write_log($key, $log) {
		$this->write_log_to_file($key, $log);
	}
	protected function write_error_log($key, $log) {
		$this->write_log_to_file($key, $log, 'error');
	}
	
	private function recursive_stingify_log_array(array $log, $break = true) {
		$return = '';
		foreach ($log as $k => $l) {
			if (!is_numeric($k)) {
				$return .= $k . ': ';
			}
			if (is_array($l)) {
				//$return .= $this->recursive_stingify_log_array($l, false);
				$return .= print_r($l, true);
			} elseif (is_string($l)) {
				$return .= $l;
			}
			$return .="\n";
			//$return .= $break ? "\n" : ', ';
			
		}
		
		return $return;
		
	}
	
}
