function previewImg(img, selection)	{
	wi1= parseInt($('#imgcropper #originalImage').css('width'));
	hi1= parseInt($('#imgcropper #originalImage').css('height'));

	wp = Math.min(selection.width, 200); //Ensure maxwidth
	scale = wp / (selection.width || wp);
	hp = Math.round(selection.height * scale);
	mx = Math.round(selection.x1 * scale);
	my = Math.round(selection.y1 * scale);
	wi2= Math.round(wi1 * scale);
	hi2= Math.round(hi1 * scale);

	$('#imagePreview').css({
		width: wi2 + 'px',
		height: hi2 + 'px',
		marginLeft: '-' + mx + 'px',
		marginTop: '-' + my + 'px'
	});
	$('#imagePreviewDiv').css({
		width: wp + 'px',
		height: hp + 'px'
	});
	$('input#cropimgx').val(selection.x1);
	$('input#cropimgy').val(selection.y1);
	$('input#cropimgw').val(selection.width);
	$('input#cropimgh').val(selection.height);
}
$(function(){
	$('<div id="imagePreviewDiv"><img id="imagePreview" src="'+$('#imgcropper > #originalImage').attr('src')+'" style="position: relative;" /></div>')
		.css({
			width: 200 + 'px',
			float: 'left',
			position: 'relative',
			overflow: 'hidden'
		}).insertAfter('#imgcropper > #originalImage');
	$('#imagePreview').css('width', '100%');
	pix1 = parseInt($('input#cropimgx').val());
	piy1 = parseInt($('input#cropimgy').val());
	piw = parseInt($('input#cropimgw').val()); pix2 = piw + pix1;
	pih = parseInt($('input#cropimgh').val()); piy2 = pih + piy1;

	if(pix1 !== false && piy1 !== false && piw !== false && pih !== false) {
		$('#imgcropper > #originalImage').imgAreaSelect({
			onSelectChange: previewImg,
			x1: pix1,
			y1: piy1,
			x2: pix2,
			y2: piy2
		});
		previewImg($('#imgcropper > #originalImage'), {x1: pix1, y1: piy1, x2: pix2, y2: piy2, width: piw, height: pih});
	} else {
		$('#imgcropper > #originalImage').imgAreaSelect({
			onSelectChange: previewImg
		});
	}
	$('#imgcropper').closest('.ui-tabs').bind('tabsshow', function(event, ui) {
					if(parseInt($('.imgareaselect-selection').css('width'))) {
						if(ui.index == 0) {
							$('#imgcropper > #originalImage').imgAreaSelect({
								show: true
							});
						} else {
							$('#imgcropper > #originalImage').imgAreaSelect({
								hide: true
							});
						}
					}
				}
	);


	/*
	var sizeX = $('#resimgx').val();
	var sizeY = $('#resimgy').val();
	$('#resval').css({
		position: 'relative'
	});

	$('<div></div>').css({
		width: 200 + 'px',
		height:  10  + 'px',
		margin: '10px'
	}).appendTo('#resval').slider({
		min: 50,
		value: 100,
		change: function(event, ui) {
					w = Math.round(sizeX * ui.value / 100);
					h = Math.round(sizeY * ui.value / 100);
					$('#resimgx').val(w);
					$('#resimgy').val(h);
					$('#resper').text(ui.value + ' %');
				}
	}).after('<span id="resper">100 %</span>');*/

	$('<span></span>').insertAfter($('#tintimgr, #tintimgg, #tintimgb').hide()).css({
		width: 400 + 'px',
		margin: '10px',
		display: 'block',
		float: 'left'
	}).each(function(){
			val = parseInt($(this).prev('input').val()); if(val=='') val=255;
			$(this).slider({
				value: val,
				max: 500,
				animate: 'true',
				change: function(event, ui) {
							me = $(this);
							me.next('span').text(me.slider('option', 'value'));
							me.prev().val(me.slider('option', 'value'))
						}
			}).after('<span style="float: left;">'+val+'</span><div style="clear: both;"></div>');
		});

	if(!$('#tint').is(':checked')) {
		$('#tintimgr, #tintimgg, #tintimgb').next('span').slider('disable');
		$('#tintbw,#tintsepia,#tintlight,#tintdark,#tintpreview').attr('disabled', 'true');
	}

	$('#tint').click(function(){
		if($(this).is(':checked')) {
			$('#tintimgr, #tintimgg, #tintimgb').next('span').slider('enable');
			$('#tintbw,#tintsepia,#tintlight,#tintdark,#tintpreview').attr('disabled', '');
		} else {
			$('#tintimgr, #tintimgg, #tintimgb').next('span').slider('disable');
			$('#tintbw,#tintsepia,#tintlight,#tintdark,#tintpreview').attr('disabled', 'true');
		}
		return true;
	});

	$('#tintbw').click(function(e){
		$('#tintimgr, #tintimgg, #tintimgb').val('255').next('span').slider('option', 'value', 255).next('span').text('255');
		return false;
	});
	$('#tintsepia').click(function(e){
		$('#tintimgr').val('304').next('span').slider('option', 'value', 304).next('span').text('304');
		$('#tintimgg').val('242').next('span').slider('option', 'value', 242).next('span').text('242');
		$('#tintimgb').val('209').next('span').slider('option', 'value', 209).next('span').text('209');
		return false;
	});
	$('#tintlight').click(function(e){
		$('#tintimgr, #tintimgg, #tintimgb').val('400').next('span').slider('option', 'value', 400).next('span').text('400');
		return false;
	});
	$('#tintdark').click(function(e){
		$('#tintimgr, #tintimgg, #tintimgb').val('127').next('span').slider('option', 'value', 127).next('span').text('127');
		return false;
	});
	$('#tintpreview').click(function(e){
		$('<img src="'+$('#originalImage').attr('src')+'&tr='+$('#tintimgr').val()+'&tg='+$('#tintimgg').val()+'&tb='+$('#tintimgb').val()+'" />').dialog({
			modal:true,
			resizable: false
			});
		return false;
	});
});