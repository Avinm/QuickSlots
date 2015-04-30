<?php

/**
 * Provides interface and back end routines that handle user logins
 * @author Avin E.M
 */

require_once ('connect_db.php');
require_once ('functions.php');

if($_POST)
{
  if(!empty($_POST['uName']) && !empty($_POST['pswd']))
  {
    $uName = strtolower($_POST['uName']);
    $query = $db->prepare('SELECT * FROM faculty WHERE uName = ?');;
    $query->execute([$uName]);
    $faculty = $query->fetch();
    if(!$faculty)
      postResponse("error", "Username is not registered!");
    if($faculty['pswd'] == pwdHash($uName, $_POST['pswd']) || 
       @ldap_bind(ldap_connect($config['ldap_host']), "uid=$uName," . $config['ldap_dn'],$_POST['pswd']))
    {
      $_SESSION['logged_in'] = true;
      $_SESSION['fName'] = $faculty['fac_name'];
      $_SESSION['uName'] = $uName;
      $_SESSION['level'] = $faculty['level'];
      $_SESSION['dept'] = $faculty['dept_code'];
    }
    else
      postResponse("error", "Invalid credentials");
  }
}
if(sessionCheck('logged_in'))
{
  $home = "faculty.php";
  if($_SESSION['level'] == "dean")
    $home = "dean.php";
  if($_POST)
    postResponse("redirect", $home);
  header("Location: " . $home);
  die();
}
?>
<!DOCTYPE HTML>
<html>

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="shortcut icon" type="image/png" href="images/favicon.png"/>
  <script src="js/jquery.min.js"></script>
  <link href="css/styles.css" rel="stylesheet" type="text/css" />
  <title>QuickSlots | Login</title>
  <script src="js/form.js"></script>
</head>

<body class="center">
  <div class="vspacer"></div>
  <div class="box middle">
    <div class="boxbg"></div>
    <div class="elements">
      <div class="avatar"><div class="icon key"></div></div>
      <div class="title">Login</div>
      <form id="loginform" method="post" action="login.php">
        <input type="text" name="uName" class="styled username" required placeholder="Username/Roll No." />
        <input type="password" name="pswd" class="styled pswd" required placeholder="Password" />
        <div class="blocktext info"></div>
        <div class="center button" >
          <button>Login</button>
          <div class="loader">
          </div>
        </div>
      </form>
    </div>
  </div>
  <div id="footer" style="margin:0">Powered by <a href="https://github.com/0verrider/QuickSlots">QuickSlots v1.0</a></div>
</body>

</html>