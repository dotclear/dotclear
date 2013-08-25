$(function() {
	
	// clean
	$('.js-remove').remove();
	$('.js-hide').hide();
	$('.widgetSettings').hide();
	
	// move
	$( ".connected" ).sortable({
		axis: "y",
		connectWith: ".connected",
		cursor: "move",
		stop: function( event, ui ) {
			$('.connected').each(function(){
				if( $(this).find('li').length == 0 ) {
					$(this).parents('.widgets').find('.empty-widgets').show();
				} else {
					$(this).parents('.widgets').find('.empty-widgets').hide();
				}
			});
		}//,
		//change: function( event, ui ) {
			//ui.helper.css('height', $('#dndnav .widget-name').css('height'));
		//}
	});
	
	// add
	$( "#widgets > li" ).draggable({
		connectToSortable: ".connected",
		helper: "clone",
		revert: "invalid",
		cursor: "move",
		start: function( event, ui ) {
			ui.helper.css({'width': $('#widgets > li').css('width')});
			//ui.helper.css({'min-height': $('#dndnav .widget-name').css('height')});
			//ui.helper.find('.form-note').hide();
			//ui.helper.find('.widget-name').css({'min-height': $('#dndnav .widget-name').css('height')});
		}/*,
		stop: function( event, ui ) {
			$('.empty-widgets').css('display', 'none');
		}*/
	});
	
	$( "#widgets, .connected" ).disableSelection();
	
});