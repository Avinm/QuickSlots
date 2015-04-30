<?php

/**
 * Provides interface for initial system setup and dean account registration.
 * @author Avin E.M
 */

if (file_exists('config.php'))
  include('config.php');

require('functions.php');

if(empty($config))
  $config = $_POST;
if($config)
{
  try
  {
    $db = new PDO("mysql:host={$config['db_host']}", $config['db_user'], $config['db_pswd'],
          [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_FOUND_ROWS => true
          ]);
    $conf = "<?php\n  \$config = " . var_export($config,true) . ";\n?>";
    $db->exec('CREATE DATABASE IF NOT EXISTS '.$config['db_name']);
    $db->exec('USE '.$config['db_name']);
    file_put_contents('config.php',$conf);
    // If the system already has an admin configured, we redirect to login page instead.
    $query = $db->query("SELECT count(*) FROM faculty where level='dean'");
    if($query->fetch()[0])
    {
      if($_POST)
        postResponse('redirect','login.php');
      header("Location: ./");
      die();
    }
    if($_POST)
      postResponse('redirect','setup.php');
  }
  catch(PDOException $e)
  {

    if($e->getCode()=='42S02')
    {
      $db->exec(file_get_contents('create_tables.sql'));
      if($_POST)
        postResponse('redirect','setup.php');
    }
    if($_POST)
    {
      if($e->getCode()==1045)
        postResponse('error',"Cannot connect to the database: Invalid username or password");
      else if($e->getCode()==1044)
        postResponse('error',$e->getMessage());
      else
      {
        postResponse('error',$e->getMessage());
      }
    }
    else
    {
      copy('config.php','tmp/config.old_invalid.php');
      file_put_contents('config.php','');
    }
  }
}
?>
<!DOCTYPE HTML>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="shortcut icon" type="image/png" href="images/favicon.png"/>
  <link href="css/styles.css" rel="stylesheet" type="text/css" />
  <link href="css/chosen.css" rel="stylesheet" type="text/css" />
  <script type="text/javascript" src="js/jquery.min.js" ></script>
  <script type="text/javascript" src="js/form.js"></script>
  <script type="text/javascript" src="js/chosen.js"></script>
  <script>
    $(function()
    {
      $("select").chosen({
        create_option: function(term){
          if(term.length < 6 || term.length>50)
          {
            var message = $(".info").addClass('error');
            message.hide();
            message.html('<b>&#10006; </b>&nbsp;Department name should be between 6 to 50 characters');
            message.show(400);
            return false;
          }
          $("#newdpt").show();
          $("#newdpt input").prop("disabled", false);
          this.append_option({
            value: term,
            text: term
          }); 
        },
        create_option_text: 'Add Department ',
        persistent_create_option: true});
    })
  </script>
  <title>QuickSlots | Setup</title>
</head>

<body class="center">
    <div class="vspacer"></div>
    <div class="box" style="vertical-align: middle">
      <div class="boxbg"></div>
      <?php if(empty($db)): ?>
      <div class="db"></div>
      <div class="title">Setup a databse for quickslots to use</div>
      <div class="elements">
        <form method="post" action="setup.php">
          <input type="text" name="db_host" class="styled details" required title="Please enter database host" placeholder="Database host" />
          <input type="text" name="db_name" class="styled details" required title="Please enter database name" placeholder="Database Name" />
          <input type="text" name="db_user" class="styled username" required title="Please enter database username" placeholder="Database User" />
          <input type="password" name="db_pswd" class="styled pswd" required title="Please enter database password" placeholder="Database Password" />
          <input type="text" name="ldap_host" class="styled details" placeholder="LDAP Server" />
          <input type="text" name="ldap_dn" class="styled uInfo" placeholder="LDAP DN" />
          <div class="blocktext info"></div> 
          <div class="center button">
            <button>Continue</button>
          </div>
        </form>
      </div>
      <?php else:?>
      <div class="avatar"><div class="add icon"></div></div>
      <div class="title">To start using the system,<br /> create an admin/dean account...</div>
      <div class="elements">
        <form method="post" action="register.php?action=addUser">
          <input type="text" name="uName" class="styled username" required pattern="[^ ]{3,25}" title="3 to 25 characters without spaces" placeholder="Username" />
          <input type="text" name="fullName" class="styled uInfo" required pattern=".{6,50}" title="6 to 50 characters" placeholder="Full Name" />
          <select name="dept" class="stretch" data-placeholder="Choose Department..." required>
            <option label="Choose Department..."></option>
            <?php
            require_once ('connect_db.php');
            foreach($db->query('SELECT * FROM depts') as $dept)
              echo "<option value=\"{$dept['dept_code']}\">{$dept['dept_name']} ({$dept['dept_code']})</option>"
            ?>
          </select>
          <div id="newdpt" style="display: none;">
            <input type="text" name="newDpt" class="styled uInfo" disabled autocomplete="off" required pattern=".{2,5}" title="2 to 5 characters" placeholder="Dept. Code" />
          </div>
          <input  type="password" name="pswd" class="styled pswd" required pattern="[^ ]{8,32}" title="8 to 32 characters without spaces" placeholder="Password" />
          <input type="password" class="styled pswd" required pattern="[^ ]{8,32}" title="8 to 32 characters without spaces" placeholder="Confirm password" />
          <div class="blocktext info"></div> 
          <div class="center button">
            <button>Register</button>
          </div>
        </form>
      </div>
      <?php endif;?>
    </div>
</body>

</html>
