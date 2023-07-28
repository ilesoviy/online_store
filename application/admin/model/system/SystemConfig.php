<?php

/**

 *

 * @author: xaboy<365615158@qq.com>

 * @day: 2017/11/02

 */

namespace app\admin\model\system;

use basic\ModelBasic;
use service\FormBuilder as Form;
use traits\ModelTrait;
use \app\admin\model\system\SystemConfigTab;

class SystemConfig extends ModelBasic {

    use ModelTrait;
    /**
     * 修改单个配置
     * */
    public static function setValue($menu,$value){
        if(empty($menu) || !($config_one = self::get(['menu_name'=>$menu]))) return self::setErrorInfo('字段名称错误');
        if($config_one['type'] == 'radio' || $config_one['type'] == 'checkbox'){
            $parameter = array();
            $option = array();
            $parameter = explode(',',$config_one['parameter']);
            foreach ($parameter as $k=>$v){
                if(isset($v) && !empty($v)){
                    $option[$k] = explode('-',$v);
                }
            }
            $value_arr = array();//选项的值
            foreach ($option as $k=>$v){
                foreach ($v as $kk=>$vv)
                if(!$kk){
                    $value_arr[$k] = $vv;
                }
            }
            $i = 0;//
            if(is_array($value)){
                 foreach ($value as $value_v){
                     if(in_array($value_v,$value_arr)){
                         $i++;
                     }
                 }
                 if(count($value) != $i) return self::setErrorInfo('输入的值不属于选项中的参数');
            }else{
                if(in_array($value,$value_arr)){
                    $i++;
                }
                if(!$i) return self::setErrorInfo('输入的值不属于选项中的参数');
            }
            if($config_one['type'] == 'radio' && is_array($value)) return self::setErrorInfo('单选按钮的值是字符串不是数组');
        }
        $bool = self::edit(['value' => json_encode($value)],$menu,'menu_name');
        return $bool;
    }
    /**
     * 获取单个参数配置
     * */
    public static function getValue($menu){
        if(empty($menu) || !($config_one = self::get(['menu_name'=>$menu]))) return false;
        return json_decode($config_one['value'],true);
    }

    /**
     * 获得多个参数
     * @param $menus
     * @return array
     */
    public static function getMore($menus)
    {
        $menus = is_array($menus) ? implode(',',$menus) : $menus;
        $list = self::where('menu_name','IN',$menus)->column('value','menu_name')?:[];
        foreach ($list as $menu => $value){
            $list[$menu] = json_decode($value,true);
        }
        return $list;
    }

    public static function getAllConfig()
    {
        $list = self::column('value','menu_name')?:[];
        foreach ($list as $menu => $value){
            $list[$menu] = json_decode($value,true);
        }
        return $list;
    }

    /**
     * text  判断
     * */
    public static function valiDateTextRole($data){
        if (!$data['width']) return self::setErrorInfo('请输入文本框的宽度');
        if ($data['width'] <= 0) return self::setErrorInfo('请输入正确的文本框的宽度');
        return true;
    }
    /**
     * radio 和 checkbox规则的判断
     * */
    public static function valiDateRadioAndCheckbox($data){
        $parameter = array();
        $option = array();
        $option_new = array();
        $data['parameter'] = str_replace("\r\n","\n",$data['parameter']);//防止不兼容
        $parameter = explode("\n",$data['parameter']);
        if(count($parameter) < 2)return self::setErrorInfo('请输入正确格式的配置参数');
        foreach ($parameter as $k=>$v){
            if(isset($v) && !empty($v)){
                $option[$k] = explode('=>',$v);
            }
        }
        if(count($option) < 2)return self::setErrorInfo('请输入正确格式的配置参数');
        $bool = 1;
        foreach ($option as $k=>$v){
            $option_new[$k] = $option[$k][0];
            foreach ($v as $kk=>$vv){
                $vv_num = strlen($vv);
                if(!$vv_num){
                    $bool = 0;
                }
            }
        }
//        dump($option);
        if(!$bool)return self::setErrorInfo('请输入正确格式的配置参数');
        $num1 = count($option_new);//提取该数组的数目
        $arr2 = array_unique($option_new);//合并相同的元素
        $num2 = count($arr2);//提取合并后数组个数
        if($num1>$num2)return self::setErrorInfo('请输入正确格式的配置参数');
        return true;
    }
    /**
     * textarea  判断
     * */
    public static function valiDateTextareaRole($data){
        if (!$data['width']) return self::setErrorInfo('请输入多行文本框的宽度');
        if (!$data['high']) return self::setErrorInfo('请输入多行文本框的高度');
        if ($data['width'] < 0) return self::setErrorInfo('请输入正确的多行文本框的宽度');
        if ($data['high'] < 0) return self::setErrorInfo('请输入正确的多行文本框的宽度');
        return true;
    }

    /**
     * 获取一数据
     * */
    public static function getOneConfig($filed,$value){
        $where[$filed] = $value;
        return self::where($where)->find();
    }
    /**
     * 获取配置分类
     * */
    public static function getAll($id){
        $where['config_tab_id'] = $id;
       $where['status'] = 1;
        return self::where($where)->order('sort desc,id asc')->select();
    }

