<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单业务接口类
 *
 * @author wangbiao@shopex.cn
 * @version 2024.04.23
 */
class erpapi_smart_request_order extends erpapi_smart_request_abstract
{
    /**
     * 同步订单获取价格
     *
     * @param $sdf
     * @return array|null
     */
    public function addOrder($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'].'门店同步';
        
        //method
        $method = 'smart.order.add';
        
        //params
        $params = $this->_format_add_params($sdf);
        if (!$params) {
            return $this->error('参数为空,终止同步');
        }
        
        //request
        //$result = $this->call($method, $params, null, $title, 30, $sdf['smart_bn']);
        
        return $this->succ('获取Smart价格成功', '200', $sdf);
    }
    
    protected function _format_add_params($sdf)
    {
        return $sdf;
    }
}
