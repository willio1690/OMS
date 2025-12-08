<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class tgstockcost_ctl_costselect extends desktop_controller
{
	function index(){
		kernel::single('tgstockcost_taog_costselect')->set_params($_REQUEST)->display();
	}

}