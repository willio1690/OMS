<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class erpapi_wms_openapi_cnss_response_stock extends erpapi_wms_response_stock
{
    /**
     * quantity
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function quantity($params){
        $items = isset($params['item']) ? json_decode($params['item'],true) : array();

        foreach ($items as $key => $value) {
            if ($value['product_bn']) {
                $bn = kernel::single('material_codebase')->getBnBybarcode($value['product_bn']);

                $items[$key]['product_bn'] = $bn;
            }
        }

        $params['item'] = json_encode($items);

        return parent::quantity($params);
    }
}
