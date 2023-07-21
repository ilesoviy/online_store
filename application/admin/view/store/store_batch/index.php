{include file="public/frame_head"}
<style>
    .panel{
        width: 100%;margin-top:20px; text-align: left;padding: 20px 40px;
    }
    .panel button{display: block;margin:5px;}
</style>
<div class="col-sm-12">
    <blockquote class="text-warning" style="font-size:14px">批量修改数据请谨慎，修改就无法恢复哦！注意数据的初始不能是0
    </blockquote>

    <hr>
</div>
<div class="row panel">
     <form class="form-horizontal" method="post" action="index" id="signupForm">
                       <div class="form-group">
                           <div class="col-md-3">
                               <div class="input-group">
                                   <span class="input-group-addon">销量</span>
                                   <input type="number" placeholder="请在这里输入销量百分比" name="sales" class="layui-input" id="sales" value="0">
                               </div>
                           </div>
                           <div class="col-md-9"><span style="color: red;">销量百分比，正数增加，负数减少 例如 5 即销量在原基础上增加5%;</span></div>
                       </div>
                       <div class="form-group">
                           <div class="col-md-3">
                               <div class="input-group">
                                   <span class="input-group-addon">虚拟销量</span>
                                   <input type="number" placeholder="请在这里输入虚拟销量百分比" name="ficti" class="layui-input" id="ficti" value="0">
                               </div>
                           </div>
                           <div class="col-md-9"><span style="color: red;">虚拟销量百分比，正数增加，负数减少 例如 5 即虚拟销量在原基础上增加5%;</span></div>
                       </div>
                       <div class="form-group">
                           <div class="col-md-3">
                               <div class="input-group">
                                   <span class="input-group-addon">库存</span>
                                   <input type="number" placeholder="请在这里输入库存百分比" name="stock" class="layui-input" id="stock" value="0">
                               </div>
                           </div>
                           <div class="col-md-9"><span style="color: red;">库存百分比，正数增加，负数减少 例如 5 即库存在原基础上增加5%;</span></div>
                       </div>

                       <div class="form-actions">
                           <div class="row">
                               <div class="col-md-offset-4 col-md-9">
                                   <button type="submit" class="btn btn-w-m btn-info save_news">确定</button>
                               </div>
                           </div>
                       </div>
                   </form>
</div>
<script>
    $('.cleardata').on('click',function(){
        window.t = $(this);
        var _this = $(this),url =_this.data('url');
        $eb.$swal('delete',function(){
            $eb.axios.get(url).then(function(res){
                if(res.status == 200 && res.data.code == 200) {
                    $eb.$swal('success',res.data.msg);
                }else
                    return Promise.reject(res.data.msg || '操作失败')
            }).catch(function(err){
                $eb.$swal('error',err);
            });
        },{'title':'您确定要进行此操作吗？','text':'数据清除无法恢复','confirm':'是的，我要操作'})
    });
    $('.creatuser').on('click',function(){
            window.t = $(this);
            var _this = $(this),url =_this.data('url');
            $eb.$swal('delete',function(){
                $eb.axios.get(url).then(function(res){
                    if(res.status == 200 && res.data.code == 200) {
                        $eb.$swal('success',res.data.msg);
                    }else
                        return Promise.reject(res.data.msg || '操作失败')
                }).catch(function(err){
                    $eb.$swal('error',err);
                });
            },{'title':'您确定要进行此操作吗？','text':'用户数据清除之后才能进行此操作','confirm':'是的，我要操作'})
        })
</script>
{include file="public/inner_footer"}
