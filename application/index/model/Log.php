<?php
namespace app\index\model;

use think\Model;
use traits\model\SoftDelete;

class Log extends Model{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    
    protected function initialize()
    {
        parent::initialize();
    }
    public function update_log_getlist($request){
        if (!empty($request["sort"])){
            $order = $request["sort"] . " " . $request["order"];
        }else{
            $order = "create_time desc";
        }
        $where = [];
        if (!empty($request["filter"])){
            $json  = json_decode($request["filter"]);
            foreach($json as $k => $v){
                if ($k == "create_time"){
                    $where[$k] = [[">",strtotime($v)],["<",strtotime($v . " 23:59:59")],"and"];
                    continue;
                }
                $where[$k] = ["LIKE","%$v%"];
            }
        }
        $data = $this->where($where)->order($order)->limit($request['offset'], $request['limit'])->select();
        $total = $this->where($where)->count();
        $returnarr= array("rows"=>$data,"total"=>$total);
        return $returnarr ;
    }
}