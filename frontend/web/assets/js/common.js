// 全站公共JS文件

function buildUrl(param) {
    var is_rewrite = true;
    return is_rewrite ? '/'+param : '/index.php?r='+param;
}



// ------------- Index page ---------------
$(function () {

    // 加载更多
    $('.btn-loadmore').click(function () {
        var _this = $(this);
        var type = _this.attr('datatype');
        var page = _this.attr('page');
        var url = buildUrl('index/load-more');

        //var url = '/index/load-more';
        $.get(url,{'type':type,'page':page},function (result) {
            console.log(result);
            if(result)
            {
                _this.parent().prev().append(result);
                _this.attr('page',parseInt(page)+1);
            }
            else
            {
                layer.msg('没有更多了');
            }
        });
    });

});

// ------------- Product page ---------------
$(function () {
    // 规格选择
    $('.des_choice ul li').click(function () {
        $(this).toggleClass('checked').siblings().removeClass('checked');

    });

    // 加入购物车
    $('#addto-cart').click(function () {
        var num = $('input[name="quantity"]').val();
        var gid = $(this).data('content');
        var spec = [];
        var specLen = $('.des_choice').length;
        if(specLen > 0)
        {
            $('.checked').each(function (k,v) {

                // console.log($(this).data('content'));
                spec.push($(v).data('content'));
            });

            if(spec.length != specLen)
            {
                layer.msg('请选择规格.');
                return null;
            }

            // 加入购物车(带规格)
            addToCart(gid,num,spec);
        }
        else
        {
            // 加入购物车(无规格)
            addToCart(gid,num,'')
        }



    });


    function addToCart(gid,num,spec) {
        //var url = '/index.php?r=product/add-to-cart';
        var url = buildUrl('product/add-to-cart');
        $.get(url,{'gid':gid,'num':num,'spec':spec},function (result) {
            console.log(result);
            if(result.code)
            {
                layer.confirm('是否立即支付？', {
                    btn: ['果断支付','继续购物'] //按钮
                }, function(){
                    window.location.href = buildUrl('cart/index');
                }, function(){

                });
            }
            else
            {
                layer.msg(result.msg);
            }
        },'json')
    }

});

// ------------- Cart page ---------------
$(function () {

    // 加载购物车
    $('.basket').click(function () {
        layer.load();
        var _this = $(this);
        // var url = '/index.php?r=cart/load-cart';
        var url = buildUrl('cart/load-cart');
        $.get(url,function (result) {
            layer.closeAll();
            _this.empty();
            _this.addClass('open');
            _this.append(result);

        });

    });

    // 累加
    $(document).on('click','.cartchange',function () {
        var _this = $(this);
        var type = _this.attr('type');
        var num = _this.siblings('input[name="quantity"]').val();
        var cid = _this.siblings('input[name="quantity"]').data('content');
        console.log(num+"  "+cid);

        // var url = '/index.php?r=cart/change-num';
        var url = buildUrl('cart/change-num');
        if(num == 0 )
        {
            layer.msg('购买数量至少为一');
            _this.siblings('input[name="quantity"]').val(1);
            return false;
        }
        $.get(url,{'num':num,'cid':cid,'type':type},function (result) {
            console.log(result);
            if(result.code)
            {
                $('#cart-page').remove();
                $('#footer').before(result.data.viewcart);
                $('.basket').empty().append(result.data.navcart);
            }
            else
            {
                _this.siblings('input[name="quantity"]').val(parseInt(num)-1);
                layer.msg(result.msg);
            }
        },'json')
    });

});

function deleteCart(cid)
{
    layer.confirm('您确认要删除吗？', {
        btn: ['确定','取消'] //按钮
    }, function(){
        // var url = '/index.php?r=cart/delete';
        var url = buildUrl('cart/delete');
        $.get(url,{'cid':cid},function (result) {
            if(result.code)
            {
                layer.msg('删除成功', {icon: 1});
                $('#cart-page').remove();
                $('#footer').before(result.data.viewcart);
                $('.basket').empty().append(result.data.navcart);

            }
            else
            {
                layer.msg(result.msg);
            }
        },'json')

    }, function(){

    });
}

// ------------- Order page ---------------
$(function () {
    $('.submit_order').click(function () {

        var aid,pid;
        // 验证是否选中 2处

        aid = $('input[name="address"]:checked').val();
        if(!aid)
        {
            layer.msg('必须选择一个收获地址');
        }

        pid = $('input[name="pay"]:checked').val();
        console.log(pid);
        if(!pid)
        {
            layer.msg('必须选择一种支付方式');
        }

        // var url = '/index.php?r=order/order-down';
        var url = buildUrl('order/order-down');
        $.get(url,{'aid':aid,'pid':pid},function (result) {
            if(result.code)
            {
                layer.confirm('您是否立即支付？', {
                    btn: ['去支付','再看看'] //按钮
                }, function(){
                    window.open(result.data.url);

                    layer.msg('是否支付成功？', {
                        time: 100000, //20s后自动关闭
                        btn: ['已支付成功', '遇到支付问题']
                    });

                }, function(){

                });
            }
            else
            {
                layer.msg(result.msg,{icon: 5});
            }
        })
    });

    /** 城市区联动 */
    $('select.le-input').change(function () {
        var _this = $(this);
        var rid = _this.val();
        // var url = '/index.php?r=order/region';
        var url = buildUrl('order/region');
        if(rid == 0)
        {
            _this.parents('.col-xs-12').nextAll().find('select').html('<option>请选择...</option>');
            return false;
        }
        $.get(url,{'rid':rid},function (result) {
            if(result.code)
            {
                var option = '<option>请选择...</option>';
                $.each(result.data,function (k,v) {
                    option += '<option value="'+k+'">'+v+'</option>';
                });

                _this.parents('.col-xs-12').nextAll().find('select').html('<option>请选择...</option>');
                _this.parents('.col-xs-12').next().find('select').html(option);

            }
            else
            {
                layer.msg(result.msg);
            }
        },'json');
    });
});


// ------------- myOrder page ---------------
// tooltip 展示联系人地址
$(function () {
    $('[data-toggle="tooltip"]').tooltip();


    $('.query-express').click(function () {
        var no = $(this).attr('data');
        var ship = $(this).attr('ship');
        var url = buildUrl('user/express');
        $.get(url,{'no':no},function (result) {
            console.log(result);

            if(result.status == '200')
            {
                var html = '<ul class="text-left" style="padding:10px;"><b>'+ship+'：'+no+'</b>';
                $.each(result.data,function (k,v) {
                   html += '<li><b>'+v.time + '</b><br>'+ v.context+'</li>';
                });
                html += '</ul>';

                // 物流弹窗
                layer.open({
                    title:'物流信息',
                    maxWidth:480,
                    type: 1,
                    skin: 'demo-class', //样式类名
                    closeBtn: 0, //不显示关闭按钮
                    anim: 2,
                    shadeClose: true, //开启遮罩关闭
                    content: html
                });
            }
            else
            {
                layer.msg('查询异常，请稍后重试.');
            }
        },'json')
    });

});