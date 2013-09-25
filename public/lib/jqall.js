$(document).ready(function(){
	$(".toshare a").click(function(){
		$(".toshare p").toggle();
		$(".toshare a .gt").toggleClass("current");
	});

	//列表页tab
	$(".tab_control").find("a").click(function(){
		$(".tab_control").find(".on").removeClass("on");
		$(this).addClass("on");
	})
});