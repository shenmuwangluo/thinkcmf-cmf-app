<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Released under the MIT License.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
// 请不要随意修改此文件内容
return [
    //七牛存储空间配置
    "QI_NIU_YUN" => [
        "access_key" => "Aplr4dKBZSGdQPf-zGrRvp3R6OQN961EpkZLxtm9",
        "secret_key" => "6df0VGNw5W3YyNuemj8YqzrTzT2cLFYP1o_xR7Su",
        "protocol"=>"http",//域名协议
        "domain"=>"yunzuofile.sxsgky.com",//空间域名
        "yunzuofile"=>"yunzuofile",//空间名称
    ],
    //设置表格高度 400PX、600PX、800PX、1200PX、1600PX
    'TABLE_HEIGHT' => ['400px', '600px', '800px', '1200px', '1600px'],
    //默认分页 可以选择数量
    'DATA_LENGTH' => [10, 20, 40, 60, 100, 200, 400],
    'ADMIN_NAME' => 'BOSS系统',
    'BU_MEN' => [
        15 => [
            'name' => '院办公室部',
            'id' => 15,
            'list' => [
                1 => [
                    'name' => '销售部',
                    'id' => 1,
                    'list' => [
                        2 => [
                            'name' => '销售总监',
                            'id' => 2,
                            'list' => [
                                3 => [
                                    'name' => '销售主管【钟伟奇组】',
                                    'id' => 3,
                                    'list' => [
                                        4 => [
                                            'name' => '销售专员【钟伟奇组】',
                                            'id' => 4,
                                            'list' => []
                                        ]
                                    ],
                                ],
                                19 => [
                                    'name' => '销售主管【陈列刚组】',
                                    'id' => 19,
                                    'list' => [
                                        18 => [
                                            'name' => '销售专员【陈列刚组】',
                                            'id' => 18,
                                            'list' => []
                                        ]
                                    ],
                                ],
                                5 => [
                                    'name' => '销售经理',
                                    'id' => 5,
                                    'list' => []
                                ]
                            ]
                        ]
                    ]
                ],
                6 => [
                    'name' => '商务部',
                    'id' => 6,
                    'list' => [
                        7 => [
                            'name' => '商务总监',
                            'id' => 7,
                            'list' => [
                                8 => [
                                    'name' => '商务主管',
                                    'id' => 8,
                                    'list' => [
                                        10 => [
                                            'name' => '商务专员',
                                            'id' => 10,
                                            'list' => []
                                        ]
                                    ]
                                ],
                                16 => [
                                    'name' => '商务主管【愈佳杰组】',
                                    'id' => 16,
                                    'list' => [
                                        17 => [
                                            'name' => '商务专员【愈佳杰组】',
                                            'id' => 17,
                                            'list' => []
                                        ]
                                    ]
                                ],
                                9 => [
                                    'name' => '商务经理',
                                    'id' => 9,
                                    'list' => []
                                ]
                            ],
                        ]
                    ]
                ],

                12 => [
                    'name' => '项目部',
                    'id' => 12,
                    'list' => [
                        13 => [
                            'name' => '项目总监',
                            'id' => 13,
                            'list' => [
                                14 => [
                                    'name' => '项目经理',
                                    'id' => 14,
                                    'list' => []
                                ]
                            ]
                        ]
                    ]

                ],
                11 => [
                    'name' => '平台部门',
                    'list' => [],
                    'id' => 11
                ],
            ]
        ]


        /*18*/


    ],
    // 应用名称
    'app_name' => '',
    // 应用地址
    'app_host' => '',
    // 应用调试模式
    'app_debug' => APP_DEBUG,
    // 应用Trace
    'app_trace' => APP_DEBUG,
    // 应用模式状态
    'app_status' => '',
    // 是否支持多模块
    'app_multi_module' => true,
    // 入口自动绑定模块
    'auto_bind_module' => false,
    // 注册的根命名空间
    'root_namespace' => ['plugins' => WEB_ROOT . 'plugins/', 'themes' => WEB_ROOT . 'themes/', 'api' => CMF_ROOT . 'api/'],
    // 默认输出类型
    'default_return_type' => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return' => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler' => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler' => 'callback',
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',
    // 是否开启多语言
    'lang_switch_on' => false,
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter' => '',
    // 默认语言
    'default_lang' => 'zh-cn',
    // 应用类库后缀
    'class_suffix' => true,
    // 控制器类后缀
    'controller_suffix' => "Controller",

    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------

    // 默认模块名
//    'default_module'         => 'portal',
    'default_module' => 'gzh',
    // 禁止访问模块
    'deny_module_list' => ['common'],
    // 默认控制器名
//    'default_controller'     => 'Index',
    'default_controller' => 'Index',
    // 默认操作名
//    'default_action'         => 'index',
    'default_action' => 'index',
    // 默认验证器
    'default_validate' => '',
    // 默认的空模块名
    'empty_module' => '',
    // 默认的空控制器名
    'empty_controller' => 'Error',
    // 操作方法前缀
    'use_action_prefix' => false,
    // 操作方法后缀
    'action_suffix' => '',
    // 自动搜索控制器
    'controller_auto_search' => false,

    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------

    // PATHINFO变量名 用于兼容模式
    'var_pathinfo' => 's',
    // 兼容PATH_INFO获取
    'pathinfo_fetch' => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo分隔符
    'pathinfo_depr' => '/',
    // HTTPS代理标识
    'https_agent_name' => '',
    // URL伪静态后缀
    'url_html_suffix' => 'html',
    // URL普通方式参数 用于自动生成
    'url_common_param' => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type' => 0,
    // 是否开启路由延迟解析
    'url_lazy_route' => false,
    // 是否强制使用路由
    'url_route_must' => false,
    // 合并路由规则
    'route_rule_merge' => false,
    // 路由是否完全匹配
    'route_complete_match' => false,
    // 使用注解路由
    'route_annotation' => false,
    // 域名根，如thinkphp.cn
    'url_domain_root' => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert' => true,
    // 默认的访问控制器层
    'url_controller_layer' => 'controller',
    // 表单请求类型伪装变量
    'var_method' => '_method',
    // 表单ajax伪装变量
    'var_ajax' => '_ajax',
    // 表单pjax伪装变量
    'var_pjax' => '_pjax',
    // 是否开启请求缓存 true自动缓存 支持设置请求缓存规则
    'request_cache' => false,
    // 请求缓存有效期
    'request_cache_expire' => null,
    // 全局请求缓存排除规则
    'request_cache_except' => [],

    // 默认跳转页面对应的模板文件
//    'dispatch_success_tmpl'  => __DIR__ . '/tpl/dispatch_jump.tpl',
//    'dispatch_error_tmpl'    => __DIR__ . '/tpl/dispatch_jump.tpl',

    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------

    // 异常页面的模板文件
//    'exception_tmpl'         => __DIR__ . '/tpl/think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message' => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg' => false,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle' => '',
];


