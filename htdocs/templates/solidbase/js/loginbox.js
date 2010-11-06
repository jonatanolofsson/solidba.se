$(function(){
	$('.login legend').css('cursor','pointer').nextAll().hide();
	$('.login legend').click(function(){$(this).nextAll().toggle('normal');});
});