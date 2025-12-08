<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * pos入库单
 *
 * @category 
 * @package 
 * @author sunjing
 * @version $Id: Z
 */
class erpapi_store_request_stockin extends erpapi_store_request_abstract
{
    
    /**
     * 入库单创建
     *
     * @return void
     * @author
     **/
    public function stockin_create($sdf){
        $stockin_bn = $sdf['io_bn'];

        $iscancel = kernel::single('console_service_commonstock')->iscancel($stockin_bn);
        if ($iscancel) {
            return $this->succ('入库单已取消,终止同步');
        }

        $title = $this->__channelObj->wms['channel_name'].'入库单添加';

        $params = $this->_format_stockin_create_params($sdf);
        if (!$params) {
            return $this->error('参数为空,终止同步');
        }
        $method = $this->get_stockin_create_apiname($sdf['bill_type']);
        $result = $this->call($method, $params, $callback, $title, 10, $stockin_bn);
        if($result['succ'] == 'succ'){
            if($result['data']['docNo']){
                $result['data']['wms_order_code'] = $result['data']['docNo'];
            }
       }
        return $result;
        
    }

    

    protected function get_stockin_create_apiname($bill_type)
    {
        return '';
    }

    protected function _format_stockin_create_params($sdf)
    {
        

       return $params;
    }

   
}