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

use app\admin\model\NavMenuModel;
use app\admin\model\UserModel;
use app\model\LolBuMenModel;
use app\model\LolBuMenUserModel;
use app\model\LolHeTongModel;
use app\model\QuanXianBiaoQianModel;
use app\model\QuanXianBiaoUserModel;
use app\model\QuanXianGuanXiModel;
use cmf\controller\AdminBaseController;
use qi_yw_wei_xi\Tree;
use think\Db;
use think\db\Query;
use think\Validate;

/**
 * Class UserController
 * @package app\admin\controller
 * @adminMenuRoot(
 *     'name'   => '管理组',
 *     'action' => 'default',
 *     'parent' => 'user/AdminIndex/default',
 *     'display'=> true,
 *     'order'  => 10000,
 *     'icon'   => '',
 *     'remark' => '管理组'
 * )
 */
class UserController extends AdminBaseController
{
    public $_lol_where = [
        'dao_chu_id' => ['user.id', 'in', "val"],
        'user_login' => ['user.user_login', 'like', "%val%"],
        'user_nickname' => ['user.user_nickname', 'like', "%val%"],
        'mobile' => ['user.qw_mobile', 'like', "%val%"],
        'department_id' => ['bu_men.bu_men_id', 'eq', "val"],
        'user_status' => ['user.user_status', 'eq', "val"],
        'user_email' => ['user.user_email', 'like', "%val%"],
        'user_bian_qian_id'=>['user_bian_qian.quan_xian_biao_qian_id','in','val']
    ];
    //时间范围查询
    public $_time_field = 'gong_si.create_time';
    public $_xia_zai_file = [
        'id' => '序号',
        'user_login' => '用户名',
        'user_email' => '邮箱',
        'user_nickname' => '姓名',
        'sex.name' => '性别',
        'qw_mobile' => '手机号',
        'department_id' => '所属部门',
        'parent_user_nickname' => '所属上级',
        'department_role' => '部门权限',
        'user_status_name' => '状态',
    ];

    /**
     * BOSS账号列表
     * @adminMenu(
     *     'name'   => 'BOSS账号',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => 'BOSS账号管理',
     *     'param'  => ''
     * )
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $content = hook_one('admin_user_index_view');

        if (!empty($content)) {
            return $content;
        }

        /**搜索条件**/
        $userLogin = $this->request->param('user_login');
        $userEmail = trim($this->request->param('user_email'));

        $data = $users = (new \app\model\UserModel())
            ->with([
                'jsonBuMenAll' => ['jsonBuMenName'],
                'joinRoleUser' => ['joinRole'],
                'joinUanXianBiaoUser' => ['joinQuanXianBiaoQian']
            ])
            ->alias('user')
            ->leftJoin(QuanXianBiaoUserModel::getTable().' user_bian_qian','user_bian_qian.user_id = user.id')
            ->where('user.user_type', 1)
            ->leftJoin(UserModel::getTable() . ' parent', 'user.parent_u_id=parent.id')
            //链接部门
            ->leftJoin(LolBuMenUserModel::getTable() . ' bu_men', 'bu_men.user_id=user.id')
            ->field('user.*,parent.user_nickname as parent_user_nickname')
            ->where($this->get_where())
            ->order("id", default_order())
            ->group('user.id')
            ->lol_paginate();
        $users->appends(['user_login' => $userLogin, 'user_email' => $userEmail]);
        // 获取分页显示
        $page = $users->render();

        $rolesSrc = Db::name('role')->select();
        $roles = [];
        foreach ($rolesSrc as $r) {
            $roleId = $r['id'];
            $roles["$roleId"] = $r;
        }

        foreach ($users as &$item) {
            $item->sex = get_sex($item->sex);
            $item->user_status_name = ['禁用', '正常', '未验证'][intval($item->user_status)];
        }
        if($this->request->isAjax()){
            $this->success('成功','',[
                'data'=>$data->items(),
                'total'=>$data->total(),
            ]);
        }

        $this->dao_chu($users, 'yong_hu');
        $this->assign("page", $page);
        $this->assign("roles", $roles);

        $this->assign("data", $data);

        $bu_men = LolBuMenModel::order('order', 'ASC')->select()->toArray();
        $bu_men = (new LolBuMenModel())->get_next(0, $bu_men);

