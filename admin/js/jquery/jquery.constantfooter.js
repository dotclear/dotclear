(function($)
{
	// This script was written by Steve Fenton
	// http://www.stevefenton.co.uk/Content/Jquery-Constant-Footer/
	// Feel free to use this jQuery Plugin
	// Version: 3.0.3
    // Contributions by: 
	
	var nextSetIdentifier = 0;
	var classModifier = "";
	
	var feedItems;
	var feedIndex;
	var feedDelay = 10;
	var feedTimer;
	
	function CycleFeedList(feedList, i) {
		feedItems = feedList;
		feedIndex = i;
		ShowNextFeedItem();
	}
	
	function ShowNextFeedItem() {
		//put that feed content on the screen!
		$("." + classModifier + " .content").fadeOut(1000, function () {
			$("." + classModifier + " .content").html(feedItems[feedIndex]).fadeIn(1000);
			PadDocument();
			feedIndex++;
			if (feedIndex >= feedItems.length) {
				feedIndex = 0;
			}
			feedTimer = window.setTimeout(ShowNextFeedItem, (feedDelay * 1000));
		});
	}

	// Gets rid of CDATA sections
	function StripCdataEnclosure(string) {
		if (string.indexOf("<![CDATA[") > -1) {
			string = string.replace("<![CDATA[", "").replace("]]>", "");
		}
		return string;
	}

	// Add padding to the bottom of the document so it can be scrolled past the footer
	function PadDocument() {
		var paddingRequired = $("." + classModifier).height();
		$("#" + classModifier + "padding").css({ paddingTop: paddingRequired+"px"});
	}
	
	$.fn.constantfooter = function (settings) {
	
		var config = {
			classmodifier: "constantfooter",
			feed: "",
			feedlink: "Read more &raquo;",
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

			// Add a div used for body padding
			$This.before("<div id=\"" + config.classmodifier + "padding\">&nbsp;</div>");
			
			// Hide it
			$This.hide().addClass(classModifier).css({ position: "fixed", bottom: "0px", left: "0px", width: "100%" })
			
			// If there is a feed, we will replace the footer HTML with the feed
			if (config.feed.length > 0) {
				$This.html("");
			}
			
			// Show a close button if required
			if (config.showclose) {
				$(this).prepend("<div style=\"float: right;\" class=\"" + classModifier + "close\">" + config.closebutton + "</div>");
				$("." + classModifier + "close").css({ cursor: "pointer" });
				$("." + classModifier + "close").click( function () {
					$(this).parent().fadeOut();
					window.clearTimeout(feedTimer);
				});
			}
			
			$This.append("<div class=\"content\"></div>");

			// Show it
			$This.fadeTo(1000, opacity);
			
			// Pad the bottom of the document
			PadDocument();
			
			// Process any feeds
			if (config.feed.length > 0) {
		
				var feedList = new Array();
				
				$.get(config.feed, function(xmlDoc) {
					
					var itemList = xmlDoc.getElementsByTagName("item");
					
					for (var i = 1; i <= itemList.length; i++) {
					
						var title = xmlDoc.getElementsByTagName("title")[i].childNodes[0].nodeValue;
						var link = xmlDoc.getElementsByTagName("link")[i].childNodes[0].nodeValue;
						var description = xmlDoc.getElementsByTagName("description")[i].childNodes[0].nodeValue;
					
						var article = "<div class=\"item\">";
						
						if (link != null) {
							article += "<a href=\"" + link + "\">";
						}
						article += "<h2>" + title + "</h2>";
						if (link != null) {
							article += "</a>";
						}
						
						article += "<div class=\"description\"><p>" + description + "</p></div>";
						
						if (link != null) {
							article += "<div class=\"link\"><a href=\"" + link + "\">" + config.feedlink + "</a></div>";
						}
						
						article += "</div>";
			 
						feedList[feedList.length] = article;
					}
					
					if (feedList.length > 0) {
						CycleFeedList(feedList, 0);
					}
				});
			}
		});
	};
})(jQuery);