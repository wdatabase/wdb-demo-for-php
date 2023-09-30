<?php
namespace app\controllers;

use app\models\ImgInfo;
use app\models\ImgInfoReq;
use app\models\ImgRaw;
use app\models\ImgReq;
use app\models\ShopProInfoReq;
use app\models\ShopProInfoRsp;
use app\models\ShopProListReq;
use app\models\ShopProListRsp;
use Yii;
use app\models\ShopProReq;
use app\models\ShopProInfo;



class ShopToProController extends CommController
{
    public function actionPost()
    {
        $req = new ShopProReq();
        $req->bind_json();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $wdb = $this->Wdb();
        $tm = time();
        $index_keys = [];
        foreach($req->tps as $ctp) {
            array_push($index_keys, "shop_pro_tp_".$ctp);
        }
        array_push($index_keys, "all_shop_pro_tp_".$uid);
        $index_raw = [
            "title:str:=".$req->title,
            "price:num:=".$req->price,
            "weight:num:=".$req->weight,
            "updateTime:num:=".$tm
        ];

        if($req->uuid == ''){
            $cuuid = $this->uuid();

            $info = new ShopProInfo();
            $info->uuid = $cuuid;
            $info->title = $req->title;
            $info->price = $req->price;
            $info->weight = $req->weight;
            $info->inventory = $req->inventory;
            $info->tps = $req->tps;
            $info->imgid = $req->imgid;
            $info->createTime = $tm;
            $info->updateTime = $tm;

            $createRsp = $wdb->CreateObj($cuuid, json_encode($info), []);
            if(isset($createRsp->code) && $createRsp->code == 200) {
                $idxRsp = $wdb->CreateIndex($index_keys, $cuuid, $index_raw);
                if($idxRsp->code != 200){
                    return $this->rsp_err($idxRsp->msg);
                }
                return $this->rsp_ok($cuuid);
            } else {
                return $this->rsp_err($createRsp->msg);
            }
        } else {
            $info = new ShopProInfo();
            $getRsp = $wdb->GetObj($req->uuid);
            if($getRsp->code != 200){
                return $this->rsp_err($getRsp->msg);
            }
            $info->load_json($getRsp->data);

            $info->title = $req->title;
            $info->price = $req->price;
            $info->weight = $req->weight;
            $info->inventory = $req->inventory;
            $info->tps = $req->tps;
            $info->imgid = $req->imgid;
            $info->updateTime = $tm;

            $updateRsp = $wdb->UpdateObj($info->uuid, json_encode($info));
            if($updateRsp->code == 200){
                $idxRsp = $wdb->UpdateIndex($index_keys, $index_keys, $info->uuid, $index_raw);
                if($idxRsp->code != 200){
                    return $this->rsp_err($idxRsp->msg);
                }
                return $this->rsp_ok($info->uuid);
            } else {
                return $this->rsp_err($updateRsp->msg);
            }
        }
    }

    public function actionInfo()
    {
        $req = new ShopProInfoReq();
        $req->bind_get();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $info = new ShopProInfo();
        $getRsp = $this->Wdb()->GetObj($req->uuid);
        if($getRsp->code != 200){
            return $this->rsp_err($getRsp->msg);
        }
        $info->load_json($getRsp->data);

        $rsp = new ShopProInfoRsp();
        $rsp->info = $info;
        return $rsp->rsp_json();
    }

