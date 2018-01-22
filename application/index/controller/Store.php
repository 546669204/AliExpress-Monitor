<?php
namespace app\index\controller;

use phpQuery;
use think\Controller;

class Store extends Controller
{
    public function index()
    {
		if (request()->isAjax()){
			return Model("Store")->getlist();
		}
		return $this->fetch();
	}

	public function add(){
		$request = request();
		if ($request->isAjax()){
			$param = $request->param();
			return Model("Store")->add($param);
		}
		return $this->error("非法访问");
	}
}
