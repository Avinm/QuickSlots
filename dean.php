<?php

/**
 * Homepage of dean level users, provides interface and back end routines to edit and save global settings
 * @author Avin E.M
 */

require_once('functions.php');
if(!sessionCheck('level','dean'))
{
  header("Location: ./login.php");
  die();
}
require_once('connect_db.php');


if($_POST)
{
    if(valueCheck('action','setSlots'))
    {
        if(empty($_POST["allowConflicts"]))
          $_POST["allowConflicts"] = 0;
        if(empty($_POST["active"]))
          $_POST["active"] = 0;
        if(empty($_POST["frozen"]))
          $_POST["frozen"] = 0;
        if($_POST["days"]<0 || $_POST["days"] > 7)
          postResponse("error", "Number of days cannot be more than 7");
        if(!$current['table_name'])
          postResponse("error", "Please select a timetable");
        $query = $db->prepare('UPDATE timetables SET
            days=?,
            slots=?,
            duration=?,
            start_hr=?,
            start_min=?,
            start_mer=?,
            allowConflicts=?,
            frozen = ?
            WHERE table_name=?');
        try {
            $query->execute([
              $_POST['days'],
              $_POST['slots'],
              $_POST['duration'],
              $_POST['start_hr'],
              $_POST['start_min'],
              $_POST['start_mer'],
              $_POST['allowConflicts'],
              $_POST['frozen'],
              $current['table_name']
            ]);
        }
        catch(PDOException $e)
        {
          postResponse("error", json_encode($e->errorInfo));
        }
        if($_POST['active'])
        {
          $query = $db->query('UPDATE timetables SET active=0 where active=1');
          $query = $db->prepare('UPDATE timetables SET active=1 where table_name=?');
          $query->execute([$current['table_name']]);
        }
        if($current["days"]<$_POST["days"])
        {
          $query = $db->prepare('INSERT INTO slots VALUES (?,?,?,?)');  
          for($d=$current["days"]+1;$d<=$_POST["days"];$d++)
            for($s=1;$s<=$current["slots"];$s++)
                $query->execute([$current['table_name'],$d,$s,'active']);
        }
        else
        {
          $query = $db->prepare('DELETE FROM slots WHERE day > ? AND table_name = ?');
          $query->execute([$_POST["days"],$current['table_name']]);
        }
        if($current["slots"]<=$_POST["slots"])
        {
          $query = $db->prepare('INSERT INTO slots VALUES (?,?,?,?)');
          for($d=1;$d<=$_POST["days"];$d++)
              for($s=$current["slots"]+1;$s<=$_POST["slots"];$s++)
                    $query->execute([$current['table_name'],$d,$s,'active']);
        }
        else
        {
          $query = $db->prepare('DELETE FROM slots WHERE slot_num > ? AND table_name = ?');
          $query->execute([$_POST["slots"],$current['table_name']]);
        }
        postResponse("updateGrid",'Timetable saved');
        die();
    }
  if(valueCheck('action','updateSlots'))
  {
    $query = $db->prepare('UPDATE slots SET state= ? WHERE day = ? AND slot_num = ? AND table_name = ?');
    $deleteAllocs = $db->prepare('DELETE FROM slot_allocs WHERE day = ? AND slot_num = ? AND table_name = ?');
    foreach ($_POST as $slotStr => $state)
    {
      $slot = explode('_', $slotStr);
      $query->execute([$state,$slot[0],$slot[1],$current['table_name']]);
      if($state=='disabled')
        $deleteAllocs->execute([$slot[0],$slot[1],$current['table_name']]);
    }
    postResponse("info",'Slots updated');
    die();
  }
  if(valueCheck('action','deleteTimetable'))
  {

    $query = $db->prepare('DELETE from timetables where table_name=? AND active=0');
    $query->execute([$_POST['table_name']]);
    if($query->rowCount())
    {
      postResponse("removeOpt",'Timetable deleted');
      die();
    }
    else
      postResponse("error",'Slot is the current active slot, choose another slot as active before deleting');
  }
}
?>
<!DOCTYPE HTML>
<html>
<head>
  <title>QuickSlots</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="shortcut icon" type="image/png" href="images/favicon.png"/>
  <link rel="stylesheet" type="text/css" href="css/styles.css">
  <link rel="stylesheet" type="text/css" href="css/dashboard.css">
  <link rel="stylesheet" type="text/css" href="css/table.css">
  <link rel="stylesheet" type="text/css" href="css/chosen.css">
  <script type="text/javascript"  src="js/jquery.min.js" ></script>
  <script type="text/javascript" src="js/chosen.js"></script>
  <script type="text/javascript" src="js/form.js"></script>
  <script type="text/javascript" src="js/grid.js"></script>
  <script type="text/javascript">
  $(function()
  {
      $("#main_menu a").each(function() {
          if($(this).prop('href') == window.location.href || window.location.href.search($(this).prop('href'))>-1)  
          {
              $(this).parent().addClass('current');
              document.title+= " | " + this.innerHTML;
              $(this).click(function(){return false;})
              return false;
          }
      })
      $("option[value='<?=$current['table_name']?>']","#table_name").attr('selected','selected');
      $("#table_name").chosen({
        no_results_text: 'No timetable named ',
        create_option : function(opt){
          this.append_option({
            value: opt,
            text: opt
          });
        },
        create_option_text: 'Add timetable ',
        persistent_create_option: true
      }).change(function(){
        window.location.href='dean.php?table='+this.value;
      })
      $("#delete_table").prop("selectedIndex",-1).chosen({ no_results_text: 'No timetable named '});
      $("#start_hr").val("<?=$current['start_hr']?>");
      $("#start_min").val("<?=$current['start_min']?>");
      $("#start_mer").val("<?=$current['start_mer']?>");
      $("select","#table_conf").chosen({no_results_text: "Invalid Time"});
      <?php 
        echo "drawGrid('{$current['table_name']}');\n";
      ?>
      $("#timetable").on("click", ".cell.blue", function()
      {
        changes = true;
        $(this).removeClass('blue').addClass('disabled');
        if(!$("input[name="+ this.id +"]")[0])
            $("#disabledSlots").append($('<input type="hidden" name="' + this.id + '" value="active">'));
        $("input[name="+ this.id +"]").val('disabled');
      })
      $("#timetable").on("click", ".cell.disabled", function()
      {
        changes = true;
        $(this).removeClass('disabled').addClass('blue');
        $("input[name="+ this.id +"]").val('active');
      })
      $("#snapshot").change(function(){
        $("#filename").val(this.value);
      })
    <?php if(valueCheck('status','restoreComplete')): ?>
      var msg=$('<div class="blocktext info" style="display:none;margin-top:10px;"><b>&#10004; </b>&nbsp;Database restored, please logout and login again.</div>');
      $("#content").prepend(msg);
      msg.show(400,function(){
        setTimeout(function(){
          msg.hide(400);
        },5000)
      })
    <?php endif; ?>
    var changes = false;
    window.onbeforeunload = function(e) {
      message = "There are unsaved changes in the timetable, are you sure you want to navigate away without saving them?.";
      if(changes)
      {
        e.returnValue = message;
        return message;
      }
    }
  })
  </script>
</head>

<body style="min-width: 1348px">
  <div id="header">
    <div id="account_info">
      <div class="infoTab"><div class="fixer"></div><div class="dashIcon usr"></div><div id="fName"><?=$_SESSION['fName']?></div></div>
      <div class="infoTab"><div class="fixer"></div><a href="logout.php" id="logout"><div class="dashIcon logout"></div><div>Logout</div></a></div>
    </div>
    <div id="header_text">QuickSlots v1.0</div>
  </div>
  <div id="shadowhead">Manage Timetables</div>
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
  <div id="content" style="padding-top: 0">
    <div class="tableContainer">
      <div class="title" style="font-size: 20px">
        <span class="inline" style="vertical-align: middle;padding-top:10px">Configure timetable:</span>
        <select id="table_name" name="table_name" class="updateSelect" style="width: 170px" data-placeholder="Add a timetable...">
          <?php
            foreach($db->query('SELECT * FROM timetables') as $timetable)
            {
              $active = $timetable['active']?' (active)':'';
              echo "<option value=\"{$timetable['table_name']}\">{$timetable['table_name']}{$active}</option>";
            }
          ?>
        </select>
      </div>
      <div class="stretch" style="text-align: justify;margin: 0 0 10px 0">
        <form action="dean.php?action=setSlots" id="table_conf">
          <div class="inline">
            <label for="numSlots">Start Time: </label>
            <select id="start_hr" name="start_hr" style="width:60px">
              <option value="01">01</option>
              <option value="02">02</option>
              <option value="03">03</option>
              <option value="04">04</option>
              <option value="05">05</option>
              <option value="06">06</option>
              <option value="07">07</option>
              <option value="08" selected>08</option>
              <option value="09">09</option>
              <option value="10">10</option>
              <option value="11">11</option>
              <option value="12">12</option>
            </select>
            <select id="start_min" name="start_min" style="width:60px">
              <option value="00">00</option>
              <option value="15">15</option>
              <option value="30" selected>30</option>
              <option value="45">45</option>
            </select>
            <select id="start_mer" name="start_mer" style="width:60px">
              <option value="AM" selected>AM</option>
              <option value="PM">PM</option>
            </select>
          </div>
          <div class="inline">
            <label for="numSlots">Number of Slots: </label><input type="text" name="slots" id="numSlots" class="short inline" required pattern="[0-9]{1,2}" value="<?=$current["slots"]?>" title="Number" />
          </div>
          <div class="inline">
            <label for="numSlots">Number of Days: </label><input type="text" name="days" id="numDays" class="short inline" required pattern="[0-7]{1,2}" value="<?=$current["days"]?>" title="Number: 0-7" />
          </div>
          <div class="inline">
            <label for="duration">Duration: </label><input type="text" name="duration" id="duration" class="short inline" required pattern="[0-9]{2,}" value="<?=$current["duration"]?>" title="Number >= 10"/>
            <label for="duration">mins</label>
          </div>
          <div class="inline stretch"></div>
          <div style="margin:-15px 0 2px 0">
            <input type="checkbox" class="styled" name="allowConflicts" value="1" id="allowConflicts" <?=($current["allowConflicts"]=="1")?"checked":""?>>
            <label for="allowConflicts">Allow conflicting allocations</label>
          </div>
          <div style="margin:0 0 2px 0">
            <input type="checkbox" class="styled" name="active" value="1" id="active" <?=($current["active"]=="1")?"checked":""?>>
            <label for="active">Current active timetable</label>
          </div>
          <div style="margin:0 0 2px 0">
            <input type="checkbox" class="styled" name="frozen" value="1" id="frozen" <?=($current["frozen"]=="1")?"checked":""?>>
            <label for="frozen">Freeze timetable</label>
          </div>
          <br>
          <div class="blocktext info" style="margin: -25px 0 0 5px"></div>
          <div class="center button"><button>Save</button></div>
        </form>
      </div>
      <div id="timetable" class="table"></div>
      <form id="disabledSlots" action="dean.php?action=updateSlots">
        <div class="blocktext info"></div>
        <?php
        $query = $db->prepare("SELECT * FROM slots WHERE state='disabled' AND table_name = ?");
        $query->execute([$current['table_name']]);
        $disabled = $query->fetchall();
        foreach ($disabled as $slot)
          echo '<input type="hidden" name="'. $slot['day'].'_'.$slot['slot_num'] .'" value="disabled" >';
        ?>
        <div class="button" id="updateButton" style="width:92px;margin:auto"><button>Update</button></div>
      </form>
      <div id="backup">
        <div class="title left" style="font-size: 20px">
          Backup and restore:
        </div>    
        <form method="get" action="backup.php" class="submit" style="font-weight: bold">
          <input type="hidden" name="action" value="backup">
          <span style="color:#555;font-size: 15px">Backup:</span> download a snapshot of the QuickSlots database that can be restored later :&nbsp;
          <div class="inline button" style="background: none">
            <button>Download</button>
          </div>
        </form>
        <label style="width:541px"><span style="color:#555;font-size: 15px">Restore:</span> restore the databse by uploading a snapshot downloaded earlier<span style="float:right">:</span></label>
        <input type="text" disabled class="left" style="width:150px;padding: 1px 15px 1px 10px" id="filename" placeholder="Choose file...">
        <div class="inline">
          <button id="browse" style="margin:7px 0 0 -12px" onclick="$('#snapshot').click()">Browse</button>
        </div>
        <br>
        <form method="post" action="backup.php?action=restore" class="inline submit" style="margin: 0 0 10px 550px;font-weight: bold" enctype="multipart/form-data">
          <input type="file" id="snapshot" name="snapshot" style="visibility: hidden;display: block;padding: 0;height: 10px;margin:0">
          <button>Restore</button>
        </form>
      </div>
      <div id="footer" style="position: relative">Powered by <a href="https://github.com/0verrider/QuickSlots">QuickSlots v1.0</a></div>
    </div>
    <div class="inline" style="width: 250px;margin: 0 0 0 2px">
      <div class="box" style="margin-top:15px;width: 250px;background-size: 250px 9px, 250px 16px">
        <div class="boxbg" style="background-size: 250px 1px"></div>
        <div class="timetable"><div class="icon remove"></div></div>
        <div class="title">Delete Timetable</div>
        <div class="elements" style="width:210px">
          <form method="post" action="dean.php?action=deleteTimetable" class="confirm">
            <select id="delete_table" name="table_name" class="updateSelect stretch" data-placeholder="Choose Timetable..." required>
            <option label="Choose Timetable..."></option>
              <?php
              foreach($db->query('SELECT * FROM timetables') as $t)
                echo "<option value=\"{$t['table_name']}\">{$t['table_name']}</option>"
              ?>
            </select>
            <input type="hidden" id="confirm_msg" value="Are you sure you want to delete the selected timetable?">
            <div class="blocktext info"></div>
            <div class="center button">
              <button>Delete</button>
            </div>
          </form>
        </div>
      </div>
      <br>
      <div id="legend" style="width:232px;">
        <div class="title">Legend</div>
        <div class="table" style="margin:auto">
          <div class="row">
            <div class="cell blue">Active</div>
          </div>
          <br>
          <div class="row">
            <div class="cell disabled">Disabled</div>
          </div>   
        </div>
        <br>
        Click on a slot to disable or enable it
      </div>
    </div>
  </div>
</body>
</html>
