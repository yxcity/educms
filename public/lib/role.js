$(function(){
	$('#roleName').blur(function(){
		$('#group-roleName').removeClass('success');
		$('#group-roleName').removeClass('warning');
        var name = $('#roleName').val();
		if (name=='')
    		{
    			$('#group-roleName').addClass('warning');
    			$('#help-roleName').html('请输入角色名称');
    		}else{
    			$('#group-roleName').addClass('success');
    			$('#help-roleName').html('正确');
    		} 
		});
	});
function clickRole()
{
	var name = $('#roleName').val();
	if (name=='')
	{
		$('#group-roleName').addClass('warning');
    	$('#help-roleName').html('请输入角色名称');
    	return false;
	}
}