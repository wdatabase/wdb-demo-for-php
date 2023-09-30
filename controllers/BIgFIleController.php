<?php
namespace app\controllers;

use app\models\BigReq;


class BigFileController extends CommController
{
    public function actionUpload()
    {
        $req = new BigReq();
        $req->bind_json();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $uploadRsp = $this->Wdb()->UploadByPath($req->path, $req->key, []);
        if(isset($uploadRsp->code) && $uploadRsp->code == 200){
            return $this->rsp_ok("");
        } else {
            return $this->rsp_err($uploadRsp->msg);
        }
    }

    public function actionDown()
    {
        $req = new BigReq();
        $req->bind_json();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $downRsp = $this->Wdb()->DownToPath($req->path, $req->key);
        if(isset($uploadRsp->code) && $downRsp->code == 200){
            return $this->rsp_ok("");
        } else {
            return $this->rsp_err($downRsp->msg);
        }
    }

}
