$(function() { 
	$('.row').sortable({ 
		connectWith: '.row',
		dropOnEmpty: true,
		cursor: 'move',
		delay: 200,
		distance: 15,
		tolerance: 'pointer',
		stop: function(event,ui) { 
			var rowObj = $(ui.item.parent());
			var rowID = rowObj.attr('id'); 
			var boxID = ui.item.attr('id');
			var list = rowObj.sortable('toArray');
			for(var newPos = 0 ; newPos < list.length ; newPos++) {
				if(list[newPos] == boxID) break;
			}
			document.location.href = 'pagelayouteditor?edit='+boxID+'&row='+rowID+'&place='+newPos;
/*
			$.ajaxQueue({
				url: 'index.php?id=frontpageeditor&edit='+boxID+'&row='+rowID+'&place='+(newPos+1),
				error: function () {
					alert('Error');
					document.location.href = document.location.href;
				}
			});
*/
		}
	}).disableSelection(); 
})

$(function() {
	$('.boxselector').change(function() {
		var selector = $(this).attr('id');
		var boxID =  $(this).parent().attr('id');
		var value = $(this).children('option:selected').attr('value');
		document.location.href = 'pagelayouteditor&edit='+boxID+'&'+selector+'='+value;
/* 		alert('index.php?id=frontpageeditor?edit='+boxID+'&'+selector+'='+value); */
/*
		$.ajaxQueue({ 
			url: 'index.php?id=frontpageeditor&edit='+boxID+'&'+selector+'='+value,
			error: function () {
				alert('Error');
				document.location.href = document.location.href;
			}
		});		
*/
	});
});

function pagepreview() {
	var modules = $('#page .module');
	$('#page .row').removeClass('row').addClass('cols');
	$('#page .module').removeClass('module').addClass('col bordered');
	$.each(modules, function(index, value) {
		var id = $(value).attr('id');
		$(value).html('<p>'+id+'</p>');
	});
	
	$('.preview').attr('href', 'javascript:edit()').attr('title', 'Edit page');
	$('.preview img').attr('src', '3rdParty/icons/large/configure-32.png').attr('title', 'Edit page');
}

function edit() {
	$('.preview').attr('href', 'javascript:preview()').attr('title', 'Preview page');
	$('.preview img').attr('src', '3rdParty/icons/large/search-32.png').attr('title', 'Preview page');
}