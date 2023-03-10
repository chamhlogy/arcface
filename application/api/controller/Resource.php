<?php
namespace app\api\controller;
use think\Controller;
use app\api\model\Resource as ResourceModel;

class Resource extends Controller{

    public function add($name,$founder,$safetygrade,$type,$localaddress,$state){
        $resource=new ResourceModel();
        $resource->data([
            "Name"=>$name,
            "Founder"=>$founder,
            "CreationDate"=>date('Y-m-d H:i'),
            "ModifiedDate"=>'',
            "Editor"=>'',
            "ChildID"=>'',
            "SafetyGrade"=>$safetygrade,
            "LocalAddress"=>$localaddress,
            "Type"=>$type,
            "State"=>$state
        ]);
        $resource->save();
        return $resource->ID;
    }

    public function edit($id,$name,$editor,$safetygrade,$type,$localaddress,$state,$note=''){
        $resource=ResourceModel::get(['ID'=>$id]);
        if(($editor)===$resource->Founder){
            $resource->data([
                "Name"=>$name,
                "Editor"=>$editor,
                "ModifiedDate"=>date('Y-m-d H:i'),
                "SafetyGrade"=>$safetygrade,
                "LocalAddress"=>$localaddress,
                "Type"=>$type,
                "State"=>$state
            ]);
            $resource->save();
        }else{
            $uploader=$resource->Founder;
            $resource=new ResourceModel();
            $resource->data([
                "Founder"=>"change",
                "Name"=>$name,
                "Editor"=>$editor,
                "ModifiedDate"=>date('Y-m-d H:i'),
                "SafetyGrade"=>$safetygrade,
                "LocalAddress"=>$localaddress,
                "Type"=>$type,
                "State"=>$state
            ]);
            $resource->save();
            $log=new \app\api\controller\Log();
            $log->addresourcechangelog($editor,$id,$resource->ID,$note,$uploader);
        }
    }

    public function editaccept($oldid,$newid,$logid){
        $old=ResourceModel::get(['ID'=>$oldid]);
        $new=ResourceModel::get(['ID'=>$newid]);
        if($old and $new){
            $old->data([
                "Name"=>$new->Name,
                "Editor"=>$new->Editor,
                "ModifiedDate"=>$new->ModifiedDate,
                "SafetyGrade"=>$new->SafetyGrade,
                "LocalAddress"=>$new->LocalAddress,
                "Type"=>$new->Type,
                "State"=>$new->State
            ]);
            $old->save();
            $new->delete();
            $log=new \app\api\controller\Log();
            $log->setresourcelog($logid);
            return json(['status'=>1]);
        }
    }

    public function editcancel($newid,$logid){
        $new=ResourceModel::get(['ID'=>$newid]);
        if($new){
            $new->delete();
            $log=new \app\api\controller\Log();
            $log->setresourcelog($logid);
            return json(['status'=>1]);
        }
    }

    public function delete($id){
        $resource=ResourceModel::get(['ID'=>$id]);
        $resource->delete();
    }

    public function deletelist($idlist){
        foreach($idlist as $value){
            $this->delete($value);
        }
    }

    public function setchild($id,$childrenlist,$editor){
        $resource=ResourceModel::get(["ID"=>$id]);
        if($resource){
            $resource->ChildID=json($childrenlist);
            $resource->Editor=$editor;
            $resource->ModifiedDate=date('Y-m-d H:i');
        }
    }

