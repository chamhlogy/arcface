<?php 
    namespace app\api\controller;
    use think\Controller;
    use app\api\model\Users;
    use app\api\model\Administractors;
    use app\api\model\Contractors;

    class Server extends Controller{

        public function getstatus(){
            $user=Users::where('OnlineStatus','>',0)->count();
            $administractor=Administractors::where('OnlineStatus','>',0)->count();
            $contractor=Contractors::where('OnlineStatus','>',0)->count();
            $data=['user'=>$user,'administractor'=>$administractor,'contractor'=>$contractor];
            return json($data); 
        } 
    }    
?>