<?php
namespace app\controllers;

use app\models\ShopInfo;
use app\models\ShopOrderInfoReq;
use app\models\ShopOrderItemRsp;
use app\models\ShopOrderListReq;
use app\models\ShopOrderListRsp;
use app\models\ShopProInfo;
use app\models\ShopOrderInfo;
use app\models\ShopOrderItem;
use app\models\ShopOrderReq;



class ShopToOrderController extends CommController
{
    public function actionCreate()
    {
        $req = new ShopOrderReq();
        $req->bind_json();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $wdb = $this->Wdb();
        $tm = time();
        $req_ids = $req->ids;
        $req_nums = $req->nums;
        $req_prices = $req->prices;

        $lock_ids = $req_ids;
        $shop_info_key = "shop_info_".$uid;
        array_push($lock_ids, $shop_info_key);

        #开始事务
        $tsBeginRsp = $wdb->TransBegin($lock_ids);
        $tsid = '';
        if ($tsBeginRsp->code == 200) {
            $tsid = $tsBeginRsp->data;
        } else {
            return $this->rsp_err($tsBeginRsp->msg);
        }
        
        #校验余额
        $shopInfoRsp = $wdb->TransGet($tsid, $shop_info_key);
        if ($shopInfoRsp->code != 200) {
            $wdb->TransRollBack($tsid);
            return $this->rsp_err($shopInfoRsp->msg);
        }
        $shopInfo = new ShopInfo();
        $shopInfo->load_json($shopInfoRsp->data);
        
        $dif = $this->floatcmp($shopInfo->balance, $req->total);
        if ($dif == -1) {
            $wdb->TransRollBack($tsid);
            return $this->rsp_err("余额不足");
        }
        
        $orderid = $this->uuid();
        $imgid = "";
        $title_list = [];
        $category_item = "shop_order_item_".$orderid;
        
        #遍历购物车相关商品
        for($idx = 0; $idx < count($req_ids); $idx++) {
            $ckey = $req_ids[$idx];
            $cnum = $req_nums[$idx];
            $cprice = $req_prices[$idx];
            
            $proRsp = $wdb->TransGet($tsid, $ckey);
            if ($proRsp->code != 200) {
                $wdb->TransRollBack($tsid);
                return $this->rsp_err($proRsp->msg);
            }
            $proInfo = new ShopProInfo();
            $proInfo->load_json($proRsp->data);
            
            #校验价格
            $difp = $this->floatcmp($proInfo->price, $cprice);
            if ($difp != 0) {
                $wdb->TransRollBack($tsid);
                return $this->rsp_err('商品价格变动，请重新确认。');
            }
            
            #校验库存
            if ($cnum > $proInfo->inventory) {
                $wdb->TransRollBack($tsid);
                return $this->rsp_err('库存不足。');
            }
            
            array_push($title_list, $proInfo->title);
            $imgid = $proInfo->imgid;
            
            #保存订单产品详情
            $itemid = $this->uuid();
            $orderItem = new ShopOrderItem();
            $orderItem->uuid = $itemid;
            $orderItem->title = $proInfo->title;
            $orderItem->imgid = $proInfo->imgid;
            $orderItem->num = $cnum;
            $orderItem->price = $cprice;
            $orderItem->updateTime = $tm;
            $orderItem->createTime = $tm;
            
            $itemRsp = $wdb->TransCreateObj($tsid, $itemid, json_encode($orderItem), [$category_item]);
            if ($itemRsp->code != 200) {
                $wdb->TransRollBack($tsid);
                return $this->rsp_err($itemRsp->msg);
            }
            
            #减库存
            $proInfo->inventory = $proInfo->inventory - $cnum;
            $updateProInfo = $wdb->TransUpdateObj($tsid, $proInfo->uuid, json_encode($proInfo));
            if ($updateProInfo->code != 200) {
                $wdb->TransRollBack($tsid);
                return $this->rsp_err($updateProInfo->msg);
            }
        }

        $titles = implode("/", $title_list);
        
        #保存订单信息
        $orderInfo = new ShopOrderInfo();
        $orderInfo->uuid = $orderid;
        $orderInfo->title = $titles;
        $orderInfo->imgid = $imgid;
        $orderInfo->total = $req->total;
        $orderInfo->ids = $req_ids;
        $orderInfo->nums = $req_nums;
        $orderInfo->prices = $req_prices;
        $orderInfo->createTime = $tm;
        $orderInfo->updateTime = $tm;
        
        $orderRsp = $wdb->TransCreateObj($tsid, $orderid, json_encode($orderInfo), []);
        if ($orderRsp->code != 200) {
            $wdb->TransRollBack($tsid);
            return $this->rsp_err($orderRsp->msg);
        }
        
        #更新余额积分
        $shopInfo->balance = $shopInfo->balance - $orderInfo->total;
        $shopInfo->point = $shopInfo->point + $orderInfo->total;
        $updateInfoRsp = $wdb->TransUpdateObj($tsid, $shop_info_key, json_encode($shopInfo));
        if ($updateInfoRsp->code != 200) {
            $wdb->TransRollBack($tsid);
            return $this->rsp_err($updateInfoRsp->msg);
        }
        
        #创建索引
        $index_keys = ["shop_order_index_".$uid];
        $index_raw = [
            "title:str:=".$orderInfo->title,
            "total:num:=".$orderInfo->total,
            "updateTime:num:=".$orderInfo->updateTime
        ];
        $idxRsp = $wdb->CreateIndex($index_keys, $orderid, $index_raw);
        if ($idxRsp->code != 200){
            $wdb->TransRollBack($tsid);
            return $this->rsp_err($idxRsp->msg);
        }
        
        #提交事务
        $commitRsp = $wdb->TransCommit($tsid);
        if ($commitRsp->code != 200){
            $wdb->TransRollBack($tsid);
            return $this->rsp_err($commitRsp->msg);
        }
        
        return $this->rsp_ok($orderid);
    }