    public function actionList()
    {
        $req = new ShopProListReq();
        $req->bind_json();

        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $indexkey = $req->indexkey;
        if ($indexkey == 'all'){
            $indexkey = "all_shop_pro_tp_".$uid;
        }else{
            $indexkey = "shop_pro_tp_".$indexkey;
        }
        
        $arr = [];
        if ($req->titlekey != ''){
            array_push($arr, sprintf("[\"title\",\"reg\",\"^.*%s.*$\"]", $req->titlekey));
        }
        if ($req->begin != '' && $req->end == ''){
            array_push($arr, sprintf("[\"updateTime\",\">=\",%s]", $req->begin));
        }
        if ($req->begin == '' && $req->end != ''){
            array_push($arr, sprintf("[\"updateTime\",\"<=\",%s]", $req->end));
        }
        if ($req->begin != '' && $req->end != ''){
            array_push($arr, sprintf("[\"updateTime\",\">=\",%s,\"<=\",%s]", $req->begin, $req->end));
        }

        $condition = "";
        if (count($arr) == 1){
            $condition = $arr[0];
        }
        if (count($arr) > 1){
            $condition = sprintf("{\"and\":[%s]}", ",".join($arr));
        }           
            
        $order = "updateTime DESC";
        if ($req->order == "tasc"){
            $order = "updateTime ASC";
        }elseif($req->order == "tdesc"){
            $order = "updateTime DESC";
        }elseif ($req->order == "pasc"){
            $order = "price ASC";
        }elseif ($req->order == "pdesc"){
            $order = "price DESC";
        }

        $glist = $this->Wdb()->ListIndex($indexkey, $condition, $req->offset, $req->limit, $order);
        if($glist->code != 200){
            return $this->rsp_err($glist->msg);
        }
        $total = $glist->total;
        $list = $glist->list;
        $arr = [];
        foreach($list as $item){
            $info = new ShopProInfo();
            $info->load_json($item);

            array_push($arr, $info);
        }
        $rsp = new ShopProListRsp();
        $rsp->total = $total;
        $rsp->list = $arr;
        return $rsp->rsp_json();
    }

    public function actionDel()
    {
        $req = new ShopProInfoReq();
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

    public function actionImgPost()
    {
        $req = new ImgReq();
        $req->bind_get();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $fileUuid = $this->uuid();
        $name = 'img';
        $fileName = $_FILES['img']['name'];
        $contentType = $_FILES['img']['type'];
        $size = $_FILES['img']['size'];
        $tmp_path = $_FILES['img']['tmp_name'];

        $handle = fopen($tmp_path, "rb");
        $contents = fread($handle, filesize($tmp_path));
        fclose($handle);
        $img_raw = new ImgRaw();
        $img_raw->raw = base64_encode($contents);

        $wdb = $this->Wdb();
        $tm = time();

        $rawRsp = $wdb->CreateObj($fileUuid, json_encode($img_raw), []);
        if($rawRsp->code != 200){
            return $this->rsp_err($rawRsp->msg);
        }

        $cuuid = $this->uuid();

        $info = new ImgInfo();
        $info->uuid = $cuuid;
        $info->name = $name;
        $info->fileName = $fileName;
        $info->contentType = $contentType;
        $info->size = $size;
        $info->fileUuid = $fileUuid;
        $info->createTime = $tm;
        $info->updateTime = $tm;

        $createRsp = $wdb->CreateObj($cuuid, json_encode($info), []);
        if($createRsp->code == 200){
            return $this->rsp_ok($cuuid);
        } else {
            return $this->rsp_err($createRsp->msg);
        }
        
    }

    public function actionImgData()
    {
        $req = new ImgInfoReq();
        $req->bind_get();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $wdb = $this->Wdb();
        $info = new ImgInfo();
        $getRsp = $wdb->GetObj($req->uuid);
        if($getRsp->code != 200){
            return $this->rsp_err($getRsp->msg);
        }
        $info->load_json($getRsp->data);

        $img_raw = new ImgRaw();
        $rawRsp = $wdb->GetObj($info->fileUuid);
        if($rawRsp->code != 200){
            return $this->rsp_err($rawRsp->msg);
        }
        $img_raw->load_json($rawRsp->data);

        $data = base64_decode($img_raw->raw);
        
        $response = Yii::$app->response;
        $response->getHeaders()->set('Content-Type', $info->contentType);
        $response->data = $data;

        return $response;
    }

}
