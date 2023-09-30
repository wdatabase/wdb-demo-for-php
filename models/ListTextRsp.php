<?php
namespace app\models;

class ListTextRsp extends CommModel
{
    public $code = 200;
    public $msg = '';
    public $total = 0;
    public $list = [];
}
