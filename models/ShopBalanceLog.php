<?php
namespace app\models;

class ShopBalanceLog extends CommModel
{
    public $uuid;
    public $uid;
    public $balance;
    public $op;
    public $createTime;
    public $updateTime;
}
