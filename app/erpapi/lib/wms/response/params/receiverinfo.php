<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * WMS 发货单参数验证
 *
 */
class erpapi_wms_response_params_receiverinfo extends erpapi_validate
{

    /**
     * query
     * @return mixed 返回值
     */

    public function query()
    {
        $params = array(
            'delivery_bn' => array('required'=>'true', 'type'=>'string','errmsg'=>'发货单号必填'),
        );
        return $params;
    }

}