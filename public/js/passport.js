/* 注册登录验证 */
//提示
var Tips = {
    //显示错误提示
    ShowError: function (id, info) {
        $('#' + id).show().removeClass('success').addClass('error').html(info);
    },
    //显示正确提示
    ShowRight: function (id, info) {
        $('#' + id).show().removeClass('error').addClass('success').html(info || '&nbsp;');
    },
    //清除错误提示
    ClearError: function (id) {
        $('#' + id).removeClass('error').html('');
    }
}

//商家注册
var Region = {
    //密码验证
    PwdValidate: function () {
        var passWord = $.trim($('#txtPwd').val());
        var regPwd = /^[\W\w]{6,20}$/; //---/^[\@A-Za-z0-9\!\#\$\%\^\&\*\.\~]{6,22}$/;//数字、字母以及特殊字符
        var pLength = passWord.length;
        if (pLength == 0) {
            Tips.ShowError('spPwd', '请输入登录密码');
            return false;
        }
        else if (pLength > 20 || pLength < 6) {
            Tips.ShowError('spPwd', '长度不对');
            return false;
        }
        else {
            if (!regPwd.test(passWord)) {
                Tips.ShowError('spPwd', '格式不对');
                return false;
            }
            else {
                Tips.ShowRight('spPwd');
                return true;
            }
        }
    },
    //确认密码
    ConfirmPwd: function () {
        var passWord = $.trim($('#txtConfirmPwd').val());
        var pLength = passWord.length;
        if (pLength == 0) {
            Tips.ShowError('spConfirmPwd', '请输入确认密码');
            return false;
        }

        if ($.trim($('#txtConfirmPwd').val()) != $.trim($('#txtPwd').val())) {
            Tips.ShowError('spConfirmPwd', '密码不一致');
            return false;
        } else {
            Tips.ShowRight('spConfirmPwd');
        }
        return true;
    },
    //用户名验证
    emailValidate: function () {
        
        var email = $.trim($('#email').val());
        if (email.length < 1) {
            Tips.ShowError('spEmail', '请输入邮箱');
            $('#email').attr('right', 0);
            return false;
        }
        var regEmail = /^\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/;
        if (!regEmail.test(email)) {
            Tips.ShowError('spEmail', '邮箱格式不对');
            $('#email').attr('right', 0);
            return false;
        }
        //lastvalue属性为最后一次输入值,判断与当前输入值是否一致,一致时不进行异步验证
        //if (!$('#email').attr('lastvalue') || $('#email').attr('lastvalue') != $.trim($('#email').val())) {
            //验证是否被使用
            $.ajax({
                type: 'GET',
                url: '?ac=checking',
                data: { "email": $("#email").val() },
                dataType: 'text',
                cache: false,
                success: function (msg) {
                    if (msg == '1') {
                        Tips.ShowError('spEmail', '该邮箱已被注册');
                        $('#spEmail').attr('right', 0);
                        return false;
                    }
                    else if (msg == '0') {
                        Tips.ShowRight('spEmail');
                        $('#spEmail').attr('right', 1); //置成功标志
                    }
                }
            });
        //}
        $('#email').attr('lastvalue', $.trim($('#email').val())); //保存最后一次输入值
		Tips.ShowRight('spEmail');
        return true;
    },
    //验证码验证
    ImgCodeValidate: function () {
        if ($.trim($('#txtCode').val()).length == 0) {
            Tips.ShowError('spCode', '请输入验证码');
            return false;
        }
        else if ($.trim($('#txtCode').val()).length != 4) {
            Tips.ShowError('spCode', '验证码长度不对');
            return false;
        }
        else {
            Tips.ClearError('spCode');
            return true;
        }
    },
    //验证企业名称
    ComNameValidate: function () {
        if ($.trim($('#txtCompanyName').val()).length == 0) {
            Tips.ShowError('spCompanyName', '请输入企业名称');
            return false;
        }
        else {
            Tips.ClearError('spCompanyName');
            return true;
        }
    },
    //验证用户名
    userNameValidate: function () {
	    var username=$.trim($('#username').val());
        if (username.length == 0 || username.length <= 5 || username.length > 19) {
            Tips.ShowError('spUserName', '请输入用户名，且长度大于5小于20');
            return false;
        }
		
		var regUser = /[^\w\/]/ig;
        if (regUser.test(username)) {
            Tips.ShowError('spUserName', '只能输入字母或数字');
            $('#username').attr('right', 0);
            return false;
        }
		
        //lastvalue属性为最后一次输入值,判断与当前输入值是否一致,一致时不进行异步验证
        //if (!$('#username').attr('lastvalue') || $('#username').attr('lastvalue') != $.trim($('#username').val())) {
            //验证是否被使用
            $.ajax({
                type: 'GET',
                url: '?ac=checking',
                data: { "username": $("#username").val() },
                dataType: 'text',
                cache: false,
                success: function (msg) {
                    if (msg == '1') {
                        Tips.ShowError('spUserName', '用户名已经被注册');
                        $('#spUserName').attr('right', 0);
                        return false;
                    }
                    else if (msg == '0') {
                        Tips.ShowRight('spUserName');
                        $('#spUserName').attr('right', 1); //置成功标志
                    }
                }
            });
        //}
		 $('#username').attr('lastvalue', $.trim($('#username').val())); //保存最后一次输入值
		 Tips.ShowRight('spUserName');
        return true;
    },
    //验证联系人手机
    MobileValidate: function () {
        var regx = /^(13|15|18|013|015|018)\d{9}$/;
        var str = $.trim($('#txtMobile').val());
        if (str.length == 0) {
            Tips.ShowError('spMobile', '请填写手机号码');
            return false;
        }
        else if (regx.exec(str) == null) {
            Tips.ShowError('spMobile', '请填写有效的手机号码');
            return false;
        }
        else {
            Tips.ClearError('spMobile');
            return true;
        }
    },
    //表单验证
    CheckSave: function () {
        var flag = true;
        if (!Region.emailValidate())
            flag = false;
        if (!Region.PwdValidate())
            flag = false;
        if (!Region.ConfirmPwd())
            flag = false;
        if (!Region.ComNameValidate())
            flag = false;
        if (!Region.userNameValidate())
            flag = false;
        if (!Region.MobileValidate())
            flag = false;

        //为了避免重复ajax请求进行验证,采用right属性来解决,1为通过验证
        if ($.trim($('#email').val()).length < 1) {//防止不填写邮箱
            Tips.ShowError('spEmail', '请输入邮箱');
            $('#email').attr('right', 0);
            flag = false;
        } else if ($('#email').attr('right') == '0') {
            flag = false;
        } else if ($('#email').attr('right') == 'undefined') {
            if (!Region.NameValidate())
                flag = false;
        }
        return flag;
    }
}
