(function($)
{
	// This script was written by Steve Fenton
	// http://www.stevefenton.co.uk/Content/Jquery-Constant-Footer/
	// Feel free to use this jQuery Plugin
	// Version: 3.0.3 - modified by DC Team
    // Contributions by: 
	
	var classModifier = "";
	
	// Add padding to the bottom of the document so it can be scrolled past the footer
	function PadDocument() {
		var paddingRequired = $("." + classModifier).height();
		$("#" + classModifier + "padding").css({ paddingTop: paddingRequired+"px"});
	}
	
	$.fn.constantfooter = function (settings) {
	
		var config = {
			classmodifier: "constantfooter",
			opacity: 0.8,
			showclose: false,
			closebutton: "[x]"
		};
		
		if (settings) {
			$.extend(config, settings);
		}

		return this.each(function () {
			
			classModifier = config.classmodifier;
			
			// Make sure opacity is a number between 0.1 and 1
			var opacity = parseFloat(config.opacity);
			if (opacity > 1) {
				opacity = 1;
			} else if (opacity < 0.1) {
				opacity = 0.1;
			}
			
			$This = $(this);

			// Hide it
			$This.hide().addClass(classModifier).css({ position: "fixed", bottom: "0px", left: "0px", width: "100%" })
			
			// Show a close button if required
			if (config.showclose) {
				$(this).prepend("<div style=\"float: right;\" class=\"" + classModifier + "close\">" + config.closebutton + "</div>");
				$("." + classModifier + "close").css({ cursor: "pointer" });
				$("." + classModifier + "close").click( function () {
					$(this).parent().fadeOut();
					window.clearTimeout(feedTimer);
				});
			}
			
			// $This.append("<div class=\"content\"></div>");

			// Show it
			$This.fadeTo(1000, opacity);
			
			// Pad the bottom of the document
			PadDocument();
		});
	};
})(jQuery);