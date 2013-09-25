$(function(){
	$('#alias_label').blur(function(){
		$('#group-alias_label').removeClass('success');
		$('#group-alias_label').removeClass('warning');
        var alias_label = $('#alias_label').val();
		if (alias_label=='')
    		{
    			$('#group-alias_label').addClass('warning');
    			$('#help-alias_label').html('请输入别名');
    		}else{
    			$('#group-alias_label').addClass('success');
    			$('#help-alias_label').html('正确');
    		} 
		});
		
		
	});
function checkAlias()
{
	var alias_label = $('#alias_label').val();
	if (alias_label=='')
	{
		$('#group-alias_label').addClass('warning');
    	$('#help-alias_label').html('请输入别名');
    	return false;
	}
}