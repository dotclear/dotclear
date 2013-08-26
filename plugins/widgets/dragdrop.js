$(function() {
	
	// clean
	$('.js-remove').remove();
	$('.js-hide').hide();
	$('.widgetSettings').hide();
	
	// move
	$( ".connected, .sortable-delete" ).sortable({
		tolerance: "move",
		cursor: "move",
		axis: "y",
		dropOnEmpty: true,
		handle: ".widget-name",
		placeholder: "ui-sortable-placeholder",
		connectWith: ".connected, .sortable-delete",
		start: function( event, ui ) {
			ui.item.css('left', ui.item.position().left + 30);
		},
		update: function(event, ui) {
			
			// mes a zéro le décalage
			ui.item.css('left', 'auto');
			
			if( $(this).find('li').length == 0 ) {
				$(this).parents('.widgets').find('.empty-widgets').show();
			} else {
				$(this).parents('.widgets').find('.empty-widgets').hide();
			}
			
			// réordonne
			if( $(this).attr('id') ) {
				$(this).find('input[title=ordre]').each(function(i){
					tab = $(this).val(i);
				});
			}
			
			// switch
			if( $(this).attr('id') != ui.item.parents('ul').attr('id') ) {
				//oldname = $(this).attr('id').split('dnd').join('');
				var name = ui.item.parents('ul').attr('id').split('dnd').join('');
				ui.item.find('*[name^=w]').each(function(){
					tab = $(this).attr('name').split('][');
					tab[0] = "w["+name;
					$(this).attr('name', tab.join(']['));
				});
			}
			
		} //,
		//change: function( event, ui ) {
			//ui.helper.css('height', $('#dndnav .widget-name').css('height'));
		//}
	});
	
	// add
	$( "#widgets > li" ).draggable({
		tolerance: "move",
		cursor: "move",
		connectToSortable: ".connected",
		helper: "clone",
		revert: "invalid",
		start: function( event, ui ) {
			ui.helper.css({'width': $('#widgets > li').css('width')});
			//ui.helper.css({'min-height': $('#dndnav .widget-name').css('height')});
			//ui.helper.find('.form-note').hide();
			//ui.helper.find('.widget-name').css({'min-height': $('#dndnav .widget-name').css('height')});
		}
	});
	
	//$( "#widgets, .connected, .sortable-delete" ).disableSelection();
	
});