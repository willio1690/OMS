<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author sunjing@shopex.cn
 * @describe 一盘货发货单
 */
class erpapi_wms_matrix_yph_request_delivery extends erpapi_wms_request_delivery
{

    protected function _getNextObjType() {
        return 'search_delivery';
    }

    /**
     * 订单来源。 JD：京东，DD：当当，PP：拍拍，QQ：QQ网购，SN：苏宁，GM：国美，WPH：唯品会，1688：阿里巴巴，POS：POS门店，TB：淘宝，TM：天猫，KS：快手，OTHER：其他
   
     */

    private $_channel_source_mapping = array(
        'pinduoduo'    => array(

            'id'    =>  '1',
            'name'  =>  '拼多多',
        ),

        'vop'    => array(

            'id'    =>  '999',
            'name'  =>  '唯品会',
        ),
       

    );

    protected function _format_delivery_create_params($sdf)
    {
        
        $params = parent::_format_delivery_create_params($sdf);

        $params['kepler_type'] = 'yph';
        
        $shop_type = $sdf['shop_type'];

        $channel_source = $this->_channel_source_mapping[$shop_type];

        $params['order_source'] = $channel_source['id'];

        $params['order_source_name'] = $channel_source['name'];
        return $params;
    }

    protected function _format_delivery_cancel_params($sdf)
    {
        
        $shop_code = kernel::single('wmsmgr_func')->getWmsShopCode($this->__channelObj->wms['channel_id'],$sdf['shop_code']);
        
   
        $shop_type = $sdf['shop_type'];

        $channel_source = $this->_channel_source_mapping[$shop_type];
        
        $order_source = $channel_source['id'];

        $order_source_name = $channel_source['name'];

        $params = array(
            'ownerCode'         => $shop_code,
            'out_order_code'        => $sdf['outer_delivery_bn'],
            'order_source'      =>  $order_source,
            'order_source_name' =>   $order_source_name,
            'kepler_type'       =>  'yph',
        );
        return $params;

    }

    /**
     * @param $rs
     * @return array
     */
    protected function _deal_search_result($rs){

        $resultData = array();
        $data       = json_decode($rs['data'], true);
        // 京东沧海
        if($data) {
    
            if (!in_array($data['status'],array('FINISH'))){
                return array();
            }

            if($data['status'] == 'FINISH'){
                $data['status'] = 'DELIVERY';
            }

            $resultData['status']       = $data['status'];
            $resultData['remark']       = $data['remark'];
            $resultData['delivery_bn']  = $data['eclp_bn'];
            $resultData['operate_time'] = $data['operate_time'];
            $resultData['logi_no']      = $data['logistics'];
            $resultData['logistics']      = 'JD';
            $resultData['warehouse']    = $data['warehouse'];
           
        }
        $rs['data'] = $resultData;
        return $rs;
    }


    

    protected function _format_delivery_search_params($sdf)
    {

        $shop_code = kernel::single('wmsmgr_func')->getWmsShopCode($this->__channelObj->wms['channel_id'],$sdf['shop_code']);
        
   
        $shop_type = $sdf['shop_type'];

        $channel_source = $this->_channel_source_mapping[$shop_type];
        
        $order_source = $channel_source['id'];

        $order_source_name = $channel_source['name'];
        $params = array(
            'out_order_code'    =>  $sdf['delivery_bn'],
            'order_source'      =>  $order_source,
            'order_source_name' =>  $order_source_name,
            'kepler_type'       =>  'yph',
            'ownerCode'         => $shop_code,
        );
        return $params;
    }
}
