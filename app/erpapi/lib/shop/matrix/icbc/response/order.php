<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单处理
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_icbc_response_order extends erpapi_shop_response_order
{
    protected function get_update_components()
    {
        $components = array('markmemo','marktype','custommemo');

        return $components;
    }

}
