<?php
namespace app\index\controller;

use phpQuery;
use think\Controller;

class Updatelog extends Controller
{
    public function index()
    {
		if (request()->isAjax()){
			$request = request()->param();
			return Model("Log")->update_log_getlist($request);
		}
		return $this->fetch();
	}

	public function productsinfo(){
		$request = request()->param();
		return Model("Products")->where(["id"=>$request["id"]])->find();
	}

}
