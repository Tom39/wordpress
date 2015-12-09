jQuery(function($) {

	//子フレームのマウス位置をreturn
	$(document).mousemove(function(e) {
		/* Act on the event */
		parent.postMessage(getMousePosition(e).x + "," + getMousePosition(e).y, "*");
	});


	function getMousePosition(e) {
		var obj = new Object();
	 
		if(e) {
			obj.x = e.pageX;
			obj.y = e.pageY;
		} else {
			obj.x = event.x + document.body.scrollLeft;
			obj.y = event.y + document.body.scrollTop;
		}
	 
		return obj;
	}




});
