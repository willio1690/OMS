<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 红字确认单参数验证
 *
 */
class erpapi_invoice_response_params_redapply extends erpapi_invoice_response_params_abstract
{
    /**
     * 发货单更新校验参数
     *
     * @return void
     * @author
     **/

    public function status_update()
    {
        $params = array (
            'status'           => array ('required' => 'true', 'type' => 'int', 'errmsg' => '状态必填'),
            'invoice_apply_bn' => array ('required' => 'true', 'type' => 'string', 'errmsg' => '流水号必填'),
            'id'               => array ('required' => 'true', 'type' => 'string', 'errmsg' => '发票id必填'),
            'item_id'          => array ('required' => 'true', 'type' => 'string', 'errmsg' => '明细id必填'),
        );
        return $params;
    }

}
