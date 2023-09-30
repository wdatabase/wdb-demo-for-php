<?php
namespace app\models;

use Yii;

class CommModel {
    public function bind_json() {
        $raw = Yii::$app->request->getRawBody();
        $this->load_json($raw);
    }

    public function bind_get() {
        $get = Yii::$app->request->getQueryParams();
        $this->bind($get);
    }

    public function load_json($raw) {
        $arr = json_decode($raw);
        if(is_object($arr)){
            foreach($arr as $k => $v){
                if(property_exists($this, $k)){
                    $this->{$k} = $v;
                }
            }
        }
    }

    public function bind($arr) {
        if(is_object($arr)||is_array($arr)){
            foreach($arr as $k => $v){
                if(property_exists($this, $k)){
                    $this->{$k} = $v;
                }
            }
        }
    }

    public function rsp_json() {
        $response = Yii::$app->response;
        $response->format = \yii\web\Response::FORMAT_JSON;
        $response->data = $this;
        return $response;
    }
}