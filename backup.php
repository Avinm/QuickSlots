<?php

/**
 * Back end routines to generate/restore backups, invoked by dean.php
 * @author Avin E.M
 */

require_once('functions.php');
if(!sessionCheck('level','dean'))
  die();
require_once('connect_db.php');
if(valueCheck('action','backup'))
{
  header('Content-type: text/plain');
  header('Content-Disposition: attachment; filename=backup_' . date("H-i_d-m-Y") . '.sql');
  passthru("mysqldump --user={$config['db_user']} --password={$config['db_pswd']} --host={$config['db_host']} {$config['db_name']}");
}
else
{
  $snapshot = $_FILES['snapshot']['tmp_name'];
  try
  {
    $db->exec(file_get_contents($snapshot));
    unlink($snapshot);
    header("Location: dean.php?status=restoreComplete");
  } 
  catch(PDOException $e)
  {
    postResponse("error",$e->errorInfo[2]);
  }
}
?>
