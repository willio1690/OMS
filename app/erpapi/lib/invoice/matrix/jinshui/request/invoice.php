<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 金税电子发票业务
 */
class erpapi_invoice_matrix_jinshui_request_invoice extends erpapi_invoice_request_invoice
{
    protected function get_create_apiname()
    {
        return STORE_JINSHUI_INVOICE_FILE_CREATE;
    }
    
    /**
     * 获取_result_apiname
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */

    public function get_result_apiname($sdf)
    {
        return $sdf['serial_no'] ? STORE_JINSHUI_INVOICE_RESULT_QUERY : STORE_JINSHUI_INVOICE_QUERY;
    }
}