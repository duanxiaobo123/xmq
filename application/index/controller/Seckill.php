<?php 
namespace app\index\controller;
use \think\Controller;
use \think\Db;
class Seckill extends Controller{
	public $redis;
    public function __construct(){
        $this->redis = new \Redis;
        $this->redis->connect('127.0.0.1',6379);
    }
    //订单号生成
    public function build_order_no(){
    	return date('ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    }
    public function index(){
    	$id = 1;
    	// $redis = $this->redis;
    	// $count = $redis->lpop('goods_store');
    	$count = Db::name('goods')->field('count')->where('id',$id)->find()['count'];
    	if($count<1){
    		echo '不好意思，卖完了';
    		exit;
    	}else{
    		$ordersn = $this->build_order_no();
    		$uid = rand(1,9999);
    		$status = 1;
    		// $data = Db::name('goods')->field('count,amount')->where('id',$id)->find();
    		$result = Db::name('order')->insert(['order_sn'=>$ordersn,'user_id'=>$uid,'goods_id'=>1,'price'=>'10','status'=>'1','addtime'=>date('Y-m-d H:i:s')]);
    		Db::name('goods')->where('id',$id)->setDec('count');
    	}
    }
}

?>