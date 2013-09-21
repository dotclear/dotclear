$(function(){

	$('.recall-for-all').change(function(){
		var v=$(this).val();
		if(v=='seperate'){
			$('.recall-per-task').removeAttr('disabled');
		}else{
			$('.recall-per-task').attr('disabled','disabled');
		}
	});
});