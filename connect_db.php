<?php

/**
 * Initiates database connections
 * @author Avin E.M
 */

if (file_exists('config.php')) {
  include('config.php');
}
if(isset($config))
{
  try
  {
    // Establish mysql connection
    $current = null;
    $db = new PDO("mysql:dbname={$config['db_name']};host={$config['db_host']}", $config['db_user'], $config['db_pswd'],
          [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_FOUND_ROWS => true
          ]);
    if(!empty($_GET['table']))
    {
      if(sessionCheck('level','dean'))
      {
        $query = $db->prepare("INSERT IGNORE into timetables(table_name) VALUES(?)");
        $query->execute([$_GET['table']]);
      }
      $query = $db->prepare("SELECT * FROM timetables where table_name=?");
      $query->execute([$_GET['table']]);
      $current = $query->fetch(PDO::FETCH_ASSOC);
      $_SESSION['timetable'] = $current;
    }
    else if(empty($_SESSION['timetable']) || empty($_SESSION['timetable']['table_name']))
    {      
      $current = $db->query("SELECT * FROM timetables where table_name=(SELECT table_name from timetables where active=1)")->fetch(PDO::FETCH_ASSOC);
      if(!$current)
      {
        $current['start_hr'] = '08';
        $current['slots'] = 0;
        $current['duration'] = 90;
        $current['days'] = 0;
        $current['start_mer'] = 'AM';
        $current['start_min'] ='30';
        $current['allowConflicts'] = 0;
        $current['frozen'] = 0;
        $current['table_name'] = '';
        $current['active'] = 0;
      }
      $_SESSION['timetable'] = $current;
    }
    else
    {
      $query = $db->prepare("SELECT * FROM timetables where table_name=?");
      $query->execute([$_SESSION['timetable']['table_name']]);
      $current = $query->fetch(PDO::FETCH_ASSOC);
      $_SESSION['timetable'] = $current;
    }
  }
  catch(PDOException $e){
    die($e->getMessage()."\n");
  }
}
else
{
  header("Location: ./setup.php");
  die();
}

?>