<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 出库单推送
 *
 * @category 
 * @package 
 * @author sunjing
 * @version $Id: Z
 */
class erpapi_store_request_stockout extends erpapi_store_request_abstract
{
    

    /**
     * 出库单创建
     *
     * @return void
     * @author
     **/
    public function stockout_create($sdf){
        $stockout_bn = $sdf['io_bn'];
       
        //是否取消状态
        $iscancel = kernel::single('console_service_commonstock')->iscancel($stockout_bn, $is_vop);
        if ($iscancel) {
            return $this->succ('出库单已取消,终止同步');
        }

        $title = $this->__channelObj->wms['channel_name'] . '出库单添加';
        
        $params = $this->_format_stockout_create_params($sdf);
       

        if(!$params){
            return $this->succ('缺少请求参数');
        }
        $method = $this->get_stockout_create_apiname($sdf['bill_type']);

        if(!$method){
            return $this->succ('缺少请求method');
        }

       $result= $this->call($method, $params, $callback, $title, 10, $stockout_bn);

       if($result['succ'] == 'succ'){
            if($result['data']['docNo']){
                $result['data']['wms_order_code'] = $result['data']['docNo'];
            }
       }
       return $result;
    }

   

    protected function _format_stockout_create_params($sdf)
    {
       
    }


    protected function get_stockout_create_apiname($bill_type){

    }
    /**
     * 出库单取消
     *
     * @return void  
     * @author
     **/
    public function stockout_cancel($sdf){
        $stockout_bn = $sdf['io_bn'];

        $title = $this->__channelObj->wms['channel_name'] . '出库单取消';

        $params = $this->_format_stockout_cancel_params($sdf);

        return $this->__caller->call(WMS_OUTORDER_CANCEL, $params, null, $title, 10, $stockout_bn);

    }

    protected function _format_stockout_cancel_params($sdf)
    {
        
    }

    
}