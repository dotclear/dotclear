
$(function(){if($('*.error').length>0){return;}
$('#ie-gui form[method=post]:has(input[type=hidden][name=autosubmit])').each(function(){$('input[type=submit]',this).remove();$(this).after('<p style="font-size:2em;text-align:center">'+dotclear.msg.please_wait+'</p>');$(this).submit();});});