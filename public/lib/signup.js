$(function() {
			$('#realname').blur(function(){
				var realname = $('#realname').val();
				$('#group-realname').removeClass('success');
			    $('#group-realname').removeClass('warning');
				if (realname=='')
				{
					$('#group-realname').addClass('warning');
					$('#help-realname').html('请输入公司名称');
					
				}else
				{
					$('#group-realname').addClass('success');
    			    $('#help-realname').html('正确');
				}
			});
			$('#tel').blur(function(){
				var tel = $('#tel').val();
				$('#group-tel').removeClass('success');
			    $('#group-tel').removeClass('warning');
				if (tel=='')
				{
					$('#group-tel').addClass('warning');
					$('#help-tel').html('请输入电话或者手机');
					
				}else
				{
					$('#group-tel').addClass('success');
    			    $('#help-tel').html('正确');
				}
			});
			$('#email').blur(function(){
				var email = $('#email').val();
				$('#group-email').removeClass('success');
			    $('#group-email').removeClass('warning');
				if (email=='')
				{
					$('#group-email').addClass('warning');
					$('#help-email').html('请输入邮箱');
					
				}else
				{
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
			$('#username').blur(function(){
				var username = $('#username').val();
				$('#group-username').removeClass('success');
			    $('#group-username').removeClass('warning');
				if (username=='')
				{
					$('#group-username').addClass('warning');
					$('#help-username').html('请输入用户名');
					
				}else{
					$.post("/users/clickuser", { "username": username },function(o){
						if (o.isok=="false")
						{
							$('#group-username').addClass('success');
    			            $('#help-username').html('正确');
						}else
						{
							$('#group-username').addClass('warning');
					        $('#help-username').html('用户名已被占用，请更换');
						}
						}, "json");
					
				}
			});
			$('#password').blur(function(){
				var password = $('#password').val();
				$('#group-password').removeClass('success');
			    $('#group-password').removeClass('warning');
				if (password=='')
				{
					$('#group-password').addClass('warning');
					$('#help-password').html('请输入用户名');
					
				}else
				{
					if (password.length < 6)
					{
						$('#group-password').addClass('warning');
						$('#help-password').html('密码不能少于6位数');
					}else
					{
						$('#group-password').addClass('success');
    			        $('#help-password').html('正确');
					}
					
				}
			});
			
			$('#repassword').blur(function(){
				var password = $('#password').val();
				var repassword = $('#repassword').val();
				$('#group-repassword').removeClass('success');
			    $('#group-repassword').removeClass('warning');
				if (repassword=='')
				{
					$('#group-repassword').addClass('warning');
					$('#help-repassword').html('请输入二次密');
					
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
			var i=true;
			var realname = $('#realname').val();
			if (realname=='')
			{
				$('#group-realname').addClass('warning');
				$('#help-realname').html('请输入公司名称');
				i=false;
			}
			var tel = $('#tel').val();
			if (tel=='')
			{
				$('#group-tel').addClass('warning');
				$('#help-tel').html('请输入电话或者手机');
				i=false;
			}
			var email = $('#email').val();
			if (email=='')
			{
				$('#group-email').addClass('warning');
				$('#help-email').html('请输入邮箱');
				i=false;
			}
			if (!email.match(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/))
			{
				$('#group-email').addClass('warning');
				$('#help-email').html('邮件格式不合法');
			}
			var username = $('#username').val();
			if (username=='')
			{
				$('#group-username').addClass('warning');
				$('#help-username').html('请输入用户名');
				i=false;
			}else
			{
				$.post("/users/clickuser", { "username": username },function(o){
							if (o.isok=="false")
							{
								$('#group-username').addClass('success');
								$('#help-username').html('正确');
							}else
							{
								$('#group-username').addClass('warning');
								$('#help-username').html('用户名已经被占用，请更换');
							}
						}, "json");
			}
			var password = $('#password').val();
			if (password=='')
			{
				$('#group-password').addClass('warning');
				$('#help-password').html('请输入密码');
				i=false;
			}else
			{
				if (password.length < 6)
				{
					$('#group-password').addClass('warning');
					$('#help-password').html('密码不能少于6位数');
					i=false;
				}
			}
			
			var repassword = $('#repassword').val();
			if (repassword == '')
			{
				$('#group-repassword').addClass('warning');
				$('#help-repassword').html('请输入二次密码');
				i=false;
			}else
			{
				if(password != repassword)
				{
					$('#group-repassword').addClass('warning');
					$('#help-repassword').html('两次密码输入不一致');
					i=false;
				}
			}
			if (i==false)
			{
				return false;
			}
		}