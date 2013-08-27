$(function() {
	
	// clean
	$('.js-remove').remove();
	$('.js-hide').hide();
	$('.widgetSettings').hide();
	$('.widgets, .sortable-delete').addClass('drag');
	
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
			// petit décalage éstétique
			ui.item.css('left', ui.item.position().left + 20);
		},
		update: function(event, ui) {
			
			ul = $(this);
			widget = ui.item;
			field = ul.parents('.widgets');
			
			// met a zéro le décalage
			ui.item.css('left', 'auto');
			
			// signale les zone vide
			if( ul.find('li').length == 0 )
				 field.find('.empty-widgets').show();
			else field.find('.empty-widgets').hide();
			
			// remove
			if( widget.parents('ul').is('.sortable-delete') ) {
				widget.hide('slow', function() {
					$(this).remove();
				});
			}
			
			// réordonne
			if( ul.attr('id') ) {
				ul.find('li').each(function(i) {
					
					// trouve la zone de récéption
					var name = ul.attr('id').split('dnd').join('');
					
					// modifie le name en conséquence
					$(this).find('*[name^=w]').each(function(){
						tab = $(this).attr('name').split('][');
						tab[0] = "w["+name;
						tab[1] = i;
						$(this).attr('name', tab.join(']['));
					});
					
					// ainssi que le champ d'ordre sans js (au cas ou)
					$(this).find('input[title=ordre]').val(i);
					
				});
			}
			
			// expand
			if(widget.find('img.expand').length == 0) {
				dotclear.postExpander(widget);
				dotclear.viewPostContent(widget, 'close');
			}
			
		}
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
		}
	});
	
});