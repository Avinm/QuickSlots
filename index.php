<?php

/**
 * Provides interface to display the timetable
 * @author Avin E.M
 */

require_once('functions.php');
require_once('connect_db.php');
$faculty="";$batchStr="";$department="";
if(sessionCheck('logged_in') && empty($_GET['faculty']) && empty($_GET['batch']))
  $department = $_SESSION['dept'];
if(!empty($_GET['department']))
  $department = $_GET['department'];
if(!empty($_GET['faculty']))
  $faculty = $_GET['faculty'];
if(!empty($_GET['batch']))
  $batchStr = $_GET['batch'];
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
  <script type="text/javascript" src="js/form.js"></script>
  <script type="text/javascript" src="js/chosen.js"></script>
  <script type="text/javascript" src="js/grid.js"></script>
  <script type="text/javascript">
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
      $("option[value=<?=$department?>]","#department").attr('selected','selected');
      $("option[value=<?=$faculty?>]","#faculty").attr('selected','selected');
      $("option[value='<?=$batchStr?>']","#batch").attr('selected','selected');
      $("select").chosen({allow_single_deselect: true});

      $("select").change(function(){
        window.location.href='./?'+$("#filters :input[value!='']").serialize();
      })
      <?php 
        $t=$current['start_hr'] .":". $current['start_min'] ." ". $current['start_mer'];
        echo"      drawGrid('{$current['table_name']}',{$current['slots']},{$current['days']},{$current['duration']},'$t');\n";
        $deptFilter = 'where dept_code=?';
        $facFilter='';$batchFilter='';
        $queryParams=[$department];
        if(!empty($faculty))
        {
          if(empty($department))
          {
            $deptFilter = '';
            $facFilter = 'where ';
            unset($queryParams[0]);
          }
          else
            $facFilter = ' AND ';
          $facFilter.= 'uName=?';
          $queryParams[] = $faculty;
        }
        if(!empty(explode(' : ', $batchStr)[1]))
        {
          if(empty($department))
          {
            $deptFilter = '';
            unset($queryParams[0]);
          }
          $batchFilter = 'NATURAL JOIN (SELECT * FROM allowed WHERE batch_name=? AND batch_dept =?) allowed_batches';
          $batch=explode(' : ', $batchStr);
          $queryParams[] = $batch[0];
          $queryParams[] = $batch[1];
        }
        $queryString = 'SELECT course_id,course_name,day,slot_num,room,fac_name,batches from slot_allocs NATURAL JOIN 
          (SELECT * from courses NATURAL JOIN
          (SELECT uName as fac_id,fac_name from faculty '. $deptFilter . $facFilter .  ') dept_fac) dept_courses  NATURAL JOIN 
          (SELECT course_id, GROUP_CONCAT(CONCAT(batch_name,\' : \',batch_dept) SEPARATOR \', \') as batches from allowed GROUP by course_id) course_batches ' . $batchFilter . ' WHERE table_name=?';
        $queryParams[]=$current['table_name'];
        $query = $db->prepare($queryString);
        $query->execute(array_values($queryParams));
        $courses = json_encode($query->fetchall(PDO::FETCH_ASSOC));
        echo "      timetable={$courses};\n";
      ?>
      var c=0;
      var color=[];
      for(i=0;i<timetable.length;i++)
      {
        var slot= $("#"+timetable[i].day+"_"+timetable[i].slot_num);
        if(!slot.html())
        {
          slot.append('<div class="course_container"><table class="course_holder"><tr></tr></table></div>');
        }
        if(!(timetable[i].course_id in color))
          color[timetable[i].course_id]=(c++)%colors.length;
        var course=$('<td>'+timetable[i].course_id+'</td>');
        course.css('background-color',colors[color[timetable[i].course_id]][0]);
        course.css('box-shadow','0 0 25px ' +colors[color[timetable[i].course_id]][1]+ ' inset');
        course.attr('data-index',i);
        if(timetable[i].course_id.length>6)
        {
          course.attr("colspan","2");
          course.addClass("long");
        }
        var target = $(".course_holder tr",slot).last();
        if($("td",target).length==2 || $("td.long",target).length)
        {
          target=$("<tr></tr>");
          $(".course_holder",slot).append(target);
        }
        target.append(course);
      }
      $(".course_holder td").click(function(){
        $(".selected").removeClass('selected');
        $(this).addClass('selected');
        $("#course_info td").remove();
        $("#course_info tr").each(function(){
          $(this).append('<td>' + timetable[$(".selected").first().attr('data-index')][this.className]+ '</td>');
        })
      });
      $("#sharelink").val(location.origin+location.pathname);
      var queryString = $("#filters :input[value!='']").serialize();
      if(queryString)
        $("#sharelink").val(location.origin+location.pathname+'?'+queryString);
  })
  </script>
  <?php if(sessionCheck('logged_in')):?>
  <script type="text/javascript">
  $(function(){
    var inputs=$("input","#download");
    var filters =$("#filters :input[value!='']");
    for(i=0;i<filters.length;i++)
      inputs[0].value+=filters[i].name+"="+filters[i].value+"&";
    inputs[1].value = filters[0].value;
    for(i=1;i<filters.length;i++)
      inputs[1].value+='_'+filters[i].value.replace(' : ',' ');
  })
  </script>
  <?php else:?>
  <style type="text/css">
    body{
      background: none !important;
    }
    #shadowhead,#content
    {
      left:0;
      margin-left: 0;
    }
  </style>
  <?php endif; ?>
  <?php if(valueCheck('print','true')): ?>
  <style type="text/css">
    body {
      visibility: hidden;
      background: none !important;
      min-width: auto !important;
    }
    #content
    {
      margin: 0;
    }
    #filters
    {
      visibility: visible;
    }
    #rightpane,#share,.guidelines
    {
      display: none !important;
    }
    .tableContainer
    {
      visibility: visible;
      min-width: auto !important;
    }
  </style>
  <?php endif;?>
</head>

<body style="min-width: 1348px">
  <div id="header">
    <div id="account_info">
    <?php if(sessionCheck('logged_in')):?>
      <div class="infoTab"><div class="fixer"></div><div class="dashIcon usr"></div><div id="fName"><?=$_SESSION['fName']?></div></div>
      <div class="infoTab"><div class="fixer"></div><a href="logout.php" id="logout"><div class="dashIcon logout"></div><div>Logout</div></a></div>
    <?php else: ?>
      <div class="infoTab"><div class="fixer"></div><a href="login.php"><div class="dashIcon logout"></div><div>Login</div></a></div>
    <?php endif; ?>
    </div>
    <div id="header_text">QuickSlots v1.0</div>
  </div>
  <div id="shadowhead">View Timetable</div>
  <?php if(sessionCheck('logged_in')):?>
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
  <?php endif; ?>
  <div id="content" style="padding:15px 0 0 15px;overflow-x: visible">
    <div>
      <form id="filters">
        <div class="title inline" style="width:77%;margin:-20px 0 0 0">
        <?php if(sessionCheck('logged_in')): ?>
          <div class="title">
            <span class="inline" style="vertical-align: middle;padding-top:10px">Timetable:</span>
            <select id="table_name" name="table" style="width: 170px" data-placeholder="Add a timetable...">
              <?php
                foreach($db->query('SELECT * FROM timetables') as $timetable)
                {
                  $active = $timetable['active']?' (active)':'';
                  echo "<option value=\"{$timetable['table_name']}\">{$timetable['table_name']}{$active}</option>";
                }
              ?>
            </select>
          </div>
        <?php endif; ?>
        <?php if(!valueCheck('print',true) || (isset($_GET['department']))): ?>
          <span class="inline" style="vertical-align: middle;padding:10px 0 0 0;margin-left: 0">Department: </span>
          <select id="department" name="department" data-placeholder="Choose Department...">
            <option label="Choose Department..."></option>
            <?php
              foreach($db->query('SELECT * FROM depts') as $dept)
                echo "<option value=\"{$dept['dept_code']}\">{$dept['dept_name']} ({$dept['dept_code']})</option>";
            ?>
          </select>
        <?php endif; ?>
        <?php if(!valueCheck('print',true) || (isset($_GET['faculty']))): ?>
          <span class="inline" style="vertical-align: middle;padding:10px 0 0 10px">Faculty: </span>
          <select id="faculty" name="faculty" data-placeholder="Choose Faculty...">
            <option label="Choose Faculty..."></option>
            <?php
              if(empty($department))
                $deptFilter='';
              $query = $db->prepare('SELECT * FROM faculty '.$deptFilter);
              $query->execute([$department]);
              foreach($query->fetchall() as $fac)
                echo "<option value=\"{$fac['uName']}\">{$fac['fac_name']}</option>"
            ?>
          </select>
        <?php endif; ?>
        <?php if(!valueCheck('print',true) || (isset($_GET['batch']))): ?>
          <span class="inline" style="vertical-align: middle;padding:10px 0 0 10px">Batch: </span>
          <select id="batch" name="batch" data-placeholder="Choose Batch...">
            <option label="Choose Batch..."></option>
            <?php
              foreach($db->query('SELECT * FROM batches') as $batch)
                echo "<option value=\"{$batch['batch_name']} : {$batch['batch_dept']}\">{$batch['batch_name']} : {$batch['batch_dept']} ({$batch['size']})</option>";
            ?>
          </select>
        <?php endif; ?>
        </div>
      </form>
    </div>
    <div class="tableContainer" >
      <div class="table consolidated" id="timetable"></div>
      <div id="legend" class="left" style="position:static;padding:5px 0 10px 0;margin:0">
        <div class="title inline" style="margin-bottom:-5px">Legend:</div>
        <div class="table" style="margin: 0 0 10px 10px;width:350px">
          <div class="cell" style="margin: 0 10px 0 0">Free</div>
          <div style="display:table-cell;width:20px"></div>
          <div class="cell disabled">Disabled</div>
        </div>
        <span style="line-height: 25px" class="guidelines">
          &#9679; Select at least one filter to view the timetable
        </span>
      </div>
      <div class="inline" style="float: right;text-align: center">
        <div id="share" class="title" style="margin:0;padding: 5px 0 19px 0"><span class="inline" style="vertical-align: middle">Share:</span>
        <textarea id="sharelink" style="vertical-align: middle;width:420px;height: 35px;resize:none" readonly onclick="this.focus;this.select()"></textarea>
        </div>
        <?php if(sessionCheck('logged_in')): ?>
        <form action="download.php" method="post" id="download" class="submit">
          <input type="hidden" name="filter">
          <input type="hidden" name="filename">
          <button>Download Timetable</button>
        </form>
        <?php endif;?>
      </div>
      <div id="footer" style="position: relative">Powered by <a href="https://github.com/0verrider/QuickSlots">QuickSlots v1.0</a></div>
    </div>
    <div id="rightpane">
      <div class="title stretch" style="padding:40px 0 10px 0">Course Details</div>
      <table id="course_info">
        <tr class="course_name">          
          <th>Course</th><td rowspan="4" style="color:#999">&#9679; Click on a course to show details</td>
        </tr>
        <tr class="fac_name">
          <th>Faculty</th>
        </tr>
        <tr class="room">
          <th>Room</th>
        </tr>
        <tr class="batches">
          <th>Batches</th>
        </tr>    
      </table>        
    </div>
    <div id="disabledSlots">
      <?php
        $query = $db->prepare("SELECT * FROM slots WHERE table_name = ? AND state='disabled'");
        $query->execute([$current['table_name']]);
        $disabled = $query->fetchall();
        foreach ($disabled as $slot)
          echo '<input type="hidden" name="'. $slot['day'].'_'.$slot['slot_num'] .'" value="disabled" >';
      ?>
    </div>
  </div>
</body>
</html>