<?php

/**
 * Restricted to dean level users, provides interface and back end routines to manage departments, faculty, batches and rooms
 * @author Avin E.M
 */

require_once('functions.php');
if(!sessionCheck('level','dean'))
{
    header("Location: ./login.php");
    die();
}
require_once ('connect_db.php');
?>
<!DOCTYPE HTML>
<html>
<head>
  <title>QuickSlots</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="shortcut icon" type="image/png" href="images/favicon.png"/>
  <link rel="stylesheet" type="text/css" href="css/styles.css">
  <link rel="stylesheet" type="text/css" href="css/dashboard.css">
  <link rel="stylesheet" type="text/css" href="css/chosen.css">
  <script type="text/javascript" src="js/jquery.min.js" ></script>
  <script type="text/javascript" src="js/form.js"></script>
  <script type="text/javascript" src="js/chosen.js"></script>
  <script>
  $(function()
  {
      $("#main_menu a").each(function() {
          if($(this).prop('href') == window.location.href || window.location.href.search($(this).prop('href'))>-1)  
          {
              $(this).parent().addClass('current');
              document.title+= " | " + this.innerHTML;
              $("#shadowhead").html(this.innerHTML);
              return false;
          }
      })
      $("select").chosen();
      $("#fac_level").change(function(){
        $("input[value="+ $("option:selected",this).attr('class') +"]",this.parentNode).attr('checked','checked');
      })
  })
  </script>
</head>