    /**
     * 获取所有配置分类
     * */
    public static function getConfigTabAll($type=0){
       $configAll = SystemConfigTab::getAll($type);
       $config_tab = array();
       foreach ($configAll as $k=>$v){
           if(!$v['info']){
               $config_tab[$k]['value'] = $v['id'];
               $config_tab[$k]['label'] = $v['title'];
               $config_tab[$k]['icon'] = $v['icon'];
               $config_tab[$k]['type'] = $v['type'];
           }
       }
       return $config_tab;
    }
    /**
     * 选择类型
     * */
    public static function radiotype($type='text'){
        return [
            ['value'=>'text','label'=>'文本框','disabled'=>1]
            ,['value'=>'textarea','label'=>'多行文本框','disabled'=>1]
            ,['value'=>'radio','label'=>'单选按钮','disabled'=>1]
            ,['value'=>'upload','label'=>'文件上传','disabled'=>1]
            ,['value'=>'checkbox','label'=>'多选按钮','disabled'=>1]
        ];
    }
    /**
    * 文本框
    * */
    public static function createInputRule($tab_id){
        $formbuider = array();
        $formbuider[] = Form::radio('type','类型','text')->options(self::radiotype());
        $formbuider[] = Form::select('config_tab_id','分类',$tab_id)->setOptions(SystemConfig::getConfigTabAll(-1));
        $formbuider[] = Form::input('info','配置名称')->autofocus(1);
        $formbuider[] = Form::input('menu_name','字段变量')->placeholder('例如：site_url');
        $formbuider[] = Form::input('desc','配置简介');
        $formbuider[] = Form::input('value','默认值');
        $formbuider[] = Form::number('width','文本框宽(%)',100);
        $formbuider[] = Form::input('required','验证规则')->placeholder('多个请用,隔开例如：required:true,url:true');
        $formbuider[] = Form::number('sort','排序');
        $formbuider[] = Form::radio('status','状态',1)->options([['value'=>1,'label'=>'显示'],['value'=>2,'label'=>'隐藏']]);
        return $formbuider;
    }
     /**
      * 多行文本框
      * */
    public static function createTextAreaRule($tab_id){
        $formbuider = array();
        $formbuider[] = Form::radio('type','类型','textarea')->options(self::radiotype());
        $formbuider[] = Form::select('config_tab_id','分类',$tab_id)->setOptions(SystemConfig::getConfigTabAll(-1));
        $formbuider[] = Form::input('info','配置名称')->autofocus(1);
        $formbuider[] = Form::input('menu_name','字段变量')->placeholder('例如：site_url');
        $formbuider[] = Form::input('desc','配置简介');
        $formbuider[] = Form::textarea('value','默认值');
        $formbuider[] = Form::number('width','文本框宽(%)',100);
        $formbuider[] = Form::number('high','多行文本框高(%)',5);
        $formbuider[] = Form::number('sort','排序');
        $formbuider[] = Form::radio('status','状态',1)->options([['value'=>1,'label'=>'显示'],['value'=>2,'label'=>'隐藏']]);
        return $formbuider;
    }
    /**
     * 单选按钮
     * */
    public static function createRadioRule($tab_id){
        $formbuider = array();
        $formbuider[] = Form::radio('type','类型','radio')->options(self::radiotype());
        $formbuider[] = Form::select('config_tab_id','分类',$tab_id)->setOptions(SystemConfig::getConfigTabAll(-1));
        $formbuider[] = Form::input('info','配置名称')->autofocus(1);
        $formbuider[] = Form::input('menu_name','字段变量')->placeholder('例如：site_url');
        $formbuider[] = Form::input('desc','配置简介');
        $formbuider[] = Form::textarea('parameter','配置参数')->placeholder("参数方式例如:\n1=>男\n2=>女\n3=>保密");
        $formbuider[] = Form::input('value','默认值');
        $formbuider[] = Form::number('sort','排序');
        $formbuider[] = Form::radio('status','状态',1)->options([['value'=>1,'label'=>'显示'],['value'=>2,'label'=>'隐藏']]);
        return $formbuider;
    }
    /**
     * 文件上传
     * */
    public static function createUploadRule($tab_id){
        $formbuider = array();
        $formbuider[] = Form::radio('type','类型','upload')->options(self::radiotype());
        $formbuider[] = Form::select('config_tab_id','分类',$tab_id)->setOptions(SystemConfig::getConfigTabAll(-1));
        $formbuider[] = Form::input('info','配置名称')->autofocus(1);
        $formbuider[] = Form::input('menu_name','字段变量')->placeholder('例如：site_url');
        $formbuider[] = Form::input('desc','配置简介');
        $formbuider[] = Form::radio('upload_type','上传类型',1)->options([['value'=>1,'label'=>'单图'],['value'=>2,'label'=>'多图'],['value'=>3,'label'=>'文件']]);
        $formbuider[] = Form::number('sort','排序');
        $formbuider[] = Form::radio('status','状态',1)->options([['value'=>1,'label'=>'显示'],['value'=>2,'label'=>'隐藏']]);
        return $formbuider;

    }
    /**
     * 多选框
     * */
    public static function createCheckboxRule($tab_id){
        $formbuider = array();
        $formbuider[] = Form::radio('type','类型','checkbox')->options(self::radiotype());
        $formbuider[] = Form::select('config_tab_id','分类',$tab_id)->setOptions(SystemConfig::getConfigTabAll(-1));
        $formbuider[] = Form::input('info','配置名称')->autofocus(1);
        $formbuider[] = Form::input('menu_name','字段变量')->placeholder('例如：site_url');
        $formbuider[] = Form::input('desc','配置简介');
        $formbuider[] = Form::textarea('parameter','配置参数')->placeholder("参数方式例如:\n1=>白色\n2=>红色\n3=>黑色");
//        $formbuider[] = Form::input('value','默认值');
        $formbuider[] = Form::number('sort','排序');
        $formbuider[] = Form::radio('status','状态',1)->options([['value'=>1,'label'=>'显示'],['value'=>2,'label'=>'隐藏']]);
        return $formbuider;
    }

    /**
     * 插入数据到数据库
     * */
    public static function set($data)
    {
        return self::create($data);
    }
}