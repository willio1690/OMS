<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 发货单处理
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_shopex_bbc_request_delivery extends erpapi_shop_request_delivery
{
    /**
     * 发货请求参数（以淘宝做为标准）
     *
     * @return void
     * @author 
     **/

    protected function get_confirm_params($sdf)
    {
        $param = parent::get_confirm_params($sdf);
        
        $item_list = array();
        foreach ($sdf['delivery_items'] as $k=>$v) {
            $item_list[] = array(
                'oid'    => $sdf['orderinfo']['order_bn'],
                'itemId' => $v['bn'],
                'num'    => $v['number'],
            );
        }

        $param['item_list'] = json_encode($item_list);
        return $param;
    }
}