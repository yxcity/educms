$(function(){
	$('#shop').blur(function (){
			$('#group-shop').removeClass('success');
			$('#group-shop').removeClass('warning');
        	var shop = $('#shop').val();
        	if (shop=='')
			{
				$('#group-shop').addClass('warning');
				$('#help-shop').html('请选择门店');
			}else
			{
				$('#group-shop').addClass('success');
				$('#help-shop').html('正确');
			}
     });
	 
	 $('#brandID').blur(function (){
			$('#group-brandID').removeClass('success');
			$('#group-brandID').removeClass('warning');
       	var cateID = $('#brandID').val();
       	if (cateID=='')
			{
				$('#group-brandID').addClass('warning');
				$('#help-brandID').html('请选择品牌');
			}else
			{
				$('#group-brandID').addClass('success');
				$('#help-brandID').html('正确');
			}
    });
	 
	 $('#cateID').blur(function (){
			$('#group-cateID').removeClass('success');
			$('#group-cateID').removeClass('warning');
        	var cateID = $('#cateID').val();
        	if (cateID=='')
			{
				$('#group-cateID').addClass('warning');
				$('#help-cateID').html('请选择分类');
			}else
			{
				$('#group-cateID').addClass('success');
				$('#help-cateID').html('正确');
			}
     });
	 
	$('#wares').blur(function (){
			$('#group-wares').removeClass('success');
			$('#group-wares').removeClass('warning');
        	var wares = $('#wares').val();
        	if (wares=='')
			{
				$('#group-wares').addClass('warning');
				$('#help-wares').html('请输入商品名称');
			}else
			{
				$('#group-wares').addClass('success');
				$('#help-wares').html('正确');
			}
     }); 
	 
	 $('#price').blur(function (){
			$('#group-price').removeClass('success');
			$('#group-price').removeClass('warning');
        	var price = $('#price').val();
        	if (price=='')
			{
				$('#group-price').addClass('warning');
				$('#help-price').html('请输入价格');
			}else
			{
				$('#group-price').addClass('success');
				$('#help-price').html('正确');
			}
     });
	 
	 $('#rebate').blur(function (){
			$('#group-rebate').removeClass('success');
			$('#group-rebate').removeClass('warning');
        	var rebate = $('#rebate').val();
        	if (rebate=='')
			{
				$('#group-rebate').addClass('warning');
				$('#help-rebate').html('请输入折扣价');
			}else
			{
				$('#group-rebate').addClass('success');
				$('#help-rebate').html('正确');
			}
     });
	 
	 $('#repertory').blur(function (){
			$('#group-repertory').removeClass('success');
			$('#group-repertory').removeClass('warning');
        	var repertory = $('#repertory').val();
        	if (repertory=='')
			{
				$('#group-repertory').addClass('warning');
				$('#help-repertory').html('请输入库存量');
			}else
			{
				$('#group-repertory').addClass('success');
				$('#help-repertory').html('正确');
			}
     });
	 
	 $('#weixin').blur(function (){
			$('#group-weixin').removeClass('success');
			$('#group-weixin').removeClass('warning');
        	var repertory = $('#weixin').val();
        	if (repertory=='')
			{
				$('#group-weixin').addClass('warning');
				$('#help-weixin').html('请输入微信描述');
			}else
			{
				$('#group-weixin').addClass('success');
				$('#help-weixin').html('正确');
			}
     });
	 
	 $('#content').blur(function (){
			$('#group-content').removeClass('success');
			$('#group-content').removeClass('warning');
        	var content = $('#content').val();
        	if (content=='')
			{
				$('#group-content').addClass('warning');
				$('#help-content').html('请输入产品描述');
			}else
			{
				$('#group-content').addClass('success');
				$('#help-content').html('正确');
			}
     });
	 
	
	});


function clickForm()
{
	 var shop = $('#shop').val();
	 if (shop=='')
	 {
		$('#group-shop').addClass('warning');
		$('#help-shop').html('请选择门店');
		return false;
	 }
	 var cateID = $('#cateID').val();
	 if (cateID=='')
	 {
		$('#group-cateID').addClass('warning');
		$('#help-cateID').html('请选择分类');
		return false;
	 }
	 var wares = $('#wares').val();
	 if (wares=='')
	 {
		$('#group-wares').addClass('warning');
		$('#help-wares').html('请输入商品名称');
		return false;
	}
	
	var price = $('#price').val();
	 if (price=='')
	 {
		$('#group-price').addClass('warning');
		$('#help-price').html('请输入价格');
		return false;
	}
	var rebate = $('#rebate').val();
	 if (price=='')
	 {
		$('#group-rebate').addClass('warning');
		$('#help-rebate').html('请输入折扣价');
		return false;
	}
	var repertory = $('#repertory').val();
	 if (repertory=='')
	 {
		$('#group-repertory').addClass('warning');
		$('#help-repertory').html('请输入库存量');
		return false;
	}
	var weixin = $('#weixin').val();
	 if (weixin=='')
	 {
		$('#group-weixin').addClass('warning');
		$('#help-weixin').html('请输入微信描述');
		return false;
	}
	var content = $('#content').val();
	 if (content=='')
	 {
		$('#group-content').addClass('warning');
		$('#help-content').html('请输入产品描述');
		return false;
	}
	
}