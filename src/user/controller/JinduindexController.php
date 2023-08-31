<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------
namespace app\user\controller;

use app\model\JdUsersModel;
use cmf\controller\HomeBaseController;
use cmf\controller\JinDuBaseController;
use gong_zong_hao\login;
use gong_zong_hao\user;
use think\Validate;

class JinduindexController extends JinDuBaseController
{

    public function get_info()
    {
        $this->success('成功','',JdUsersModel::get(cmf_jin_du_user()['id']));
    }

    public function set_mobile()
    {
        $post = $this->request->post();
        $validata = new Validate([
            "mobile|手机号"=>'require',
            "code|验证码"=>'require',
        ]);
        if(!$validata->check($post)){
            $this->error($validata->getError());
        }
        $mobile = new \app\mobile\controller\IndexController();
        $mobile->pi_pei_code($post['mobile'],$post['code'],$mobile->_number_type['jin_du_set_mobile']);
        if(JdUsersModel::where('mobile',$post['mobile'])->count() != 0){
            $this->error('手机号已存在');
        }

        $userDb = JdUsersModel::get(cmf_jin_du_user()['id']);
        $userDb->mobile = $post['mobile'];
        $userDb->save();
        $this->success('绑定成功');
    }



}
