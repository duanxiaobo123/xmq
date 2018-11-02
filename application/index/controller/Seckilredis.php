<?php
 
class SeckillRedis {
 
	static protected $total_num_50 = 50; // 可允许抢购总数量
	static protected $total_num_100 = 100;
	static protected $total_num_500 = 500;
	static protected $total_num_1000 = 1000;
	static protected $validity_time = 300; // 有效期 5分钟
	static protected $seckill_free_num_50_key = 'seckill_free_num_50'; // 空闲允许抢购数量的redis key
	static protected $seckill_use_num_50_key = 'seckill_use_num_50'; // 被使用的redis key
	static protected $seckill_free_num_100_key = 'seckill_free_num_100';
	static protected $seckill_use_num_100_key = 'seckill_use_num_100';
	static protected $seckill_free_num_500_key = 'seckill_free_num_500';
	static protected $seckill_use_num_500_key = 'seckill_use_num_500';
	static protected $seckill_free_num_1000_key = 'seckill_free_num_1000';
	static protected $seckill_use_num_1000_key = 'seckill_use_num_1000';
 
	static protected $redisConnect;
 
	/* 获取redis链接 */
	static public function getRedis(){
		if(empty(self::$redisConnect)){
			self::$redisConnect = MyRedis::connect('redis');
		}
		return self::$redisConnect;
	}
 
	/* 获取所有key数组 */
	static public function getRedisKeys(){
		return array(
			50 => array('free_key' => self::$seckill_free_num_50_key, 'use_key' => self::$seckill_use_num_50_key, 'total_num' => self::$total_num_50),
			100 => array('free_key' => self::$seckill_free_num_100_key, 'use_key' => self::$seckill_use_num_100_key, 'total_num' => self::$total_num_100),
			500 => array('free_key' => self::$seckill_free_num_500_key, 'use_key' => self::$seckill_use_num_500_key, 'total_num' => self::$total_num_500),
			1000 => array('free_key' => self::$seckill_free_num_1000_key, 'use_key' => self::$seckill_use_num_1000_key, 'total_num' => self::$total_num_1000)
		);
	}
 
	/* 设置--初始化 */
	static public function setSeckillRedisKey($free_key, $use_key, $total_num){
		$redis = self::getRedis();
		$redis->delete($free_key);
		$redis->delete($use_key);
 
		for ($i=0; $i < $total_num; $i++) {
			$redis->rPush($free_key, 1);
		}
	}
 
	/* 定时更新秒杀商品入口人数 */
	static public function poling_set_seckill_redis(){
		$seckill_array = self::getRedisKeys();
 
		$redis = self::getRedis();
		foreach ($seckill_array as $key => $value) {
			if($redis->exists($value['free_key']) == true){
				// 清除过期的使用数量
				$use_list = $redis->lRange($value['use_key'], 0, -1);
				foreach ($use_list as $k => $v) {
					$data = json_decode($v, true);
					if(time() - $data['time'] > self::$validity_time){
						// 超过有效期 删除
						self::returnFree($key, $k);
					}
				}
			}else{
				self::setSeckillRedisKey($value['free_key'], $value['use_key'], $value['total_num']);
				echo 'set';
			}
		}
	}
 
	/* 获取空闲抢购--type:50 100 500 1000 */
	static public function getFree($type, $uid){
		$seckill_array = self::getRedisKeys();
		if(empty($type) || !in_array($type, array_keys($seckill_array))){
			return array('result' => false, 'message' => '抢购信息:类型不正确');
		}
		if(empty($uid)){
			return array('result' => false, 'message' => '抢购信息:用户ID不能为空');
		}
		$redis = self::getRedis();
		$result = $redis->lPop($seckill_array[$type]['free_key']);
		if($result == true){
			// 添加使用数量
			$index = $redis->rPush($seckill_array[$type]['use_key'], json_encode(array('uid' => $uid, 'time' => time()))) - 1;
			return array('result' => true, 'index' => $index);
		}else{
			return array('result' => false, 'message' => '抢购信息:被抢光啦');
		}
	}
 
	/* 返回空闲抢购--type:50 100 500 1000 */
	static public function returnFree($type, $index){
		$seckill_array = self::getRedisKeys();
		if(empty($type) || !in_array($type, array_keys($seckill_array))){
			return array('result' => false, 'message' => '抢购信息:类型不正确');
		}
		$redis = self::getRedis();
		$value = $redis->lGet($seckill_array[$type]['use_key'], $index);
		if(!empty($value)){
			$redis->lRem($seckill_array[$type]['use_key'], $value, 1);
			// 添加空闲数量
			$redis->rPush($seckill_array[$type]['free_key'], 1);
			return array('result' => true);
		}else{
			return array('result' => false, 'message' => '抢购信息:不存在索引');
		}
	}
 
	/* 打印 */
	static public function printRedis($type){
		$seckill_array = self::getRedisKeys();
		$redis = self::getRedis();
		$free_list = $redis->lRange($seckill_array[$type]['free_key'], 0, -1);
		print_r($free_list);
 
		$use_list = $redis->lRange($seckill_array[$type]['use_key'], 0, -1);
		print_r($use_list);
	}
 
 
 
}

--------------------- 
作者：CyborgLin 
来源：CSDN 
原文：https://blog.csdn.net/mxdzchallpp/article/details/52956272 
版权声明：本文为博主原创文章，转载请附上博文链接！