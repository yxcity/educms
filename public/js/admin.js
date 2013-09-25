
//鼠标悬停切换行背景颜色
function switchRowBgColor()
{
	$("tr").mouseover(function(){
		$(this).addClass("over");
	}).mouseout(function(){
		$(this).removeClass("over");
	});
}

//隔行变色的实现
function setRowBgColor()
{
	$("tr:even").addClass("dbl");
}

//高亮导航菜单操作
function highlightMenu()
{
	//getCookie
	var _this_a = $.cookie("_this_a");
	var _this_item = $.cookie("_this_item");
	
	_this_a = _this_a ? _this_a : "#dashboard-menu";//控制面板
	_this_item = _this_item ? _this_item : "37563370ded05f5a3bf3ec";//控制主页
	
	if(_this_a){
		$(".sidebar-nav a[href="+_this_a+"]").removeClass("collapsed");
		$(_this_a).addClass("in");	
	}
	if(_this_item){
		$("#"+_this_item).css("backgroundColor","#ccc");	
	}
	
	
	
	//setCookie
	$(".sidebar-nav a[data-toggle=collapse]").click(function(){
		var id = $(this).attr("href");
		if(id){
			$.cookie('_this_a',id,{path:"/"});
		}
	});
	
	$("ul.nav-list li a").click(function(){
		var id2 = this.id;
		$.cookie('_this_item',id2,{path:"/"});
	});
}

//全选与反选操作
function jsCheckBox()
{
	$("#btn_chk").click(function(){
		if($(this).is(":checked")){
			$("[name=chkItem]:checkbox").each(function(){
				$(this).attr("checked",true);
			});
		}else{
			$("[name=chkItem]:checkbox").each(function(){
				$(this).attr("checked",false);
			});
		}
	});
}

//Ajax批处理
function jsAjaxBatHandle(url)
{
	var allItems = new Array();
	$("[name=chkItem]:checkbox").each(function () {
        if ($(this).is(":checked")) {
            allItems.push($(this).attr("value"));
        }
    });
	
	if(allItems.length<1){
		alert("请选择操作项!");
		return false;
	}		
	
	$.ajax({
	  type: 'POST',
	  url: url,
	  data: {check_items:allItems.join(",")},
	  success: function(result){
			alert(result.msg);
			window.location.reload(); 	
	  },
	  dataType: "json"
	});		
}
	
$(document).ready(function(){

	switchRowBgColor();
	setRowBgColor();
	jsCheckBox();
	highlightMenu();
	//批处理示例
	/*$("#btn_clear").click(function(){
		jsAjaxBatHandle("test.php");	
	});*/
});