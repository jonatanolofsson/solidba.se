/**
 * @author Kalle Karlsson [kakar]
 * Functions for resizeing the cointainer div of the FlashURL object
 */
function setFlashWidth(divID, newW){
	$('#'+divID).css('width', newW);
	$('#'+divID).closest("span").css('width', newW);
}
function setFlashHeight(divID, newH){
	$('#'+divID).css('height', newH);
	$('#'+divID).closest("span").css('height', newH);
}
function setFlashSize(divID, newW, newH){
	setFlashWidth(divID, newW);
	setFlashHeight(divID, newH);
}
