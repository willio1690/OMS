<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2018/4/12
 * @describe 订单处理
 */

class erpapi_shop_matrix_shunguang_response_order extends erpapi_shop_response_order
{
    protected function get_update_components()
    {
        $components = array('custommemo','markmemo','marktype');
        return $components;
    }
}
