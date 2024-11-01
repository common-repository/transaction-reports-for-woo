<?php

if (!class_exists('HM_XLS_Export')) {
	class HM_XLS_Export {

		private $phpExcel, $sheet, $rowPointer;
		
		public function __construct() {
			include_once(dirname(__FILE__).'/lib/PHPExcel/Classes/PHPExcel.php');
			$this->phpExcel = new PHPExcel();
			$this->sheet = $this->phpExcel->getActiveSheet();
			$this->rowPointer = 1;
		}
		
		public function putTitle($title) {
			if ($this->rowPointer != 1) {
				return false;
			}
			$this->sheet->setCellValueByColumnAndRow(0, 1, $title);
			$this->sheet->getStyleByColumnAndRow(0, 1)->getFont()->setBold(true)->setSize(14);
			$this->rowPointer = 2;
		}
		
		public function putRow($data, $header=false, $footer=false) {
			foreach (array_values($data) as $col => $value) {
				if (!empty($value)) {
					$this->sheet->setCellValueByColumnAndRow($col, $this->rowPointer, $value);
					$this->sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
					if ($header)
						$this->sheet->getStyleByColumnAndRow($col, $this->rowPointer)->getFont()->setBold(true);
				}
			}
			++$this->rowPointer;
		}
		
		public function outputXLSX($path) {
			$writer = new PHPExcel_Writer_Excel2007($this->phpExcel);
			$writer->save($path);
		}
		
		public function outputXLS($path) {
			$writer = new PHPExcel_Writer_Excel5($this->phpExcel);
			$writer->save($path);
		}
	}
}

?>