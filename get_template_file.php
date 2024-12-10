<?php

require __DIR__ . '/inc.php';

$filepath = required_param('template', PARAM_PATH);

// check access
// only for afmins
if (!block_exastud_is_siteadmin()) {
    echo 'Only for site admins!';
    exit;
}

// only for existing files in the only fixed folders
$exists = false;
if (file_exists(block_exastud_file_area_name('templates').$filepath)) {
    $exists = true;
    $realFilePath = block_exastud_file_area_name('templates').$filepath;
}
if (file_exists($CFG->dirroot.'/blocks/exastud/template/'.$filepath)) {
    $exists = true;
    $realFilePath = $CFG->dirroot.'/blocks/exastud/template/'.$filepath;
}
if (!$exists) {
    echo 'Only for known files!';
//    echo $CFG->dirroot.'/blocks/exastud/template/'.$filepath;
    exit;
}

// only for files from templates
$exists = false;
$allTemplates = block_exastud_get_template_files();
$tmpInfo = pathinfo($filepath);
$tempName = ($tmpInfo['dirname'] != '.' ? $tmpInfo['dirname'].'/' : '').$tmpInfo['filename'];
if (!array_key_exists($tempName, $allTemplates)) {
    echo 'Only for known templates!';
//    echo $tempName;
    exit;
}

if (!file_exists($realFilePath)) {
    print_error('filenotfound', 'error');
}

// Get the MIME type of the file
$mimeType = mime_content_type($realFilePath); // Requires PHP >= 5.3
if (!$mimeType) {
    $mimeType = 'application/octet-stream'; // Fallback to a generic binary stream
}

// Serve the file
header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . basename($realFilePath) . '"');
header('Content-Length: ' . filesize($realFilePath));
readfile($realFilePath);
exit;