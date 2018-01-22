<?php
namespace app\index\model;

use think\Model;
use traits\model\SoftDelete;

class Store extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $resultSetType = 'collection';

    protected function initialize()
    {
        parent::initialize();
    }

    public function getlist(){
        $data = $this->where("")->select()->toArray();
        $products_sell_getlist = Model("Products")->products_sell_getlist();
        $products_sell = [];
        $products_wish = [];
        $products_sell_count = [];
        foreach($products_sell_getlist as $v){
            if(array_key_exists($v["store_id"],$products_sell)){
                $products_sell[$v["store_id"]] += intval($v["sell_num"]);
            }else{
                $products_sell[$v["store_id"]] = intval($v["sell_num"]);
            }
            if(array_key_exists($v["store_id"],$products_wish)){
                $products_wish[$v["store_id"]] += intval($v["wishlist_num"]);
            }else{
                $products_wish[$v["store_id"]] = intval($v["wishlist_num"]);
            }
            if(array_key_exists($v["store_id"],$products_sell_count)){
                $products_sell_count[$v["store_id"]] += intval($v["sell_count"]);
            }else{
                $products_sell_count[$v["store_id"]] = intval($v["sell_count"]);
            }
        }
        foreach($data as $k => $v){
            $data[$k]["products_count"] = Model("Products")->where(["store_id"=>$v["id"]])->count();
            $data[$k]["products_sell"] =  array_key_exists($v["id"],$products_sell)?$products_sell[$v["id"]]:0;
            $data[$k]["products_wish"] =  array_key_exists($v["id"],$products_wish)?$products_wish[$v["id"]]:0;
            $data[$k]["sell_count"] =  array_key_exists($v["id"],$products_sell_count)?$products_sell_count[$v["id"]]:0;
        }
        return $data;
    }

    public function add($param){
        $data = $this->save($param);
        if ($data>=1){return ["status"=>1];}
        return ["status"=>0];
    }
}