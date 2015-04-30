<?php

/**
 * Back end routines for user management, invoked by manage.php and setup.php
 * @author Avin E.M
 */

require_once ('functions.php');
require_once ('connect_db.php');

if($_POST)
{
  // if the user is not a dean, allow registration only if the system has no dean
  $newAdmin = 0;
  if(!sessionCheck('level','dean'))
  {
    $query = $db->query("SELECT count(*) FROM faculty where level='dean'"); // Check if the system has no admin configured yet
    if($query->fetch()[0])
      postResponse("error", "You are not authorized to register accounts.");
    $newAdmin = 1;
  }
  rangeCheck('uName', 3, 25, false);
  if(valueCheck('action', 'addUser'))
  {
    rangeCheck('fullName', 6, 50);
    rangeCheck('pswd', 8, 32, false);
  }
  $uName = strtolower($_POST['uName']);

  if(valueCheck('action','deleteFaculty'))
  {
    $db->prepare('DELETE FROM faculty where uName =?')->execute([$uName]);
    postResponse("removeOpt","Faculty deleted");
    die();
  }
  if(!empty($_POST['dept'])) //Register new faculty (and new department if required)
  {
    $dept_code = "";
    if(!empty($_POST['newDpt'])) // Register New department
    {
      rangeCheck('newDpt', 2, 5);
      rangeCheck('dept', 6, 50);
      $query = $db->prepare('INSERT INTO depts(dept_code,dept_name) VALUES (?,?)');
      try
      {
        $query->execute([$_POST['newDpt'], $_POST['dept']]);
      }
      catch (PDOException $e)
      {
        if($e->errorInfo[0]!=23000) // Ignoring if department already exists, reporting otherwise
          postResponse("error", $e->errorInfo[2]);
      }
      $dept_code = strtoupper($_POST['newDpt']);
    }
    else // Use existing department
    {
      rangeCheck('dept', 2, 5);
      $dept_code = strtoupper($_POST['dept']);
    }
    // Add faculty to the databases
    $query = $db->prepare('INSERT INTO faculty(uName,fac_name,pswd,dept_code,dateRegd) VALUES (?,?,?,?,?)');
    $pswd = pwdHash($uName, $_POST['pswd']);
    try
    {
      $query->execute(array(
        $uName,
        $_POST['fullName'],
        $pswd,
        $dept_code,
        date("d M Y  h:i A")));
    }
    catch(PDOException $e)
    {
      if($e->errorInfo[0]==23000)
        postResponse("error", "Username already exists");
      else
        postResponse("error",$e->errorInfo[2]);
    }

    if($newAdmin) // True when the request is coming from setup.php, log the new user in
    {
      changeUserLevel($uName, 'dean');
      $_SESSION['logged_in'] = true;
      $_SESSION['uName'] = $uName;
      $_SESSION['level'] = "dean";
      $_SESSION['fName'] = $_POST['fullName'];
      $_SESSION['dept'] = $dept_code;
      postResponse("redirect", $_SESSION['level'] . ".php");
    }
  }
  if(!empty($_POST['level']))
    changeUserLevel($uName, $_POST['level']);
  if(valueCheck('action', 'changeLevel'))
    postResponse("updateOpt", "Level Changed");
  else
    postResponse("addOpt", "Faculty Added",[$_POST["fullName"],$uName]); 
}

/**
 * changeUserLevel()
 * 
 * Add or remove $user to the admin table with the given $level
 */

function changeUserLevel($user, $level)
{
  global $db;
  try
  {
    $query = $db->prepare('UPDATE faculty SET level = ? where uName = ?');
    $query->execute([$level, $user]);
    if(!$query->rowCount())
      postResponse("error","The selected user might have been deleted. Try reloading the page.");
  }
  catch(PDOException $e)
  {
    postResponse("error",$e->errorInfo[2]);
  }
}

?>
