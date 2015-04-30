<?php

/**
 * Back end routines to add/delete departments, invoked by manage.php
 * @author Avin E.M
 */

require_once('functions.php');
if(!sessionCheck('level','dean'))
  die();
require_once('connect_db.php');
rangeCheck('dept_code',2,5,false);
$dept_code = strtoupper($_POST['dept_code']);
if(valueCheck('action','add'))
{
  rangeCheck('dName',6,50);
  try{
    $query = $db->prepare('INSERT INTO depts(dept_code,dept_name) values (?,?)');
    $query->execute([$dept_code,$_POST['dName']]);
    postResponse("addOpt","Deparment Added",[$_POST['dName'],$dept_code]);         
  }
  catch(PDOException $e)
  {
    if($e->errorInfo[0]==23000)
      postResponse("error","Deparment already exists");
    else
      postResponse("error",$e->errorInfo[2]);
  }
  
}
elseif(valueCheck('action','delete'))
{
  $query = $db->prepare('DELETE FROM depts where dept_code =?');
  $query->execute([$dept_code]);
  postResponse("removeOpt","Deparment deleted");
}
?>