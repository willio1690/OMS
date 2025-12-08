<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2016/4/25
 * @describe 自由体系版本2订单接口请求相关类
 */
class erpapi_shop_matrix_shopex_request_v2_order extends erpapi_shop_matrix_shopex_request_order {

    public function updateIframe($order,$is_request=true,$ext=array())
    {
        // 判断是否发请求
        if ($is_request != true) {
            $edit_type = $order['source'] == 'matrix' ? 'iframe' : 'local';
            return array('rsp'=>'success','msg'=>'','data'=>array('edit_type'=>$edit_type));
        }
        $order_bn   = trim($order['order_bn']);
        $notify_url = $ext['notify_url'];
        $param = array(
            'tid'        => $order_bn,
            'notify_url' => base64_encode($notify_url),
        );
        return $this->__caller->center_call(SHOP_IFRAME_TRADE_EDIT_RPC,$param, 5,'GET');
    }

    /**
     * 更新Order
     * @param mixed $order order
     * @return mixed 返回值
     */

    public function updateOrder($order)
    {
        //余单撤消后(更新前端店铺的发货状态和删除未发货的商品)
        if($order['process_status'] == 'remain_cancel')
        {
            return parent::updateOrder($order);
        }
        
        return array('rsp'=>'success','msg'=>'新版本无需发起订单编辑');
    }
}