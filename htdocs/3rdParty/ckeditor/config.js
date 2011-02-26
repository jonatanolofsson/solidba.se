/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
	config.toolbar = 'Basic';
	config.defaultLanguage = 'sv';//fixa auto av language?
	config.toolbar_Basic =
	[
    		['Format', 'FontSize', 'Bold', 'Italic', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink','-','Image','-','PasteFromWord']//mera olika?
	];
   	config.filebrowserImageBrowseUrl = '/index.php?id=files&popup=ckeditor&filter=images';//fixa callback
   	config.filebrowserImageUploadUrl = '/fileRoot?popup=src&amp;filter=images&amp;action=upload';//fixa upload vart
	config.filebrowserImageWindowWidth = '640';
        config.filebrowserImageWindowHeight = '480';
};

/*

config.toolbar_Full =
[
    ['Source','-','Save','NewPage','Preview','-','Templates'],
    ['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print', 'SpellChecker', 'Scayt'],
    ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
    ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'],
    ['BidiLtr', 'BidiRtl'],
    '/',
    ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
    ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote','CreateDiv'],
    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
    ['Link','Unlink','Anchor'],
    ['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak'],
    '/',
    ['Styles','Format','Font','FontSize'],
    ['TextColor','BGColor'],
    ['Maximize', 'ShowBlocks','-','About']
];
Förenkla dialoger, ta bort onödigt:
http://docs.cksource.com/CKEditor_3.x/Developers_Guide/Dialog_Customization
*/

CKEDITOR.on( 'dialogDefinition', function( ev )
	{
		// Take the dialog name and its definition from the event data.
		var dialogName = ev.data.name;
		var dialogDefinition = ev.data.definition;
 
		// Check if the definition is from the dialog we're
		// interested on (the Link dialog).
		if ( dialogName == 'link' )
		{
			// FCKConfig.LinkDlgHideAdvanced = true
			dialogDefinition.removeContents( 'advanced' );
 
			// FCKConfig.LinkDlgHideTarget = true
			dialogDefinition.removeContents( 'target' );
/*
Enable this part only if you don't remove the 'target' tab in the previous block.
 
			// FCKConfig.DefaultLinkTarget = '_blank'
			// Get a reference to the "Target" tab.
			var targetTab = dialogDefinition.getContents( 'target' );
			// Set the default value for the URL field.
			var targetField = targetTab.get( 'linkTargetType' );
			targetField[ 'default' ] = '_blank';
*/
		}
 
		if ( dialogName == 'image' )
		{
			// FCKConfig.ImageDlgHideAdvanced = true	
			dialogDefinition.removeContents( 'advanced' );
			// FCKConfig.ImageDlgHideLink = true
			dialogDefinition.removeContents( 'Link' );
		}
 
		if ( dialogName == 'flash' )
		{
			// FCKConfig.FlashDlgHideAdvanced = true
			dialogDefinition.removeContents( 'advanced' );
		}
 
	});
