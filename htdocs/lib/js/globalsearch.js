/**
 * @author Kalle Karlsson (kakar)
 * @version 1.2
 * @package Template
 *
 * Handles all clientside globalsearch functions
 */

/* Constants */

//Maximum number of characters in the searchfield
var maxChar = 50;

//Minimum number of character to start the search
var minChar = 2;

//Delay between ajax-requests in ms
var typeDelay = 250;

var lastValue = "";
var searchTimer;

//Navigation
var curSel = false;
var keyPressed = false;


/* Hides shortcuts if  */
$(document).ready(function(){
	$(document.body).click(function(event){
		var clicked = $(event.target);
		if(!(clicked.is("#inside") || clicked.parent().hasClass(".gs-results") || clicked.parent().parent().is("#gs-form"))){
			$("#inside").empty();
		}
	});
});


/* Clear-button action */
$(document).ready(function(){
	$("#gs-action").click(function(){
		if($(this).hasClass("gs-clear")){
			$("#gs-searchtext").focus().attr("value", '');
			$(this).removeClass("gs-clear");
			$("#inside").empty();
			lastValue = "";
		} else {
			$("#gs-searchtext").focus();
		}
	});
});

/* AJAX search function */
$(document).ready(function(){
	$("#gs-searchtext").bind("change keyup",function(){
		var qstr = $(this).val().replace(/^\s+|\s+$/g, '');
		if(qstr.length >= minChar && qstr != $(this).attr("title")){
				if(qstr.length > maxChar){
					$(this).attr("value",$(this).val().substr(0,maxChar));
					alert('Too many characters');
				} else if(qstr != lastValue) {
					$("#gs-action").addClass("gs-loading");
					if(searchTimer){ clearTimeout(searchTimer); }
						searchTimer = setTimeout(function(){
							$.post("/search",{ q: "" + qstr + "", r: "shortcuts" },
								function(data){
									if(data.length > 0){
										$("#inside").html('<div class="gs-result-arrow"></div>'+data);
										$("#gs-action").removeClass("gs-loading").addClass("gs-clear");
										curSel = false;
										$(".gs-results li:not(.resultCat):not(.viewall)").hover(
											function(){
												$(".gs-results li.selected").removeClass("selected");
												$(this).addClass("selected");
												curSel = $(this);
											},
											function(){
												$(this).removeClass("selected");
												curSel = false;
											}
										)
										keynav();
									}
									lastValue = qstr;
								}
							);
					},typeDelay);
				}
		} else {
			$("#inside").empty();
			$("#gs-action").removeClass("gs-clear");
			lastValue = "";
		}
	}).attr('autocomplete', 'off').mouseover(function(e){e.preventDefault();return false;});
	
	
// KEYNAV
	function keynav(){
		$(document).keydown(function(e) {
			var key = 0;
			if (e == null) {
				key = event.keyCode;
			} else { // mozilla
				key = e.which;
			}
			if(!keyPressed){
				keyPressed=true;
				switch(key) {
					case 38: //up
						goUp();
						break;
					case 40: //down
						goDown();
						break;
					case 13: //enter
						activate();
						break;
					case 27: //esc
						deactivate();
						break;
				}
			}
		});
		
		$(document).keyup(function(e){keyPressed=false;});
	}
	
	function goUp(){
		if(!curSel){
			selectLast();
		} else {
			var prev = getPrev();
			if(curSel && prev){	
				curSel.removeClass("selected");
				prev.addClass("selected");
				curSel = prev;
			}
		}
	}
	function goDown(){
		if(!curSel){
			selectFirst();
		}else{
			var nxt = getNext();
			if(curSel && nxt){
				curSel.removeClass("selected");
				nxt.addClass("selected");
				curSel = nxt;
			}
		}
	}
	function activate(){
		//curSel.children("a").trigger('click');
		window.location.href = curSel.children("a").attr("href");
		return false;
	}
	function deactivate(){
		console.log("quit");
		$("#inside").empty();
	}
	
	function getSelected(){
		var cur = $(".gs-results li.selected");
		if(cur.length>0){
			return cur;
		} else {
			return false;
		}
	}
	
	function getPrev(){
		var prev = curSel.prev();
		if(prev.hasClass("keynav")){
			return prev;
		} else if(prev.hasClass("resultCat")){
			prev = prev.prev(".keynav");
			if(prev.length>0){
				return prev;
			} else {
				return false;
			}
		}
	}
	
	function getNext(){
		var nxt = curSel.next();
		if(nxt.hasClass("keynav")){
			console.log("keynav");
			return nxt;
		} else if(nxt.hasClass("resultCat")){
			console.log("resultCat");
			return nxt.next(".keynav");
		} else {
			console.log("false");
			return false;
		}
	}
	
	function selectFirst(){
		curSel = $(".gs-results .keynav").first().addClass("selected");
	}
	function selectLast(){
		curSel = $(".gs-results .keynav").last().addClass("selected");
	}
	
});


/* Searchfield validation */
$(document).ready(function(){
	$("#gs-form").submit(function(){
		if($("#gs-searchtext").val().replace(/^\s+|\s+$/g, '').length < 1 || curSel){
			return false;
		} else {
			return true;
		}
	});
});

/*----- Placeholder functions ------*/
$(document).ready(function(){
	//if($.browser.safari) return false;
	$("input").each(function(){
		if($(this).attr("type") == "text"){
			if($(this).attr("value").length < 1 && $(this).attr("title") && $(this).attr("title").length > 0){
				$(this).attr("value",$(this).attr("title"));
				$(this).css("color","#aaa");
			}
			$(this).focus(function(){
				if($(this).attr("value") == $(this).attr("title")){
					$(this).attr("value",'');
					$(this).css("color","#323232");
				}
			});
			$(this).blur(function(){
				if($(this).attr("value").length < 1){
					$(this).attr("value",$(this).attr("title"));
					$(this).css("color","#aaa");
				}
			});
		}
	});
});
