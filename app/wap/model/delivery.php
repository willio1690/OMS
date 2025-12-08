<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wap_mdl_delivery extends dbeav_model{

    var $has_many = array(
        'delivery_items' => 'delivery_items',
    );

    var $defaultOrder = array('delivery_id',' DESC');

    //格式化filter
    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter, $tableAlias=null, $baseWhere=null)
    {
        $where    = '';
        
        //订单号(合并订单有多个order_bn以|分隔)
        if($filter['order_bn'])
        {
            $orderObj    = app::get('ome')->model('orders');
            $orderRow    = $orderObj->dump(array('order_bn'=>$filter['order_bn']), 'order_id');
            if($orderRow)
            {
                $where    .= " AND order_bn LIKE '%". $filter['order_bn'] ."%'";
            }
            else
            {
                $where    .= " AND order_bn='no_false'";
            }
            
            unset($filter['order_bn']);
        }
        
        return parent::_filter($filter, $tableAlias, $baseWhere) . $where;
    }
}
?>
