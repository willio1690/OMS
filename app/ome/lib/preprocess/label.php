<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单打标签
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class ome_preprocess_label
{
    /**
     * 执行打标签
     * 
     * @param int $order_id
     * @param string $msg
     * @return boolean
     */
    public function process($order_id, &$msg=null)
    {
        if(empty($order_id)){
            $msg = '打标签缺少处理参数';
            return false;
        }
        
        //打标签
        $labelLib = kernel::single('omeauto_order_label');
        $result = $labelLib->makeOrderLabel($order_id, $msg);
        if(!$result){
            $msg = '订单打标记失败：'. $msg;
            return false;
        }
        
        return true;
    }
}