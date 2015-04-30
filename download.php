<?php

/**
 * Back end routines that call PhantomJS to generate printable timetable snapshot images
 * @author Avin E.M
 */

require_once('functions.php');
if(!empty($_POST['filter']) && sessionCheck('logged_in'))
{
  $imgPath = 'tmp/print_' . time() . '.png';
  $phantom = 'phantomjs' .DIRECTORY_SEPARATOR. 'phantomjs';
  $basUrl = 'http://'.$_SERVER['SERVER_ADDR'].dirname($_SERVER['SCRIPT_NAME']);
  $printUrl = escapeshellarg($basUrl .'/?print=true&'. $_POST['filter']); // Serious vulnerability if not escaped
  exec($phantom . ' js/capture.js ' . $printUrl . ' ' . $imgPath);
  header('Content-Disposition: attachment; filename='.$_POST['filename'].'.png');
  header('Content-Type: '.mime_content_type($imgPath));
  header('Content-Transfer-Encoding: binary');
  header('Content-Length: '.filesize($imgPath));
  readfile($imgPath);
  unlink($imgPath);
}
?>
