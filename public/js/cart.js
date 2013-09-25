$(function(){
   sum(); 
});
var vCart = {
    //添加+1
    add: function(i) {
        var num = $('#' + i).val();
        num++;
        $('#' + i).val(num);
        ajax(i,'add',1);
        sum();
    },
    //减少-1
    subtract: function(i) {
        var num = $('#' + i).val();
        if (num>1)
        {
             ajax(i,'subtract',1); 
        }
        num--;
        if (num < 1)
        {
            num = 1;
            $('#' + i).val(num);
        }
        $('#' + i).val(num);
        
        sum();
    },
    //输入数量
    import: function(i) {
        var num = $('#' + i).val();
        if (num < 1)
        {
            num = 1;
            $('#' + i).val(num);
        }
        ajax(i,'import',num); 
        sum();
    },
    //删除单个
    del: function(i) {
        $('#li' + i).remove();
        ajax(i,'del',0); 
        sum();
    },
    //删除所有选定
    delall: function()
    {
        $(".number_info").each(function() {
            var id = this.id;
            if ($('#box_' + id).attr('checked'))
            {
                $('#li' + id).remove();
                var num = this.value;
                ajax(id,'del',num);
            }
        });
       
        sum();
        // window.location.href = "/cart/cartlist?ref=footer_carts";
    },
    //全选
    checkAll: function()
    {
        $('#cart_0').find("input[type='checkbox']").attr('checked', $('.checkall').is(':checked'));
    }
};

function ajax(i, event, num) {
    $.ajax({
        type: "get",
        url: "/cart",
        data:{ac:event,id:i,num:num},
        dataType: "json",
        success: function(data) {
        }
    });
}

function sum()
{
    var TotalPrice = 0;
    var Discount = 0;
    var TotalPoint = 0;
    var TotalNum = 0;
    $(".number_info").each(function() {
        var id = this.id;
        var price = parseInt($('#price_' + id).attr('price'));
        var num = parseInt($('#' + id).val());
        TotalPrice = TotalPrice + (price * num);
        TotalNum = TotalNum + num;
    });
    $('#TotalPrice').html(TotalPrice);
    $('#Discount').html(Discount);
    $('#TotalPoint').html(TotalPoint);
    $('#TotalNum').html(TotalNum);
}
