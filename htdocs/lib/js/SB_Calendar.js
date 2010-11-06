SB_Calendar = {};
SB_Calendar.popup = function () {
	if(window.open($(this).attr('href')+'&js=true', 'cal_popup', 'width=300,height=450')) {
		return false;
	} else {return true;}
}