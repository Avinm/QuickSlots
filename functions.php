<?php

/**
 * Contains various common helper routines used across most files
 * @author Avin E.M
 */

ini_set('session.gc_maxlifetime', 24*3600);
ini_set('session.cookie_lifetime', 24*3600);
session_start();

array_walk_recursive($_REQUEST, function (&$val) 
{ 
  $val = trim($val); 
});

/**
 * postResponse()
 *
 * Prints out a json string that contains a server message along with
 * the type (error,redirect or info)
 */
function postResponse($type, $msg, $data="")
{
  echo json_encode([$type, $msg, json_encode($data)]);
  if($type === "error" || $type == "redirect")
    die();
}

/**
 * sessionCheck()
 * 
 * Returns true if the field $var in the $_SESSION is set and is equal to $val 
 */
function sessionCheck($var, $val = true)
{
  if(!empty($_SESSION[$var]))
    return ($_SESSION[$var] == $val);
  return false;
}

/**
 * valueCheck()
 * 
 * Returns true if the field $var in the $_REQUEST is set and is equal to $val 
 */
function valueCheck($var, $val)
{
  if(!empty($_REQUEST[$var]))
    return ($_REQUEST[$var] == $val);
  return 0;
}

/**
 * rangeCheck()
 * 
 * Returns true if the parameter $postvar is bounded in length by $min and $max 
 */
function rangeCheck($postvar, $min, $max, $spaceAllowed = true)
{
  if(empty($_POST['ajaxcheck']) || $_POST['ajaxcheck'] == $postvar)
  {
    if(!empty($_POST[$postvar]))
    {
      if(!$spaceAllowed)
      {
        if(strpos($_POST[$postvar], " ") !== false)
          postResponse("error", $postvar . ' Cannot contain spaces');    
      }
      if(strlen($_POST[$postvar]) < $min)
        postResponse("error", $postvar . ' must be atleast ' . $min . ' characters long');
      else if(strlen($_POST[$postvar]) > $max)
        postResponse("error", $postvar . ' must not be longer than ' . $max . ' characters');
    }
    else
      postResponse("error", 'Please enter ' . $postvar);
  }
}
/**
 * pwdHash()
 * 
 * function that returns a custom hash of $pwd using $salt
 * as a seed for salting  
 */
function pwdHash($salt, $pwd)
{
    $off = ord($salt) % 17;
    $salt = md5($salt);
    $crypt = substr($salt, 0, $off);
    $i = -1;
    while(isset($pwd[++$i]))
    {
        $crypt .= $pwd[$i];
        $crypt .= $salt[$i + $off];
    }
    $crypt = $crypt . substr($salt, $i + $off);
    return $crypt;
    hash('sha256', $crypt);
}
?>