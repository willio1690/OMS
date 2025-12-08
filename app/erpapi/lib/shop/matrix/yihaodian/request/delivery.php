<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 发货单处理
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_yihaodian_request_delivery extends erpapi_shop_request_delivery
{
    /**
     * 发货请求参数
     *
     * @return void
     * @author 
     **/

    protected function get_confirm_params($sdf)
    {
        $param = parent::get_confirm_params($sdf);

        $param['logistics_company'] = $sdf['logi_name'];

        return $param;
    }
}