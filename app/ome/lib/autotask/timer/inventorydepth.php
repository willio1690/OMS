<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 库存回写处理类
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class ome_autotask_timer_inventorydepth
{
    public function process($params, &$error_msg=''){

        $obj = kernel::single('inventorydepth_logic_stock');

        $obj->start();

        if (method_exists($obj, 'get_errmsg')) {
            $error_msg = $obj->get_errmsg();
        }

        return true;
    }
}