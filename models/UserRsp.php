<?php
namespace app\models;

class UserRsp extends CommModel
{
    public $code = 200;
    public $uid = '';
    public $time = 0;
    public $sign = '';
    public $msg = '';
}
