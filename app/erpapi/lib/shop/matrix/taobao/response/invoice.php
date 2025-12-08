<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 自助开发票
 * 
 * @author wangbiao<wangbiao@shopex.cn>
 * @version 0.1
 */
class erpapi_shop_matrix_taobao_response_invoice extends erpapi_shop_response_invoice
{
    /**
     * 获取数据
     *
     * @param array $params
     * @return array:
     */
    protected function _returnParams($params) {
        $sdf = $this->_formatParams($params);
        
        return $sdf;
    }
}
