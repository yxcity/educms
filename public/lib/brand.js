$(function(){
	$('#brand_name').blur(function(){
		$('#group-brand_name').removeClass('success');
		$('#group-brand_name').removeClass('warning');
        var name = $('#brand_name').val();
		if (name=='')
    		{
    			$('#group-brand_name').addClass('warning');
    			$('#help-brand_name').html('请输入品牌名称');
    		}else{
    			$('#group-brand_name').addClass('success');
    			$('#help-brand_name').html('正确');
    		} 
		});
	});
function checkBrand()
{
	var name = $('#brand_name').val();
	if (name=='')
	{
		$('#group-brand_name').addClass('warning');
    	$('#help-brand_name').html('请输入品牌名称');
    	return false;
	}
}