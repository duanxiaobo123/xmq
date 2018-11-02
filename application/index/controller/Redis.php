<?php
namespace app\index\controller;
use \think\Controller;
use \think\Db;
use \app\index\model\Log;
class Redis extends Controller{
    public $redis;
    public function __construct(){
        $this->redis = new \Redis;
        $this->redis->connect('127.0.0.1',6379);
    }
    public function index(){
        $store=10;  
        $redis = $this->redis;
        $redis->del('goods_store');
        $res = $redis->llen('goods_store');
        $count = $store - $res;
        for($i=0;$i<$count;$i++){
            $redis->lpush('goods_store',1);
        }
        echo $redis->llen('goods_store');
    }
}
