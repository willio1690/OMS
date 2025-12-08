<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 消息地址接口
 *
 * @category
 * @package
 * @author sunjing
 * @version $Id: Z
 */
class erpapi_shop_matrix_kuaishou_response_qianniu extends erpapi_shop_response_qianniu
{
    
    
    
    /**
     * 添加ress_modify
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function address_modify($sdf)
    {
        $this->_qnordersdf = $sdf;
        $this->__apilog['result']['data'] = array('tid'=>$this->_qnordersdf['bizOrderId']);
        $this->__apilog['original_bn']    = $this->_qnordersdf['bizOrderId'];
        $this->__apilog['title']          = '千牛/平台修改订单地址['.$this->_qnordersdf['bizOrderId'].']';

        $this->_order_detail=array();

        $accept = $this->_canModify();

        //本地作配置开启判断
        if ($accept === false) {
            //失败通知
            $shop_id = $this->_order_detail['shop_id'];
            $order_bn = $this->_order_detail['order_bn'];
            $rs = kernel::single('erpapi_shop_response_process_qianniu')->_confirmModifyAdress($shop_id, ['order_bn'=>$order_bn,'confirm'=>false]);


            return array();
        }
        $this->_formatSdf();
        
        //地址是否发生变化

        $new_order = array();
        $new_order['order_id']      = $this->_order_detail['order_id'];
       
        //暂停成功
        $new_order['confirm']        = 'N';
        $new_order['process_status'] = 'unconfirmed';
        $new_order['pause']          = 'false';
        $convert_order = array(
            'new_order'     =>  $new_order,
            'order_detail'  =>  $this->_order_detail,
        );
        return $convert_order;
       
    }


 
}
