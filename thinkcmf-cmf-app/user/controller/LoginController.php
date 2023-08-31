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

use app\mobile\controller\IndexController;
use app\model\LxrModel;
use qi_yw_wei_xi\qi_ye_ma\Login;
use qi_yw_wei_xi\qi_ye_ma\ShouJiJieMi;
use qi_yw_wei_xi\yuan_gong;
use think\facade\Validate;
use cmf\controller\HomeBaseController;
use app\user\model\UserModel;

class LoginController extends HomeBaseController
{

    public function yuan_gong()
    {

        $yuan_gong = new yuan_gong();

        $autoLoginUrl = $this->request->get('autoLoginUrl');

        if (!empty($autoLoginUrl)) {
            //实现自动登录
            cookie('autoLoginUrl', $autoLoginUrl);
        }

        $code = $this->request->param('code');
        if (empty($code)) {
            //返回地址
            $yuan_gong->get_code(cmf_url('', [], true, true), time());
        }
        else {
            $user_id = $yuan_gong->get_user_id($code);
            //查询用户是否存在

            $data = UserModel::where('qi_wx_user_id', $user_id)
                ->find();
            if (!$data) {
                $this->error('用户不存在');
            }
            if ($data->user_status != 1) {
                $this->error('用户已被禁用，请联系管理员');
            }
            //执行前端登入
            $this->deng_ru($data->toArray());
        }
    }

    /**执行登入
     * @param $user
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    protected function deng_ru($user)
    {
        session('user', $user);
        $data = [
            'last_login_time' => time(),
            'last_login_ip' => get_client_ip(0, true)
        ];
        UserModel::where('id', $user["id"])->update(UserModel::getUpdate($data));
        $token = cmf_generate_user_token($user["id"], 'web');
        if (!empty($token)) {
            session('token', $token);
        }

        $autoLoginUrl = cookie('autoLoginUrl');  //获取
        if ($autoLoginUrl) {
            cookie('autoLoginUrl', null);
            $this->redirect($autoLoginUrl);
        }
        else {
            $this->redirect('/vhome/user/profile/center');
        }

    }

    /**
     * 登录
     */
    public function index()
    {
        $redirect = $this->request->param("redirect");
        if (empty($redirect)) {
            $redirect = $this->request->server('HTTP_REFERER');
        }
        else {
            if (strpos($redirect, '/') === 0 || strpos($redirect, 'http') === 0) {
            }
            else {
                $redirect = base64_decode($redirect);
            }
        }
        if (!empty($redirect)) {
            session('login_http_referer', $redirect);
        }
        if (cmf_is_user_login()) { //已经登录时直接跳到首页
            if (cmf_get_current_user()['lol_user_type'] == 1) {
                $this->redirect(url('gky/public_order/index'));
            }
            else {
                $this->redirect('/vhome/user/profile/center');
            }
//            return redirect($this->request->root() . '/');
        }
        else {
            return $this->fetch(":login");
        }
    }

    //登入页面 {:cmf_url('user/Login/login_page')}
    public function login_page()
    {
        $redirect = $this->request->param("redirect");
        if (empty($redirect)) {
            $redirect = $this->request->server('HTTP_REFERER');
        }
        else {
            if (strpos($redirect, '/') === 0 || strpos($redirect, 'http') === 0) {
            }
            else {
                $redirect = base64_decode($redirect);
            }
        }
        if (!empty($redirect)) {
            session('login_http_referer', $redirect);
        }
        if (cmf_is_user_login()) { //已经登录时直接跳到首页
            if (cmf_get_current_user()['lol_user_type'] == 1) {
                $this->redirect(url('gky/public_order/index'));
            }
            else {
                $this->redirect(url('user/profile/center'));
            }
//            return redirect($this->request->root() . '/');
        }
        else {
            return $this->fetch();
        }
    }


    /**
     * 登录验证提交
     */
    public function doLogin()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $userModel = new UserModel();

