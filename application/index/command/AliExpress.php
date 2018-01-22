<?php
namespace app\index\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use app\index\model\Store;
use app\index\model\Products;
use app\index\model\Log;
use phpQuery;

class AliExpress extends Command{
    protected $debug = true ;
    protected function configure(){
        //设置参数
        //$this->addArgument('email', Argument::REQUIRED); //必传参数
        $this->addArgument('action', Argument::OPTIONAL);//可选参数
        //选项定义
        //$this->addOption('message', 'm', Option::VALUE_REQUIRED, 'test'); //选项值必填
        //$this->addOption('status', 's', Option::VALUE_OPTIONAL, 'test'); //选项值选填
        $this->setName("AliExpress")->setDescription("This is AliExpress Tool Box");
    }
    protected function execute(Input $input, Output $output){
        //获取参数值
        $args = $input->getArguments();
        
        if (file_exists("recookie.txt")){
            unlink("recookie.txt");
        }
        switch ($args["action"]) {
            case 'ProductsInfo':
                $this->ProductsInfo($input,$output);
                break;
            case 'ProductsList':
                $this->ProductsList($input,$output);
                break;
            case 'ClearCache':
                $time = strtotime("-3 day");
                $path = "runtime" . DIRECTORY_SEPARATOR . "curl_log";
                if(is_dir($path) && $handle=opendir($path)){
                    while($file=readdir($handle)){ 
                        if($file=='.'||$file=='..') continue;  
                        $path2 = $path.DIRECTORY_SEPARATOR.$file;  
                        if(is_dir($path2) && $handle2=opendir($path2)){
                            while($file2=readdir($handle2)){
                                if($file2=='.'||$file2=='..') continue;  
                                if ((int)str_replace("",".json|.html",$file2) <  $time){
                                    unlink($path2 .DIRECTORY_SEPARATOR. $file2);
                                }
                                
                            }
                        }
                    }  
                } 
                break;
            default:
            $msg = <<<END
            
命令列表
ProductsList
ProductsInfo
ClearCache
END;
                $output->writeln($msg);
                break;
        }

        return ;
        

    }
    private function ProductsInfo(Input $input, Output $output){
        $products = new Products;
        foreach($products->select() as $v){
            mkdirs("runtime" . DIRECTORY_SEPARATOR . "curl_log" . DIRECTORY_SEPARATOR . $v['products_num']);
        }
        while (1) {
            $output->writeln(date("Y-m-d h:i:s"));
            $store = new Store;
            $products = new Products;
            $log = new Log;
            $data = $products->select();
            shuffle($data);
            $output->writeln("一共" . count($data) . "款产品店铺.");
            $time_100 = 1;
            $time_limit = mt_rand(70,95);
            foreach($data as $value){
				$path = "runtime" . DIRECTORY_SEPARATOR . "curl_log" . DIRECTORY_SEPARATOR . $value['products_num'] . DIRECTORY_SEPARATOR;
                $output->writeln("$time_100 编号:{$value['products_num']},名称:{$value['products_name']}");
                $html = go_curl("https:" . $value['products_url'],"get");
                $t=time();
                file_put_contents($path . "$t.html",$html);
				
                phpQuery::newDocumentHTML($html);
                $products_info = [];
                $temp = pq('meta[property="og:url"]')->attr("content");
                $temp = substr($temp,0,strrpos($temp,"?"));
                $products_info["products_url"] = $temp;
                $products_info["products_name"] = pq('.product-name')->text();
                $products_info["products_order"] = intval(pq('#j-order-num')->text());
                $products_info["products_prices"] = pq('.p-price')->text();
                $products_info["products_price"] = pq('#j-sku-price')->text();
                $products_info["products_discount_price"] = pq('#j-sku-discount-price')->text();

                $temp = go_curl("https://my.aliexpress.com/wishlist/wishlist_item_count.htm?itemtype=product&itemid=" . $value['products_num'],"get");
                file_put_contents($path . "$t.wish",$temp);
		        $temp = json_decode(substr($temp,strpos($temp,"=")+1));
                $products_info["products_wishlist"] = $temp != null && property_exists($temp, "num")?$temp->num:$value['products_wishlist'];
                $products_info["products_hash"] = hash("sha512",join($products_info,""));
                

                file_put_contents($path . "$t.json",json_encode($products_info) . "\n\n=============\n\n" . json_encode($value));

                if (!empty($products_info["products_name"]) && !empty($products_info["products_url"])){
                    if ($value["products_hash"] != $products_info["products_hash"]){
                        $logs = [];
                        foreach($products_info as $k => $v){
                            if ($k == "products_hash"){continue;}
                            if ($products_info[$k] != $value[$k]){
                                $log_info = [];
                                $log_info["products_id"] = $value["id"];
                                $log_info["update_key"] = $k;
                                $log_info["update_old"] = $value[$k];
                                $log_info["update_now"] = $products_info[$k];
                                $log_info["update_msg"] = "";
                                $logs[] = $log_info;
                            }
                        }
                        $log->saveAll($logs);
                        $products_info["id"] = $value["id"];
                        $products->isUpdate(true)->save($products_info);
                    }
                    $output->writeln("OK !" );
                }else{
                    $output->writeln("访问出错。" ,$html); 
                    sleep(60);
                    //continue;
                }
                $time_100++;
                if($time_100>=$time_limit){
                    sleep(60*mt_rand(3,5));
                    $time_100 = 0;
                }
                phpQuery::$documents = [];
                //sleep(mt_rand(7, 15));
            }
            unset($data);
            unset($time_100);
            $output->writeln("Success !" );
            $output->writeln(date("Y-m-d h:i:s"));
            sleep(60*10);
        }
    } 
    private function ProductsList(Input $input, Output $output){
        $store = new Store;
        $products = new Products;
        $data = $store->select();
        $output->writeln("一共" . count($data) . "家店铺.");
        $shore_i = 1;

        foreach($data as $value){
            $output->writeln("当前第{$shore_i}家店铺,编号:{$value['store_num']},名称:{$value['store_name']}");

            $page_current = 1;
            $page_max = 2;
            while ($page_current <= $page_max) {
                $html = go_curl("https://www.aliexpress.com/store/{$value['store_num']}/search/$page_current.html","get");
                //$output->writeln($html);
                phpQuery::newDocumentHTML($html);
                $products_li = pq('.items-list li');
                $products_count = count($products_li);
    
                $page_current = intval(pq(".ui-pagination-active")->text());
                $page_max = intval(pq(".ui-pagination-navi .ui-pagination-next")->prev()->text());
    
                $output->writeln("当前页数:{$page_current},最大页数:{$page_max},当前页产品数:$products_count");
                
                foreach($products_li as $elm){ 
                    $products_info = [];
                    $products_info["store_id"] = $value["id"]; 
                    $products_info["products_num"] = pq($elm)->find('.atc-product-id')->val();
                    $products_info["products_name"] = pq($elm)->find('.detail h3 a')->attr("title");
                    $products_info["products_url"] = pq($elm)->find('.pic-rind')->attr("href");
                    $products_info["products_hash"] = hash("sha512",$products_info["products_url"] . $products_info["products_name"]);
    
                    $products_data = $products->where(["products_num"=>$products_info["products_num"]])->find();
                    if ($products_data == null){
                        $output->writeln("新增商品==编号:{$products_info['products_num']}");
                        
                        $products->data($products_info);
                        $products->isUpdate(false)->save();
                    }
                }
                sleep(mt_rand(6, 15));
                $page_current++;
            }

            $shore_i++;
        }
        //$output->writeln(go_curl("http://www.baidu.com","get"));
        $output->writeln("Success !" );
    }
}