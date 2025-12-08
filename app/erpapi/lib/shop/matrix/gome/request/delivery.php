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
class erpapi_shop_matrix_gome_request_delivery extends erpapi_shop_request_delivery
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

        $addressShort_name = null;
        #如果承运商为国美代运,addressShort_name是商家在后配置的发货地址
        if(in_array($sdf['logi_type'],array('wu074quanfeng','GOME_ZJS'))){
            $addressShort_name = $this->__channelObj->channel['addr'];
        }

        $param['addressShort_name'] = $addressShort_name;

        return $param;
    }
}