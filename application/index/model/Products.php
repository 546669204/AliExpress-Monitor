<?php
namespace app\index\model;

use think\Model;
use traits\model\SoftDelete;

class Products extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    protected function initialize()
    {
        parent::initialize();
    }

    public function storeProfile(){
        return $this->hasOne('Store',"id","store_id")->field('id,store_name')->setEagerlyType(1);
    }
    public function getlist(){
        $data = $this->with("storeProfile")->select();
                
        foreach($data as &$v){
            $v["store_name"] = $v["store_profile"]["store_name"];
        }
        return $data;  
    }

    public function products_sell_getlist(){
		$time = time();
		$today16 = strtotime(date("Y-m-d",$time) . " 16:00:00");
		if ($time < $today16){
			$day_start = $today16 - 86400;
			$day_end = $today16;
		}else{
			$day_start = $today16;
			$day_end = $today16 + 86400;
		}
        $data = model("Log")->field("products_id,update_old,update_now,create_time,update_key")->where(["update_key"=>["IN",["products_order","products_wishlist"]]])->where("create_time",[">",$day_start],["<",$day_end],"and")->select();
        $products = $this->select();
        $p = [];
        $ps = [];
        $json = [];
        foreach($data as $k => $v){
            if(!array_key_exists($v["products_id"],$p)){
                $p[$v["products_id"]][$v["update_key"]] = [];
            }
            $p[$v["products_id"]][$v["update_key"]][] = $k;
        }
        foreach($products as $v){
            $ps[$v["id"]] = $v;
        }
        foreach($p as $k => $v){
            $sell_num = 0;
            $wishlist_num = 0;
            if (array_key_exists("products_order",$v)){
                $tmp = $v["products_order"];
                if(count($tmp) == 1){
                    $sell_num = ($data[$tmp[0]]["update_now"] - $data[$tmp[0]]["update_old"]);
                }else{
                    $sell_num = ($data[$tmp[count($tmp)-1]]["update_now"] - $data[$tmp[0]]["update_now"]);
                }
            }

            if (array_key_exists("products_wishlist",$v)){
                $tmp = $v["products_wishlist"];
                if(count($tmp) == 1){
                    $wishlist_num = ($data[$tmp[0]]["update_now"] - $data[$tmp[0]]["update_old"]);
                }else{
                    $wishlist_num = ($data[$tmp[count($tmp)-1]]["update_now"] - $data[$tmp[0]]["update_now"]);
                }
            }
            
            $temp = [];
            $temp["id"] = $ps[$k]['id'];
            $temp["products_num"] = $ps[$k]['products_num'];
            $temp["products_name"] = $ps[$k]['products_name'];
            $temp["products_url"] = $ps[$k]['products_url'];
            $temp["store_id"] = $ps[$k]['store_id'];
            $temp["sell_num"] = $sell_num>0?$sell_num:0;
            $sell_price = empty(floatval($ps[$k]['products_discount_price']))?(empty(floatval($ps[$k]['products_price']))?0:floatval($ps[$k]['products_price'])):floatval($ps[$k]['products_discount_price']);
            $temp["sell_count"] = $sell_price * $temp["sell_num"];
            $temp["wishlist_num"] = $wishlist_num>0?$wishlist_num:0;
            $json[] = $temp;
            
        }
        return $json;
    }
}