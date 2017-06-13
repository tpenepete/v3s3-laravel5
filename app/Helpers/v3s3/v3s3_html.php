<?php

namespace App\Helpers\v3s3;

class v3s3_html {
	static function simple_table($rows) {
		$keys = array_keys(reset($rows));
		$th = "\t\t\t".'<th>'.implode('</th>'."\n\t\t\t".'<th>', $keys).'</th>';
		$tr = array();
		foreach ($rows as $row) {
			$td = "\t\t\t".'<td>'."\n\t\t\t\t".implode("\n\t\t\t".'</td>'."\n\t\t\t".'<td>'."\n\t\t\t\t", $row)."\n\t\t\t".'</td>';
			$tr[] = <<< EOT
		<tr>
$td
		</tr>
EOT;
		}
		$tr = implode("\n", $tr);
		$html = <<< EOT
<table>
	<thead>
		<tr>
$th
		</tr>
	</thead>
	<tbody>
$tr
	</tbody>
</table>
EOT;
		return $html;
	}
}