$(function(){	
	$('#ads').blur(function(){
		$('#group-ads').removeClass('success');
		$('#group-ads').removeClass('warning');
        var ads = $('#ads').val();
		if (ads=='')
    		{
    			$('#group-ads').addClass('warning');
    			$('#help-ads').html('请选择广告位');
    		}else{
    			$('#group-ads').addClass('success');
    			$('#help-ads').html('正确');
    		} 
	});
	
	$('#title').blur(function(){
		$('#group-title').removeClass('success');
		$('#group-title').removeClass('warning');
        var title = $('#title').val();
		if (title=='')
    		{
    			$('#group-title').addClass('warning');
    			$('#help-title').html('请输入广告名称');
    		}else{
    			$('#group-title').addClass('success');
    			$('#help-title').html('正确');
    		} 
	});
	
	/*$('#fileField').blur(function(){
		$('#group-fileField').removeClass('success');
		$('#group-fileField').removeClass('warning');
        var fileField = $('#fileField').val();
		if (fileField=='')
    		{
    			$('#group-fileField').addClass('warning');
    			$('#help-fileField').html('请上传图片');
    		}else{
    			$('#group-fileField').addClass('success');
    			$('#help-fileField').html('正确');
    		} 
	});*/
});
function clickAds()
{
	var ads = $('#ads').val();
	if (ads=='')
	{
		$('#group-ads').addClass('warning');
    	$('#help-ads').html('请选择广告位');
    	return false;
	}
	var title = $('#title').val();
	if (title=='')
	{
		$('#group-title').addClass('warning');
    	$('#help-title').html('请输入广告名称');
    	return false;
	}
	/*var fileField = $('#fileField').val();
	if (fileField=='')
	{
		$('#group-fileField').addClass('warning');
    	$('#help-fileField').html('请上传图片');
    	return false;
	}*/
}