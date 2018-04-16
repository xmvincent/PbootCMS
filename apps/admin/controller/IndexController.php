<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2017年3月13日
 *  默认主页
 */
namespace app\admin\controller;

use core\basic\Controller;
use app\admin\model\IndexModel;

class IndexController extends Controller
{

    private $model;

    public function __construct()
    {
        $this->model = new IndexModel();
    }

    // 登陆页面
    public function index()
    {
        if (session('sid')) {
            location(url('admin/Index/home'));
        }
        $this->display('index.html');
    }

    // 主页面
    public function home()
    {
        $this->assign('shortcuts', session('shortcuts'));
        $this->assign('server', get_server_info());
        $this->display('system/home.html');
    }

    // 异步登录验证
    public function login()
    {
        if (! $_POST) {
            return;
        }
        
        // 在安装了gd库时才执行验证码验证
        if (extension_loaded("gd") && session('config.admin_check_code') && post('checkcode') != session('checkcode')) {
            json(0, '验证码错误！');
        }
        
        // 就收数据
        $username = post('username', 'var', true);
        $password = post('password');
        
        if (! $username) {
            json(0, '用户名不能为空！');
        }
        
        if (! $password) {
            json(0, '密码不能为空！');
        }
        
        // 执行用户登录
        $where = array(
            'username' => $username,
            'password' => encrypt_string($password)
        );
        
        if (! ! $login = $this->model->login($where)) {
            
            session_regenerate_id(true);
            session('sid', encrypt_string($_SERVER['HTTP_USER_AGENT'] . $login->id)); // 会话标识
            session('M', M);
            
            session('id', $login->id); // 用户id
            session('ucode', $login->ucode); // 用户编码
            session('username', $login->username); // 用户名
            session('realname', $login->realname); // 真实名字
            
            session('acodes', $login->acodes); // 用户管理区域
            if ($login->acodes) { // 当前显示区域
                session('acode', $login->acodes[0]);
            } else {
                session('acode', '');
            }
            
            session('rcodes', $login->rcodes); // 用户角色代码表
            session('levels', $login->levels); // 用户权限URL列表
            session('menu_tree', $login->menus); // 菜单树
            $menu_html = make_tree_html($login->menus, 'name', 'url');
            session('menu_html', $menu_html); // 菜单HTML代码
            session('shortcuts', $login->shortcuts); // 桌面图标
            
            session('area_map', $login->area_map); // 区域代码名称映射表
            session('area_tree', $login->area_tree); // 用户区域树
            
            $this->log('登陆成功!');
            json(1, url('admin/Index/home'));
        } else {
            $this->log('登陆失败!');
            json(0, '用户名或密码错误！');
        }
    }

    // 退出登录
    public function loginOut()
    {
        session_unset();
        location(url('/admin/index/index'));
    }

    // 用户中心，修改密码
    public function ucenter()
    {
        if ($_POST) {
            $username = post('username'); // 用户名
            $realname = post('realname'); // 真实姓名
            $cpassword = post('cpassword'); // 现在密码
            $password = post('password'); // 新密码
            $rpassword = post('rpassword'); // 确认密码
            
            if (! $username) {
                alert_back('用户名不能为空！');
            }
            if (! $cpassword) {
                alert_back('当前密码不能为空！');
            }
            
            $data = array(
                'username' => $username,
                'realname' => $realname,
                'update_user' => $username
            );
            
            // 如果有修改密码，则添加数据
            if ($password) {
                if ($password != $rpassword) {
                    alert_back('确认密码不正确！');
                }
                $data['password'] = encrypt_string($password);
            }
            
            // 检查现有密码
            if ($this->model->checkUserPwd(encrypt_string($cpassword))) {
                if ($this->model->modUserInfo($data)) {
                    session('username', post('username'));
                    session('realname', post('realname'));
                    $this->log('用户修改密码成功！');
                    success('用户密码修改成功！', - 1);
                }
            } else {
                $this->log('用户修改密码失败！');
                alert_back('当前密码错误！');
            }
        }
        $this->display('system/ucenter.html');
    }

    // 切换显示的数据区域
    public function area()
    {
        if ($_POST) {
            $acode = post('acode');
            if (in_array($acode, session('acodes'))) {
                session('acode', $acode);
            }
            location(- 1);
        }
    }

    // 清理缓存
    public function clearCache()
    {
        if (path_delete(RUN_PATH . '/cache') && path_delete(RUN_PATH . '/complile')) {
            $this->log('清理缓存成功！');
            alert_back('清理缓存成功！');
        } else {
            $this->log('清理缓存失败！');
            alert_back('清理缓存失败！');
        }
    }

    // 文件上传方法
    public function upload()
    {
        $upload = upload('upload');
        echo json_encode($upload);
    }
}