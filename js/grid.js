/**
 * Client side script to render timetable grids
 */

/**
 * Set colors to be used for course slots in the grid
 */
var colors=
[
    ['rgba(65, 127, 242, 0.18)','rgba(58, 56, 207, 0.41)'],

    ['rgba(200, 0, 166, 0.18)', 'rgba(137, 0, 152, 0.38)'],

    ['rgba(198, 250, 250, 0.75)', 'rgba(0, 95, 96, 0.48)'],

    ['rgba(159, 255, 104, 0.5)', 'rgba(54, 126, 18, 0.60)'],

    ['#fefa9a', '#C99B08']
]

/**
 * colourCourses()
 *
 * Colors courses display in the right pane with the set of predifined colors 
 */
function colorCourses()
{
    $(".course").each(function(i){
        $(this).css('background',colors[i%colors.length][0]);
    })
}

/**
 * timeAdd()
 *
 * Takes as input a string 't' of the form (HH:mm:AM/PM),
 * and duration 'dur' in minutes. Returns a string of the
 * same form as 't' with 'dur' added to it
 */
function timeAdd(t,dur)
{
    var hr = parseInt(t.substr(0,2),10),
        min = parseInt(t.substr(3,2),10),
        mer = t.substr(-2),
        h = Math.floor(dur/60);
    min += dur - h*60;
    hr +=h;
    h = Math.floor(min/60);
    hr +=h;
    min -= h*60;
    hr = (hr - 1) % 12 + 1;
    if(hr>=12 && (h || dur>=60) )
    {
        mer=(mer=="PM")?"AM":"PM";
    }  
    return ("0"+hr).substr(-2)+":"+("0"+min).substr(-2)+" "+mer;
}

/**
 * drawGrid()
 *
 * Draws a grid on the element #timetable with the specified parameters
 *
 */
function drawGrid(not_empty,numSlots,numDays,slotDur,start_time)
{
    var cell_color="";
    if (!numSlots)
    {
      numSlots = parseInt($("#numSlots").val()),
          numDays  = parseInt($("#numDays").val()),
          slotDur   = parseInt($("#duration").val()),
          start_time     = $("#start_hr").val() + ":" + $("#start_min").val()
                  + " " + $("#start_mer").val();
      cell_color = "blue";
    }
    var table = $("#timetable"),
        row=$("<div>").addClass('row');
        row.append($("<div>").addClass('cell blank'));
    if(!numSlots || !numDays)
    {
        $("#legend").hide();
        $("#updateButton").hide();
        msg = 'There are no timetables to display';
        if(not_empty)
          msg ='Add one or more numSlots and days to display the timetable'
        table.html('<br><br><div style="font-weight:bold;text-align:center">' + msg + '</div>');
        table.css('height','100px');
        return;
    }
    else
    {
        table.removeAttr('style');
        $("#updateButton").show();
        $("#legend").show();
    }
    table.html('');
    for(i=0;i<numSlots;i++)
    {
        var content = start_time+'<br>−<br>';
        start_time=timeAdd(start_time,slotDur);
        content+=start_time;
        row.append($("<div>").addClass('cell time').html(content));            
    }
    table.append(row);
    var days = ["Mon","Tue","Wed","Thu","Fri","Sat","Sun"];
    for(d=0;d<numDays;d++) 
    {
        row = $("<div>").addClass('row');
        row.append($("<div>").addClass('cell day').html(days[d]));
        for (i=0; i < numSlots; i++)
            row.append($('<div id="'+ (d+1)+"_"+(i+1) +'">').addClass("cell "+cell_color)); 
        table.append(row);
    }
    $("#disabledSlots input[value=disabled]").each(function(){
        var cell=$("#"+this.name);
        if(cell[0])
            $(cell).removeClass('blue').addClass('disabled');
        else
            $(this).remove();
    })
}
