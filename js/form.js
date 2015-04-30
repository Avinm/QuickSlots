/**
 * Handles all form submissions and validation that needs to be done via AJAX
 */
$(function(){
    $("form").not(".submit").submit(function(e){
        changes = false;
        if($(this).hasClass('confirm'))
        {
          r = confirm($("#confirm_msg").val())
          if(!r)
            return false;
        }
        form = $(this);
        var button=$("button",this);
        var message=$(".info",this);
        pwd_fields = $("input[type=password]",this);
        if(pwd_fields.length>1)
            if(pwd_fields[0].value!=pwd_fields[1].value)
            {
                message.hide();
                message.addClass('error');
                message.html("<b>&#10006; </b>&nbsp;Passwords do not match.");
                message.show(400);    
                return false;
            }
        button.fadeOut('fast');
        $.post(
            form.attr('action'),
            form.serialize(),
            function(reply){
                button.fadeIn('fast');
                reply=JSON.parse(reply);
                if(reply[0]=="redirect")
                {
                    window.location=reply[1];
                }
                else if(reply[0]=="error")
                {
                    message.hide();
                    message.addClass('error');
                    message.html("<b>&#10006; </b>&nbsp;" + reply[1]);
                    message.show(400);
                }
                else
                {
                    if(reply[0]=="addOpt")
                    {
                        var opt = JSON.parse(reply[2]);
                        var val =opt[1];
                        if(reply[1]=="Room Added" || reply[1]=="Batch Added")
                            val=opt[0];
                        $(".updateSelect").append($('<option>').html(opt[0]+" ("+opt[1]+")").val(val));
                        $(".updateSelect").prop("selectedIndex", -1);
                        $(".updateSelect").trigger("chosen:updated");
                    }    
                    else if(reply[0]=="removeOpt")
                    {
                        var optVal = $(".updateSelect",form).find('option:selected').val();
                        $("option[value='"+optVal+"']",".updateSelect").remove();
                        $(".updateSelect").trigger("chosen:updated");
                    }
                    else if(reply[0]=="updateOpt")
                    {
                      $("option:selected",form).attr('class',$("input[type=radio]:checked",form).val());
                    }
                    else if(reply[0]=="updateGrid")
                        drawGrid('true');
                    message.hide();
                    message.removeClass('error');
                    message.html("<b>&#10004; </b>&nbsp;" + reply[1]);
                    message.show(400);
                }
            });
        return false;
    })
});