<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 云交易商品映射关系--渠道商品列表
 *
 * @author xueding@shopex.cn
 * @version 0.1
 */
class material_ctl_admin_material_channel extends desktop_controller
{
    /**
     * index
     * @return mixed 返回值
     */

    public function index()
    {
		if (empty($_POST)) {
			$_REQUEST['is_error'] = 1;
		}
        kernel::single('material_basic_material_channel')->set_params($_REQUEST)->display();
    }
}
