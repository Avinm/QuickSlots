<?php

/**
 * Back end routines to add/delete courses, invoked by faculty.php
 * @author Avin E.M
 */

require_once('functions.php');
if(!sessionCheck('logged_in'))
  postResponse("error","Your session has expired, please login again");
require_once('connect_db.php');


rangeCheck('cId',2,20);
$cId = strtoupper($_POST['cId']);
if(!isset($_SESSION['faculty']))
  $_SESSION['faculty'] = $_SESSION['uName'];
if(!sessionCheck('level','faculty') && !empty($_GET['faculty']))
  $_SESSION['faculty'] = $_GET['faculty'];
if(valueCheck('action','add'))
{
  rangeCheck('cName',6,100);
  if(empty($_POST["allowConflict"]))
    $_POST["allowConflict"] = 0;
  try
  {
    $query = $db->prepare('INSERT INTO courses(course_Id,course_name,fac_id,allow_conflict) values (?,?,?,?)');
    $query->execute([$cId,$_POST['cName'],$_SESSION['faculty'],$_POST["allowConflict"]]);
    $query = $db->prepare('INSERT INTO allowed(course_Id,batch_name,batch_dept) values (?,?,?)');
    foreach ($_POST['batch'] as $batch) 
    {
      $batch = explode(" : ",$batch);
      $query->execute([$cId,$batch[0],$batch[1]]);      
    }
    postResponse("addOpt","Course Added",[$_POST['cName'],$cId]);  
  }
  catch(PDOException $e)
  {
    if($e->errorInfo[0]==23000)
      postResponse("error","Course ID already exists");
    else
      postResponse("error",$e->errorInfo[2]);
  }
}
elseif(valueCheck('action','delete'))
{
  $query = $db->prepare('DELETE FROM courses where course_id =? and fac_id =?');
  $query->execute([$_POST['cId'],$_SESSION['faculty']]);
  postResponse("removeOpt","Course deleted");
}

?>
