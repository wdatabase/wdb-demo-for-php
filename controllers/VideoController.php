<?php
namespace app\controllers;

use Yii;
use app\models\VideoReq;
use app\models\VideoInfo;
use app\models\VideoRsp;
use app\models\VideoListReq;
use app\models\VideoListInfo;
use app\models\VideoListRsp;
use app\models\VideoInfoReq;
use app\models\VideoInfoRsp;
use app\wdb\WdbDrive;


class VideoController extends CommController
{
    public function actionPost()
    {
        $req = new VideoReq();
        $req->bind_get();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $fileUuid = $this->uuid();
        $name = 'video';
        $fileName = $_FILES['video']['name'];
        $contentType = $_FILES['video']['type'];
        $size = $_FILES['video']['size'];
        $tmp_path = $_FILES['video']['tmp_name'];

        $handle = fopen($tmp_path, "rb");
        $contents = fread($handle, filesize($tmp_path));
        fclose($handle);

        $wdb = $this->Wdb();
        $tm = time();

        $rawRsp = $wdb->CreateRawData($fileUuid, $contents, []);
        if($rawRsp->code != 200){
            return $this->rsp_err($rawRsp->msg);
        }

        if($req->uuid == ''){
            $cuuid = $this->uuid();

            $info = new VideoInfo();
            $info->uuid = $cuuid;
            $info->name = $name;
            $info->fileName = $fileName;
            $info->contentType = $contentType;
            $info->size = $size;
            $info->fileUuid = $fileUuid;
            $info->createTime = $tm;
            $info->updateTime = $tm;

            $createRsp = $wdb->CreateObj($cuuid, json_encode($info), ['my_video_'.$uid]);
            if($createRsp->code == 200){
                return $this->rsp_ok($cuuid);
            } else {
                return $this->rsp_err($createRsp->msg);
            }
        } else {
            $info = new VideoInfo();
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
            if(isset($updateRsp->code) && $updateRsp->code == 200){
                return $this->rsp_ok($info->uuid);
            } else {
                return $this->rsp_err($updateRsp->msg);
            }
        }
    }

    public function actionInfo()
    {
        $req = new VideoInfoReq();
        $req->bind_get();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $info = new VideoInfo();
        $getRsp = $this->Wdb()->GetObj($req->uuid);
        if($getRsp->code != 200){
            return $this->rsp_err($getRsp->msg);
        }
        $info->load_json($getRsp->data);

        $rsp = new VideoInfoRsp();
        $rsp->info = $info;
        return $rsp->rsp_json();
    }

    public function actionData()
    {
        $req = new VideoInfoReq();
        $req->bind_get();
        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $wdb = $this->Wdb();
        $info = new VideoInfo();
        $getRsp = $wdb->GetObj($req->uuid);
        if($getRsp->code != 200){
            return $this->rsp_err($getRsp->msg);
        }
        $info->load_json($getRsp->data);

        $data = '';
        $code = 200;
        $response = Yii::$app->response;
        $headers = Yii::$app->request->headers;

        if($headers->has('Range')){
            $code = 206;
            $range = substr($headers->get('Range'), 6);
            $arr = explode('-', $range);
            $start = (int)(isset($arr[0])?$arr[0]:0);
            $end = (int)(isset($arr[1])?$arr[1]:'');
            if($end == 0){
                $end = $start + 1024 * 1024;
            }
            
            $rangeRsp = $wdb->GetRangeData($info->fileUuid, $start, $end - $start);
            if($rangeRsp->code != 200){
                return $this->rsp_err($rangeRsp->msg);
            }

            $size = $rangeRsp->all_size;
            if($end > $size){
                $end = $size - 1;
            }
            $data = $rangeRsp->raw;
            $response->getHeaders()->set('Content-Range', 'bytes '.$start.'-'.$end.'/'.$size);
        } else {
            $rawRsp = $wdb->GetRawData($info->fileUuid);
            if($rawRsp->code != 200){
                return $this->rsp_err($rawRsp->msg);
            }

            $response->getHeaders()->set('Accept-Range', 'bytes');
            $data = $rawRsp->raw;
        }

        $response->getHeaders()->set('Last-Modified', date('Y-m-d\TH:i:s.z',$info->updateTime).'Z');
        $response->getHeaders()->set('Etag', $info->uuid);

        $this->layout = false;
        $response->getHeaders()->set('Content-Type', $info->contentType);
        $response->statusCode = $code;
        $response->data = $data;

        return $response;
    }

    public function actionList()
    {
        $req = new VideoListReq();
        $req->bind_get();

        list($is_login, $uid) = $this->auth($req->o);
        if(!$is_login){
            return $this->rsp_nologin();
        }

        $category = 'my_video_'.$uid;
        $glist = $this->Wdb()->ListObj($category, $req->offset, $req->limit, $req->order);
        if($glist->code != 200){
            return $this->rsp_err($glist->msg);
        }
        $total = $glist->total;
        $list = $glist->list;
        $arr = [];
        foreach($list as $item){
            $info = new VideoInfo();
            $info->load_json($item);

            $list_info = new VideoListInfo();
            $list_info->uuid = $info->uuid;
            $list_info->name = $info->name;
            $list_info->fileName = $info->fileName;
            $list_info->contentType = $info->contentType;
            $list_info->size = $info->size;
            $list_info->fileUuid = $info->fileUuid;
            $list_info->time = $info->createTime;

            array_push($arr,$list_info);
        }
        $rsp = new VideoListRsp();
        $rsp->total = $total;
        $rsp->list = $arr;
        return $rsp->rsp_json();
    }

    public function actionDel()
    {
        $req = new VideoInfoReq();
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
