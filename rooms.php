<?php

/**
 * Back end routines to add/delete rooms, invoked by dean.php
 * @author Avin E.M
 */

require_once('functions.php');
require_once('connect_db.php');
if(!sessionCheck('level','dean'))
  die();
rangeCheck('room_name',2,25);
if(valueCheck('action','add'))
{
  rangeCheck('capacity',1,3);
  try{
    $query = $db->prepare('INSERT INTO rooms(room_name,capacity) values (?,?)');
    $query->execute([$_POST['room_name'],$_POST['capacity']]);
    postResponse("addOpt","Room Added",[$_POST['room_name'],$_POST['capacity']]);    
  }
  catch(PDOException $e)
  {
    if($e->errorInfo[0]==23000)
      postResponse("error","Room already exists");
    else
      postResponse("error",$e->errorInfo[2]);
  }
  
}
elseif(valueCheck('action','delete'))
{
  $query = $db->prepare('DELETE FROM rooms where room_name = ?');
  $query->execute([$_POST['room_name']]);
  postResponse("removeOpt","Room deleted");
}
?>