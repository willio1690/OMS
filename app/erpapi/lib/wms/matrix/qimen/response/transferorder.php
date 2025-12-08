<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_wms_matrix_qimen_response_transferorder extends erpapi_wms_response_transferorder
{


    /**
     * 更新
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function update($params){

        if ($params['order_status'] == '100') {
            $params['order_status'] = 'FINISH';
        }

        return parent::update($params);
    }
}
