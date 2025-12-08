<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class tgstockcost_ctl_taog_costdetail extends tgstockcost_ctl_costdetail
{
	function __consruct($app)
	{
		$this->app = $app;
		parent::__construct($app);
	}

	function download(){	
		$params = array();
		$this->finder('tgstockcost_mdl_stockdetail',$params);
	}

}