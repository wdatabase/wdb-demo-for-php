<?php
namespace app\models;

class ListSearchReq extends CommModel
{
    public $o;
    public $title;
    public $score;
    public $begin;
    public $end;
    public $offset;
    public $limit;
    public $order;
}
