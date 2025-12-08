<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 门店发货单响应参数定义类
 *
 * @author xiayuanjun@shopex.cn
 * @version 0.1
 *
 */
class erpapi_store_response_params_delivery extends erpapi_store_response_params_abstract
{
    /**
     * 发货单更新校验参数
     * status: confirm(发货单确认)、sign(签收)
     *
     * @return void
     * @author 
     **/
    public function status_update()
    {
        $params = array(
            'delivery_bn' => array('required'=>'true', 'type'=>'string','errmsg'=>'发货单号必填'),
            'status'=>array('type'=>'enum','value'=>array('delivery','print','check','cancel','update','accept','pick','package','partin','confirm','sign',
                'refuse')),
        );
        return $params;
    }

}