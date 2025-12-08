<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 翱象平台通知OMS业务
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version 2023.01.05
 */
class erpapi_shop_response_shop extends erpapi_shop_response_abstract 
{
    /**
     * 翱象系统通知签约信息给到OMS
     * method：alibaba.dchain.aoxiang.sign.seller.notify
     *
     * @param array $params
     * @return array
     */
    public function aoxiang_signed($params)
    {
        $this->__apilog['title'] = '翱象系统通知签约信息';
        $this->__apilog['original_bn'] = $params['bizRequestId'];
        
        $sdf = $this->_formatSignedParams($params);
        
        return $sdf;
    }
    
    /**
     * 格式化签约信息参数
     *
     * @param array $params
     * @return array
     */
    protected function _formatSignedParams($params)
    {
        $sdf = array(
            'bizRequestId' => $params['bizRequestId'], //业务请求ID，用于做幂等
            'bizRequestTime' => $params['bizRequestTime'], //业务请求时间戳(毫秒)
            'signed_type' => $params['signed_method'], //操作方法:sign(签约)、cancel(取消签约)
            'shop_id' => $this->__channelObj->channel['shop_id'], //OMS店铺shop_id
        );
        
        //转换时间
        if($sdf['bizRequestTime']){
            $sdf['bizRequestTime'] = ceil($sdf['bizRequestTime'] / 1000);
        }
        
        return $sdf;
    }
}