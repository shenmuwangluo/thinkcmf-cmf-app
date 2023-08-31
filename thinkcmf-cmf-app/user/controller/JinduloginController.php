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

class JinduloginController extends HomeBaseController
{

    public function login()
    {
        $post = $this->request->post();
        $validate = new Validate([
            'mobile|手机号' => "require",
            "code|验证码" => "require"
        ]);
        if (!$validate->check($post)) {
            $this->error($validate->getError());
        }
        $mobile = new \app\mobile\controller\IndexController();
        $mobile->pi_pei_code($post['mobile'], $post['code'], $mobile->_number_type["jin_du"]);
        $userDb = JdUsersModel::get(['mobile'=>$post['mobile']]);

        if(empty($userDb)){
            $userDb = new JdUsersModel();
            $userDb->nickname = '未填写';
            $userDb->sex = 0;
            $userDb->mobile = $post['mobile'];
            $userDb->tou_xiang = "http://qny.shenmuwl.com/headicon.png";
            $userDb->save();
        }

        cmf_jin_du_user($userDb);
        $this->success('登录成功','',$userDb);
    }

    public function wx_login()
    {
        $a = new login();
        $url = cmf_url("yan_zheng_code",'',true,true);
        $a->getCode($url);
    }

    public function yan_zheng_code()
    {
        $code = $this->request->get('code');
        if(empty($code)){
            $this->redirect(cmf_url('wx_login'));
        }
        $user = new user();
        $data = $user->getOpenid($code);
        if(empty($data)){
            $this->error('获取openId失败');
        }
        $userDb = JdUsersModel::get(['wx_openid'=>$data->openid]);
        if(empty($userDb)){
            $userInfo = $user->getUserInfo($data->openid,$data->access_token);
            $userDb = new JdUsersModel();
            $userDb->nickname = $userInfo->nickname;
            $userDb->sex = $userInfo->sex;
            $userDb->wx_openid = $userInfo->openid;
            $userDb->tou_xiang = $userInfo->headimgurl;
            $userDb->save();
        }

        if(empty($userDb)){
            $this->error('获取用户信息失败');
        }
        cmf_jin_du_user($userDb);

        header("location:/gzh/user/jin_du_index");

    }


}
