<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * /BMS sn码查询
 *
 * @author sunjing@shopex.cn
 * @time 2017/12/7 11:48:33
 */
class erpapi_wms_matrix_bms_request_order extends erpapi_wms_request_order
{
  
    /**
     * query
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function query($sdf){
        $order_bn = $sdf['order_bn'];

        $title = $this->__channelObj->wms['channel_name'] . 'SN码查询';

        $params = $this->_format_query_params($sdf);

        $rs = $this->__caller->call(WMS_BMS_SNINFO_QUERY, $params, null, $title, 10, $order_bn);

        if($rs['rsp'] == 'succ') {
       
            
        }
        return $rs;
    }

   
    protected function _format_query_params($sdf){
        $params = array(

            'tid'               =>  $sdf['order_bn'],
            'order_code_type'   =>  '10',
            //'page_index'        =>  '50',

        );
        
        return $params;
    }
}