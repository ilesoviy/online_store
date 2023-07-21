{extend name="public/container"}
{block name="content"}
<div class="layui-fluid">
    <div class="layui-row layui-col-space15">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">搜索条件</div>
                <div class="layui-card-body">
                    <form class="layui-form layui-form-pane" action="">
                        <div class="layui-form-item">
                            <div class="layui-inline">
                                <label class="layui-form-label">昵称/ID</label>
                                <div class="layui-input-block">
                                    <input type="text" name="nickname" class="layui-input">
                                </div>
                            </div>
                            <div class="layui-inline" style="display: none;">
                                <label class="layui-form-label">用户性别</label>
                                <div class="layui-input-block">
                                    <select name="sex">
                                        <option value="">全部</option>
                                        <option value="1">男</option>
                                        <option value="2">女</option>
                                        <option value="0">未知</option>
                                    </select>
                                </div>
                            </div>
                            <div class="layui-inline" style="display: none;">
                                <label class="layui-form-label">用户类型</label>
                                <div class="layui-input-block">
                                    <select name="user_type">
                                        <option value=" ">全部</option>
                                        <option value="1">打通用户</option>
                                        <option value="2">微信用户</option>
                                        <option value="3">小程序用户</option>
                                    </select>
                                </div>
                            </div>
                            <div class="layui-inline">
                                <label class="layui-form-label">时间范围</label>
                                <div class="layui-input-inline" style="width: 200px;">
                                    <input type="text" name="start_time" id="start_time" placeholder="开始时间" autocomplete="off" class="layui-input">
                                </div>
                                <div class="layui-form-mid">-</div>
                                <div class="layui-input-inline" style="width: 200px;">
                                    <input type="text" name="end_time" id="end_time" placeholder="结束时间" autocomplete="off" class="layui-input">
                                </div>
                            </div>
                        </div>
                        <div class="layui-form-item" style="display: none;">
                            <div class="layui-inline">
                                <label class="layui-form-label">是否关注</label>
                                <div class="layui-input-block">
                                    <select name="subscribe">
                                        <option value="">全部</option>
                                        <option value="1">关注</option>
                                        <option value="0">未关注</option>
                                    </select>
                                </div>
                            </div>
                            <div class="layui-inline">
                                <div class="layui-input-inline">
                                    <button class="layui-btn layui-btn-sm layui-btn-normal" lay-submit="search" lay-filter="search">
                                        <i class="layui-icon layui-icon-search"></i>搜索</button>
                                   <!--  <button class="layui-btn layui-btn-primary layui-btn-sm export" lay-submit="export" lay-filter="export">
                                        <i class="fa fa-floppy-o" style="margin-right: 3px;"></i>导出</button> -->
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">分销员列表</div>
                <div class="layui-card-body">
                    <div class="layui-btn-container">
                        <div class="layui-btn-group conrelTable">
                            {if $store_brokerage_statu==1}<button class="layui-btn layui-btn-sm layui-btn-normal" type="button" data-type="delete_spread"><i class="fa fa-check-circle-o"></i>解除推广权限</button>{/if}
                            <button class="layui-btn layui-btn-sm layui-btn-normal" type="button" data-type="refresh"><i class="layui-icon layui-icon-refresh" ></i>刷新</button>
                        </div>
                    </div>
                    <table class="layui-hide" id="userList" lay-filter="userList"></table>
                    <script type="text/html" id="headimgurl">
                        <img style="cursor: pointer" lay-event='open_image' src="{{d.headimgurl}}">
                    </script>
                    <script type="text/html" id="act">
                        <button type="button" class="layui-btn layui-btn-xs" onclick="dropdown(this)">操作 <span class="caret"></span></button>
                        <ul class="layui-nav-child layui-anim layui-anim-upbit">
                            <li>
                                <a href="javascript:void(0);" class="" onclick="$eb.createModalFrame(this.innerText,'{:Url('stair')}?uid={{d.uid}}')">
                                    <i class="fa fa-eye"></i> 一级推广列表</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="" onclick="$eb.createModalFrame(this.innerText,'{:Url('stair_two')}?uid={{d.uid}}')">
                                    <i class="fa fa-eye"></i> 二级推广列表</a>
                            </li>
                            {{# if(d.openid || d.unionid){ }}
                            <li>
                                <a href="javascript:void(0);" lay-event='look_wx_code'>
                                    <i class="fa fa-eye"></i> 公众号推广二维码</a>
                            </li>
                            {{# } }}
                            {{# if(d.routine_openid || d.unionid){ }}
                            <li>
                                <a href="javascript:void(0);" lay-event='look_xcx_code'>
                                    <i class="fa fa-eye"></i> 小程序推广二维码</a>
                            </li>
                            {{# } }}
                            <li>
                                <a href="javascript:void(0);" lay-event='empty_spread'>
                                    <i class="fa fa-times-circle"></i> 清除推广人</a>
                                </a>
                            </li>
                            {{# if(d.is_show){ }}
                            <li>
                                <a href="javascript:void(0);" lay-event='delete_spread'>
                                    <i class="fa fa-unlock"></i> 解除推广权限</a>
                                </a>
                            </li>
                            {{# } }}
                        </ul>
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{__ADMIN_PATH}js/layuiList.js"></script>
{/block}
{block name="script"}
<script>
    var action={
        refresh:function () {
            layList.reload();
        },
        delete_spread:function () {
            var ids=layList.getCheckData().getIds('uid');
            if(ids.length){
                $eb.$swal('delete',function(){
                    $eb.axios.post(layList.U({a:'delete_promoter'}),{uids:ids}).then(function(res){
                        if(res.status == 200 && res.data.code == 200) {
                            $eb.$swal('success',res.data.msg);
                            layList.reload();
                        }else
                            return Promise.reject(res.data.msg || '清除失败')
                    }).catch(function(err){
                        $eb.$swal('error',err);
                    });
                },{
                    title:'您将解除选中用户的推广权限，请谨慎操作！',
                    text:'解除后可在会员管理里单个开启推广权限',
                    confirm:'是的我要解除'
                })
            }else{
                layList.msg('请选择要解除权限的用户');
            }
        }
    };
    layList.form.render();
    layList.tableList('userList',"{:Url('get_spread_list')}",function () {
        return [
            {type:'checkbox'},
            {field: 'uid', title: '编号', sort: true,width:'8%'},
            {field: 'nickname', title: '微信昵称' },
            {field: 'headimgurl', title: '头像',templet:'#headimgurl'},
            {field: 'type_name', title: '用户类型'},
            // {field: 'sex_name', title: '性别'},
            {field: 'order_stair', title: '一级推广人订单',sort:true},
            {field: 'order_second', title: '二级推广人订单',sort:true},
            {field: 'extract_count_price', title: '累计提现',sort:true},
            {field: 'extract_count_num', title: '提现次数',sort:true},
            {field: 'new_money', title: '可提现金额',sort:true},
            {field: 'spread_name', title: '上级推广人'},
            {field: 'right', title: '操作',toolbar:'#act',width:'5%'},
        ];
    });
    layList.date({elem:'#start_time',theme:'#393D49',type:'datetime'});
    layList.date({elem:'#end_time',theme:'#393D49',type:'datetime'});
    var that=this;
    layList.search('search',function(where){
        if(where.start_time!='' && where.end_time=='') return layList.msg('请选择结束时间')
        if(where.end_time!='' && where.start_time=='') return layList.msg('请选择开始时间')
        console.log(where);
        layList.reload(where,true);
    });
    layList.search('export',function(where){
        where.excel=1;
        location.href=layList.U({a:'get_spread_list',q:where});
    })
    $('.conrelTable').find('button').each(function () {
        var type=$(this).data('type');
        $(this).on('click',function () {
            action[type] && action[type]();
        })
    })
    //下拉框
    $(document).click(function (e) {
        $('.layui-nav-child').hide();
    })
    function dropdown(that){
        var oEvent = arguments.callee.caller.arguments[0] || event;
        oEvent.stopPropagation();
        var offset = $(that).offset();
        var top=offset.top-$(window).scrollTop();
        var index = $(that).parents('tr').data('index');
        $('.layui-nav-child').each(function (key) {
            if (key != index) {
                $(this).hide();
            }
        })
        if($(document).height() < top+$(that).next('ul').height()){
            $(that).next('ul').css({
                'padding': 10,
                'top': - ($(that).parent('td').height() / 2 + $(that).height() + $(that).next('ul').height()/2),
                'min-width': 'inherit',
                'left':-64,
                'position': 'absolute'
            }).toggle();
        }else{
            $(that).next('ul').css({
                'padding': 10,
                'left':-64,
                'top':$(that).parent('td').height() / 2 + $(that).height(),
                'min-width': 'inherit',
                'position': 'absolute'
            }).toggle();
        }
    }
    layList.tool(function (event,data,obj) {
        switch (event) {
            case 'empty_spread':
                var url=layList.U({a:'empty_spread',q:{uid:data.uid}});
                $eb.$swal('delete',function(){
                    $eb.axios.get(url).then(function(res){
                        if(res.status == 200 && res.data.code == 200) {
                            $eb.$swal('success',res.data.msg);
                        }else
                            return Promise.reject(res.data.msg || '清除失败')
                    }).catch(function(err){
                        $eb.$swal('error',err);
                    });
                },{
                    title:'您将清除【'+data.nickname+'】的全部推广人，请谨慎操作！',
                    text:'清除后无法恢复，建议先备份数据库',
                    confirm:'是的我要清除'
                })
                break;
            case 'delete_spread':
                var url=layList.U({a:'delete_spread',q:{uid:data.uid}});
                $eb.$swal('delete',function(){
                    $eb.axios.get(url).then(function(res){
                        if(res.status == 200 && res.data.code == 200) {
                            $eb.$swal('success',res.data.msg);
                            obj.del();
                        }else
                            return Promise.reject(res.data.msg || '清除失败')
                    }).catch(function(err){
                        $eb.$swal('error',err);
                    });
                },{
                    title:'您将解除【'+data.nickname+'】的推广权限，请谨慎操作！',
                    text:'解除后可在会员管理里面开启',
                    confirm:'是的我要解除'
                })
                break;
            case 'look_wx_code':
                layList.baseGet(layList.U({a:'look_code',q:{uid:data.uid}}),function (res) {
                    if($eb){
                        $eb.openImage(res.data.code_src);
                    }else{
                        layList.layer.open({
                            type: 1,
                            title: false,
                            closeBtn: 0,
                            shadeClose: true,
                            content: '<img src="'+res.data.code_src+'" style="display: block;width: 100%;" />'
                        });
                    }
                },function (res) {
                    layList.msg(res.msg);
                });
                break;
            case 'look_xcx_code':
                layList.baseGet(layList.U({a:'look_xcx_code',q:{uid:data.uid}}),function (res) {
                    if($eb){
                        $eb.openImage(res.data.code_src);
                    }else{
                        layList.layer.open({
                            type: 1,
                            title: false,
                            closeBtn: 0,
                            shadeClose: true,
                            content: '<img src="'+res.data.code_src+'" style="display: block;width: 100%;" />'
                        });
                    }
                },function (res) {
                    layList.msg(res.msg);
                });
                break;
            case 'open_image':
                if($eb)
                    $eb.openImage(data.headimgurl);
                else
                    layList.layer.open({
                        type: 1,
                        title: false,
                        closeBtn: 0,
                        shadeClose: true,
                        content: '<img src="'+data.headimgurl+'" style="display: block;width: 100%;" />'
                    });
                break;

        }
    })
</script>
{/block}
