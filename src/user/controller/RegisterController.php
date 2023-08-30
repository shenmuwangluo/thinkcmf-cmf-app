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

use app\gky\validate\UserValidate;
use app\model\LolCmfGysModel;
use app\model\LolContactsModel;
use app\model\LolGongSiModel;
use app\model\LxrModel;
use app\model\UserModel;
use cmf\controller\HomeBaseController;
use think\Db;
use think\facade\Validate;

class RegisterController extends HomeBaseController
{

    /**
     * 前台用户注册
     */
    public function index()
    {
        $redirect = $this->request->post("redirect");
        if (empty($redirect)) {
            $redirect = $this->request->server('HTTP_REFERER');
        } else {
            $redirect = base64_decode($redirect);
        }
        session('login_http_referer', $redirect);

        if (cmf_is_user_login()) {
            return redirect($this->request->root() . '/');
        } else {
            return $this->fetch(":register");
        }
    }

    /**
     * 前台用户注册提交
     */
    public function doRegister()
    {
        exit;
        if ($this->request->isPost()) {
            $rules = [
                'captcha' => 'require',
                'code' => 'require',
                'password' => 'require|min:6|max:32',

            ];

            $isOpenRegistration = cmf_is_open_registration();

            if ($isOpenRegistration) {
                unset($rules['code']);
            }

            $validate = new \think\Validate($rules);
            $validate->message([
                'code.require' => '验证码不能为空',
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

            if (!$isOpenRegistration) {
                $errMsg = cmf_check_verification_code($data['username'], $data['code']);
                if (!empty($errMsg)) {
                    $this->error($errMsg);
                }
            }

            $register = new UserModel();
            $user['user_pass'] = $data['password'];
            if (Validate::is($data['username'], 'email')) {
                $user['user_email'] = $data['username'];
                $log = $register->register($user, 3);
            } else if (cmf_check_mobile($data['username'])) {
                $user['mobile'] = $data['username'];
                $log = $register->register($user, 2);
            } else {
                $log = 2;
            }
            $sessionLoginHttpReferer = session('login_http_referer');
            $redirect = empty($sessionLoginHttpReferer) ? cmf_get_root() . '/' : $sessionLoginHttpReferer;
            switch ($log) {
                case 0:
                    $this->success('注册成功', $redirect);
                    break;
                case 1:
                    $this->error("您的账户已注册过");
                    break;
                case 2:
                    $this->error("您输入的账号格式错误");
                    break;
                default :
                    $this->error('未受理的请求');
            }

        } else {
            $this->error("请求错误");
        }

    }

    public function zhuCe()
    {

        if ($this->request->isAjax()) {

            //数据验证
            $vali  = new UserValidate();
            $data = $this->request->param('data');
            if (!$vali->scene('zhu_ce')->check($data)) {
                $this->error($vali->getError());
            }

            //验证码 验证
            (new \app\mobile\controller\IndexController())->pi_pei_code($data['mobile'], $data['code'], 3);


            try {//写入公司
                Db::startTrans();
                $gong_si = [];
                $gong_si['g_name'] = $data['g_name'];
                $gong_si['g_code'] = get_order_number();
                $gong_si['update_time'] = $gong_si['create_time'] = time();
                if ($data['shen_fen'] == '企业人士') {
                    $gong_si['is_customer'] = '客户';//是否为客户
                    $gong_si['is_supplier'] = '';//是否为供应商
                }
                else {
                    $gong_si['is_customer'] = '';//是否为客户
                    $gong_si['is_supplier'] = '供应商';//是否为供应商
                }
                //$g_id = Db::name('lol_gong_si')->insertGetId($gong_si);
                $g_id = LolGongSiModel::addData($gong_si);//添加供应商表
                if ($g_id && $gong_si['is_supplier'] == '供应商') {
                    LolCmfGysModel::addData([
                        'g_id' => $g_id,
                        'gys_status' => '未审核'
                    ]);
                }//写入用户注册
                $user = [
                    'g_id' => $g_id,
                    'user_nickname' => $data['user_nickname'],
                    'mobile' => $data['mobile'],
                    'user_type' => 2,
                    'sex' => 0,
                    'last_login_time' => $gong_si['create_time'],
                    'create_time' => $gong_si['create_time'],
                    'user_status' => 1,
                    'lol_user_type' => 1,
                    'shen_fen' => $data['shen_fen'],
                    'update_u_id' => 0,
                ];
                $u_id = LxrModel::addData($user);//添加用户关联公司
                LolContactsModel::addData($g_id, $u_id);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

            if ($u_id) {
                UserModel::dengRu($u_id);
                $this->success('平台注册成功', cmf_url('user/login/login_page'));
            } else {
                $this->error('注册失败');
            }
        }
        $this->assign('shen_fen', shen_fen());
        return $this->fetch();
    }


}
