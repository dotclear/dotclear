$(function(){
	$('#maintenance-recall-time h5').toggleWithLegend($('#maintenance-recall-time').children().not('h5'),{
		user_pref: 'dcx_maintenance_recall_time',
		legend_click:true
	});
	$('.recall-for-all').change(function(){
		var v=$(this).val();
		if(v=='seperate'){
			$('.recall-per-task').removeAttr('disabled');
		}else{
			$('.recall-per-task').attr('disabled','disabled');
		}
	});
});