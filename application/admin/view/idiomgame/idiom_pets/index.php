{extend name="public/container"}
{block name="content"}

<div class="layui-fluid">
    <div class="layui-row layui-col-space15"  id="app">
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">搜索条件</div>
                <div class="layui-card-body">
                    <form class="layui-form layui-form-pane" action="">
                        <div class="layui-form-item">
                            <div class="layui-inline">
                                <label class="layui-form-label">所有分类</label>
                                <div class="layui-input-block">
                                    <select name="status">
                                        <option value="">是否显示</option>
                                        <option value="1">显示</option>
                                        <option value="0">不显示</option>
                                    </select>
                                </div>
                            </div>
                         
                            <div class="layui-inline">
                                <label class="layui-form-label">种子名称</label>
                                <div class="layui-input-block">
                                    <input type="text" name="name" class="layui-input" placeholder="请输入分类名称">
                                </div>
                            </div>
                            <div class="layui-inline">
                                <div class="layui-input-inline">
                                    <button class="layui-btn layui-btn-sm layui-btn-normal" lay-submit="search" lay-filter="search">
                                        <i class="layui-icon layui-icon-search"></i>搜索</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!--产品列表-->
        <div class="layui-col-md12">
            <div class="layui-card">
                <div class="layui-card-header">分类列表</div>
                <div class="layui-card-body">
                    <!-- <div class="alert alert-info" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div> -->
                    <div class="layui-btn-container">
                       <!--  <a class="layui-btn layui-btn-sm" href="{:Url('index')}">分类首页</a> -->
                        <button type="button" class="layui-btn layui-btn-sm" onclick="$eb.createModalFrame(this.innerText,'{:Url('create')}')">添加种子</button>
                    </div>
                    <table class="layui-hide" id="List" lay-filter="List"></table>
                    <script type="text/html" id="pet_simg">
                        <img style="cursor: pointer" lay-event='open_image' src="{{d.pet_simg}}">
                    </script>
                    <script type="text/html" id="status">
                        <input type='checkbox' name='id' lay-skin='switch' value="{{d.id}}" lay-filter='status' lay-text='显|隐'  {{ d.status == 1 ? 'checked' : '' }}>
                    </script>
                   
                    <script type="text/html" id="act">
                        <button class="layui-btn layui-btn-xs" onclick="$eb.createModalFrame('编辑','{:Url('edit')}?id={{d.id}}')">
                            <i class="fa fa-paste"></i> 编辑
                        </button>
                        <button class="layui-btn layui-btn-xs" lay-event='delstor'>
                            <i class="fa fa-warning"></i> 删除
                        </button>
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
    setTimeout(function () {
        $('.alert-info').hide();
    },3000);
    //实例化form
    layList.form.render();
    //加载列表
    layList.tableList('List',"{:Url('pet_list')}",function (){
        return [
            {field: 'id', title: 'ID', sort: true,event:'id',width:'6%'},
            {field: 'pet_name', title: '名称',edit:'pet_name'},
            {field: 'need_integral', title: '兑换积分',edit:'need_integral'},
            {field: 'add_energy', title: '可换积分',edit:'add_energy'},
           
            {field: 'pet_simg', title: '图片',templet:'#pet_simg'},
           
            {field: 'status', title: '状态',templet:'#status',width:'6%'},
            {field: 'right', title: '操作',align:'center',toolbar:'#act',width:'14%'},
        ];
    });
    //自定义方法
    var action= {
        set_category: function (field, id, value) {
            layList.baseGet(layList.Url({
                c: 'idiomgame.idiom_pets',
                a: 'set_category',
                q: {field: field, id: id, value: value}
            }), function (res) {
                layList.msg(res.msg);
            });
        },
    }
    //查询
    layList.search('search',function(where){
        layList.reload(where);
    });
    layList.switch('status',function (odj,value) {
        if(odj.elem.checked==true){
            layList.baseGet(layList.Url({c:'idiomgame.idiom_pets',a:'set_show',p:{status:1,id:value}}),function (res) {
                layList.msg(res.msg);
            });
        }else{
            layList.baseGet(layList.Url({c:'idiomgame.idiom_pets',a:'set_show',p:{status:0,id:value}}),function (res) {
                layList.msg(res.msg);
            });
        }
    });
    //快速编辑
    layList.edit(function (obj) {
        var id=obj.data.id,value=obj.value;
        switch (obj.field) {
            case 'name':
                action.set_category('name',id,value);
                break;
            case 'sort':
                action.set_category('sort',id,value);
                break;
        }
    });
    //监听并执行排序
    layList.sort(['id','sort'],true);
    //点击事件绑定
    layList.tool(function (event,data,obj) {
        switch (event) {
            case 'delstor':
                var url=layList.U({c:'idiomgame.idiom_pets',a:'delete',q:{id:data.id}});
                $eb.$swal('delete',function(){
                    $eb.axios.get(url).then(function(res){
                        if(res.status == 200 && res.data.code == 200) {
                            $eb.$swal('success',res.data.msg);
                            obj.del();
                        }else
                            return Promise.reject(res.data.msg || '删除失败')
                    }).catch(function(err){
                        $eb.$swal('error',err);
                    });
                })
                break;
            case 'open_image':
                $eb.openImage(data.pet_simg);
                break;
        }
    })
</script>
{/block}
