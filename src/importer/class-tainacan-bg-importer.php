<?php 

namespace Tainacan;

class Background_Importer extends Background_Process {
	
	/**
	 * @var string
	 */
	protected $action = 'import';
	
	function task($batch) {

		$data = $batch->data;
		$key = $batch->key;
		
		$className = $data['class_name'];
		if (class_exists($className)) {
			$object = new $className($data);
			$runned = $object->run();
			
			$this->write_log($key, $object->get_log());
			$this->write_error_log($key, $object->get_error_log());
			
			if (true === $object->get_abort()) {
				throw new \Exception('Process aborted by Importer');
			}
			
			if (false === $runned) {
				return false;
			}
			
			$batch->progress_label = $object->get_progress_label();
			$batch->progress_value = $object->get_progress_value();
			
			$batch->data = $object->_to_Array(true);
			
			return $batch;
		}
		return false;
		
		
	}
	
	
}


 ?>