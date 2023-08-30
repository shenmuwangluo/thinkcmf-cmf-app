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

use app\gky\validate\TongJiValidate;
use app\model\LolBuMenModel;
use app\model\LolContactsModel;
use app\model\LolHeTongModel;
use app\model\LolJieDanModel;
use app\model\LolShangJiModel;
use app\model\LolXiangMuLogModel;
use app\model\LolXiangMuModel;
use app\model\LolXiaoShouBaoJiaModel;
use app\model\LolYeWuShenQingJiLuModel;
use app\model\NavMenuModel;
use app\tongji\TongJiController;
use cmf\lib\Storage;
use think\Validate;
use think\Image;
use cmf\controller\UserBaseController;
use app\user\model\UserModel;
use think\Db;

class ProfileController extends UserBaseController
{
    public $_this_title = [
        'center' => '主页',
        'index' => '我的'
    ];

    public function getUserInfo()
    {
        return $this->success('', '', cmf_get_current_user());
    }

    //我的 {：cmf_url('user/Profile/index')}
    public function index()
    {


        $user = cmf_get_current_user();
        $this->assign($user);

        $userId = cmf_get_current_user_id();
        $this->assign('user', $user);
        $this->di_bu_an_niu();
        /**
         * 商机：商机中负责人为当前账号，状态为：待处理、跟进中  的商机数量
         * 接单：接单中负责人为当前账号，状态为：待处理、跟进中  的接单数量
         * 报价：当前账号创建的销售报价数量，销售报价对应的接单状态为：跟进中
         * 订单：订单中负责人为当前账号，订单状态为：进行中(审核通过，关联项目未全部是报告交付)  的订单数量
         * 待回款：订单中负责人为当前账号，订单状态：回款中（销售订单-回款中）  的订单数量
         */


        //商机：商机中负责人为当前账号，状态为：待处理、跟进中  的商机数量
        $shang_ji = LolShangJiModel::whereIn('s_status', '20,30')
            ->where('u_id', cmf_get_current_user_id())
            ->count();
        //接单：接单中负责人为当前账号，状态为：待处理、跟进中  的接单数量
        $jie_dan = LolJieDanModel::where('create_u_id', cmf_get_current_user_id())
            ->whereIn('dang_qian_jie_duan', '10,20,30,40,50')
            ->count();
        //报价：当前账号创建的销售报价数量，销售报价对应的接单状态为：跟进中
        $bao_ji = LolXiaoShouBaoJiaModel::hasWhere('jieDan', function ($req) {
            $req->whereIn('dang_qian_jie_duan', '10,20,30,40,50');//获取跟进中的接单
        })
//            ->where('create_u_id', cmf_get_current_user_id())
            ->count();
//        订单：订单中负责人为当前账号，订单状态为：进行中(审核通过，关联项目未全部是报告交付)  的订单数量
        $din_dan = LolHeTongModel::hasWhere('xiangMu', function ($req) {
            $req->where('xm_status', '<', 4);
        })
            ->where('fu_ze_u_id', cmf_get_current_user_id())
            ->count();

//        待回款：订单中负责人为当前账号，订单状态：回款中（销售订单-回款中）  的订单数量
//        $where[] = ['h.audit_status', 'eq', '审核通过'];
//        $where[] = Db::raw('h.yi_hui_jin_e < h.h_money AND xm.xm_status is Null');

        $hui_kuan = LolHeTongModel::alias('h')
            ->leftJoin(LolXiangMuModel::getTable() . ' xm', 'xm.h_id = h.h_id AND xm.xm_status != 4')
            ->where('h.fu_ze_u_id', cmf_get_current_user_id())
            ->where('h.audit_status', 'eq', '审核通过')
            ->where(Db::raw('h.yi_hui_jin_e < h.h_money AND xm.xm_status is Null'))
            ->count();

        //业务申请
        $ye_wu_shen_qing = LolYeWuShenQingJiLuModel::where('shen_qing_zhuang_tai', '10')
            ->where('shen_qing_ren_u_id', cmf_get_current_user_id())
            ->count();

        $shen_pi_yuan = false;
        if (!empty(lol_lang('审批人员邮箱')['content'])) {
            $shen_pi_yuan = cmf_get_current_user()['user_email'] == lol_lang('审批人员邮箱')['content'];
        }
        $shen_pi_shu = 0;
        if ($shen_pi_yuan) { //获取待审批申领

            $shen_pi_yuan = LolYeWuShenQingJiLuModel::where('shen_qing_zhuang_tai', '10')
                ->count();
        }



        return $this->fetch('', [
            'shang_ji' => $shang_ji,
            'jie_dan' => $jie_dan,
            'hui_kuan' => $hui_kuan,
            'din_dan' => $din_dan,
            'bao_ji' => $bao_ji,
            'ye_wu_shen_qing' => $ye_wu_shen_qing,
            'shen_pi_yuan' => $shen_pi_yuan,
            'shen_pi_shu' => $shen_pi_yuan
        ]);
    }

