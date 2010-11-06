/**
 * @author Kalle Karlsson (kakar)
 * @version 1.2
 * @package Template
 *
 * Handles all clientside search functions
 */

/*------ FullSearch page ------- */

/* Searchfield validation */
$(document).ready(function(){
	$("#sb-form").submit(function(){
		if($("#sb-searchtext").val().replace(/^\s+|\s+$/g, '').length < 1){
			return false;
		}else{
			return true;
		}
	});
});

$(document).ready(function(){
	$("#sb-searchtext").bind("change keyup",function(){
		if($(this).val().length > 0){
			$("#sb-action").show();
		}else{
			$("#sb-action").hide();
		}
	});
	if($("#sb-searchtext").val().length > 0){
		$("#sb-action").show();
	};
});

$("#sb-action").click(function(){
	$("#sb-searchtext").focus().attr("value",'');
	$(this).hide();
});

/*------- FullSearch Result visibiltity handling -----*/

$(".section > h2").click(function(){
	$(this).next().slideToggle();
	$(this).children(".label-icon").toggleClass("expanded").toggleClass("collapsed");
});

$("#search-categories > li > a").click(function(){
	$(this).parent().parents().find(".activeli").removeClass("activeli");
	$(this).parent().addClass("activeli");
	var cat = $(this).parent().attr("id").substring(9,$(this).parent().attr("id").length);
	if(cat == 'all'){
		$("#results-main").children().each(function(){
			expandResult($(this).attr("id").substring(8,$(this).attr("id").length));
		});
		$("#results-main").children().show();
	} else {
		$("#results-main").children().hide();
		expandResult(cat);
		$("#results-"+cat).show();
	}
	return false;
});

function expandResult(cat){
var catID = "#results-"+cat;
if($(catID+" > h2 > .label-icon").hasClass("collapsed")){
		$(catID+" > div").show();
		$(catID+" > h2 > .label-icon").toggleClass('expanded').toggleClass('collapsed');
	}
}

/* Image hover-preview */
xOffset = 10;
yOffset = 10;

$("#file-list > ul > li > a > .pic").hover(function(e){
	var link = $(this).parent("a").get(0);
/*	this.t = this.title;
	this.title = "";	
	var c = (this.t != "") ? "<br/>" + this.t : ""; */
	$("body").append("<p id='preview'><img src='"+ link +"&amp;mw=300&amp;=300' alt='Image preview' /></p>");
	$("#preview")
		.css("top",(e.pageY + xOffset) + "px")
		.css("left",(e.pageX + yOffset) + "px")
		.fadeIn("fast");						
},
function(){
/*	this.title = this.t; */
	$("#preview").remove();
});	
$("#file-list > ul > li > a > .pic").mousemove(function(e){
	$("#preview")
		.css("top",(e.pageY + xOffset) + "px")
		.css("left",(e.pageX + yOffset) + "px");
});