<?php
namespace app\models;

class ShopCartListRsp extends CommModel
{
    public $code = 200;
    public $msg = '';
    public $total = 0;
    public $listinfo = [];
}