            //手机登入
            if (array_key_exists('mobile', $data)) {

                //员工不能登入
                $validate = new \think\Validate([
                    'mobile' => 'require',
                    'code' => 'require',
                ]);
                $validate->message([
                    'mobile' => '请输入手机号',
                    'code' => '请输入验证码',
                ]);

                if (!$validate->check($data)) {
                    $this->error($validate->getError());
                }
                if (!cmf_check_mobile($data['mobile'])) {
                    $this->error('手机格式错误');
                }

                $user['mobile'] = $data['mobile'];
                $user['user_pass'] = 000;
                $log = $userModel->doMobile($user);
            }
            else {
//                账号登入
                $validate = new \think\Validate([
                    'username' => 'require',
                    'password' => 'require|min:6|max:32',
                ]);
                $validate->message([
                    'username.require' => '用户名不能为空',
                    'password.require' => '密码不能为空',
                    'password.max' => '密码不能超过32个字符',
                    'password.min' => '密码不能小于6个字符',
                    'captcha.require' => '验证码不能为空',
                ]);

                if (!$validate->check($data)) {
                    $this->error($validate->getError());
                }
                $user['user_pass'] = $data['password'];


                if (Validate::is($data['username'], 'email')) {
                    $this->dengRUYanZheng('user_email', $data['username']);
                    $user['user_email'] = $data['username'];
                    $log = $userModel->doEmail($user);
                }
                else {
                    $this->dengRUYanZheng('user_login', $data['username']);

                    $user['user_login'] = $data['username'];
                    $log = $userModel->doName($user);
                }

            }

            $session_login_http_referer = session('login_http_referer');
            $redirect = empty($session_login_http_referer) ? $this->request->root() : $session_login_http_referer;
            switch ($log) {
                case 0:
                    cmf_user_action('login');
                    $this->success(lang('LOGIN_SUCCESS'), '/vhome/user/profile/center');

                    break;
                case 1:
                    $this->error(lang('PASSWORD_NOT_RIGHT'));
                    break;
                case 2:
                    $this->error('账户不存在');
                    break;
                case 3:
                    $this->error('账号被禁止访问系统');
                    break;

                default :
                    $this->error('未受理的请求');
            }
        }
        else {
            $this->error("请求错误");
        }
    }

    public function dengRUYanZheng($field, $val)
    {

        if (!APP_DEBUG) {
            $count = \app\model\UserModel::where($field, $val)->where('user_type', 1)->count();
            if ($count) {
                $this->error('员工禁止登入,请通过企业微信进入');
            }
        }
    }

    /**
     * 找回密码
     */
    public function findPassword()
    {
        return $this->fetch('/find_password');
    }

    /**
     * 用户密码重置
     */
    public function passwordReset()
    {

        if ($this->request->isPost()) {
            $validate = new \think\Validate([
                'captcha' => 'require',
                'verification_code' => 'require',
                'password' => 'require|min:6|max:32',
            ]);
            $validate->message([
                'verification_code.require' => '验证码不能为空',
                'password.require' => '密码不能为空',
                'password.max' => '密码不能超过32个字符',
                'password.min' => '密码不能小于6个字符',
                'captcha.require' => '验证码不能为空',
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            $captchaId = empty($data['_captcha_id']) ? '' : $data['_captcha_id'];
            if (!cmf_captcha_check($data['captcha'], $captchaId)) {
                $this->error('验证码错误');
            }

            $errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
            if (!empty($errMsg)) {
                $this->error($errMsg);
            }

            $userModel = new UserModel();
            if (Validate::is($data['username'], 'email')) {

                $log = $userModel->emailPasswordReset($data['username'], $data['password']);

            }
            else if (cmf_check_mobile($data['username'])) {
                $user['mobile'] = $data['username'];
                $log = $userModel->mobilePasswordReset($data['username'], $data['password']);
            }
            else {
                $log = 2;
            }
            switch ($log) {
                case 0:
                    $this->success('密码重置成功', cmf_url('user/Profile/center'));
                    break;
                case 1:
                    $this->error("您的账户尚未注册");
                    break;
                case 2:
                    $this->error("您输入的账号格式错误");
                    break;
                default :
                    $this->error('未受理的请求');
            }

        }
        else {
            $this->error("请求错误");
        }
    }



}
