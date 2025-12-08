<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单标签规则接口
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
interface omeauto_order_label_interface
{
    /**
     * 检查订单数据是否符合要求
     * 
     * @param omeauto_auto_group_item $item
     * @return boolean
     */
    public function vaild($item);
}