<body style="white-space:nowrap">
  <div id="header">
    <div id="account_info">
      <div class="infoTab"><div class="fixer"></div><div class="dashIcon usr"></div><div id="fName"><?=$_SESSION['fName']?></div></div>
      <div class="infoTab"><div class="fixer"></div><a href="logout.php" id="logout"><div class="dashIcon logout"></div><div>Logout</div></a></div>
    </div>
    <div id="header_text">QuickSlots v1.0</div>
  </div>
  <div id="shadowhead"></div>
  <div id="nav_bar">
    <ul class="main_menu" id="main_menu">
      <li class="limenu"><a href="dean.php">Manage Timetables</a></li>
      <li class="limenu"><a href="manage.php?action=departments">Manage Departments</a></li>
      <li class="limenu"><a href="manage.php?action=faculty">Manage Faculty</a></li>
      <li class="limenu"><a href="manage.php?action=batches">Manage Batches</a></li>
      <li class="limenu"><a href="manage.php?action=rooms">Manage Rooms</a></li>
      <li class="limenu"><a href="faculty.php">Manage Courses</a></li>
      <li class="limenu"><a href="allocate.php">Allocate Timetable</a></li>
      <li class="limenu"><a href="./">View Timetable</a></li>
    </ul>
  </div>
  <div id="content">
  <?php if(valueCheck('action','faculty')) : ?>
    <div class="box">
      <div class="boxbg"></div>
      <div class="avatar"><div class="icon add"></div></div>
      <div class="title">Add Faculty</div>
      <div class="elements">
        <form method="post" action="register.php">
          <input type="text" name="fullName" class="styled uInfo" required pattern=".{6,50}" title="6 to 50 characters" placeholder="Full Name" />
          <input type="text" name="uName" class="styled username" required pattern="[^ ]{3,25}" title="3 to 25 characters without spaces" placeholder="Username" />
          <select  name="dept" class="stretch" data-placeholder="Choose Department..." required>
            <option label="Choose Department..."></option>
            <?php
            foreach($db->query('SELECT * FROM depts') as $dept)
              echo "<option value=\"{$dept['dept_code']}\">{$dept['dept_name']} ({$dept['dept_code']})</option>";
            ?>
          </select>
          <input  type="password" name="pswd" class="styled pwd" required pattern="[^ ]{8,32}" title="8 to 32 characters without spaces" placeholder="Password" />
          <input type="password" class="styled pwd" required pattern="[^ ]{8,32}" title="8 to 32 characters without spaces" placeholder="Confirm password" />
          <div style="text-align: justify;height: 18px">
            <div class="inline">
              <input type="radio" class="styled" name="level" id="addFaculty" value="faculty" checked><label for="addFaculty">Faculty</label>
            </div>
            <div class="inline">
              <input type="radio" class="styled" name="level" id="addHOD" value="hod"><label for="addHOD">HOD</label>
            </div>
            <div class="inline">
              <input type="radio" class="styled" name="level" id="addDean" value="dean"><label for="addDean">Dean</label>
            </div>
            <span class="inline stretch"></span>
          </div>
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Register</button>
          </div>
        </form>
      </div>
    </div>
    <div class="box">
      <div class="boxbg"></div>
      <div class="avatar"><div class="icon key"></div></div>
      <div class="title">Change Faculty Access</div>
      <div class="elements">
        <form method="post" action="register.php?action=changeLevel">
          <select name="uName" id="fac_level" class="updateSelect stretch" data-placeholder="Choose Faculty..." required>
            <option label="Choose Faculty..."></option>
            <?php
            foreach($db->query('SELECT * FROM faculty') as $fac)
              echo "<option value=\"{$fac['uName']}\" class=\"{$fac['level']}\">{$fac['fac_name']} ({$fac['uName']})</option>"
            ?>
          </select>
          <div style="text-align: justify;height: 18px">
            <div class="inline">
              <input type="radio" class="styled" name="level" id="changeFaculty" value="faculty"><label for="changeFaculty">Faculty</label>
            </div>
            <div class="inline">
              <input type="radio" class="styled" name="level" id="changeHOD" value="hod"><label for="changeHOD">HOD</label>
            </div>
            <div class="inline">
              <input type="radio" class="styled" name="level" id="changeDean" value="dean"><label for="changeDean">Dean</label>
            </div>
            <span class="inline stretch"></span>
          </div>
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Change</button>
          </div>
        </form>
      </div>
    </div>
    <div class="box">
      <div class="boxbg"></div>
      <div class="avatar"><div class="icon remove"></div></div>
      <div class="title">Delete Faculty</div>
      <div class="elements">
        <form method="post" action="register.php?action=deleteFaculty" class="confirm">
          <select name="uName" class="updateSelect stretch" data-placeholder="Choose Faculty..." required>
            <option label="Choose Faculty..."></option>
            <?php
            foreach($db->query('SELECT * FROM faculty') as $fac)
              echo "<option value=\"{$fac['uName']}\">{$fac['fac_name']} ({$fac['uName']})</option>"
            ?>
          </select>
          <input type="hidden" id="confirm_msg" value="Are you sure you want to delete the selected faculty?">
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Delete</button>
          </div>
        </form>
      </div>
    </div>
  <?php elseif(valueCheck('action','departments')) : ?>
    <div class="box">
      <div class="boxbg"></div>
      <div class="information"><div class="icon add"></div></div>
      <div class="title">Add Department</div>
      <div class="elements">
        <form method="post" action="depts.php?action=add">
          <input type="text" name="dept_code" class="styled details" required pattern="[^ ]{2,5}" title="2 to 5 characters" placeholder="Department Code" />
          <input type="text" name="dName" class="styled details" required pattern=".{6,50}" title="6 to 50 characters" placeholder="Department Name" />
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Add</button>
          </div>
        </form>
      </div>
    </div>
    <div class="box">
      <div class="boxbg"></div>
      <div class="information"><div class="icon remove"></div></div>
      <div class="title">Delete Department</div>
      <div class="elements">
        <form method="post" action="depts.php?action=delete">
          <select name="dept_code" class="updateSelect stretch"  data-placeholder="Choose Department..." required>
            <option label="Choose Department..."></option>
            <?php
            foreach($db->query('SELECT * FROM depts') as $dept)
              echo "<option value=\"{$dept['dept_code']}\">{$dept['dept_name']} ({$dept['dept_code']})</option>";
            ?>
          </select>
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Delete</button>
          </div>
        </form>
      </div>
    </div>
  <?php elseif(valueCheck('action','batches')) : ?>
    <div class="box">
      <div class="boxbg"></div>
      <div class="information"><div class="icon add"></div></div>
      <div class="title">Add Batch</div>
      <div class="elements">
        <form method="post" action="batches.php?action=add">
          <input type="text" name="batch_name" class="styled uInfo" required pattern="[^:]{2,30}" title="2 to 30 alphanumeric characters" placeholder="Batch Name" />
          <select name="dept" class="stretch" data-placeholder="Choose Department..." required>
            <option label="Choose Department..."></option>
            <?php
            foreach($db->query('SELECT * FROM depts') as $dept)
              echo "<option value=\"{$dept['dept_code']}\">{$dept['dept_name']} ({$dept['dept_code']})</option>";
            ?>
          </select>
          <input type="text" name="size" class="styled details" required pattern="[0-9]{1,3}" title="Number less than 1000, this will be used to suggest rooms" placeholder="Batch Size" />
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Add</button>
          </div>
        </form>
      </div>
    </div>
    <div class="box">
      <div class="boxbg"></div>
      <div class="information"><div class="icon remove"></div></div>
      <div class="title">Delete Batch</div>
      <div class="elements">
        <form method="post" action="batches.php?action=delete" class="confirm">
          <select name="batch" class="updateSelect stretch"  data-placeholder="Choose Batch..." required>
            <option label="Choose Batch..."></option>
            <?php
            foreach($db->query('SELECT * FROM batches') as $batch)
              echo "<option value=\"{$batch['batch_name']} : {$batch['batch_dept']}\">{$batch['batch_name']} : {$batch['batch_dept']} ({$batch['size']})</option>";
            ?>
          </select>
          <input type="hidden" id="confirm_msg" value="Are you sure you want to delete the selected batch?">
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Delete</button>
          </div>
        </form>
      </div>
    </div>
  <?php else: ?>
    <div class="box">
      <div class="boxbg"></div>
      <div class="information"><div class="icon add"></div></div>
      <div class="title">Add Room</div>
      <div class="elements">
        <form method="post" action="rooms.php?action=add">
          <input type="text" name="room_name" class="styled details" required pattern="[^:]{2,25}" title="2 to 25 alphanumeric characters" placeholder="Room Name" />
          <input type="text" name="capacity" class="styled details" required pattern="[0-9]{1,3}" title="Number less than 1000" placeholder="Capacity" />
          <div class="blocktext info"></div>
          <div class="center button">
            <button>Add</button>
          </div>
        </form>
      </div>
    </div>
    <div class="box">
      <div class="boxbg"></div>
      <div class="information"><div class="icon remove"></div></div>
      <div class="title">Delete Room</div>
      <div class="elements">
        <form method="post" action="rooms.php?action=delete" class="confirm">
          <select name="room_name" class="updateSelect stretch"  data-placeholder="Choose Room..." required>
            <option label="Choose Room..."></option>
            <?php
            foreach($db->query('SELECT * FROM rooms') as $room)
              echo "<option value=\"{$room['room_name']}\">{$room['room_name']} ({$room['capacity']})</option>";
            ?>
          </select>
          <div class="blocktext info"></div>
          <input type="hidden" id="confirm_msg" value="Are you sure you want to delete the selected room?">
          <div class="center button">
            <button>Delete</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>
  </div>
  <div id="footer">Powered by <a href="https://github.com/0verrider/QuickSlots">QuickSlots v1.0</a></div>
</body>
</html>
