$(function(){
	$('#realname').blur(function(){
		$('#group-realname').removeClass('success');
		$('#group-realname').removeClass('warning');
		var realname=$('#realname').val();
		if (realname=='')
		{
			$('#group-realname').addClass('warning');
			$('#help-realname').html('请输入联系人');
			i=false;
		}else
		{
			$('#group-realname').addClass('success');
			$('#help-realname').html('正确');
		}
	});
	$('#contact').blur(function(){
		$('#group-contact').removeClass('success');
		$('#group-contact').removeClass('warning');
		var contact=$('#contact').val();
		if (contact=='')
		{
			$('#group-contact').addClass('warning');
			$('#help-contact').html('请输入联系方式');
			i=false;
		}else
		{
			$('#group-contact').addClass('success');
			$('#help-contact').html('正确');
		}
	});
	$('#ask').blur(function(){
		$('#group-ask').removeClass('success');
		$('#group-ask').removeClass('warning');
		var ask=$('#ask').val();
		if(ask=='')
		{
			$('#group-ask').addClass('warning');
			$('#help-ask').html('请输入提交问题');
			i=false;
		}else
		{
			$('#group-ask').addClass('success');
			$('#help-ask').html('正确');
		}
	});
});

function clickForm(){
	var i=true;
	var realname=$('#realname').val();
	if (realname=='')
	{
		$('#group-realname').addClass('warning');
    	$('#help-realname').html('请输入联系人');
		i=false;
	}else
	{
		$('#group-realname').addClass('success');
    	$('#help-realname').html('正确');
	}
	
	var contact=$('#contact').val();
	if (contact=='')
	{
		$('#group-contact').addClass('warning');
    	$('#help-contact').html('请输入联系方式');
		i=false;
	}else
	{
		$('#group-contact').addClass('success');
    	$('#help-contact').html('正确');
	}
	
	var ask=$('#ask').val();
	if(ask=='')
	{
		$('#group-ask').addClass('warning');
    	$('#help-ask').html('请输入提交问题');
		i=false;
	}else
	{
		$('#group-ask').addClass('success');
    	$('#help-ask').html('正确');
	}
	return i;
}