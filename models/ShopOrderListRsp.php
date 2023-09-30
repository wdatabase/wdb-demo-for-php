<?php
namespace app\models;

class ShopOrderListRsp extends CommModel
{
    public $code = 200;
    public $msg = '';
    public $total = 0;
    public $list = [];
}
