/*处理HTML模板*/
function tmpl_parser(input, tmpl_data) {
	if (!input) return "";
	var tmpl_func = /\/\*TEMPLATES\*\//.test(input) ? new Function("obj", "var p=[];with(obj){p.push('" + function (input) {
		input = input.replace(/[\r\t\n]/g, " ").split("<#TS#").join("	");
		while (/((^|#TS#>)[^\t]*)'/g.test(input)) input = input.replace(/((^|#TS#>)[^\t]*)'/g, "$1\r");
		return input.replace(/\t=(.*?)#TS#>/g, "',$1,'").split("	").join("');").split("#TS#>").join("p.push('").split("\r").join("\\'")
	}(input) + "');}return p.join('');") : tmpl_parser($(input).html());
	return tmpl_data && typeof tmpl_func == "function" ? tmpl_func(tmpl_data) : tmpl_func;
}

/*顶部显示警示信息*/
function msg_alert(msg,msecs){
        var dlg_str = '<div class="alert fade in" id="alert_msg" style="display:none;">\
                        <button type="button" class="close" data-dismiss="alert">&times;</button>\
                        <span></span>\
                        </div>';
        if(typeof msecs != 'number')
            msecs = 3000;
        if(!$('#alert_msg').get(0)){
            if($('.breadcrumb').get(0)){
                $(dlg_str).insertAfter($('.breadcrumb').get(0));
            }else if($('.container-fluid').get(0)){
                $(dlg_str).insertBefore($('.container-fluid').get(0));
            }else{/*find no place to insert*/
                return;
            }
        }
        
        $('#alert_msg span').html(msg);
        $('#alert_msg').fadeIn();
        window.setTimeout(function(){
            $('#alert_msg').fadeOut();
        },msecs);
}

var SCRM = {
    getDefaultButton:function(){
        var defSelector = $(document).data('defbutton');
        if(defSelector){
            return $(defSelector);
        }
        return null;
    },
    setDefaultButton:function(selector){
        $(document).data('defbutton',selector)
    },
    getQueryString:function(name) {
        name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
        var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
            results = regex.exec(location.search);
        return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
    },
    msg_alert:window.msg_alert,
    parse_tmpl:window.tmpl_parser,
    rand:function(min,max){
        return min+Math.floor(Math.random()*(max-min+1));
    }
}
        
$(document).ready(function(){
    $(document).keypress(function (e) {
            var theEvent = window.event || e; 
            var code = theEvent.keyCode || theEvent.which; 
            if (code == 13) {
                var defBtn = SCRM.getDefaultButton();
                if(defBtn && defBtn.get(0)){
                    defBtn.click(); 
                    return false;
                }else{
                    return true;
                }
            } 
    });
    /*window.setInterval(function(){
        $.ajax({
            type: 'get',
            url: '/message/syncuser',
            async: true,
            cache:false,
        });
    }, 1000000)*/
});
  