$(function(){
	$('#level_name').blur(function(){
		$('#group-level_name').removeClass('success');
		$('#group-level_name').removeClass('warning');
        var name = $('#level_name').val();
		if (name=='')
    		{
    			$('#group-level_name').addClass('warning');
    			$('#help-level_name').html('请输入会员等级名称');
    		}else{
    			$('#group-level_name').addClass('success');
    			$('#help-level_name').html('正确');
    		} 
		});
	});
function checkLevel()
{
	var name = $('#level_name').val();
	if (name=='')
	{
		$('#group-level_name').addClass('warning');
    	$('#help-level_name').html('请输入会员等级名称');
    	return false;
	}
}