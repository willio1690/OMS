<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 盘点
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_mixture_response_inventory extends erpapi_wms_response_inventory
{
    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function add($params)
    {
        $params = parent::add($params);

        if ($params['items']){
            foreach ((array) $params['items'] as $key=>$item){
                $barcode = $item['bn'] ? $item['bn'] : $item['product_bn'];

                // 条码转货号
                if ($barcode) {
                    $bn = kernel::single('material_codebase')->getBnBybarcode($barcode);
                    $params['items'][$key]['bn'] = $params['items'][$key]['product_bn'] = $bn ? $bn : $barcode;    
                }
            }
        }

        return $params;
    }
}
