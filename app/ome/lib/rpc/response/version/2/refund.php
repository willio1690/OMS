<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_rpc_response_version_2_refund extends ome_rpc_response_version_base_refund
{

    /**
     * 添加退款单
     * @access public
     * @param array $refund_sdf 退款单数据
     * @return array 退款单主键ID array('refund_id'=>'退款单主键ID')
     */
    function add($refund_sdf){
        $rs = parent::add($refund_sdf);
        return $rs;
    }

    /**
     * 更新退款单状态
     * @access public
     * @param array $status_sdf 退款单状态数据
     */
    function status_update($status_sdf){
        $rs = parent::status_update($status_sdf);
        return $rs;
    }
}