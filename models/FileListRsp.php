<?php
namespace app\models;

class FileListRsp extends CommModel
{
    public $code = 200;
    public $msg = '';
    public $total = '';
    public $list = [];
}
