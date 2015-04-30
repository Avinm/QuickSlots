<?php

/**
 * Provides interface and back end routines for allocation of courses to time slots
 * @author Avin E.M
 */

require_once('functions.php');
if(!sessionCheck('logged_in'))
{
  header("Location: ./login.php");
  die();
}
require_once('connect_db.php');
if(!isset($_SESSION['faculty']))
  $_SESSION['faculty'] = $_SESSION['uName'];
if(!sessionCheck('level','faculty'))
{
  if(!empty($_GET['faculty']))
  {
    $query = $db->prepare('SELECT uName FROM faculty where uName = ? AND dept_code=?');
    $query->execute([$_GET['faculty'],$_SESSION['dept']]); 
    $fac = $query->fetch();
    if(!empty($fac['uName']))   
      $_SESSION['faculty'] = $_GET['faculty'];
  }
}
$query = $db->prepare('SELECT * FROM courses where fac_id = ?');
$query->execute([$_SESSION['faculty']]);
$courses = $query->fetchall();
foreach ($courses as $course) {
  if($course['allow_conflict'] && $current['allowConflicts'])
    continue;
  $blocked[$course['course_id']] = [];
  $filter = !$current['allowConflicts']?"OR allow_conflict=1":"";
  $query = $db->prepare("SELECT course_id,count(*) as batches FROM 
      (SELECT * FROM allowed where course_id NOT IN
      (SELECT course_id FROM courses where fac_id = ? $filter)) other NATURAL JOIN 
      (SELECT batch_name,batch_dept FROM allowed where course_id=?) batches 
       group by course_id");

  $query->execute([$_SESSION['faculty'],$course['course_id']]);

  $conflicts = $query->fetchall();

  foreach ($conflicts as $conflict) 
  {
    $query = $db->prepare('SELECT day,slot_num FROM slot_allocs where table_name=? AND course_id=?');
    $query->execute([$current['table_name'],$conflict['course_id']]);
    $conf_slots=$query->fetchall();
    foreach ($conf_slots as $conf_slot) 
    {
      $slotStr = $conf_slot['day']. "_" .$conf_slot['slot_num'];
      if(isset($blocked[$course['course_id']][$slotStr]))
          $blocked[$course['course_id']][$slotStr] += $conflict['batches'];
      else
          $blocked[$course['course_id']][$slotStr] = $conflict['batches'];
    }
  }
}

if(valueCheck('action','saveSlots'))
{
  if($current['frozen'])
    postResponse("error","This timetable has been frozen");
  foreach ($_POST as $slotStr => $course_room)
  {
    $course=explode(':', $course_room)[0];
    if(!empty($blocked[$course][$slotStr]))
      postResponse("redirect","allocate.php?error=conflict");
  }
  $query = $db->prepare('DELETE FROM slot_allocs where table_name=? AND course_id IN (SELECT course_id FROM courses where fac_id=?)');
  $query->execute([$current['table_name'],$_SESSION['faculty']]);
  $query = $db->prepare('INSERT INTO slot_allocs values(?,?,?,?,?)');
  try
  {
    foreach ($_POST as $slotStr => $course_room) 
    {
      $course_room = explode(':', $course_room);
      $course = $course_room[0];
      $room = $course_room[1]; 
      $slot = explode('_', $slotStr);
      $query->execute([$current['table_name'],$slot[0],$slot[1],$room,$course]);
    }
  }
  catch(PDOException $e)
  {
    if($e->errorInfo[0]==23000)
      postResponse("error","The selected room has been booked already, rooms list has been refreshed");
    else
      postResponse("error",$e->errorInfo[2]);
  }
  postResponse("info","Slots Saved");
  die();
}
if(valueCheck('action','queryRooms'))
{
    $slot = explode('_', $_POST["slot"]);
    $query = $db->prepare('SELECT min(size) FROM allowed NATURAL JOIN batches where course_id=?');
    $query->execute([$_POST['course']]);
    $minCap = $query->fetch()[0];
    $query = $db->prepare('SELECT room_name,capacity FROM rooms 
             where capacity>=? AND room_name NOT IN 
             (SELECT room FROM slot_allocs where table_name=? AND day=? AND slot_num=?
              AND course_id NOT IN (SELECT course_id FROM courses where fac_id=?)
              ) ORDER BY capacity');
    $query->execute([$minCap,$current['table_name'],$slot[0],$slot[1],$_SESSION['faculty']]);
    $rooms = $query->fetchall(PDO::FETCH_NUM);
    die(json_encode($rooms));
}
if(valueCheck('action','queryConflict'))
{
    $slot = explode('_', $_POST["slot"]);
    $query = $db->prepare('SELECT course_id,course_name,fac_id,fac_name,GROUP_CONCAT(CONCAT(batch_name,\' : \',batch_dept) ORDER BY batch_name SEPARATOR \', \') as batches from allowed NATURAL JOIN courses NATURAL JOIN (SELECT fac_name,uName as fac_id from faculty) faculty where course_id IN (SELECT course_id from slot_allocs where table_name=? AND day=? AND slot_num=?) AND (batch_name,batch_dept) IN (SELECT batch_name,batch_dept FROM allowed where course_id=?) GROUP BY course_id');
    $query->execute([$current['table_name'],$slot[0],$slot[1],$_POST['course']]);
    $conflicts = $query->fetchall();
    $inf_html = "";
    foreach ($conflicts as $conflict) {
      $fac_info = $conflict['fac_name'];
      if(!sessionCheck('level','faculty'))
        $fac_info = "<a href=\"allocate.php?faculty={$conflict['fac_id']}\">{$conflict['fac_name']}</a>";
      $inf_html .= <<<HTML
      <tr class="data">
          <td class="course_name">{$conflict['course_name']}</td>
          <td class="faculty">$fac_info</td>
          <td class="batch">{$conflict['batches']}</td>
      </tr>
HTML;
  }
  die($inf_html);
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
  <link rel="stylesheet" type="text/css" href="css/chosen.css">
  <link rel="stylesheet" type="text/css" href="css/table.css">
  <script type="text/javascript" src="js/jquery.min.js" ></script>
  <script type="text/javascript" src="js/ui.min.js" ></script>
  <script type="text/javascript" src="js/ui-touch-punch.min.js" ></script>
  <script type="text/javascript" src="js/form.js"></script>
  <script type="text/javascript" src="js/chosen.js"></script>
  <script type="text/javascript" src="js/grid.js"></script>
  <script>
  $(function()
  {
    $("#main_menu a").each(function() {
      if($(this).prop('href') == window.location.href || window.location.href.search($(this).prop('href'))>-1) 
      {
          $(this).parent().addClass('current');
          document.title+= " | " + this.innerHTML;
          return false;
      }
    });
    $("option[value='<?=$current['table_name']?>']","#table_name").attr('selected','selected');
    <?php 
      $t=$current['start_hr'] .":". $current['start_min'] ." ". $current['start_mer']; 
      echo"drawGrid('{$current['table_name']}',{$current['slots']},{$current['days']},{$current['duration']},'$t');";
    ?>
    $(".course").draggable({
      helper:"clone",
      opacity: 0.7,
      appendTo: "#rightpane",
      tolerance: "fit",
      start: function(e,ui)
      {
        var blocked = $("."+this.id,".blocked");
        resetInfo();
        $("input",blocked).each(function(){
            var cell=$("#"+this.name);
                cell.addClass('conflicting');
            $.data(cell[0],"content",cell.html());
            cell.html(this.value);
        })
      },
      stop: function(){
        $(".conflicting").each(function(){
            if($(this).hasClass('showInfo'))
              return;
            if(this.innerHTML)
                $(this).html($.data(this,"content"));
            $(this).removeClass('conflicting');
        });
      }
    });
    $(".cell","#timetable").click(function(){
      if(!this.innerHTML || $(this).hasClass('conflicting'))
        return false;
      $(".selected").removeClass('selected');
      $(this).addClass('selected');
      resetInfo();
      $("#roomSelect").html('<div class="center button"></div>');
      $.ajax({
        type: "POST",
        url: "allocate.php?action=queryRooms",
        data: "slot="+this.id+"&course="+$("input[name="+this.id+"]","#courseAlloc").val().split(':')[0],
        success: function(result)
        {
            $("#roomSelect").html('<select name="room_name" style="width:150px" class="updateSelect"  data-placeholder="Choose Room..." required onchange="assignRoom(this.value)">');
            var roomSelect=$("select[name=room_name]"),
            rooms=JSON.parse(result);
            for(i=0;i<rooms.length;i++)
              roomSelect.append('<option value="' + rooms[i][0] +'">'+rooms[i][0]+ ' (' + rooms[i][1] +')</option>');
            var current = $(".selected").attr('id');
            if(current)
            {
              var current_room = $("input[name="+ current +"]","#courseAlloc").val().split(':')[1];
              if(current_room && current_room!="undefined")
                $("option[value='"+ current_room + "']",roomSelect).attr("selected", "selected");
              else
              {
                roomSelect.prop("selectedIndex", 0)
              }
              roomSelect.change();
              roomSelect.chosen();
            }
            else
              roomSelect.remove();
        }
      });
    })
    $("button").click(function(){$(".selected").click()}) // Refresh Room list on submit
    var active = $(".cell","#timetable").not(".disabled,.blank,.day,.time");
    active.droppable(
    {
        drop: function(e,ui)
        {
        <?php
          if(!$current["allowConflicts"]):
        ?>
          if($(this).hasClass('conflicting'))
          {
            $(this).removeAttr('style');
            $(this).addClass('showInfo');
            changes = true;
            $("input[name="+this.id+"]","#courseAlloc").remove();
            $("#conflict_help").html('<div class="center button"></div>');
            $.ajax({
              type: "POST",
              url: "allocate.php?action=queryConflict",
              data: "slot="+this.id+"&course="+ui.draggable[0].id,
              success: function(data)
              {
                $("#conflict_help").hide();
                $("#conflict_info").append(data);
              }
            })
            return;
          }
        <?php
          endif;
        ?>
          var i = ui.draggable.index()%colors.length;
          var inner = $('<div class="course_holder"></div>');
          $(this).html(inner);
          $.data(this,"content",inner);
          inner.html(ui.draggable.html());
          $(this).css('background-color',colors[i][0]);
          $(this).css('box-shadow','0 0 25px ' +colors[i][1]+ ' inset');
          $("input[name="+ this.id +"]","#courseAlloc").remove();
          changes = true;
          $("#courseAlloc").append('<input type="hidden" name="'+ this.id +'" value="'+ ui.draggable[0].id +":" + $("select[name=room_name]").val() +'">')
          $(this).click();
        },
        over: function(e,ui){
            var i= ui.draggable.index()%colors.length;
            if(!this.innerHTML)
            {
                $(this).css('background-color',colors[i][0]);
                $(this).css('box-shadow','0 0 25px ' +colors[i][1]+ ' inset');
            }
        },
        out: function(){
            if(!this.innerHTML)
                $(this).removeAttr('style');
        }
    });
    active.dblclick(function()
    {
        $(this).removeClass('selected');
        $(this).html('');
        $(this).removeAttr('style');
        $("input[name="+this.id+"]","#courseAlloc").remove();
    })
    $("input","#courseAlloc").each(function(){
        var slot = $("#"+this.name),
            inner = $('<div class="course_holder"></div>'),
            course = $("#"+this.value.split(':')[0].replace('/','\\/')),
            i=course.index()%colors.length;
        slot.html(inner);
        inner.html(course.html());
        slot.css('background-color',colors[i][0]);
        slot.css('box-shadow','0 0 25px ' +colors[i][1]+ ' inset');
    })
    colorCourses();
    $("option[value=<?=$_SESSION['faculty']?>]","#faculty").attr('selected','selected');
    $("#table_name").chosen();
    $("#faculty").chosen().change(function(){
      window.location.href='allocate.php?faculty='+this.value;
    })
    $("#table_name").change(function(){
      window.location.href='allocate.php?table='+this.value;
    })
  })
  function assignRoom(room)
  {
    var slotId=$(".selected")[0].id;
    var slot=$("input[name=" + slotId + "]")[0];
    slot.value = slot.value.split(":")[0]+":"+room;
  }
  function resetInfo()
  {
    $(".showInfo").html('');
    $("tr.data").remove();
    $("#conflict_help").html('&#9679; Drop a course into a conflicting slot to show conflict details');
    $("#conflict_help").show();
    $(".showInfo").removeClass('showInfo conflicting');
  }
  var changes = false;
  window.onbeforeunload = function(e) {
    message = "There are unsaved changes in the timetable, are you sure you want to navigate away without saving them?.";
    if(changes)
    {
      e.returnValue = message;
      return message;
    }
  }
  </script>
</head>

<body style="min-width: 1347px;">
  <div id="shadowhead">Allocate Timetable</div>
  <div id="header">
    <div id="account_info">
      <div class="infoTab"><div class="fixer"></div><div class="dashIcon usr"></div><div id="fName"><?=$_SESSION['fName']?></div></div>
      <div class="infoTab"><div class="fixer"></div><a href="logout.php" id="logout"><div class="dashIcon logout"></div><div>Logout</div></a></div>
    </div>
    <div id="header_text">QuickSlots v1.0</div>
  </div>
  <div id="nav_bar">
    <ul class="main_menu" id="main_menu">
    <?php
    if(sessionCheck('level','dean'))
      echo '<li class="limenu"><a href="dean.php">Manage Timetables</a></li>
            <li class="limenu"><a href="manage.php?action=departments">Manage Departments</a></li>
            <li class="limenu"><a href="manage.php?action=faculty">Manage Faculty</a></li>
            <li class="limenu"><a href="manage.php?action=batches">Manage Batches</a></li>
            <li class="limenu"><a href="manage.php?action=rooms">Manage Rooms</a></li>';
    ?>
            <li class="limenu"><a href="faculty.php">Manage Courses</a></li>
            <li class="limenu"><a href="allocate.php">Allocate Timetable</a></li>
            <li class="limenu"><a href="./">View Timetable</a></li>
    </ul>
  </div>
  <div id="content" style="padding:15px 0 0 15px;overflow-x: visible">   
    <div class="tableContainer">
      <div class="title" style="margin-top:-15px">
        <span class="inline" style="vertical-align: middle;padding-top:10px">Timetable:</span>
        <select id="table_name" name="table" style="width: 170px" data-placeholder="Choose a timetable...">
          <?php
            foreach($db->query('SELECT * FROM timetables') as $timetable)
            {
              $active = $timetable['active']?' (active)':'';
              echo "<option value=\"{$timetable['table_name']}\">{$timetable['table_name']}{$active}</option>";
            }
          ?>
        </select>
      </div>
      <div id="timetable" class="table"></div>
      <form id="courseAlloc" action="allocate.php?action=saveSlots">
        <?php
        $query = $db->prepare('SELECT * FROM slot_allocs where table_name=? AND course_id IN (SELECT course_id FROM courses where fac_id=?)');
        $query->execute([$current['table_name'],$_SESSION['faculty']]);
        while($slot = $query->fetch())
          echo '<input type="hidden" name="'. $slot['day'].'_'.$slot['slot_num'] .'" value="'.$slot['course_id']. ':'.$slot['room'].'" >';
        ?>
        <?php if(valueCheck("error","conflict")): ?>
          <div class="blocktext info error">
            <b>&#10006; </b>&nbsp; Another faculty has just allocated one of the slots. Please try again  
          </div>
        <?php else: ?>
          <div class="blocktext info">
          </div>
        <?php endif; ?>
        <div class="center">
          <button>Save</button>
        </div>
      </form>
      <div id="legend" class="left" style="position:static;padding:0;margin: -30px 0 10px 0">
        <div class="title inline" style="height: 30px">Legend:</div>
        <div class="table" style="margin-left: 10px;width:350px">
          <div class="cell" style="margin: 0 10px 0 0">Free</div>
          <div style="display:table-cell;width:20px"></div>
          <div class="cell disabled">Disabled</div>
        </div>
        <span style="line-height: 25px">
          &#9679; Drag and Drop a course from the right panel to the required slot<br>
          &#9679; Double-click on a slot to clear it<br>
          &#9679; Conflicting Slots are indicated in red and would contain the number of batches affected<br>
          &#9679; A '~' before a course indicates that its conflicts are not considered
        </span>
      </div>
      <div id="disabledSlots">
      <?php
        $query = $db->prepare("SELECT * FROM slots WHERE table_name=? AND state='disabled'");
        $query->execute([$current['table_name']]);
        $disabled = $query->fetchall();
        foreach ($disabled as $slot)
          echo '<input type="hidden" name="'. $slot['day'].'_'.$slot['slot_num'] .'" value="disabled" >';
      ?>
      </div>
      <div class="blocked">
        <?php
        foreach ($courses as $course)
        {
          if($course['allow_conflict'] && $current['allowConflicts'])
            continue;
          echo "<div class=\"{$course['course_id']}\">";
          foreach ($blocked[$course['course_id']] as $slot => $batches) 
            echo "<input type= \"hidden\" name=\"$slot\" value=\"$batches\" >";
          echo "</div>";
        }
        ?>
      </div>
      <div id="footer" style="position: relative">Powered by <a href="https://github.com/0verrider/QuickSlots">QuickSlots v1.0</a></div>
    </div>
    <div id="rightpane" style="width: 235px;margin-left:10px">
    <?php if(!sessionCheck('level','faculty')) : ?>
      <div class="title">Faculty</div>
      <select id="faculty" class="stretch">
        <?php
          $query = $db->prepare('SELECT * FROM faculty where dept_code=?');
          $query->execute([$_SESSION['dept']]);
          foreach($query->fetchall() as $fac)
            echo "<option value=\"{$fac['uName']}\">{$fac['fac_name']} ({$fac['uName']})</option>"
        ?>
      </select>
    <?php endif; ?>
      <div class="title" style="padding: 15px 0">Courses</div>
      <div id="courseScroll">
        <?php
        foreach ($courses as $course)
        { 
          $conflict = $course['allow_conflict']?'class="conflict"':'';
          echo "<div class=\"course\" id=\"{$course['course_id']}\"> <span {$conflict}>{$course['course_name']} ({$course['course_id']})</span></div>";
        }
        if(!$courses)
          echo 'You have not started offering any courses.<br>Visit the <b>Manage Courses</b> section to add courses'
        ?>        
      </div>
      <div class="title" style="padding: 15px 0">Assign Room</div>
      <span id="roomSelect">Click on a slot to assign room</span>
      <div class="title stretch" style="padding:20px 0 10px 0">Conflict Details</div>
      <table id="conflict_info">
        <tr>
          <th>Course</th>
          <th>Faculty</th>   
          <th>Batches</th>
        </tr>
        <tr style="font-style:">
          <td colspan="3" id="conflict_help" >&#9679; Drop a course into a conflicting slot to show conflict details</td>
        </tr>       
      </table>
    </div>
  </div>
</body>
</html>
