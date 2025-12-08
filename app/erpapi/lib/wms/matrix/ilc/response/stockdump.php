<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 转储单
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_ilc_response_stockdump extends erpapi_wms_response_stockdump
{

    /**
     * status_update
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function status_update($params){
        $params = parent::status_update($params);

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