    public function getallresourcelist($userid){
        $list=ResourceModel::all(["Founder"=>'c'.$userid]);
        $project=new \app\api\controller\Project();
        $result=$project->getprojectresultidbycontractorid($userid);
        $publiclist=ResourceModel::all(["State"=>"public"]);
        $list=\array_merge($list,$result,$publiclist);
        $friendlist=\think\Db::query("select * from resource where State like '%,c".$userid.",%'");
        $data=[];
        foreach($list as $value){
            if($value){
                if($value->Founder!='change'){
                    $item=[
                        'id'=>$value->ID,
                        'name'=>$value->Name,
                        'uploader'=>$this->getUserName($value->Founder),
                        'uploadtime'=>$value->CreationDate,
                        'editor'=>$this->getUserName($value->Editor),
                        'modifieddate'=>$value->ModifiedDate,
                        'safetygrade'=>$value->SafetyGrade,
                        'type'=>$value->Type
                    ];
                    if(!in_array($item,$data)){
                        array_push($data,$item);
                    }
                }
            }
        }
        foreach($friendlist as $value){
            if($value['Founder']!='change'){
                $item=[
                    'id'=>$value['ID'],
                    'name'=>$value['Name'],
                    'uploader'=>$value['Founder'],
                    'uploadtime'=>$value['CreationDate'],
                    'editor'=>$value['Editor'],
                    'modifieddate'=>$value['ModifiedDate'],
                    'safetygrade'=>$value['SafetyGrade'],
                    'type'=>$value['Type']
                ];
                if(!in_array($item,$data)){
                    array_push($data,$item);
                }
            }
        }
        return $data;
    }
    /**
     * ??????????????????
     */
    public function getresourcelist($safetygrade,$projectid){
        $list=ResourceModel::where('SafetyGrade','<=',$safetygrade)->select();
        $data=[];
        $project=\app\api\model\Project::get(['ID'=>$projectid]);
        $resourcelist1=\json_decode($project->Resources);
        $resourcelist=[];
        foreach($resourcelist1 as $value){
            array_push($resourcelist,$value);
        }
        foreach($list as $value){
            if(in_array($value->ID,$resourcelist)===false){
                $item=[
                    'id'=>$value->ID,
                    'name'=>$value->Name,
                    'uploader'=>$this->getUserName($value->Founder),
                    'uploadtime'=>$value->CreationDate,
                    'editor'=>$this->getUserName($value->Editor),
                    'modifieddate'=>$value->ModifiedDate,
                    'safetygrade'=>$value->SafetyGrade,
                    'type'=>$value->Type
                ];
                array_push($data,$item);
            }
        }
        return $data;
    }
    private function getUserName($id){
        if(strpos($id,'c')!==false){
            $user=new \app\api\controller\Contractors();
            $userid=(explode('c',$id))[1];
            return '????????? '.$user->getusername($userid);
        }else if(strpos($id,'u')!==false){
            $user=new \app\api\controller\Users();
            $userid=(explode('u',$id))[1];
            return '????????? '.$user->getusername($userid);
        }
    }

    public function getresource($id){
        $resource=ResourceModel::get(['ID'=>$id]);
        if($resource){
                $data=[
                    'name'=>$resource->Name,
                    'type'=>$resource->Type,
                    'safetygrade'=>$resource->SafetyGrade,
                    'address'=>$resource->LocalAddress,
                    'state'=>$resource->State,
                    'id'=>$resource->ID,
                    'uploader'=>$resource->Founder,
                    'uploadtime'=>$resource->CreationDate,
                ];
                return $data;
                $data=[
                    'name'=>$resource->Name,
                    'type'=>$resource->Type,
                    'safetygrade'=>$resource->SafetyGrade,
                    'address'=>$resource->LocalAddress,
                    'state'=>$resource->State,
                    'id'=>$resource->ID,
                    'uploader'=>$resource->Founder,
                    'uploadtime'=>$resource->CreationDate,
                ];
                return $data;
            }*/
        }
    }
    public function acceptresult($id){
        $resource=ResourceModel::get(['ID'=>$id]);
        if($resource){
            if($resource->State=="????????????"){
                $resource->State="?????????";
                $resource->save();
            }
        }
    }
    public function returnresult($id){
        $resource=ResourceModel::get(['ID'=>$id]);
        if($resource){
            if($resource->State=="????????????"){
                $resource->State="???????????????";
                $resource->save();
            }
        }
    }
}