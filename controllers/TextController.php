<?php
namespace app\controllers;

use app\models\TextReq;
use app\models\TextInfo;
use app\models\ListTextReq;
use app\models\ListTextInfo;
use app\models\ListTextRsp;
use app\models\TextInfoReq;
use app\models\TextInfoRsp;



class TextController extends CommController
{
    public function actionPost()
    {
        $req = new TextReq();
        $req->bind_json();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $wdb = $this->Wdb();
        $tm = time();

        if($req->uuid == ''){
            $cuuid = $this->uuid();

            $info = new TextInfo();
            $info->uuid = $cuuid;
            $info->title = $req->title;
            $info->content = $req->content;
            $info->createTime = $tm;
            $info->updateTime = $tm;

            $createRsp = $wdb->CreateObj($cuuid, json_encode($info), ['my_text_'.$uid]);
            if(isset($createRsp->code) && $createRsp->code == 200){
                return $this->rsp_ok($cuuid);
            } else {
                return $this->rsp_err($createRsp->msg);
            }
        } else {
            $info = new TextInfo();
            $getRsp = $wdb->GetObj($req->uuid);
            if($getRsp->code != 200){
                return $this->rsp_err($getRsp->msg);
            }
            $info->load_json($getRsp->data);

            $info->title = $req->title;
            $info->content = $req->content;
            $info->updateTime = $tm;

            $updateRsp = $wdb->UpdateObj($info->uuid, json_encode($info));
            if($updateRsp->code == 200){
                return $this->rsp_ok($info->uuid);
            } else {
                return $this->rsp_err($updateRsp->msg);
            }
        }
    }

    public function actionInfo()
    {
        $req = new TextInfoReq();
        $req->bind_get();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $info = new TextInfo();
        $getRsp = $this->Wdb()->GetObj($req->uuid);
        if($getRsp->code != 200){
            return $this->rsp_err($getRsp->msg);
        }
        $info->load_json($getRsp->data);

        $rsp = new TextInfoRsp();
        $rsp->info = $info;
        return $rsp->rsp_json();
    }

    public function actionList()
    {
        $req = new ListTextReq();
        $req->bind_get();

        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $category = 'my_text_'.$uid;
        $glist = $this->Wdb()->ListObj($category, $req->offset, $req->limit, $req->order);
        if($glist->code != 200){
            return $this->rsp_err($glist->msg);
        }
        $total = $glist->total;
        $list = $glist->list;
        $arr = [];
        foreach($list as $item){
            $info = new TextInfo();
            $info->load_json($item);

            $list_info = new ListTextInfo();
            $list_info->uuid = $info->uuid;
            $list_info->title = $info->title;
            $list_info->time = $info->createTime;

            array_push($arr,$list_info);
        }
        $rsp = new ListTextRsp();
        $rsp->total = $total;
        $rsp->list = $arr;
        return $rsp->rsp_json();
    }

    public function actionDel()
    {
        $req = new TextInfoReq();
        $req->bind_get();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $delRsp = $this->Wdb()->DelObj($req->uuid);
        if($delRsp->code != 200){
            return $this->rsp_err($delRsp->msg);
        }

        return $this->rsp_ok($req->uuid);
    }

}