        $this->assign("btn_men", $bu_men);
        return $this->fetch();
    }

    /**
     * BOSS账号添加
     * @adminMenu(
     *     'name'   => 'BOSS账号添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => 'BOSS账号添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        $this->assign('bu_men', config('BU_MEN'));
        $content = hook_one('admin_user_add_view');

        if (!empty($content)) {
            return $content;
        }

        $roles = Db::name('role')->where('status', 1)->order("id DESC")->select();
        $this->assign("roles", $roles);

        $this->assign("user_all", $this->get_user());
        $this->assign('ben_men_all', get_bu_men_select());
        return $this->fetch();
    }


    protected function get_user($id = '')
    {
        $where = [];


        $data = Db::name('user')->where('user_type', 1)
            ->where('id', 'neq', $id)
            ->whereOr('id', 1)
            ->where('user_status', 1)
            ->select()->toArray();


        foreach ($data as &$datum) {
            $user_title = '';
            if (!empty($datum['user_login'])) {
                $user_title .= '用户名：' . $datum['user_login'] . '&nbsp;';
            }

            if (!empty($datum['user_nickname'])) {
                $user_title .= '名称：' . $datum['user_nickname'] . '&nbsp;';
            }

            if (!empty($datum['user_email'])) {
                $user_title .= '邮箱：' . $datum['user_email'] . '&nbsp;';
            }

            if (!empty($datum['mobile'])) {
                $user_title .= '手机号：' . $datum['mobile'] . '&nbsp;';
            }

            $datum['user_title'] = $user_title;
        }

        return $data;
    }

    /**
     * BOSS账号添加提交
     * @adminMenu(
     *     'name'   => 'BOSS账号添加提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => 'BOSS账号添加提交',
     *     'param'  => ''
     * )
     */
    public function addPost()
    {
        $this->error('添加用户,已关闭');
        if ($this->request->isPost()) {
            if (!empty($_POST['role_id']) && is_array($_POST['role_id'])) {
                $role_ids = $_POST['role_id'];
                unset($_POST['role_id']);
                $result = $this->validate($this->request->param(), 'User');
                if ($result !== true) {
                    $this->error($result);
                }
                else {
                    $_POST['user_pass'] = cmf_password($_POST['user_pass']);
                    $_POST['lol_user_type'] = 0;
                    $_POST['role_id'] = 0;

//                    $_POST['department_id'] = get_parent_id($_POST['department_role'])['id'];
                    $this->is_user_data([
                        'mobile' => $_POST['mobile']
                    ], 0, ['mobile' => '手机号已存在']);


                    $result = DB::name('user')->insertGetId($_POST);
                    if ($result !== false) {
                        //$role_user_model=M("RoleUser");
                        foreach ($role_ids as $role_id) {
                            if (cmf_get_current_admin_id() != 1 && $role_id == 1) {
                                $this->error("为了网站的安全，非网站创建者不可创建超级BOSS账号！");
                            }
                            Db::name('RoleUser')->insert(["role_id" => $role_id, "user_id" => $result]);
                        }
                        $this->success("添加成功！", url("user/index"));
                    }
                    else {
                        $this->error("添加失败！");
                    }
                }
            }
            else {
                $this->error("请为此用户指定角色！");
            }

        }
    }

    //验证是存在
    protected function is_user_data($data = [], $id = 0, $title = [])
    {
        foreach ($data as $key => $datum) {
            if (!empty($datum)) {
                if (Db::name('user')->where($key, $datum)->where('id', 'neq', $id)->count()) {
                    $this->error($title[$key]);
                }
            }

        }
    }

    /**
     * BOSS账号编辑
     * @adminMenu(
     *     'name'   => 'BOSS账号编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => 'BOSS账号编辑',
     *     'param'  => ''
     * )
     */
    public function edit()
    {


        $content = hook_one('admin_user_edit_view');

        if (!empty($content)) {
            return $content;
        }

        $id = $this->request->param('id', 0, 'intval');
        $roles = DB::name('role')->where('status', 1)->order("id DESC")->select();
        $this->assign("roles", $roles);
        $role_ids = DB::name('RoleUser')->where("user_id", $id)->column("role_id");
        $this->assign("role_ids", $role_ids);

        $user = \app\model\UserModel::get($id);
        $this->assign($user->toArray());

        $bian_qian_id = array_column($user->joinUanXianBiaoUser->toArray(), 'quan_xian_biao_qian_id');


        $quan_xiang_biao_qian = QuanXianBiaoQianModel::order('id', 'desc')->all()->toArray();
        array_walk($quan_xiang_biao_qian, function (&$item) use ($bian_qian_id) {
            $item['checked'] = in_array($item['id'], $bian_qian_id) ? 'checked' : '';
        });


        $this->assign("user_all", $this->get_user($id));
        $this->assign("quan_xian_biao_qian", $quan_xiang_biao_qian);

        $this->assign('ben_men_all', get_bu_men_select());

        return $this->fetch();
    }

    /**
     * BOSS账号编辑提交
     * @adminMenu(
     *     'name'   => 'BOSS账号编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => 'BOSS账号编辑提交',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        if ($this->request->isPost()) {
            if (!empty($_POST['role_id']) && is_array($_POST['role_id'])) {
                if(empty($_POST['user_nickname'])){
                    $this->error('名称不能为空');
                }
                if (empty($_POST['user_pass'])) {
                    unset($_POST['user_pass']);
                }
                else {
                    $_POST['user_pass'] = cmf_password($_POST['user_pass']);
                }
                $qian_xian_biao_qian = $this->request->post('quan_xian_biao_qian/a',array());
                $role_ids = $this->request->param('role_id/a');
                unset($_POST['role_id']);
                unset($_POST['quan_xian_biao_qian']);
                $result = $this->validate($this->request->param(), 'User.edit');

                if ($result !== true) {
                    // 验证失败 输出错误信息
                    $this->error($result);
                }
                else {
                    //验证数据是否存在
                    $this->is_user_data(
                        ['mobile' => $_POST['mobile']],
                        $_POST['id'],
                        ['mobile' => '手机号已存在']
                    );

                    //修改上下级关系
                    $parent_id = $this->request->post('parent_u_id', 0);
                    $parent_id = intval($parent_id);
                    /**
                     * 验证上级关系
                     * 上级不能是自己的下级
                     */
                    if ($parent_id == $this->request->post('id')) {
                        $this->error('上级不能选择自己');
                    }

                    if (!empty($parent_id)) {
                        $parendInfo = UserModel::get($parent_id);
                        if (empty($parendInfo)) {
                            $this->error('上级不存在');
                        }
                        $tuiJianGuanXi = explode(',', $parendInfo->relation);
                        if (in_array($this->request->post('id'), $tuiJianGuanXi)) {
                            $this->error('不能选择下级做上级');
                        }

                    }


                    $userInfo = \app\model\UserModel::get($this->request->post('id'));
                    \app\model\UserModel::updateAutoParent($this->request->post('id'), $parent_id);

                    $result = DB::name('user')->update($_POST);
                    if ($result !== false) {
                        $uid = $this->request->param('id', 0, 'intval');
                        DB::name("RoleUser")->where("user_id", $uid)->delete();
                        foreach ($role_ids as $role_id) {
                            if (cmf_get_current_admin_id() != 1 && $role_id == 1) {
                                $this->error("为了网站的安全，非网站创建者不可创建超级BOSS账号！");
                            }
                            DB::name("RoleUser")->insert(["role_id" => $role_id, "user_id" => $uid]);
                        }
                        $installData = array();
                        foreach ($qian_xian_biao_qian as $item) {
                            $installData[] = [
                                'user_id' => $uid,
                                'quan_xian_biao_qian_id' => $item
                            ];
                        }
                        QuanXianBiaoUserModel::where('user_id', $uid)->delete();
                        (new QuanXianBiaoUserModel())->saveAll($installData);

                        $this->success("保存成功！");
                    }
                    else {
                        $this->error("保存失败！");
                    }
                }
            }
            else {
                $this->error("请为此用户指定角色！");
            }

        }
    }

    /**
     * BOSS账号个人信息修改
     * @adminMenu(
     *     'name'   => '个人信息',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => 'BOSS账号个人信息修改',
     *     'param'  => ''
     * )
     */
    public function userInfo()
    {
        $id = cmf_get_current_admin_id();
        $user = Db::name('user')->where("id", $id)->find();
        $this->assign($user);
        return $this->fetch();
    }

    /**
     * BOSS账号个人信息修改提交
     * @adminMenu(
     *     'name'   => 'BOSS账号个人信息修改提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => 'BOSS账号个人信息修改提交',
     *     'param'  => ''
     * )
     */
    public function userInfoPost()
    {
        if ($this->request->isPost()) {

            $data = $this->request->post();
            $data['birthday'] = strtotime($data['birthday']);
            $data['id'] = cmf_get_current_admin_id();
            $create_result = Db::name('user')->update($data);;
            if ($create_result !== false) {
                $this->success("保存成功！");
            }
            else {
                $this->error("保存失败！");
            }
        }
    }

    /**
     * BOSS账号删除
     * @adminMenu(
     *     'name'   => 'BOSS账号删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => 'BOSS账号删除',
     *     'param'  => ''
     * )
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id == 1) {
            $this->error("最高BOSS账号不能删除！");
        }

        if (Db::name('user')->delete($id) !== false) {
            Db::name("RoleUser")->where("user_id", $id)->delete();
            $this->success("删除成功！");
        }
        else {
            $this->error("删除失败！");
        }
    }

    /**
     * 停用BOSS账号
     * @adminMenu(
     *     'name'   => '停用BOSS账号',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '停用BOSS账号',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!empty($id)) {
            $result = Db::name('user')->where(["id" => $id, "user_type" => 1])->setField('user_status', '0');
            if ($result !== false) {
//                $this->success("BOSS账号停用成功！", url("user/index"));
                $this->success("BOSS账号停用成功！");
            }
            else {
                $this->error('BOSS账号停用失败！');
            }
        }
        else {
            $this->error('数据传入失败！');
        }
    }

    /**
     * 启用BOSS账号
     * @adminMenu(
     *     'name'   => '启用BOSS账号',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '启用BOSS账号',
     *     'param'  => ''
     * )
     */
    public function cancelBan()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!empty($id)) {
            $result = Db::name('user')->where(["id" => $id, "user_type" => 1])->setField('user_status', '1');
            if ($result !== false) {
//                $this->success("BOSS账号启用成功！", url("user/index"));
                $this->success("BOSS账号启用成功！");
            }
            else {
                $this->error('BOSS账号启用失败！');
            }
        }
        else {
            $this->error('数据传入失败！');
        }
    }

    //编辑标签
    public function editBiaoQian()
    {
        $validate = new Validate([
            'id|id' => 'number|>:0',
            'title|名称' => 'require'
        ]);
        $post = $this->request->post();
        if (!$validate->check($post)) {
            $this->error($validate->getError());
        }


        $biao_qian = new QuanXianBiaoQianModel();
        if (isset($post['id']) && !empty($post['id'])) {
            $biao_qian->isUpdate(true);
        }

        if ($biao_qian->save($post)) {
            $this->success('保存成功');
        }
        $this->error('保存失败');
    }

    /**
     * @throws \think\Exception
     * @throws \think\Exception\DbException
     * 创建时间：2021/12/9 19:46
     * 获取权限标签
     */
    public function getBiaoQian()
    {
        $this->success('成功', '', QuanXianBiaoQianModel::order('id', 'desc')->all());
    }

    //删除标签
    public function delBiaoQian()
    {
        $id = $this->request->post('id/n');
        if (QuanXianBiaoQianModel::destroy($id)) {
            $this->success('删除成功');
        }
        else {
            $this->error('删除失败');
        }
    }

    /**
     * @throws \think\Exception
     * @throws \think\Exception\DbException
     * 创建时间：2021/12/9 19:31
     * 获取权限地址
     */
    public function getQuanXIanUrl()
    {
        $nav_menu = \app\model\NavMenuModel::getQuanXianBiaoQuan();
        $che_id = [];
        if (!empty($quan_xian_biao_qian_id = $this->request->get('quan_xian_biao_qian_id'))) {
            $che_id = QuanXianGuanXiModel::where('quan_xian_biao_qian_id', $quan_xian_biao_qian_id)->field(['nav_menu_id'])->all()->toArray();
            $che_id = array_column($che_id, 'nav_menu_id');
        }
        $Tree = new Tree();
        $data = $Tree->makeTree($nav_menu->toArray(), ['parent_key' => 'parent_id']);
        $this->success('成功', '', [
            'data' => $data,
            'che_id' => $che_id
        ]);
    }

    /**
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 创建时间：2021/12/9 19:45
     * 标签设置权限
     */
    public function setBiaoQianUrl()
    {
        $post = $this->request->post();
        $validate = new Validate([
            'quan_xian_biao_qian_id' => 'require|number',
            'list' => 'array'
        ]);
        if (!$validate->check($post)) $this->error($validate->getError());
        $instrll = [];
        if (isset($post['list'])) {
            foreach ($post['list'] as $item) {
                $instrll[] = $item;
            }
        }


        QuanXianGuanXiModel::where('quan_xian_biao_qian_id', $post['quan_xian_biao_qian_id'])->delete();
        if (!empty($instrll)) {
            $quan_xian = new QuanXianGuanXiModel();
            $quan_xian->saveAll($instrll);
        }


        $this->success('保存成功');
    }
}
