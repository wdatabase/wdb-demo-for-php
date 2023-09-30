<?php
namespace app\controllers;

use Yii;
use app\models\SearchReq;
use app\models\SearchInfo;
use app\models\ListSearchReq;
use app\models\ListSearchInfo;
use app\models\ListSearchRsp;
use app\models\SearchInfoReq;
use app\models\SearchInfoRsp;



class SearchController extends CommController
{
    public function actionPost()
    {
        $req = new SearchReq();
        $req->bind_json();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $wdb = $this->Wdb();
        $tm = time();
        $index_keys = ["my_search_index_".$uid];
        $index_raw = [
            "title:str:=".$req->title,
            "score:num:=".$req->score,
            "updateTime:num:=".$tm
        ];

        if($req->uuid == ''){
            $cuuid = $this->uuid();

            $info = new SearchInfo();
            $info->uuid = $cuuid;
            $info->title = $req->title;
            $info->score = $req->score;
            $info->content = $req->content;
            $info->createTime = $tm;
            $info->updateTime = $tm;

            $createRsp = $wdb->CreateObj($cuuid, json_encode($info), []);
            if($createRsp->code == 200){
                $idxRsp = $wdb->CreateIndex($index_keys, $cuuid, $index_raw);
                if ($idxRsp->code != 200){
                    return $this->rsp_err($idxRsp->msg);
                }
                return $this->rsp_ok($cuuid);
            } else {
                return $this->rsp_err($createRsp->msg);
            }
        } else {
            $info = new SearchInfo();
            $getRsp = $wdb->GetObj($req->uuid);
            if($getRsp->code != 200){
                return $this->rsp_err($getRsp->msg);
            }
            $info->load_json($getRsp->data);

            $info->title = $req->title;
            $info->score = $req->score;
            $info->content = $req->content;
            $info->updateTime = $tm;

            $updateRsp = $wdb->UpdateObj($info->uuid, json_encode($info));
            if($updateRsp->code == 200){
                $idxRsp = $wdb->UpdateIndex($index_keys, $index_keys, $info->uuid, $index_raw);
                if ($idxRsp->code != 200){
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
        $req = new SearchInfoReq();
        $req->bind_get();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $info = new SearchInfo();
        $getRsp = $this->Wdb()->GetObj($req->uuid);
        if($getRsp->code != 200){
            return $this->rsp_err($getRsp->msg);
        }
        $info->load_json($getRsp->data);

        $rsp = new SearchInfoRsp();
        $rsp->info = $info;
        return $rsp->rsp_json();
    }

    public function actionList()
    {
        $req = new ListSearchReq();
        $req->bind_json();

        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $arr = [];
        if ($req->title != ''){
            array_push($arr, sprintf("[\"title\",\"reg\",\"^.*%s.*$\"]", $req->title));
        }
        if ($req->score != ''){
            array_push($arr, sprintf("[\"score\",\">=\",%s]", $req->score));
        }
        if ($req->begin != '' && $req->end != ''){
            $st = strtotime("%Y-%m-%d %H:%M", $req->begin);
            $se = strtotime("%Y-%m-%d %H:%M", $req->end);
            array_push($arr, sprintf("[\"updateTime\",\">=\",%d,\"<=\",%d]", $st, $se));
        }

        $condition = "";
        if (count($arr) == 1){
            $condition = $arr[0];
        }
        if (count($arr) == 2){
            $condition = sprintf("{\"and\":[%s]}", ",".join($arr));             
        }
        $order = "updateTime DESC";
        if ($req->order == "tasc"){
            $order = "updateTime ASC";
        }else if ($req->order == "tdesc"){
            $order = "updateTime DESC";
        }else if ($req->order == "sasc"){
            $order = "score ASC";
        }else if ($req->order == "sdesc"){
            $order = "score DESC";
        }

        $indexkey = "my_search_index_".$uid;
        $glist = $this->Wdb()->ListIndex($indexkey, $condition, $req->offset, $req->limit, $order);
        $total = $glist->total;
        $list = $glist->list;
        $arr = [];
        foreach($list as $item){
            $info = new SearchInfo();
            $info->load_json($item);

            $list_info = new ListSearchInfo();
            $list_info->uuid = $info->uuid;
            $list_info->title = $info->title;
            $list_info->score = $info->score;
            $list_info->time = $info->createTime;

            array_push($arr,$list_info);
        }
        $rsp = new ListSearchRsp();
        $rsp->total = $total;
        $rsp->list = $arr;
        return $rsp->rsp_json();
    }

    public function actionDel()
    {
        $req = new SearchInfoReq();
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
