<?php
namespace app\models;

class ListSearchRsp extends CommModel
{
    public $code = 200;
    public $msg = '';
    public $total = 0;
    public $list = [];
}
