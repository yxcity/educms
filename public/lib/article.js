$(function(){
	$('#art_title').blur(function(){
		$('#group-art_title').removeClass('success');
		$('#group-art_title').removeClass('warning');
        var art_title = $('#art_title').val();
		if (art_title=='')
    		{
    			$('#group-art_title').addClass('warning');
    			$('#help-art_title').html('请输入标题');
    		}else{
    			$('#group-art_title').addClass('success');
    			$('#help-art_title').html('正确');
    		} 
		});
	$('#art_content').blur(function(){
		$('#group-art_content').removeClass('success');
		$('#group-art_content').removeClass('warning');
        var art_title = $('#art_content').val();
		if (art_title=='')
    		{
    			$('#group-art_content').addClass('warning');
    			$('#help-art_content').html('请输入内容');
    		}else{
    			$('#group-art_content').addClass('success');
    			$('#help-art_content').html('正确');
    		} 
		});
		
		
	});
function checkArt()
{
	var art_title = $('#art_title').val();
	if (art_title=='')
	{
		$('#group-art_title').addClass('warning');
    	$('#help-art_title').html('请输入标题');
    	return false;
	}
	var art_content = $('#art_content').val();
	if (art_content=='')
	{
		$('#group-art_content').addClass('warning');
    	$('#help-art_content').html('请输入内容');
    	return false;
	}
}