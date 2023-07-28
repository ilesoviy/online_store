{extend name="public/container"}
{block name="head_top"}
<link href="{__ADMIN_PATH}module/wechat/news_category/css/style.css" type="text/css" rel="stylesheet">
{/block}
{block name="content"}
<div class="row">
    <div class="col-sm-12">
        <div class="ibox">
            <div class="ibox-title">
<!--                <button type="button" class="btn btn-w-m btn-primary add-filed">添加图文消息</button>-->
                <button type="button" class="btn btn-w-m btn-primary append-filed">添加图文消息</button>
                <div class="ibox-tools">
                    <button class="btn btn-white btn-sm" onclick="location.reload()"><i class="fa fa-refresh"></i> 刷新</button>
                </div>
                <div style="margin-top: 2rem"></div>
                <div class="row">
                    <div class="col-sm-8 m-b-xs">
                        <form action="" class="form-inline">
                            <i class="fa fa-search" style="margin-right: 10px;"></i>
                            <div class="input-group">
                                <input type="text" name="cate_name" value="{$where.cate_name}" placeholder="请输入关键词" class="input-sm form-control"> <span class="input-group-btn">
                                    <button type="submit" class="btn btn-sm btn-primary"> 搜索</button> </span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="ibox-content">
                <div id="news_box">
                {volist name="list" id="vo"}
                    <div class="news_item col-sm-2" >
                        <div class="title" ><span>图文名称：{$vo.cate_name}</span></div>
                        <div class="news_tools hide">
                            <!--                            <a href="javascript:void(0)">预览</a>-->
                            <!--                            <a href="javascript:void(0)" data-url="{:Url('push',array('id'=>$vo['id']))}" class="push">推送</a>-->
                            <!--                            <a onclick="$eb.createModalFrame(this.innerText,'{:Url('edit',array('id'=>$vo['id']))}')" href="javascript:void(0)">编辑</a>-->
                            <a onclick="$eb.createModalFrame(this.innerText,'{:Url('modify',array('id'=>$vo['id']))}',{w:1200,h:666})" href="javascript:void(0)">编辑</a>
                            <a href="javascript:void(0)" data-url="{:Url('delete',array('id'=>$vo['id']))}" class="del_news_one">删除</a>
                        </div>
                    {volist name="$vo['new']" id="vvo" key="k"}
                        {if condition="$k eq 1"}
                        <div class="news_articel_item" style="background-image:url({$vvo.image_input})">
                            <p>{$vvo.title}</p>
                        </div>
                        <div class="hr-line-dashed"></div>
                        {else/}
                        <div class="news_articel_item other">
                            <div class="right-text">{$vvo.title}</div>
                            <div class="left-image" style="background-image:url({$vvo.image_input});">
                            </div>
                        </div>
                        <div class="hr-line-dashed"></div>
                        {/if}
                    {/volist}
                    </div>
                {/volist}
                </div>
            </div>
        </div>
    </div>
</div>
<div style="margin-left: 10px">
    {include file="public/inner_page"}
</div>
{/block}
{block name="script"}
<script>
    $('.add-filed').on('click',function (e) {
        $eb.createModalFrame(this.innerText,"{:Url('create')}");
    });
    $('.append-filed').on('click',function (e) {
        $eb.createModalFrame(this.innerText,"{:Url('append')}",{w:1200,h:666});
    });
    $('body').on('mouseenter', '.news_item', function () {
        $(this).find('.news_tools').removeClass('hide');
    }).on('mouseleave', '.news_item', function () {
        $(this).find('.news_tools').addClass('hide');
    });
    $('.del_news_one').on('click',function(){
        window.t = $(this);
        var _this = $(this),url =_this.data('url');
        $eb.$swal('delete',function(){
            $eb.axios.get(url).then(function(res){
                console.log(res);
                if(res.status == 200 && res.data.code == 200) {
                    $eb.$swal('success',res.data.msg);
                    _this.parents('.news_item').remove();
                }else
                    return Promise.reject(res.data.msg || '删除失败')
            }).catch(function(err){
                $eb.$swal('error',err);
            });
        })
    });
    $('.push').on('click',function(){
        window.t = $(this);
        var _this = $(this),url =_this.data('url');
        $eb.$swal('delete',function(){
            $eb.axios.get(url).then(function(res){
                if(res.status == 200 && res.data.code == 200) {
                    $eb.$swal('success',res.data.msg);
                }else
                    return Promise.reject(res.data.msg || '删除失败')
            }).catch(function(err){
                $eb.$swal('error',err);
            });
        })
    });
</script>
{/block}