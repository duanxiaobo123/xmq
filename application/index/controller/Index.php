<?php
namespace app\index\controller;
use \think\Controller;
use \think\Db;
use \app\index\model\Log;
class Index extends Controller
{
    public function index()
    {
        $list = Log::paginate(10);
        // 把分页数据赋值给模板变量list
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch('log');
    }
    public function add_log(){
    	return $this->fetch();
    }
    public function add(){
        //创建上传目录不是目录创建目录
        $dir = 'uploads' . DS . date('Ymd',time());
        if(!is_dir($dir)){
            mkdir($dir,0777);
        }
        //截取原始文件的后缀并把付给新的名字
        $o_file=$_FILES['o_file']['name'];
        $n_file=$_FILES['n_file']['name'];
        if(!is_file($dir.$o_file)){
            $o_file_name = $dir . DS . 'old_'.$o_file;
        }
        if(empty($o_file)){
            $o_file_name = '';
        }
        if(!is_file($dir.$n_file)){
            $n_file_name = $dir . DS . 'new_'.$n_file;
        }
        move_uploaded_file($_FILES['o_file']['tmp_name'], $o_file_name);
        move_uploaded_file($_FILES['n_file']['tmp_name'], $n_file_name);
        $result = Db::name('log')->insert(['name'=>input('post.name'),'url'=>input('post.url'),'o_file'=>$o_file_name,'n_file'=>$n_file_name,'time'=>time()]);
    	if($result){
            $this->success('增加成功',url('index/index'));
        }
    }
    //冒泡排序从小到大
    public function getpao(){
        $arr = array(1,43,54,62,21,66,32,78,36,76,39);  
        $len = count($arr);
        //设置一个空数组 用来接收冒出来的泡
        //该层循环控制 需要冒泡的轮数
        for($i=1;$i<$len;$i++){ 
            //该层循环用来控制每轮 冒出一个数 需要比较的次数
            for($k=0;$k<$len-$i;$k++){
                if($arr[$k]>$arr[$k+1]){
                    $tmp=$arr[$k+1];
                    $arr[$k+1]=$arr[$k];
                    $arr[$k]=$tmp;
                }
            }
        }
        echo "<pre>";
        print_r($arr);
        echo "</pre>";
        exit;
    }
    //冒泡排序从大到小
    public function maopao(){
        $arr = array(1,43,54,62,21,66,32,78,36,76,39);  
        $len = count($arr);
        for($i=1;$i<$len;$i++){
            for($j=0;$j<$len-$i;$j++){
                if($arr[$j]<$arr[$j+1]){
                    $temp = $arr[$j];
                    $arr[$j] = $arr[$j+1];
                    $arr[$j+1] = $temp;
                }
            }
        }
        echo "<pre>";
        print_r($arr);
        echo "</pre>";
        exit;    
    }
    /**
    * 交换两个数的位置
    * @param $a
    * @param $b
    */
    public function swap(&$a,&$b){
        $temp = $b;
        $b = $a;
        $a = $temp;
    }

    /**
    * 鸡尾酒排序
    * @param $arr
    * @return mixed
    */
    public function Cocktailsort() {
        $arr = array(1,43,54,62,21,66,32,78,36,76,39);  
        $arr_len  =count($arr);
        // for($i = 0 ; $i < ($arr_len/2) ; $i ++){
            //将最小值排到队尾
            for( $j = 0 ; $j < ( $arr_len  - 1 ) ; $j ++ ){
                if($arr[$j] < $arr[$j + 1] ){
                    $this->swap($arr[$j],$arr[$j + 1]);
                }
            }
            //将最大值排到队头
            for($j = $arr_len - 1 - (0 + 1); $j > 0 ; $j --){
                if($arr[$j] > $arr[$j - 1]){
                    $this->swap($arr[$j],$arr[$j - 1]);
                }
            }
        // }
        echo "<pre>";
        print_r($arr);
        echo "</pre>";
        exit;
    }
}
