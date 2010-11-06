//Deps: jquery/jquery-1*,jquery/jquery-plugin-ajaxqueue
$(function(){
	$('.menu_move_up, .menu_move_down').hide();
	$('.menulist').sortable({
	    connectWith: $('.menulist'),
		placeholder: 'ui-state-highlight ui-state-highlight-sort-inlay',
		tolerance: 'pointer',
		cursorAt: 'topleft',
		distance: '10',
		delay: '300',
		update: function (event, ui) {
			$('.menulist>li:even').removeClass('odd even').addClass('even');
			$('.menulist>li:odd').removeClass('odd even').addClass('odd');

			var MovedObject 	= $(ui.item);
			var MovedID			= MovedObject.attr('id');
			
			var ParentObject	= MovedObject.parent().closest('li, #menusort_0');
			var ParentID 		= ParentObject.attr('id');
			
			var ListObject		= ParentObject.children('.menulist');
			var List			= ListObject.sortable('toArray');
			
			for(var NewPlace = 0 ; NewPlace < List.length ; NewPlace++) {
				if(List[NewPlace] == MovedID) break;
			}
			
			$.ajaxQueue({
				url: '/menuEditor?moving='+MovedID+'&toParent='+ParentID+'&toPlace='+NewPlace,
				error: function () {
					alert('Error');
					document.location.href = document.location.href;
				}
			});
		}
	});
});


/**
 * Used for displaying the tool menus when hover
 */
$(".menuedit li").hover(
	function(e){
		e.stopPropagation();
		$(".tools, .tools2").hide();
		$(this).children(".tools, .tools2").show();
	},
	function(e){
		e.stopPropagation();
		$(this).children(".tools, .tools2").hide();
	}
);