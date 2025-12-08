<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class tgstockcost_instance_router
{
	var $_instance = null;
	function __construct($app)
	{
		$config = base_setup_config::deploy_info();
		if($this->_instance) $this->_instance;
		else{
			//if($config['product_id'] == 'ECC-K')$this->_instance = kernel::single("stockcost_ocs_instance");
			//else 
			$this->_instance = kernel::single("tgstockcost_taog_instance");
		}
		$this->app = $app;
	}
	/*
	*创建期初数据队列
	*/
	function create_queue()
	{
		$this->_instance->create_queue();
	}
	/*出入库调用方法  各自实现*/
	function iostock_set($io,$data)
	{
		$this->_instance->iostock_set($io,$data);
	}
	/*销售出库时记录销售单毛利率等字段方法*/
	function set_sales_iostock_cost($io,$data)
	{
		$this->_instance->set_sales_iostock_cost($io,$data);
	}
	
}