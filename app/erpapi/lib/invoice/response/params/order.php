<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 发票回传参数验证
 *
 */
class erpapi_invoice_response_params_order extends erpapi_invoice_response_params_abstract
{
    /**
     * 发货单更新校验参数
     *
     * @return void
     * @author
     **/

    public function status_update()
    {
        // todo 数组结构,无法校验
        $params = array (
            //            'is_status' => array('required'=>'true', 'type'=>'int','errmsg'=>'状态必填'),
            //            'invoice_status' => array('required'=>'true', 'type'=>'int','errmsg'=>'开票状态必填'),
            //            'sync' => array('required'=>'true', 'type'=>'int','errmsg'=>'同步状态必填'),
            //            'invoice_apply_bn' => array('required'=>'true', 'type'=>'string','errmsg'=>'发票申请单号必填'),
        );
        return $params;
    }

}
