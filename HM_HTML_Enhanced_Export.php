<?php

if (!class_exists('HM_HTML_Enhanced_Export')) {
	class HM_HTML_Enhanced_Export {

		private $handle, $inTable=false, $hasTHead=false, $inTBody=false;
		
		public function __construct($handle, $css='') {
			$this->handle = $handle;
			@header('Content-Type: text/html; charset=utf-8');
			fwrite($this->handle, '
				<html>
					<head>
						<style type="text/css">
							'.wp_strip_all_tags($css).'
						</style>
						<link rel="stylesheet" type="text/css" href="'.plugins_url('js/datatables/datatables.min.css', __FILE__).'" />
						<script type="text/javascript" src="'.plugins_url('js/datatables/datatables.min.js', __FILE__).'"></script>
						<script>$(document).ready(function() { $(\'table\').DataTable({pageLength:25,colReorder:true,fixedHeader:true,responsive:true,select:true}); });</script>
					</head>
					<body>
			');
		}
		
		public function close() {
			if ($this->inTBody)
				fwrite($this->handle, '</tbody>');
			fwrite($this->handle, '</table></body></html>');
		}
		
		public function putTitle($title) {
			if ($this->inTable) {
				return false;
			}
			fwrite($this->handle, '<h1>'.esc_html($title).'</h1>');
		}
		
		public function putRow($data, $header=false, $footer=false) {
			if (!$this->inTable) {
				fwrite($this->handle, '<table>');
				$this->inTable = true;
			}
			if ($header && !$this->hasTHead) {
				fwrite($this->handle, '<thead>');
			} else if ($footer) {
				if ($this->inTBody) {
					fwrite($this->handle, '</tbody>');
					$this->inTBody = false;
				}
				fwrite($this->handle, '<tfoot>');
			} else {
				if (!$this->hasTHead) {
					$columnNames = array();
					for ($i = 1; $i <= count($data); ++$i)
						$columnNames[] = 'Column '.$i;
					$this->putRow($columnNames, true);
				}
				if (!$this->inTBody) {
					fwrite($this->handle, '<tbody>');
					$this->inTBody = true;
				}
			}
			
			fwrite($this->handle, '<tr>');
			foreach ($data as $field) {
				fwrite($this->handle, ($header ? '<th>' : '<td>').htmlspecialchars($field).($header ? '</th>' : '</td>'));
			}
			fwrite($this->handle, '</tr>');
			
			if ($header && !$this->hasTHead) {
				fwrite($this->handle, '</thead>');
				$this->hasTHead = true;
			} else if ($footer) {
				fwrite($this->handle, '</tfoot>');
			}
		}
		

	}
}


?>