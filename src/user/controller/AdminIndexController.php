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

use app\gky\controller\LiXiREenController;
use app\gky\validate\AdminLiXiREenValidate;
use app\gky\validate\LiXiREenValidate;
use app\gky\validate\UpdateFuZeRenValidate;
use app\model\AreaModel;
use app\model\AuthAccessModel;
use app\model\AuthRuleModel;
use app\model\LolBuMenModel;
use app\model\LolContactsModel;
use app\model\LolGongSiModel;
use app\model\LolHeTongModel;
use app\model\LolShangJiModel;
use app\model\LolSJXmModel;
use app\model\LolUserImageModel;
use app\model\LolXiangMuModel;
use app\model\LxrModel;
use app\model\NavMenuModel;
use app\model\RoleUserModel;
use app\model\UserModel;
use cmf\controller\AdminBaseController;
use http\Client\Curl\User;
use think\Db;
use think\db\Query;
use think\Exception;
use think\facade\Lang;
use think\Validate;

/**
 * Class AdminIndexController
 * @package app\user\controller
 *
 * @adminMenuRoot(
 *     'name'   =>'用户管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 10,
 *     'icon'   =>'group',
 *     'remark' =>'用户管理'
 * )
 *
 * @adminMenuRoot(
 *     'name'   =>'用户组',
 *     'action' =>'default1',
 *     'parent' =>'user/AdminIndex/default',
 *     'display'=> true,
 *     'order'  => 10000,
 *     'icon'   =>'',
 *     'remark' =>'用户组'
 * )
 */
class AdminIndexController extends AdminBaseController
{

    public $_lol_where = [
        'user_nickname' => ['u.user_nickname', 'like', "%val%"],
        'mobile' => ['u.mobile', 'like', "%val%"],
        'tel' => ['u.tel', 'like', "%val%"],
        'fzr' => ['fzr.user_nickname', 'like', "%val%"],
        'g_name' => ['g.g_name', 'like', "%val%"],
        'shen_fen' => ['u.shen_fen', 'eq', "val"],
        'dao_chu_id' => ['u.id', 'in', 'val'],
        'tui_jian_ren' => ['tui_jian.user_nickname', 'like', '%val%']

    ];
    public $_time_field_1 = [
        'start_time|end_time' => [
            ['u.update_time', '>= time', 0],
            ['u.update_time', '<= time', 1],
        ]
    ];
    public $_xia_zai_file = [
        'id' => '序号',
        'user_nickname' => '姓名',
        'sex.name' => '性别',
        'mobile' => '手机',
        'tel' => '电话',
        'shen_fen' => '身份',
        'view_jurisdiction.name' => '查看权限',
        'gong_si_name' => '所属客户',
        'update_time' => '更新时间',
        'fuZeRen.user_nickname' => '负责人',
        'tui_jian_ren' => '推荐人',
        'remark' => '备注',
    ];

