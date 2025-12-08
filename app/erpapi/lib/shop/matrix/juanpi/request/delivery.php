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
class erpapi_shop_matrix_juanpi_request_delivery extends erpapi_shop_request_delivery
{
    /**
     * 发货回调
     *
     * @return void
     * @author 
     **/

    public function confirm_callback($response, $callback_params)
    {
        if ($response['err_msg']) {
            $err_msg = @json_decode($response['err_msg'],true);
            if ($err_msg['info'] == '10015' && $err_msg['status'] == '0') {
                $response['res'] = 'W90012';
            }
        }

        return parent::confirm_callback($response, $callback_params);
    }
}