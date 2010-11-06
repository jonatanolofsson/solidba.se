/**
 * @author Kalle Karlsson [kakar]
 * @version 1.1
 * @packade GUI
 * Handles the action of the ImagePicker input field
 */

/**
 * Setup function
 * Binds the update function to the field and changes the elements at startup
 */ 
function setupPreview(formid) {
	$('#'+formid).change(function(e) {
		updatePreview(formid);
	});
	
	if($('#'+formid).val() == '') {
		$('.'+formid+'remIcon, #'+formid+'prev:visible').hide();
	} else {
		$('#'+formid).css('width', '255px');
	}
}

/**
 * Opens the popup window with the filesystem
 */
function explore(formid,folder) {
	if(!folder) folder = 'files';
	window.open('index.php?id='+folder+'&popup='+formid+'&filter=images', 'selectImage', 'width=400,height=400');
}

function fileCallback(id,src) {
	document.getElementById(src).value = id;
	updatePreview(src);
}

/**
 * Toggles the preview field and changes the preview image to the selected one
 */
function updatePreview(formid) {
	var id = $('#'+formid).val();
	if(id != '') {
		$('#'+formid+'img').attr('src', 'index.php?id='+id+'&mw=300&mh=300');
		$('#'+formid+'prev:hidden').slideDown();
		$('.'+formid+'remIcon').show();
		$('#'+formid).css('width', '255px');
	} else {
		$('#'+formid+'prev:visible').slideUp();
		$('.'+formid +'remIcon').hide();
		$('#'+formid).css('width', '278px');
	}
}

/**
 * Clears the textfield
 */
function removePreview(formid) {
	$('#'+formid).val('');
	updatePreview(formid);
}

/*
jQuery.imagePicker = function(formid) {
	if(!formid) return false;
	var formid = formid;
	var src = '';
	
	_imagePicker = function(formid) {
	
		this.explore = function() {
			alert(formid);
			window.open('index.php?id=files&popup='+formid+'&filter=images', 'selectImage', 'width=400,height=400');
			this.updatePreview();
		};
		
		this.remove = function() {
			$('#'+formid).empty();
		};
		
		this.updatePreview = function() {
			if($('#'+formid).val() != '') {
				$('#'+formid+'img').attr('src', $('#'+formid).val()+'&w=300');
				$('#'+formid+'prev').slideDown();
				$('.'+formid+'remIcon').show();
				$('#'+formid).css('width', '255px');
			}
		};
		
		$('#'+formid).bind('change', function(e){
			this.updatePreview();
		});
	};
	
	return new _imagePicker(formid);
};
*/
