<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2022/6/28 17:40:21
 * @describe: 类
 * ============================
 */

class erpapi_shop_matrix_tmall_response_maochao_order extends erpapi_shop_matrix_tmall_response_order
{
    
    protected function get_create_plugins()
    {
        $plugins = parent::get_create_plugins();

        $plugins[] = 'received';

        return $plugins;
    }
}
