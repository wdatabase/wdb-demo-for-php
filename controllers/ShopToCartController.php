<?php
namespace app\controllers;

use app\models\ShopCartDelReq;
use app\models\ShopCartReq;
use app\models\ShopCartInfo;
use app\models\ShopCartListInfo;
use app\models\ShopCartListReq;
use app\models\ShopCartListRsp;
use app\models\ShopProInfo;



class ShopToCartController extends CommController
{
    public function actionAdd()
    {
        $req = new ShopCartReq();
        $req->bind_json();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $wdb = $this->Wdb();
        $tm = time();
        $key = 'shop_cart_'.$uid;

        $getRsp = $wdb->GetObj($key);
        if ($getRsp->code != 200) {
            if($getRsp->msg == 'not found key'){
                $cuuid = $this->uuid();

                $cartInfo = new ShopCartInfo();
                $cartInfo->uuid = $cuuid;
                $cartInfo->uid = $uid;
                $cartInfo->ids = [$req->uuid];
                $cartInfo->nums = [$req->num];
                $cartInfo->createTime = $tm;
                $cartInfo->updateTime = $tm;

                $createRsp = $wdb->CreateObj($key, json_encode($cartInfo), []);
                if($createRsp->code != 200){
                    return $this->rsp_err($createRsp->msg);
                }
                return $this->rsp_ok($cuuid);
            } else {
                return $this->rsp_err($getRsp->msg);
            }
        }

        $cartInfo = new ShopCartInfo();
        $cartInfo->load_json($getRsp->data);

        if(in_array($req->uuid, $cartInfo->ids)) {
            $idx = array_search($req->uuid, $cartInfo->ids);
            $cartInfo->nums[$idx] = $req->num;
        } else {
            array_push($cartInfo->ids, $req->uuid);
            array_push($cartInfo->nums, $req->num);
        }

        $updateRsp = $wdb->UpdateObj($key, json_encode($cartInfo));
        if($updateRsp->code == 200){
            return $this->rsp_ok($cartInfo->uuid);
        } else {
            return $this->rsp_err($updateRsp->msg);
        }

    }

    public function actionList()
    {
        $req = new ShopCartListReq();
        $req->bind_get();

        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $wdb = $this->Wdb();
        $key = 'shop_cart_'.$uid;
        $getRsp = $wdb->GetObj($key);
        if($getRsp->code != 200) {
            return $this->rsp_err($getRsp->msg);
        }

        $cartInfo = new ShopCartInfo();
        $cartInfo->load_json($getRsp->data);

        $plist = [];
        $cidx = 0;
        $total = 0.0;
        foreach($cartInfo->ids as $pid) {
            $proRsp = $wdb->GetObj($pid);
            if($proRsp->code != 200){
                return $this->rsp_err($proRsp->msg);
            }
            $proInfo = new ShopProInfo();
            $proInfo->load_json($proRsp->data);

            $cnum = $cartInfo->nums[$cidx];
            $cidx += 1;
            $total += $proInfo->price * $cnum;

            $listInfo = new ShopCartListInfo();
            $listInfo->proid = $proInfo->uuid;
            $listInfo->title = $proInfo->title;
            $listInfo->price = $proInfo->price;
            $listInfo->inventory = $proInfo->inventory;
            $listInfo->imgid = $proInfo->imgid;
            $listInfo->num = $cnum;

            array_push($plist, $listInfo);
        }

        $rsp = new ShopCartListRsp();
        $rsp->total = $total;
        $rsp->listinfo = $plist;
        return $rsp->rsp_json();

    }

    public function actionDel()
    {
        $req = new ShopCartDelReq();
        $req->bind_get();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $wdb = $this->Wdb();
        $key = 'shop_cart_'.$uid;
        $getRsp = $wdb->GetObj($key);
        if($getRsp->code != 200) {
            return $this->rsp_err($getRsp->msg);
        }

        $cartInfo = new ShopCartInfo();
        $cartInfo->load_json($getRsp->data);

        if($req->uuid == 'all'){
            $cartInfo->ids = [];
            $cartInfo->nums = [];
        } else {
            $idx = array_search($req->uuid, $cartInfo->ids);
            unset($cartInfo->ids[$idx]);
            unset($cartInfo->nums[$idx]);
        }

        $updateRsp = $wdb->UpdateObj($key, json_encode($cartInfo));
        if($updateRsp->code == 200){
            return $this->rsp_ok($cartInfo->uuid);
        } else {
            return $this->rsp_err($updateRsp->msg);
        }

    }

}
