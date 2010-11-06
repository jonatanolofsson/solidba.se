
    	tinyMCE.init({
    		mode : "specific_textareas",
    		theme : "advanced",
    		convert_urls: false,
    		editor_selector : "mceEditor",
    		plugins : "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",
    		theme_advanced_buttons1 : "formatselect,template,|,bold,italic,underline,|,hr,bullist,numlist,|,undo,redo,|,link,unlink,anchor,image,,|,insertdate,inserttime,|,preview,code,fullscreen,cleanup",
    		theme_advanced_buttons2 : "",
    		theme_advanced_buttons3 : "",
    		theme_advanced_buttons4 : "",
    		theme_advanced_toolbar_location : "top",
    		theme_advanced_toolbar_align : "left",
    		theme_advanced_statusbar_location : "bottom",
    		object_resizing: false,
    		content_css : "templates/yweb/style.css",
    		theme_advanced_blockformats : "h1,h2,h3,p",
    		template_external_list_url : "lib/elements.php",
    		template_popup_width : "700px",
    		template_popup_height : "400px",
    		height: '300px',
    		width: '90%',
    		
    		template_replace_values : { //AUTOFILL-function
				className : function(element) {
					// do something and then:
					// element.innerHTML = something
			}
}
      		});

$(function(){
$('tinyMCEtoggle').show();
$('select.editor_type').change(function()
{
	var id = $(this).parent().next().attr('id');
	var editor_type = $(this).val();
    tinyMCE.execCommand('mceRemoveControl', false, id);
    //select which toolbar to be used
    switch (editor_type)
    {
    case "simple":
    	tinyMCE.init({
    		mode : "exact",
    		elements: id,
    		theme : "advanced",
    		convert_urls: false,
    		plugins : "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",
    		theme_advanced_buttons1 : ",bold,italic,underline,|,outdent,indent,blockquote,|,justifyleft,justifycenter,justifyright,justifyfull,|formatselect,fontselect,fontsizeselect",
    		theme_advanced_buttons2 : "pasteword,|bullist,numlist,|,undo,redo,|,link,unlink,anchor,image,cleanup,code,|,insertdate,inserttime,preview,|,forecolorfullscreen|pagebreak",
    		theme_advanced_buttons3 : "",
    		theme_advanced_buttons4 : "",
    		theme_advanced_toolbar_location : "top",
    		theme_advanced_toolbar_align : "left",
    		theme_advanced_statusbar_location : "bottom",
    		content_css : "templates/yweb/style.css",
    		height: '300px',
    		width: '90%'
    		});
        break;

    case "advanced":
    	tinyMCE.init({
    		mode : "exact",
    		elements: id,
    		theme : "advanced",
    		convert_urls: false,
    		plugins : "safari,pagebreak,style,table,save,advhr,advimage,advlink,emotions,insertdatetime,preview,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",
    		theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect",
    		theme_advanced_buttons2 : "cut,copy,paste,pasteword,|,search,replace,|,undo,redo,|,bullist,numlist,|,outdent,indent,blockquote,|,insertdate,inserttime,preview,|,forecolor",
    		theme_advanced_buttons3 : "removeformat,visualaid,|,sub,sup,|,charmap,emotions,advhr,|,print,|,ltr,rtl,|,link,unlink,anchor,image,cleanup,code,|,fullscreen",
    		theme_advanced_buttons4 : "tablecontrols,|,visualchars,template,pagebreak",
    		theme_advanced_toolbar_location : "top",
    		theme_advanced_toolbar_align : "left",
    		theme_advanced_statusbar_location : "bottom",
    		content_css : "templates/yweb/style.css",
    		height: '300',
    		width: '90%'
    		});
         break;
     }
});
});