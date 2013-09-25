$(function(){
	$('#typeName').blur(function(){
		$('#group-typeName').removeClass('success');
		$('#group-typeName').removeClass('warning');
        var name = $('#typeName').val();
		if (name=='')
    		{
    			$('#group-typeName').addClass('warning');
    			$('#help-typeName').html('请输入属性名称');
    		}else{
    			$('#group-typeName').addClass('success');
    			$('#help-typeName').html('正确');
    		} 
		});
	});
function clickType()
{
	var name = $('#typeName').val();
	if (name=='')
	{
		$('#group-typeName').addClass('warning');
    	$('#help-typeName').html('请输入属性名称');
    	return false;
	}
}