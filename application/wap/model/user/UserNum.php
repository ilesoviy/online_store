<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/30
 */

namespace app\wap\model\user;


use basic\ModelBasic;
use traits\ModelTrait;
use think\Db;

class UserNum extends ModelBasic
{
    use ModelTrait;

    protected $insert = ['add_time'];

    protected function setAddTimeAttr()
    {
        return time();
    }
    /**
     * 用户使用次数获得
     */
    public static function income($title,$uid,$number,$balance = 0,$mark = '')
    {
        $data=[
            'uid'=>$uid,
            'pm'=>1,
            'title'=>$title,
            'number'=>$number,
            'balance'=>$balance,
            'mark'=>$mark,
            'add_time'=>time()
        ];
        return db('user_num_bill')->insert($data);
    }
    /**
     * 用户使用次数使用
     */
    public static function expend($title,$uid,$number,$balance = 0,$mark = '')
    {
       $data=[
            'uid'=>$uid,
            'pm'=>0,
            'title'=>$title,
            'number'=>$number,
            'balance'=>$balance,
            'mark'=>$mark,
            'add_time'=>time()
        ];
        return db('user_num_bill')->insert($data);
    }

}