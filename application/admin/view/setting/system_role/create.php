<!DOCTYPE html>
<html lang="zh-CN">
<head>
    {include file="public/head"}
    <title></title>
</head>
<body>
<div id="form-add" class="mp-form" v-cloak="">
    <i-Form :model="formData" :label-width="80">
        <Form-Item label="身份名称">
            <i-input v-model="formData.role_name" placeholder="请输入身份名称"></i-input>
        </Form-Item>
        <Form-Item label="是否开启">
            <Radio-Group v-model="formData.status">
                <Radio label="1">开启</Radio>
                <Radio label="0">关闭</Radio>
            </Radio-Group>
        </Form-Item>
        <Form-Item label="权限">
            <Tree :data="menus" show-checkbox ref="tree"></Tree>
        </Form-Item>
        <Form-Item :class="'add-submit-item'">
            <i-Button :type="'primary'" :html-type="'submit'" :size="'large'" :long="true" :loading="loading" @click.prevent="submit">提交</i-Button>
        </Form-Item>
    </i-Form>
</div>
<script>
    $eb = parent._mpApi;
    var menus = <?php echo $menus; ?> || [];
    mpFrame.start(function(Vue){
        new Vue({
            el:'#form-add',
           data:{
               formData:{
                   role_name:'',
                   status:1,
                   checked_menus:[]
               },
               menus:[],
               loading:false
           },
            methods:{
                tidyRes:function(){
                    var data = [];
                    menus.map((menu)=>{
                        data.push(this.initMenu(menu));
                    });
                    this.$set(this,'menus',data);
                },
                initMenu:function(menu){
                    var data = {};
                    data.title = menu.menu_name;
                    data.id = menu.id;
                    data.expand = false;
                    if(menu.child && menu.child.length >0){
                        data.children = [];
                        menu.child.map((child)=>{
                            data.children.push(this.initMenu(child));
                        })
                    }
                    return data;
                },
                submit:function(){
                    this.loading = true;
                    this.formData.checked_menus = [];
                    this.$refs.tree.getCheckedNodes().map((node)=>{
                        this.formData.checked_menus.push(node.id);
                    });
                    $eb.axios.post("{$saveUrl}",this.formData).then((res)=>{
                        if(res.status && res.data.code == 200)
                            return Promise.resolve(res.data);
                        else
                            return Promise.reject(res.data.msg || '添加失败,请稍候再试!');
                    }).then((res)=>{
                        $eb.message('success',res.msg || '操作成功!');
                        $eb.closeModalFrame(window.name);
                    }).catch((err)=>{
                        this.loading=false;
                        $eb.message('error',err);
                    });
                }
            },
            mounted:function(){
                t = this;
                this.tidyRes();
            }
        });
    });
</script>
</body>
