<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_refund_negotiate extends dbeav_model{
    
    /**
     * 协商类型常量
     */
    const NEGOTIATE_TYPE_REFUND_FEE = 2;                    // 退款金额协商
    const NEGOTIATE_TYPE_REFUND_PROOF = 4;                  // 退款凭证协商
    const NEGOTIATE_TYPE_REFUND_AGING = 8;                  // 已超售后时效
    const NEGOTIATE_TYPE_REFUND_ITEM_RESHIPPING = 9;        // 补发商品
    const NEGOTIATE_TYPE_REFUND_MAIL_NO = 15;               // 修改运单号协商
    const NEGOTIATE_TYPE_REFUND_ITEM_ABNORMAL = 16;         // 退货商品异常
    const NEGOTIATE_TYPE_REFUND_COMPOSITE_STYLE = 17;       // 协商售后信息
    
    /**
     * 协商状态常量
     */
    const SYNC_STATUS_NONE = 'none';
    const SYNC_STATUS_PENDING = 'pending';
    const SYNC_STATUS_SUCC = 'succ';
    const SYNC_STATUS_FAIL = 'fail';
    const SYNC_STATUS_RUNNING = 'running';
    
    /**
     * 退款类型常量
     */
    const REFUND_TYPE_REFUND = 'refund';
    const REFUND_TYPE_RETURN = 'return';
    
    /**
     * 获取协商类型选项
     */
    public function getNegotiateTypeOptions()
    {
        return array(
            self::NEGOTIATE_TYPE_REFUND_FEE => '退款金额协商',
            self::NEGOTIATE_TYPE_REFUND_PROOF => '退款凭证协商',
            self::NEGOTIATE_TYPE_REFUND_AGING => '已超售后时效',
            self::NEGOTIATE_TYPE_REFUND_ITEM_RESHIPPING => '补发商品',
            self::NEGOTIATE_TYPE_REFUND_MAIL_NO => '修改运单号协商',
            self::NEGOTIATE_TYPE_REFUND_ITEM_ABNORMAL => '退货商品异常',
            self::NEGOTIATE_TYPE_REFUND_COMPOSITE_STYLE => '协商售后信息',
        );
    }
    
    /**
     * 获取协商状态选项
     */
    public function getSyncStatusOptions()
    {
        return array(
            self::SYNC_STATUS_NONE => '不可协商',
            self::SYNC_STATUS_PENDING => '可协商',
            self::SYNC_STATUS_SUCC => '协商发起成功',
            self::SYNC_STATUS_FAIL => '协商发起失败',
            self::SYNC_STATUS_RUNNING => '协商发起中',
        );
    }
    
    /**
     * 获取退款类型选项
     */
    public function getRefundTypeOptions()
    {
        return array(
            self::REFUND_TYPE_REFUND => '仅退款',
            self::REFUND_TYPE_RETURN => '退货退款',
        );
    }
    
    /**
     * 根据退货单ID获取协商记录
     */
    public function getByReturnId($return_id, $source = 'return_product')
    {
        // 根据source类型确定refund_type
        $refund_type = ($source == 'refund_apply') ? self::REFUND_TYPE_REFUND : self::REFUND_TYPE_RETURN;
        
        return $this->getList('*', array('original_id' => $return_id, 'refund_type' => $refund_type));
    }
    
    /**
     * 根据订单ID获取协商记录
     */
    public function getByOrderId($order_id)
    {
        return $this->getList('*', array('order_id' => $order_id));
    }
    
    /**
     * 创建协商记录
     */
    public function createNegotiate($data)
    {
        $insert_data = array(
            'refund_type' => $data['refund_type'],
            'original_id' => $data['original_id'],
            'original_bn' => $data['original_bn'],
            'order_id' => $data['order_id'],
            'order_bn' => $data['order_bn'],
            'shop_id' => $data['shop_id'],
            'negotiate_type' => $data['negotiate_type'],
            'negotiate_sync_status' => $data['negotiate_sync_status'],
            'negotiate_sync_msg' => $data['negotiate_sync_msg'],
            'negotiate_desc' => $data['negotiate_desc'],
            'negotiate_text' => $data['negotiate_text'],
            'negotiate_refund_fee' => $data['negotiate_refund_fee'],
            'negotiate_reason_id' => $data['negotiate_reason_id'],
            'negotiate_reason_text' => $data['negotiate_reason_text'],
            'negotiate_address_id' => $data['negotiate_address_id'],
            'negotiate_address_text' => $data['negotiate_address_text'],
            'refund_type_code' => $data['refund_type_code'],
            'refund_version' => $data['refund_version'],
        );
        
        return $this->insert($insert_data);
    }
    
    /**
     * 更新协商记录
     */
    public function updateNegotiate($id, $data)
    {
        $update_data = array(
            'negotiate_type' => $data['negotiate_type'],
            'negotiate_sync_status' => $data['negotiate_sync_status'],
            'negotiate_sync_msg' => $data['negotiate_sync_msg'],
            'negotiate_desc' => $data['negotiate_desc'],
            'negotiate_text' => $data['negotiate_text'],
            'negotiate_refund_fee' => $data['negotiate_refund_fee'],
            'negotiate_reason_id' => $data['negotiate_reason_id'],
            'negotiate_reason_text' => $data['negotiate_reason_text'],
            'negotiate_address_id' => $data['negotiate_address_id'],
            'negotiate_address_text' => $data['negotiate_address_text'],
            'refund_type_code' => $data['refund_type_code'],
            'refund_version' => $data['refund_version'],
        );
        
        return $this->update($update_data, array('id' => $id));
    }
}
