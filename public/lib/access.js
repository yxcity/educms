$(function(){
		$('#aclName').blur(function(){
			$('#group-aclName').removeClass('success');
			$('#group-aclName').removeClass('warning');
	        var name = $('#aclName').val();
			if (name=='')
	    	{
	    			$('#group-aclName').addClass('warning');
	    			$('#help-aclName').html('请输入名称');
	    	}else{
	    			$('#group-aclName').addClass('success');
	    			$('#help-aclName').html('正确');
	    	} 
		});
		
		$('#actKEY').blur(function(){
			$('#group-actKEY').removeClass('success');
			$('#group-actKEY').removeClass('warning');
	        var actKEY = $('#actKEY').val();
			if (actKEY=='')
	    	{
	    			$('#group-actKEY').addClass('warning');
	    			$('#help-actKEY').html('请输入操作唯一标识');
	    	}else{
	    			$('#group-actKEY').addClass('success');
	    			$('#help-actKEY').html('正确');
	    	} 
		});
		
		$('#aclURL').blur(function(){
			$('#group-aclURL').removeClass('success');
			$('#group-aclURL').removeClass('warning');
	        var aclURL = $('#aclURL').val();
			if (aclURL=='')
	    	{
	    			$('#group-aclURL').addClass('warning');
	    			$('#help-aclURL').html('请输入链接地址');
	    	}else{
	    			$('#group-aclURL').addClass('success');
	    			$('#help-aclURL').html('正确');
	    	} 
		});
	});
function clickAccess()
{
	var name = $('#aclName').val();
	var actKEY = $('#actKEY').val();
	var aclURL = $('#aclURL').val();
	if (name=='')
	{
		$('#group-aclName').addClass('warning');
    	$('#help-aclName').html('请输入名称');
    	return false;
	}
	if (aclURL=='')
	{
		$('#group-aclURL').addClass('warning');
    	$('#help-aclURL').html('请输入链接地址');
    	return false;
	}
	if (actKEY=='')
	{
		$('#group-actKEY').addClass('warning');
    	$('#help-actKEY').html('请输入操作唯一标识');
    	return false;
	}
}