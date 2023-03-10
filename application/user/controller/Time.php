<?php
namespace app\user\controller;
use think\Controller;
//use think\db;
/**
 * 时间表
 */
class Time extends Controller
{
    public function index(){
        $user=new \app\api\controller\Users();
        $userid=$user->checkuser($_COOKIE["userid"]);
        if($userid){
            $username=$user->getusername($userid);
            $this->assign('username',$username);
            $this->assign('userid',$userid);
            return $this->fetch('time');
        }
        return $this->error('404 未知接包人员');
    }
}