<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/11/29 11:21:30
 * @describe: 加工单
 * ============================
 */
class erpapi_wms_response_params_storeprocess extends erpapi_wms_response_params_abstract
{
    
    /**
     * status_update
     * @return mixed 返回值
     */

    public function status_update()
    {
        $params = array(
            'mp_bn' => array('required'=>'true','type'=>'string','errmsg'=> '加工单号必填'),
            'material_items' => array('required'=>'true','type'=>'array','errmsg'=> '缺少materialitems'),
            'product_items' => array('required'=>'true','type'=>'array','errmsg'=> '缺少productitems'),
        );

        return $params;
    }
}