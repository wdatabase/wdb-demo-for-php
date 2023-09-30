<?php
namespace app\controllers;

use app\models\ShopBalanceReq;
use app\models\ShopInfoReq;
use app\models\ShopInfoRsp;
use app\models\ShopInfo;
use app\models\ShopBalanceLog;


class ShopToInfoController extends CommController
{
    public function actionBalance()
    {
        $req = new ShopBalanceReq();
        $req->bind_json();

        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $wdb = $this->Wdb();
        $key = "shop_info_".$uid;
        $tm = time();

        $tsBeginRsp = $wdb->TransBegin([$key]);
        $tsid = '';
        if ($tsBeginRsp->code == 200){
            $tsid = $tsBeginRsp->data;
        } else {
            return $this->rsp_err($tsBeginRsp->msg);
        }

        $logid = $this->uuid();
        $balancelog = new ShopBalanceLog();
        $balancelog->uuid = $logid;
        $balancelog->uid = $uid;
        $balancelog->balance = $req->balance;
        $balancelog->op = "in";
        $balancelog->createTime = $tm;
        $balancelog->updateTime = $tm;
        
        $logRsp = $wdb->TransCreateObj($tsid, $logid, json_encode($balancelog), ["shop_balance_log_".$uid]);
        if ($logRsp->code != 200){
            $wdb->TransRollBack($tsid);
            return $this->rsp_err($logRsp->msg);
        }
        
        $shopInfoRsp = $wdb->TransGet($tsid, $key);
        if ($shopInfoRsp->code != 200){
            $wdb->TransRollBack($tsid);
            return $this->rsp_err($shopInfoRsp->msg);
        }
        $shopInfo = new ShopInfo();
        $shopInfo->load_json($shopInfoRsp->data);
        
        $shopInfo->balance = $shopInfo->balance + $req->balance;
        $shopInfo->updateTime = $tm;
        $updateInfoRsp = $wdb->TransUpdateObj($tsid, $key, json_encode($shopInfo));
        if ($updateInfoRsp->code != 200){
            $wdb->TransRollBack($tsid);
            return $this->rsp_err($updateInfoRsp->msg);
        }

        $commitRsp = $wdb->TransCommit($tsid);
        if ($commitRsp->code != 200){
            $wdb->TransRollBack($tsid);
            return $this->rsp_err($commitRsp->msg);
        }
        
        return $this->rsp_ok($shopInfo->uuid);
    }

    public function actionInfo()
    {
        $req = new ShopInfoReq();
        $req->bind_get();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $info = new ShopInfo();
        $key = "shop_info_".$uid;
        $wdb = $this->Wdb();

        $getRsp = $wdb->GetObj($key);
        if($getRsp->code != 200){
            if ($getRsp->msg == 'not found key'){
                $tm = time();
                $cuuid = $this->uuid();

                $shopInfo = new ShopInfo();
                $shopInfo->uuid = $cuuid;
                $shopInfo->uid = $uid;
                $shopInfo->balance = 0.0;
                $shopInfo->point = 0.0;
                $shopInfo->createTime = $tm;
                $shopInfo->updateTime = $tm;

                $createRsp = $wdb->CreateObj($key, json_encode($shopInfo), []);
                if(isset($createRsp->code) && $createRsp->code == 200){
                    $rsp = new ShopInfoRsp();
                    $rsp->info = $shopInfo;
                    return $rsp->rsp_json();
                } else {
                    return $this->rsp_err($createRsp->msg);
                }
            } else {
                return $this->rsp_err($getRsp->msg);
            }
        }
        $info->load_json($getRsp->data);

        $rsp = new ShopInfoRsp();
        $rsp->info = $info;
        return $rsp->rsp_json();
    }
    
}
