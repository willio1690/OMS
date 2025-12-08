<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * WMS 退货参数验证
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_params_reship extends erpapi_wms_response_params_abstract
{
    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function status_update()
    {
        $params = array(
            'reship_bn' => array('required'=>'true','type'=>'string','errmsg'=>'退货单号必填'),
            'status'=>array('type'=>'enum','value'=>array('FINISH','PARTIN','CLOSE','FAILED','DENY','ACCEPT')),
        );

        return $params;
    }
    
    public function add_complete()
    {
        $params = array(
            'reship_bn' => array('required'=>'true','type'=>'string','errmsg'=>'退货单号必填'),
            'items' => array('required'=>'true','type'=>'array','errmsg'=>'明细缺少'),
        );

        return $params;
    }
    
    /**
     * 京东云交易订单退款MQ消息
     * @todo：消息主题：ct_order_refund
     *
     * @param array $params
     * @return array
     */
    public function service_refund()
    {
        $params = array();
        
        return $params;
    }
}