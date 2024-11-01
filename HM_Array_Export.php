<?php

if (!class_exists('HM_Array_Export')) {
	class HM_Array_Export {

		private $dataArray;
		
		public function putRow($data, $header=false, $footer=false) {
			$this->dataArray[] = $data;
		}
		
		public function getData() {
			return $this->dataArray;
		}

	}
}


?>