<?php
namespace app\controllers;

use app\models\ShopCategorizeInfo;
use app\models\ShopCategorizeListReq;
use app\models\ShopCategorizeListInfo;
use app\models\ShopCategorizeListRsp;
use app\models\ShopCategorizeReq;
use app\models\ShopCategorizeInfoRsp;



class ShopToCategorizeController extends CommController
{
    public function actionPost()
    {
        $req = new ShopCategorizeReq();
        $req->bind_json();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $wdb = $this->Wdb();
        $tm = time();

        if($req->uuid == ''){
            $cuuid = $this->uuid();

            $info = new ShopCategorizeInfo();
            $info->uuid = $cuuid;
            $info->name = $req->name;
            $info->sort = $req->sort;
            $info->createTime = $tm;
            $info->updateTime = $tm;

            $createRsp = $wdb->CreateObj($cuuid, json_encode($info), ['shop_categorize_'.$uid]);
            if(isset($createRsp->code) && $createRsp->code == 200){
                return $this->rsp_ok($cuuid);
            } else {
                return $this->rsp_err($createRsp->msg);
            }
        } else {
            $info = new ShopCategorizeInfo();
            $getRsp = $wdb->GetObj($req->uuid);
            if($getRsp->code != 200){
                return $this->rsp_err($getRsp->msg);
            }
            $info->load_json($getRsp->data);

            $info->name = $req->name;
            $info->sort = $req->sort;
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
        $req = new ShopCategorizeReq();
        $req->bind_get();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $info = new ShopCategorizeInfo();
        $getRsp = $this->Wdb()->GetObj($req->uuid);
        if($getRsp->code != 200){
            return $this->rsp_err($getRsp->msg);
        }
        $info->load_json($getRsp->data);

        $rsp = new ShopCategorizeInfoRsp();
        $rsp->info = $info;
        return $rsp->rsp_json();
    }

    public function actionList()
    {
        $req = new ShopCategorizeListReq();
        $req->bind_json();

        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $category = 'shop_categorize_'.$uid;
        $glist = $this->Wdb()->ListObj($category, $req->offset, $req->limit, $req->order);
        if($glist->code != 200){
            return $this->rsp_err($glist->msg);
        }
        $total = $glist->total;
        $list = $glist->list;
        $arr = [];
        foreach($list as $item){
            $info = new ShopCategorizeInfo();
            $info->load_json($item);

            $list_info = new ShopCategorizeListInfo();
            $list_info->uuid = $info->uuid;
            $list_info->name = $info->name;
            $list_info->sort = $info->sort;

            array_push($arr,$list_info);
        }
        $rsp = new ShopCategorizeListRsp();
        $rsp->total = $total;
        $rsp->list = $arr;
        return $rsp->rsp_json();
    }

    public function actionDel()
    {
        $req = new ShopCategorizeReq();
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
