<?php

namespace app\controllers;

use Yii;
use app\models\InfoRsp;
use yii\web\Controller;
use Wdb\Drive\WdbDrive;


class CommController extends Controller
{
    public $enableCsrfValidation = false;

    public function init()
    {
        $response = Yii::$app->getResponse();
        $response->getHeaders()->set('Access-Control-Allow-Origin', '*');
        $response->getHeaders()->set('Access-Control-Allow-Methods', 'POST,GET,DELETE,OPTIONS');
        $response->getHeaders()->set('Access-Control-Allow-Headers', 'x-requested-with,content-type');

        if (Yii::$app->request->getMethod() == 'OPTIONS') {
            $response->data = 'options';
            $response->send();
            Yii::$app->end();
        }
    }

    public function uuid(){
        $chars = md5(uniqid(mt_rand(), true));  
        $uuid = substr ( $chars, 0, 8 ) . '-'
                . substr ( $chars, 8, 4 ) . '-' 
                . substr ( $chars, 12, 4 ) . '-'
                . substr ( $chars, 16, 4 ) . '-'
                . substr ( $chars, 20, 12 );  
        return $uuid ;  
    }

    public function sign($uid, $tm){
        $raw = 'wdb_dfgrDR43d_'.$uid.'_'.$tm;
        return $this->hash($raw);
    }

    public function hash($text){
        return hash('sha256', $text);
    }

    public function Wdb() {
        $wdb_drive = new WdbDrive();
        $wdb_drive->set_api('http://127.0.0.1:8000', 'key');
        return $wdb_drive;
    }

    public function rsp_err($msg){
        $rsp = new InfoRsp();
        $rsp->code = 500;
        $rsp->msg = $msg;
        return $rsp->rsp_json();
    }

    public function rsp_nologin(){
        $rsp = new InfoRsp();
        $rsp->code = 403;
        $rsp->msg = 'nologin';
        return $rsp->rsp_json();
    }

    public function rsp_ok($data){
        $rsp = new InfoRsp();
        $rsp->uuid = $data;
        return $rsp->rsp_json();
    }

    public function floatcmp($num1, $num2){
        if ($num1-$num2 > 0.000001){
            return 1;
        } elseif ($num1-$num2 < -0.000001){
            return -1;
        }else{
            return 0;
        }
    }

    public function auth($o){
        $arr = explode('_', $o);
        if(count($arr) == 3){
            list($uid, $tm, $sg) = $arr;
            $ctm = time();
            $dtm = abs($ctm - $tm);
            if($dtm < 6000 && $this->sign($uid, $tm) == $sg) {
                return [true, $uid];
            }
        }
        return [false, ''];
    }
}
