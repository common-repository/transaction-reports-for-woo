<?php

if (!class_exists('HM_HTML_Export')) {
	class HM_HTML_Export {

		private $handle, $includeHtmlBodyTags, $inTable=false, $hasTHead=false, $inTBody=false;
		
		public function __construct($handle, $css='') {
			$this->handle = $handle;
			$this->includeHtmlBodyTags = !(headers_sent() || ob_get_contents());
			if ($this->includeHtmlBodyTags) {
				header('Content-Type: text/html; charset=utf-8');
				fwrite($this->handle, '
					<html>
						<head>
							<style type="text/css">
								'.wp_strip_all_tags($css).'
							</style>
						</head>
						<body>
				');
			}
		}
		
		public function close() {
			fwrite($this->handle, '</table>'.($this->includeHtmlBodyTags ? '</body></html>' : ''));
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