    public function actionInfo()
    {
        $req = new ShopOrderInfoReq();
        $req->bind_get();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $category = "shop_order_item_".$req->uuid;
        $listRsp = $this->Wdb()->ListObj($category, 0, 100, 'ASC');
        if ($listRsp->code != 200) {
            return $this->rsp_err($listRsp->msg);
        }
        
        $itemList = [];
        foreach($listRsp->list as $item) {
            $orderItem = new ShopOrderItem();
            $orderItem->load_json($item);
            array_push($itemList, $orderItem);
        }
        
        $rsp = new ShopOrderItemRsp();
        $rsp->list = $itemList;
        return $rsp->rsp_json();
    }

    public function actionList()
    {
        $req = new ShopOrderListReq();
        $req->bind_json();

        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $arr = [];
        if ($req->titlekey != '') {
            array_push($arr, sprintf("[\"title\",\"reg\",\"^.*%s.*$\"]", $req->titlekey));
        }

        $condition = "";
        if (count($arr) == 1){
            $condition = $arr[0];
        }
        if (count($arr) > 1) {
            $condition = sprintf("{\"and\":[%s]}", implode(",", $arr));
        }             
            
        $order = "updateTime DESC";
        if ($req->order == "tasc") {
            $order = "updateTime ASC";
        }elseif($req->order == "tdesc") {
            $order = "updateTime DESC";
        }elseif($req->order == "sasc") {
            $order = "total ASC";
        }elseif($req->order == "sdesc") {
            $order = "total DESC";
        }

        $indexkey = "shop_order_index_".$uid;
        $listRsp = $this->Wdb()->ListIndex($indexkey, $condition, $req->offset, $req->limit, $order);
        if ($listRsp->code != 200) {
            return $this->rsp_err($listRsp->msg);
        }
        
        $total = $listRsp->total;
        $infos = [];
        foreach($listRsp->list as $item) {
            $info = new ShopOrderInfo();
            $info->load_json($item);
            
            array_push($infos, $info);
        }

        $rsp = new ShopOrderListRsp();
        $rsp->total = $total;
        $rsp->list = $infos;
        return $rsp->rsp_json();
    }

}
