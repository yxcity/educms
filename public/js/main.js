$(function(){
    $('.p_btn_buy span').click(function(){
        $('#alert_car').show();
    });
});

//点击加入购物车
function add_cart(id, i) {

    //获取用户挑选商品规格信息及数量
    var spec = new Array();
    $(".current1").each(function(i, t) {
        spec.push(t.id.replace('spec_', ''));
    });

    $.ajax({
        type: "get",
        url: "/cart",
        data: {ac: 'add', id: id, num: parseInt($("#buycount").val()), spec: spec},
        dataType: "json",
        success: function(data) {
            if (data.isok === true)
            {
                if (i === 1)
                {
                    window.location.href = "/cart/cartlist";
                } else
                {
                    alert('添加成功！');
                }
            } else {
                alert('操作失败！');
            }
        }
    });
}  