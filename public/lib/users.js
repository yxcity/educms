$(function(){
        $('#username').blur(function (){
			$('#group-username').removeClass('success');
			$('#group-username').removeClass('warning');
        	var username = $('#username').val();
        	var id = $('#username').attr('uid');
        	if (username=='')
			{
				$('#group-username').addClass('warning');
				$('#help-username').html('请输入账号');
				return false;
			}
            if(username.length<'6'){
				$('#group-username').addClass('warning');
				$('#help-username').html('用户名长度需至少6位');
				return false;
			}
			
			var regUsername = /[^\w\/]/ig;
	        if (regUsername.test(username)) {
	            $('#group-username').addClass('warning');
				$('#help-username').html('用户名只能是字母或数字');
	            return false;
	        }
		
			$.ajax({
                type: 'GET',
                url: '?ac=checking&id='+id,
                data: { "username": username },
                dataType: 'text',
                cache: false,
                success: function (msg) {
                    if (msg == '1') {
                       $('#group-username').addClass('warning');
				       $('#help-username').html('<font color=red>用户名已经被占用，请更换</font>');
                        return false;
                    }else if (msg == '0') {
                        $('#group-username').addClass('success');
			            $('#help-username').html('正确');
                    }
                }
            });
			
            }); 
        
        $('#realname').blur(function(){
			$('#group-realname').removeClass('success');
			$('#group-realname').removeClass('warning');
        	var realname = $('#realname').val();
    		if (realname=='')
    		{
    			$('#group-realname').addClass('warning');
    			$('#help-realname').html('请输入名称');
    		}else{
    			$('#group-realname').addClass('success');
    			$('#help-realname').html('正确');
    		} 
            });

        $('#email').blur(function(){
			$('#group-email').removeClass('success');
			$('#group-email').removeClass('warning');
        	var email = $('#email').val();
			if(email =='')
			{
				$('#group-email').addClass('warning');
				$('#help-email').html('请输入邮箱');
			}else{
				if (!email.match(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/))
				{
					$('#group-email').addClass('warning');
					$('#help-email').html('邮件格式不合法');
				}else
				{
                    $('#group-email').addClass('success');
	    			$('#help-email').html('正确');
				}
				
				}
            });
        $('#tel').blur(function(){
        	var tel = $('#tel').val();
			$('#group-tel').removeClass('warning');
			$('#group-tel').removeClass('success');
    		if (tel=='')
    		{
    			$('#group-tel').addClass('warning');
    			$('#help-tel').html('请输入电话号码');
    		}else
    		{
    			$('#group-tel').addClass('success');
    			$('#help-tel').html('正确');
        	}
            });
			
		$('#city').blur(function(){
			$('#group-city').removeClass('success');
			$('#group-city').removeClass('warning');
			var city = $('#city').val();
			if (city == '')
			{
				$('#group-city').addClass('warning');
				$('#help-city').html('请选择省份');
			}else
			{
				$('#group-city').addClass('success');
				$('#help-city').html('正确');
			}
			});
        $('#address').blur(function(){
			$('#group-address').removeClass('success');
			$('#group-address').removeClass('warning');
			var address = $('#address').val();
			if (address == '')
			{
				$('#group-address').addClass('warning');
				$('#help-address').html('请输入地址');
			}else
			{
				$('#group-address').addClass('success');
				$('#help-address').html('正确');
			}
			});
		$('#shopCount').blur(function(){
			$('#group-shopCount').removeClass('success');
			$('#group-shopCount').removeClass('warning');
			var address = $('#shopCount').val();
			if (address == '')
			{
				$('#group-shopCount').addClass('warning');
				$('#help-shopCount').html('请输入门店数量');
			}else
			{
				$('#group-shopCount').addClass('success');
				$('#help-shopCount').html('正确');
			}
			});
		$('#password').blur(function(){
			$('#group-password').removeClass('success');
			$('#group-password').removeClass('warning');
			var password = $('#password').val();
			if (password == '')
			{
				$('#group-password').addClass('warning');
				$('#help-password').html('请输入密码');
			}else if(password.length<'6')
			{
				$('#group-password').addClass('success');
				$('#help-password').html('密码长度需至少6位');
			}else
				{
					$('#group-password').addClass('success');
					$('#help-password').html('正确');
				}
			});
		$('#repassword').blur(function(){
			$('#group-repassword').removeClass('success');
			$('#group-repassword').removeClass('warning');
			var password = $('#password').val();
			var repassword = $('#repassword').val();
			if (repassword == '')
			{
				$('#group-repassword').addClass('warning');
				$('#help-repassword').html('请输入二次密码');
			}else
			{
				if(password != repassword)
				{
					$('#group-repassword').addClass('warning');
					$('#help-repassword').html('两次密码输入不一致');
				}else
				{
					$('#group-repassword').addClass('success');
				    $('#help-repassword').html('正确');
				}
			}
			});
		
        });
        
        function clickForm()
        {
        	var username = $('#username').val();
			if (username=='')
			{
				$('#group-username').addClass('warning');
				$('#help-username').html('请输入账号');
				return false;
			}
			
			if(username.length<'6'){
				$('#group-username').addClass('warning');
				$('#help-username').html('用户名长度需至少6位');
				return false;
			}
			
			var regUsername = /[^\w\/]/ig;
	        if (regUsername.test(username)) {
	            $('#group-username').addClass('warning');
				$('#help-username').html('用户名只能是字母或数字');
	            return false;
	        }

			var realname = $('#realname').val();
			if (realname=='')
			{
				$('#group-realname').addClass('warning');
				$('#help-realname').html('请输入名称');
				return false;
			}
			
			var email = $('#email').val();
			if(email =='')
			{
				$('#group-email').addClass('warning');
				$('#help-email').html('请输入邮箱');
				return false;
			}
			
			if (!email.match(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/))
			{
				$('#group-email').addClass('warning');
				$('#help-email').html('邮件格式不合法');
				return false;
			}
			
			var tel = $('#tel').val();
			if (tel=='')
			{
				$('#group-tel').addClass('warning');
				$('#help-tel').html('请输入电话号码');
				return false;
			}
			
			var city = $('#city').val();
			if (city == '')
			{
				$('#group-city').addClass('warning');
				$('#help-city').html('请选择省份');
				return false;
			}
			var area = $('#area').val();
			if (area == '')
			{
				$('#group-area').addClass('warning');
				$('#help-area').html('请选择地区');
				return false;
			}
			var address = $('#address').val();
			if (address == '')
			{
				$('#group-address').addClass('warning');
				$('#help-address').html('请输入地址');
				return false;
			}
			
			var shopCount=$('#shopCount').val();
			if (shopCount == '')
			{
				$('#group-shopCount').addClass('warning');
				$('#help-shopCount').html('请输入门店数量');
				return false;
			}
        }