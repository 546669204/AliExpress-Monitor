<?php
namespace app\index\controller;

use phpQuery;
use think\Controller;

class Index extends Controller
{
    public function index()
    {
		if (request()->isAjax()){
			return Model("Products")->products_sell_getlist();
		}
		return $this->fetch();
	}

}