    //统计 {：cmf_url('user/Profile/tong_ji')}
    public function tong_ji()
    {

//        $this->di_bu_an_niu();
//        $this->assign(TongJiController::jiChu());

        $jin_zi_ta = [];
        $jie_dan_jie_duan = config('JIE_DAN_JIE_DUAN');
        foreach ([60, 50, 40, 30, 20, 10] as $item) {
            $jin_zi_ta[] = [
                'name' => $jie_dan_jie_duan[$item]['name'] . "：{$jie_dan_jie_duan[$item]['jin_du']}%",
                'value' => $jie_dan_jie_duan[$item]['jin_zi_ta'],
                'id' => $jie_dan_jie_duan[$item]['id']
            ];
        }
        $this->success('', '', $jin_zi_ta);

//        return $this->fetch('', [
//
//            'jin_zi_ta' => json_encode($jin_zi_ta)
//        ]);
    }

    /**
     * start_time 开始时间
     * end_time 结束时间
     * 查询的人员id 多个id使用“,”隔开 比如：1,2,3
     */
    public function tongJiPost()
    {
        $data = $this->request->post();
        $validate = new TongJiValidate();
        if (!$validate->scene('select')->check($data)) {
            $this->error($validate->getError());
        }
        $jin_zi_ta = [];
        $all = LolJieDanModel::whereIn('create_u_id', $data['u_id'])
            ->whereBetweenTime('update_time', $data['start_time'], $data['end_time'])
            ->all();
        $jie_dan_jie_duan = config('JIE_DAN_JIE_DUAN');
        foreach ([60, 50, 40, 30, 20, 10] as $item) {
            $jin_zi_ta[] = [
                'name' => $jie_dan_jie_duan[$item]['name'],
                'length' => $this->qiuHe($all, $jie_dan_jie_duan[$item]['id'])
            ];
        }
        return $this->success('成功', '', $jin_zi_ta);

    }

    /**获取数据 数量，用于统计订单
     * @param $data
     * @param $type
     */
    protected function qiuHe($data, $type)
    {
        $sum = 0;
        foreach ($data as $datum) {

            if ($datum->dang_qian_jie_duan['id'] == $type) {
                $sum++;
            }
        }
        return $sum;
    }

    function lolPath($val, $f = "/")
    {
        //替换重复的符号
        $http = '';
        preg_match("/^http.*?\/\//", $val, $data);

        if (count($data) != 0) {
            $http = $data[0];
            $val = preg_replace("/^http.*?\/\//", '', $val);
        }

        $val = preg_replace("/\/+/", $f, $val);

        return $http . $val;
    }

    /**
     * 会员中心首页
     */
    public function center()
    {
        $user = cmf_get_current_user();
        $bu_men = get_bu_men_quan_xian(UserModel::where('id',cmf_get_current_user_id())->value('qi_wx_department'));
        $quan_xian = NavMenuModel::where('nav_id', 10)
            ->whereIn('id', array_column($bu_men, 'nav_menu_id'))
            ->order('list_order', 'ASC')
            ->where('status', 1)
            ->select()
            ->toArray();
        $ying_yong_quan_xian = (new NavMenuModel())->getParents($quan_xian, 188, 0);
        $this->success('', '/vhome/user/profile/center', $ying_yong_quan_xian);

    }

