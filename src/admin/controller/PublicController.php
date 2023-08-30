<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\user\model\UserModel;
use cmf\controller\AdminBaseController;
use qi_yw_wei_xi\message;
use qi_yw_wei_xi\yuan_gong;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;

class PublicController extends AdminBaseController
{
    public function initialize()
    {
    }

    public function yuan_gong()
    {

        $wx = new yuan_gong();

        if (!$this->request->param('code')) {
            $wx->get_code(cmf_url('', [], true, true), time());
        } else {
            $user_id = $wx->get_user_id($this->request->param('code'));
            //查询用户是否存在
            $data = UserModel::where('qi_wx_user_id', $user_id)
                ->where('user_type', 1)
                ->find();

            if (!$data) {
                $this->error('用户不存在');
            }
            if ($data['user_status'] == 0) {
                exit('用户已被禁用');
            }
            if ($this->deng_ru($data->toArray())) {
                $this->redirect(url("admin/Index/index"));
            } else {
                $this->error('登入失败');
            }
        }

    }

    /**后台登入
     * @param $user
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    protected function deng_ru($user)
    {
        if (!empty($user) && $user['user_type'] == 1) {
            //验证角色
            try {
                UserModel::adminLogin($user);
            } catch (DataNotFoundException $e) {
                $this->error($e->getMessage());
            } catch (ModelNotFoundException $e) {
                $this->error($e->getMessage());
            } catch (PDOException $e) {
                $this->error($e->getMessage());
            } catch (DbException $e) {
                $this->error($e->getMessage());
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
            return true;


        } else {
            $this->error(lang('USERNAME_NOT_EXIST'));
        }


    }

    /**
     * 后台登陆界面
     */
    public function login()
    {
        $loginAllowed = session("__LOGIN_BY_CMF_ADMIN_PW__");
        if (empty($loginAllowed)) {
            //$this->error('非法登录!', cmf_get_root() . '/');
            return redirect(cmf_get_root() . "/");
        }

        $admin_id = session('ADMIN_ID');
        if (!empty($admin_id)) {//已经登录


            return redirect(url("admin/Index/index"));
        } else {
            session("__SP_ADMIN_LOGIN_PAGE_SHOWED_SUCCESS__", true);
            $result = hook_one('admin_login');
            if (!empty($result)) {
                return $result;
            }
            return $this->fetch(":login");
        }
    }

    /**
     * 登录验证
     */
    public function doLogin()
    {

        if (hook_one('admin_custom_login_open')) {
            $this->error('您已经通过插件自定义后台登录！');
        }

        $loginAllowed = session("__LOGIN_BY_CMF_ADMIN_PW__");
        if (empty($loginAllowed)) {
//            $this->error('非法登录!', cmf_get_root() . '/');
        }

        $captcha = $this->request->param('captcha');
        if (empty($captcha)) {
            $this->error(lang('CAPTCHA_REQUIRED'));
        }
        //验证码
        if (!cmf_captcha_check($captcha)) {
            $this->error(lang('CAPTCHA_NOT_RIGHT'));
        }

        $name = $this->request->param("username");
        if (empty($name)) {
            $this->error(lang('USERNAME_OR_EMAIL_EMPTY'));
        }
        $pass = $this->request->param("password");
        if (empty($pass)) {
            $this->error(lang('PASSWORD_REQUIRED'));
        }
        if (strpos($name, "@") > 0) {//邮箱登陆
            $where['user_email'] = $name;
        } else {
            $where['user_login'] = $name;
        }

        $result = Db::name('user')->where($where)->find();

        if (!in_array($result['id'], [1, 45]) && !APP_DEBUG) {
            $this->error('管理员请使用企业微信登入');
        }
        if (cmf_compare_password($pass, $result['user_pass'])) {
            if ($this->deng_ru($result) == true) {
                $this->success('登入成功', cmf_url('admin/Index/index'));
            } else {
                $this->error('登入失败');
            }
        } else {
            $this->error('账号或密码错误');
        }

        /*if (!empty($result) && $result['user_type'] == 1) {

            if (cmf_compare_password($pass, $result['user_pass'])) {
                $groups = Db::name('RoleUser')
                    ->alias("a")
                    ->join('__ROLE__ b', 'a.role_id =b.id')
                    ->where(["user_id" => $result["id"], "status" => 1])
                    ->value("role_id");
                print_r($groups);

                if ($result["id"] != 1 && (empty($groups) || empty($result['user_status']))) {
                    $this->error(lang('USE_DISABLED'));
                }
                //登入成功页面跳转
                session('ADMIN_ID', $result["id"]);
                session('name', $result["user_login"]);
                $result['last_login_ip']   = get_client_ip(0, true);
                $result['last_login_time'] = time();
                $token                     = cmf_generate_user_token($result["id"], 'web');
                if (!empty($token)) {
                    session('token', $token);
                }
                Db::name('user')->update($result);
                cookie("admin_username", $name, 3600 * 24 * 30);
                session("__LOGIN_BY_CMF_ADMIN_PW__", null);
                $this->success(lang('LOGIN_SUCCESS'), url("admin/Index/index"));
            } else {
                $this->error(lang('PASSWORD_NOT_RIGHT'));
            }
        } else {
            $this->error(lang('USERNAME_NOT_EXIST'));
        }*/
    }

    /**
     * 后台BOSS账号退出
     */
    public function logout()
    {
        session('ADMIN_ID', null);
        return redirect(url('admin/index/index', [], false, true));
    }
}
