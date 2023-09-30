<?php
namespace app\controllers;

use app\models\LoginReq;
use app\models\RegReq;
use app\models\UserInfo;
use app\models\UserRsp;


class ApiController extends CommController
{
    public function actionReg()
    {
        $req = new RegReq();
        $req->bind_json();

        $wdb = $this->Wdb();
        $cuuid = $this->uuid();
        $tm = time();

        $info = new UserInfo();
        $info->uuid = $cuuid;
        $info->user = $req->user;
        $info->pwd = $this->hash($req->user.'_'.$req->pwd);
        $info->mail = $req->mail;
        $info->createTime = $tm;
        $info->updateTime = $tm;

        $key = 'user_'.$req->user;
        $createRsp = $wdb->CreateObj($key, json_encode($info), ['user_list']);

        if($createRsp->code == 200){
            $rsp = new UserRsp();
            $rsp->uid = $cuuid;
            return $rsp->rsp_json();
        } else {
            $rsp = new UserRsp();
            $rsp->code = 400;
            $rsp->msg = $createRsp->msg;
            return $rsp->rsp_json();
        }
    }

    public function actionLogin()
    {
        $req = new LoginReq();
        $req->bind_json();

        $wdb = $this->Wdb();
        $key = 'user_'.$req->user;
        $info = new UserInfo();
        $getRsp = $wdb->GetObj($key);

        if($getRsp->code == 200){
            $info->load_json($getRsp->data);
            
            $cpwd = $this->hash($req->user.'_'.$req->pwd);
            if($cpwd == $info->pwd){
                $tm = time();
                $sg = $this->sign($info->uuid, $tm);
                
                $rsp = new UserRsp();
                $rsp->uid = $info->uuid;
                $rsp->time = $tm;
                $rsp->sign = $sg;
                return $rsp->rsp_json();
            } else {
                $rsp = new UserRsp();
                $rsp->code = 400;
                $rsp->msg = 'pwd fail';
                return $rsp->rsp_json();
            }
        } else {
            $rsp = new UserRsp();
            $rsp->code = 400;
            $rsp->msg = $getRsp->msg;
            return $rsp->rsp_json();
        }
    }
}
