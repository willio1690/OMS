<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_refund_func{
    
    /**
     * 退款申请单据状态列表
     * @access static public
     * @return ArrayIterator 状态数组对象
     */
    static public function refund_apply_status(){
        $status = array(
            0 => '未审核',
            1 => '审核中',
            2 => '已接受申请',
            3 => '已拒绝',
            4 => '已退款',
            5 => '退款中',
            6 => '退款失败',
        );
        return $status;
    }
    
    /**
     * 获取退款申请单据状态名称
     * @access static public
     * @param String $status
     * @return String 状态名称
     */
    static public function refund_apply_status_name($status=''){
        if (empty($status)) return NULL;
        $refund_apply_status = self::refund_apply_status();
        $status_name = $refund_apply_status[$status];
        if ($status_name){
            return $status_name;
        }else{
            return $status;
        }
    }
    
    /**
     * 退款单据状态列表
     * @access static public
     * @return ArrayIterator 状态数组对象
     */
    static public function refund_status(){
        $status = array (
            'succ' => '支付成功',
            'failed' => '支付失败',
            'cancel' => '未支付',
            'error' => '处理异常',
            'invalid' => '非法参数',
            'progress' => '处理中',
            'timeout' => '超时',
            'ready' => '准备中',
          );
        return $status;
    }
    
    /**
     * 获取申请单据状态名称
     * @access static public
     * @param String $status
     * @return String 状态名称
     */
    static public function refund_status_name($status=''){
        if (empty($status)) return NULL;
        $refund_status = self::refund_status();
        $status_name = $refund_status[$status];
        if ($status_name){
            return $status_name;
        }else{
            return $status;
        }
    }
    
    /**
     * 平台退款状态
     *
     * @param $source_status
     * @param $return_type
     * @return array
     */
    public function get_source_status($source_status, $return_type='code')
    {
        $all = array(
            'REFUND_WAIT_SELLER_AGREE'          => '买家已经申请退款，等待卖家同意',
            'WAIT_SELLER_AGREE'                 => '买家已经申请退款，等待卖家同意',
            'WAIT_BUYER_RETURN_GOODS'           => '卖家已经同意退款，等待买家退货',
            'WAIT_SELLER_CONFIRM_GOODS'         => '买家已经退货，等待卖家确认收货',
            'SELLER_REFUSE_BUYER'               => '卖家拒绝退款',
            'CLOSED'                            => '退款关闭',
            'SUCCESS'                           => '退款成功',
        );
        
        //switch
        switch($return_type)
        {
            case 'all':
                return $all;
                break;
            case 'code':
                return $source_status;
            default:
                return $all[$source_status];
                break;
        }
        
        return $source_status;
    }
}