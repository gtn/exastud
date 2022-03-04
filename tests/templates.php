<?php

require __DIR__.'/../inc.php';

echo '<h1>Templates</h1>';

$templates = \block_exastud\print_templates::get_all_template_configs();

echo '<table border="1" cellspacing="0">';
foreach ($templates as $template) {
	echo '<tr>';
	echo '<td><b>'.$template['name'];
	echo '<td>'.$template['file'];
	echo '<br/>';
	echo is_file(__DIR__.'/../templates/'.$template['file']).'.docx' ? 'ok' : 'NOT FOUND';
	echo '<td><pre>'.print_r($template['grades'], true);
	echo '<td><pre>'.print_r($template['inputs'], true);
}