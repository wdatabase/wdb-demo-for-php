<?php
namespace app\controllers;

use Yii;
use app\models\ImgReq;
use app\models\ImgInfo;
use app\models\ImgListReq;
use app\models\ImgListInfo;
use app\models\ImgListRsp;
use app\models\ImgInfoReq;
use app\models\ImgInfoRsp;
use app\models\ImgRaw;


class ImgController extends CommController
{
    public function actionPost()
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

        if($req->uuid == ''){
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

            $createRsp = $wdb->CreateObj($cuuid, json_encode($info), ['my_img_'.$uid]);
            if($createRsp->code == 200){
                return $this->rsp_ok($cuuid);
            } else {
                return $this->rsp_err($createRsp->msg);
            }
        } else {
            $info = new ImgInfo();
            $getRsp = $wdb->GetObj($req->uuid);
            if($getRsp->code != 200){
                return $this->rsp_err($getRsp->msg);
            }
            $info->load_json($getRsp->data);

            $info->name = $name;
            $info->fileName = $fileName;
            $info->contentType = $contentType;
            $info->size = $size;
            $info->fileUuid = $fileUuid;
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
        $req = new ImgInfoReq();
        $req->bind_get();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $info = new ImgInfo();
        $getRsp = $this->Wdb()->GetObj($req->uuid);
        if($getRsp->code != 200){
            return $this->rsp_err($getRsp->msg);
        }
        $info->load_json($getRsp->data);

        $rsp = new ImgInfoRsp();
        $rsp->info = $info;
        return $rsp->rsp_json();
    }

    public function actionData()
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

    public function actionList()
    {
        $req = new ImgListReq();
        $req->bind_get();
        

        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $category = 'my_img_'.$uid;
        $glist = $this->Wdb()->ListObj($category, $req->offset, $req->limit, $req->order);
        if($glist->code != 200){
            return $this->rsp_err($glist->msg);
        }
        $total = $glist->total;
        $list = $glist->list;
        $arr = [];
        foreach($list as $item){
            $info = new ImgInfo();
            $info->load_json($item);

            $list_info = new ImgListInfo();
            $list_info->uuid = $info->uuid;
            $list_info->name = $info->name;
            $list_info->fileName = $info->fileName;
            $list_info->contentType = $info->contentType;
            $list_info->size = $info->size;
            $list_info->fileUuid = $info->fileUuid;
            $list_info->time = $info->createTime;

            array_push($arr,$list_info);
        }
        $rsp = new ImgListRsp();
        $rsp->total = $total;
        $rsp->list = $arr;
        return $rsp->rsp_json();
    }

    public function actionDel()
    {
        $req = new ImgInfoReq();
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