    /**
     * 获取底部菜单
     */
    public function di_bu_an_niu()
    {
        $bu_men = get_bu_men_quan_xian(cmf_get_current_user()['qi_wx_department']);
        $bi_bu_an_niu = NavMenuModel::where('parent_id', 0)
            ->whereIn('id', array_column($bu_men, 'nav_menu_id'))
            ->order('list_order', 'ASC')
            ->where('status', 1)
            ->where('id','neq',209)
            ->where('gui_dang_zi_liao','底部菜单')
            ->select()->toArray();

        if($this->request->isAjax()){
            $this->success('', '', $bi_bu_an_niu);

        }
        $this->assign('bi_bu_an_niu',$bi_bu_an_niu);

    }

    /**
     * 获取快捷键
     */
    public function get_kuai_jie_jian()
    {
        $this->success(
            '', '',
            NavMenuModel::where('parent_id', 209)
                ->order('list_order', 'ASC')
                ->where('status', 1)
                ->all()
        );
    }

    /**
     * 编辑用户资料
     */
    public function edit()
    {

        if ($this->request->isAjax()) {
            $this->success('', '',
                UserModel::field(['user_pass'],true)->get(cmf_get_current_user_id())
            );
        }
        $user = cmf_get_current_user();
        $this->assign($user);
        return $this->fetch('edit');
    }


