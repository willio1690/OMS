<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2019/2/15
 * @describe 名融订单处理
 */

class erpapi_shop_matrix_mingrong_response_order extends erpapi_shop_response_order
{

    protected function get_update_components()
    {
        $components = array('markmemo','marktype','custommemo');
        
        return $components;
    }
}
