<?php
namespace app\index\controller;

use phpQuery;
use think\Controller;

class Products extends Controller
{
    public function index()
    {
		if (request()->isAjax()){
			return Model("Products")->getlist();
		}
		return $this->fetch();
	}

}
