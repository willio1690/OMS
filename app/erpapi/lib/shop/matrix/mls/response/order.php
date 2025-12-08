<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @desc
 * @author: jintao
 * @since: 2016/8/25
 */
class erpapi_shop_matrix_mls_response_order extends erpapi_shop_response_order
{
    protected function get_update_components()
    {
        $components = array('master','markmemo','marktype','custommemo');

        return $components;
    }
}