    /**
     * 后台本站用户列表
     * @adminMenu(
     *     'name'   => '本站用户',
     *     'parent' => 'default1',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $this->getList([
            ['u.shen_fen', 'neq', '供应商人士']
        ]);
        return $this->fetch('index', [
            'keHuType' => '所属客户'
        ]);
    }

    public function getList(array $where = [])
    {
        $data = LxrModel::alias('u')
            ->leftJoin(LxrModel::getTable() . ' tui_jian', 'tui_jian.id = u.parent_lxr_id')
            //获取公司
            ->leftJoin(LolContactsModel::getTable() . ' guan_lian', 'guan_lian.l_u_id = u.id')
            ->leftJoin(LolGongSiModel::getTable() . ' g', 'guan_lian.g_id = g.g_id')
            //获取负责人
            ->leftJoin(UserModel::getTable() . ' fzr', 'fzr.id = u.fu_ze_ren_u_id')
            //获取名片
            ->leftJoin(LolUserImageModel::getTable() . ' img', 'img.u_id = u.id')
            ->where(function ($req) {
                switch (input('ming_pian')) {
                    case "有":
                        $req->whereNotNull('img.image_url');
                        break;
                    case "无":
                        $req->whereNull('img.image_url');
                        break;
                }
            })
            ->where($this->get_where())
            ->where($this->get_time_where_1())
            ->order('u.update_time', default_order())
            ->group('u.id')
            ->where($where)
//            ->where('u.shen_fen','neq','供应商人士')
            ->field(['u.*', 'tui_jian.user_nickname as tui_jian_ren'])
            ->lol_paginate();
        // 获取分页显示
        $page = $data->render();
        $list = $data->items();

        foreach ($list as &$item) {
            $gong_si_name = [];
            foreach ($item->lolContacts as $lolContact) {
                if (isset($lolContact->gongSi->g_name)) {
                    if(isset($lolContact->gongSi->g_name))  $gong_si_name[] = $lolContact->gongSi->g_name;

                }
            }
            $item['gong_si_name'] = implode('、', $gong_si_name);
        }

        $this->dao_chu($list, '联系人');

        $this->assign('list', $list);
        $this->assign('data', $data);
        $this->assign('page', $page);
        $user_type = $bu_men = $shen_fen = NavMenuModel::getNavNextData('用户身份');
        $this->assign('user_type', $user_type);
        $this->assign('bu_men', $bu_men);
        $this->assign('shen_fen', $shen_fen);
    }

    public function gysUserList()
    {

        $this->getList([
            ['u.shen_fen', 'eq', '供应商人士']
        ]);
        return $this->fetch('index', [
            'keHuType' => '所属供应商'
        ]);
    }


    /**
     * 本站用户禁用
     * @adminMenu(
     *     'name'   => '本站用户禁用',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户禁用',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
//            $result = Db::name("user")->where(["id" => $id, "user_type" => 2])->setField('user_status', 0);
            $result = Db::name("user")->where(["id" => $id])->setField('user_status', 0);
            if ($result) {
                $this->success("会员禁用成功！", "adminIndex/index");
            } else {
                $this->error('会员禁用失败,会员不存在,或者是BOSS账号！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    /**
     * 本站用户启用
     * @adminMenu(
     *     'name'   => '本站用户启用',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户启用',
     *     'param'  => ''
     * )
     */
    public function cancelBan()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
//            Db::name("user")->where(["id" => $id, "user_type" => 2])->setField('user_status', 1);
            Db::name("user")->where(["id" => $id])->setField('user_status', 1);
            $this->success("会员启用成功！", '');
        } else {
            $this->error('数据传入失败！');
        }
    }

    public function add()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param('data');

            $validate = new AdminLiXiREenValidate();

            if (!$validate->scene('admin_add')->check($data)) {
                $this->error($validate->getError());
            }
            if (!(new Validate())->checkRule($data['mobile'], 'mobileExist')) {
                $this->error('手机号已存在');
            }

            $user = new LxrModel();
            Db::startTrans();
            try {
                $data['last_login_ip'] = get_client_ip(0, true);
                $data['user_status'] = 1;
                $data['user_type'] = 2;
                $user->allowField(true)->save($data);
                if (!$user->allowField(true)->save()) {
                    throw new Exception('添加用户失败');
                }//添加所属客户
                LolContactsModel::addData($data['g_id'], $user->id);//添加名片
                if (!empty($data['user_image'])) {
                    (new LolUserImageModel())
                        ->isUpdate(false)
                        ->save([
                            'u_id' => $user->id,
                            'image_url' => $data['user_image']
                        ]);
                }
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            $this->success('添加成功', cmf_url('info', ['u_id' => $user->id]));
        }

        //获取客户，执行分类


        $shen_fen = NavMenuModel::get(input('shen_fen', 0, 'intval'));
        if (!$shen_fen) {
            $this->error('请选择身份');
        }

        $KHAll = LolGongSiModel::where(function ($req) use ($shen_fen) {
            //获取对应的客户
            if ($shen_fen->icon == '企业') {
                $shen_fen->where('g_type', 'neq', '政府');
            } else {
                $shen_fen->where('g_type', 'eq', '政府');
            }
        })->select();
        return $this->fetch('', [
            'KHAll' => $KHAll,
            'shen_fen' => $shen_fen
        ]);
    }

    /**
     * 会员详情
     */
    public function info()
    {
        $u_id = $this->request->param('u_id', 0, 'intval');
        $data = LxrModel::get($u_id);

        if (!$data) {
            $this->error('数据不存在');
        }
        /**
         * 获取所属客户
         */
        $ke_hu_id = [];
        foreach ($data->lolContacts as $lolContact) {
            $ke_hu_id[] = $lolContact->g_id;
        }
        $ke_hu = LolGongSiModel::whereIn('g_id', $ke_hu_id)->select();

        //获取商机
        $shang_ji = LolShangJiModel::whereIn('g_id', $ke_hu_id)->select();

        $he_tong = LolHeTongModel::whereIn('g_id', $ke_hu_id)->select();

        return $this->fetch('', [
            'ke_hu' => $ke_hu,
            'data' => $data,
            'shang_ji' => $shang_ji,
            'he_tong' => $he_tong
        ]);
    }

    public function edit()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param('data');

            $validate = new AdminLiXiREenValidate();
            if (!$validate->scene('admin_update')->check($data)) {
                $this->error($validate->getError());
            }

            if (!(new Validate())->checkRule($data['mobile'], 'mobileExist:' . $data['id'])) {
                $this->error('手机号已存在');
            }
            $user = LxrModel::get($data['id']);
            Db::startTrans();
            try {
                if (!$user->isUpdate(true)->save($data)) {
                    throw new Exception('修改失败');
                }//添加名片
                if (!empty($data['user_image'])) {
                    LolUserImageModel::addMingPian($user->id, [
                        'image_url' => $data['user_image']
                    ]);
                }
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            $this->success('修改成功', cmf_url('info', ['u_id' => $user->id]));
        }
        $u_id = $this->request->param('u_id', 0, 'intval');
        $data = LxrModel::get($u_id);
        if (!$data) {
            $this->error('数据不存在');
        }
        $this->assign('data', $data);

        return $this->fetch();
    }

    //获取用户信息
    public function getUser()
    {
        $id_group = $this->request->param('id');
        $this->success('成功', '', LxrModel::whereIn('id', $id_group)->select());
    }

    //弹出选择用户
    public function alert_yuan_gong()
    {

        $b_id = 0;

        switch (input('type')) {

            case "ye_wu":
                //业务部
                $b_id = 4;
                break;
            case "all":
                //全部
                $b_id = 0;
                break;
            case 'xiang_mu':
                //项目部
                $b_id = 17;

        }

        $bu_men = (new LolBuMenModel())->xia_ji_bu_men($b_id, 0, []);
        $bu_men = LolBuMenModel::zhuan_huan_select2($bu_men);
        return $this->fetch('', [
            'bu_men' => json_encode($bu_men)
        ]);
    }

    /**
     * 修改负责人
     */
    public function updateFuZeRen()
    {
        $data = $this->request->post();
        $validate = new UpdateFuZeRenValidate();
        if (!$validate->scene('update_fu_ze_ren')->check($data)) {
            $this->error($validate->getError());
        }

        $update = [];
        $data['array_id'] = explode(',', $data['array_id']);

        foreach ($data['array_id'] as $datum) {
            $update[] = [
                'id' => $datum,
                'fu_ze_ren_u_id' => $data['u_id']
            ];
        }
        $user = new LxrModel();
        if ($user->isUpdate(true)->saveAll($update)) {
            $this->success('修改成功');
        } else {
            $this->error('修改失败');
        }
    }

    /**
     * 修改身份
     */
    public function updateShenFenPost()
    {
        $data = $this->request->param();
        $validate = new Validate([
            'id' => 'require|number',
            'shen_fen' => 'require',
        ]);
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        Db::startTrans();
        try {
            $user = LxrModel::get($data['id']);
            $user->shen_fen = $data['shen_fen'];
            if (!$user->save()) {
                throw new Exception('修改身份失败');
            }
//            LolContactsModel::where('l_u_id', $user->id)->delete();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('修改成功');

    }

    /**
     * 创建时间：2021/5/18 17:26
     * 后台-获取不能访问的地址
     */
    public function get_quan_xuan()
    {

        header('Content-Type: application/x-javascript; charset=UTF-8');
        $jie_se = RoleUserModel::where('role_id', 1)->where('user_id', cmf_get_current_admin_id())->count();
        if (cmf_get_current_admin_id() == 1 || $jie_se > 0) {
            $noUrls = [];
        } else {
            //获取角色id
            $jiaoSeId = RoleUserModel::where('user_id', cmf_get_current_admin_id())->field('role_id')->all()->toArray();
            $jiaoSeId = array_column($jiaoSeId, 'role_id');

            //获取已有权限url
            $urls = AuthAccessModel::whereIn('role_id', $jiaoSeId)->field('rule_name')->all()->toArray();
            $urls = array_column($urls, 'rule_name');

            //获取没有的权限url
            $noUrls = AuthRuleModel::whereNotIn('name', $urls)->field('name')->all()->toArray();
            $noUrls = array_column($noUrls, 'name');
        }
        echo "var ADMIN_ROLE=" . json_encode($noUrls);
        exit;

    }


}