    /**
     * 编辑用户资料提交
     */
    public function editPost()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'user_nickname' => 'max:32',
                'sex' => 'between:0,2',
                'birthday' => 'dateFormat:Y-m-d|after:-88 year|before:-1 day',
                'user_url' => 'url|max:64',
                'signature' => 'max:128',
            ]);
            $validate->message([
                'user_nickname.max' => lang('NICKNAME_IS_TO0_LONG'),
                'sex.between' => lang('SEX_IS_INVALID'),
                'birthday.dateFormat' => lang('BIRTHDAY_IS_INVALID'),
                'birthday.after' => lang('BIRTHDAY_IS_TOO_EARLY'),
                'birthday.before' => lang('BIRTHDAY_IS_TOO_LATE'),
                'user_url.url' => lang('URL_FORMAT_IS_WRONG'),
                'user_url.max' => lang('URL_IS_TO0_LONG'),
                'signature.max' => lang('SIGNATURE_IS_TO0_LONG'),
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $editData = new UserModel();
            if ($editData->editData($data)) {
                $this->success(lang('EDIT_SUCCESS'), "user/profile/center");
            } else {
                $this->error(lang('NO_NEW_INFORMATION'));
            }
        } else {
            $this->error(lang('ERROR'));
        }
    }

    /**
     * 个人中心修改密码
     */
    public function password()
    {
        $user = cmf_get_current_user();
        $this->assign($user);
        return $this->fetch();
    }

    /**
     * 个人中心修改密码提交
     */
    public function passwordPost()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'old_password' => 'require|min:6|max:32',
                'password' => 'require|min:6|max:32',
                'repassword' => 'require|min:6|max:32',
            ]);
            $validate->message([
                'old_password.require' => lang('old_password_is_required'),
                'old_password.max' => lang('old_password_is_too_long'),
                'old_password.min' => lang('old_password_is_too_short'),
                'password.require' => lang('password_is_required'),
                'password.max' => lang('password_is_too_long'),
                'password.min' => lang('password_is_too_short'),
                'repassword.require' => lang('repeat_password_is_required'),
                'repassword.max' => lang('repeat_password_is_too_long'),
                'repassword.min' => lang('repeat_password_is_too_short'),
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            $login = new UserModel();
            $log = $login->editPassword($data);
            switch ($log) {
                case 0:
                    $this->success(lang('change_success'), 'user/profile/center');
                    break;
                case 1:
                    $this->error(lang('password_repeat_wrong'));
                    break;
                case 2:
                    $this->error(lang('old_password_is_wrong'));
                    break;
                default :
                    $this->error(lang('ERROR'));
            }
        } else {
            $this->error(lang('ERROR'));
        }

    }

    // 用户头像编辑
    public function avatar()
    {
        $user = cmf_get_current_user();
        $this->assign($user);
        return $this->fetch();
    }

    // 用户头像上传
    public function avatarUpload()
    {
        $file = $this->request->file('file');
        $result = $file->validate([
            'ext' => 'jpg,jpeg,png',
            'size' => 1024 * 1024
        ])->move(WEB_ROOT . 'upload' . DIRECTORY_SEPARATOR . 'avatar' . DIRECTORY_SEPARATOR);

        if ($result) {
            $avatarSaveName = str_replace('//', '/', str_replace('\\', '/', $result->getSaveName()));
            $avatar = 'avatar/' . $avatarSaveName;
            session('avatar', $avatar);

            return json_encode([
                'code' => 1,
                "msg" => "上传成功",
                "data" => ['file' => $avatar],
                "url" => ''
            ]);
        } else {
            return json_encode([
                'code' => 0,
                "msg" => $file->getError(),
                "data" => "",
                "url" => ''
            ]);
        }
    }

    // 用户头像裁剪
    public function avatarUpdate()
    {
        $avatar = session('avatar');
        if (!empty($avatar)) {
            $w = $this->request->param('w', 0, 'intval');
            $h = $this->request->param('h', 0, 'intval');
            $x = $this->request->param('x', 0, 'intval');
            $y = $this->request->param('y', 0, 'intval');

            $avatarPath = WEB_ROOT . "upload/" . $avatar;

            $avatarImg = Image::open($avatarPath);
            $avatarImg->crop($w, $h, $x, $y)->save($avatarPath);

            $result = true;
            if ($result === true) {
                $storage = new Storage();
                $result = $storage->upload($avatar, $avatarPath, 'image');

                $userId = cmf_get_current_user_id();
                Db::name("user")->where("id", $userId)->update(["avatar" => $avatar]);
                session('user.avatar', $avatar);
                $this->success("头像更新成功！");
            } else {
                $this->error("头像保存失败！");
            }

        }
    }

    /**
     * 绑定手机号或邮箱
     */
    public function binding()
    {
        $user = cmf_get_current_user();
        $this->assign($user);
        return $this->fetch();
    }

    /**
     * 绑定手机号
     */
    public function bindingMobile()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'username' => 'require|number|unique:user,mobile',
                'verification_code' => 'require',
            ]);
            $validate->message([
                'username.require' => '手机号不能为空',
                'username.number' => '手机号只能为数字',
                'username.unique' => '手机号已存在',
                'verification_code.require' => '验证码不能为空',
            ]);

            $data = $this->request->post();

            var_dump($data);

            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
            if (!empty($errMsg)) {
                $this->error($errMsg);
            }
            $userModel = new UserModel();
            $log = $userModel->bindingMobile($data);
            switch ($log) {
                case 0:
                    $this->success('手机号绑定成功');
                    break;
                default :
                    $this->error('未受理的请求');
            }
        } else {
            $this->error("请求错误");
        }
    }

    /**
     * 绑定邮箱
     */
    public function bindingEmail()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'username' => 'require|email|unique:user,user_email',
                'verification_code' => 'require',
            ]);
            $validate->message([
                'username.require' => '邮箱地址不能为空',
                'username.email' => '邮箱地址不正确',
                'username.unique' => '邮箱地址已存在',
                'verification_code.require' => '验证码不能为空',
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
            if (!empty($errMsg)) {
                $this->error($errMsg);
            }
            $userModel = new UserModel();
            $log = $userModel->bindingEmail($data);
            switch ($log) {
                case 0:
                    $this->success('邮箱绑定成功');
                    break;
                default :
                    $this->error('未受理的请求');
            }
        } else {
            $this->error("请求错误");
        }
    }

    public function bIdSwitchUId()
    {
        $bId = $this->request->post('bId');
        $bIdArray = (new LolBuMenModel())->get_nex_bu_men_id($bId);
        $bId = array_column($bIdArray, 'id');
        $user = \app\model\UserModel::whereIn('qi_wx_department', $bId)->all();
        $this->success('成功', '', $user);
    }

}
