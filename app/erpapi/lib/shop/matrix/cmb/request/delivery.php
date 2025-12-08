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
class erpapi_shop_matrix_cmb_request_delivery extends erpapi_shop_request_delivery
{
    /**
     * 发货请求参数
     *
     * @return void
     * @author 
     **/

    protected function get_confirm_params($sdf)
    {
        $params = parent::get_confirm_params($sdf);

        // 货号对应平台商品ID
        $productId = $sellerSku = array();
        foreach ($sdf['orderinfo']['order_objects'] as $object) {
            $sellerSku[] = $object['bn'];
            
            if ($object['shop_goods_id'] && $object['shop_goods_id'] != '-1') {
                $productId[] = $object['shop_goods_id'];
            }
        }

        $params['ProductID'] = json_encode($productId);
        $params['SellerSKU'] = json_encode($sellerSku);

        return $params;
    }
}