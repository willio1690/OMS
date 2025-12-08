<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 售后业务
 *
 * @author 
 * @version 
 */
class erpapi_smart_request_aftersale extends erpapi_smart_request_abstract
{
    /**
     * 同步售后单
     *
     * @param $sdf
     * @return array|null
     */
    public function add($sdf)
    {
        $title = '售后同步';
        
        //method
        $method = 'smart.aftersale.add';
        
        //params
        $params = $this->_format_add_params($sdf);
        if (!$params) {
            return $this->error('参数为空,终止同步');
        }
        
        //request
        //$result = $this->call($method, $params, null, $title, 30, $sdf['smart_bn']);
        
        return $this->succ('成功', '200', $sdf);
    }
    
    protected function _format_add_params($sdf)
    {
        return $sdf;
    }
}
