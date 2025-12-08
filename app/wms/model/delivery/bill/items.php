<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_mdl_delivery_bill_items extends dbeav_model
{
    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter, $tableAlias = null, $baseWhere = null)
    {
        $deliveryBillMdl = app::get('wms')->model('delivery_bill');

        $baseWhere = [];
        if ($filter['delivery_bn']) {
            $delivery = app::get('wms')->model('delivery')->db_dump(['delivery_bn' => $filter['delivery_bn']], 'delivery_id');

            $delivery_id  = $delivery ? $delivery['delivery_id'] : 0;
            $deliveryBill = $deliveryBillMdl->getList('b_id', ['delivery_id' => $delivery_id]);

            $baseWhere[] = 'bill_id IN(' . implode(',', array_column($deliveryBill ? $deliveryBill : [['b_id' => '0']], 'b_id')) . ')';

            unset($filter['delivery_bn']);
        }

        if ($filter['package_bn']) {
            $deliveryBill = $deliveryBillMdl->db_dump(['package_bn' => $filter['package_bn']]);

            $baseWhere[] = 'bill_id=' . intval($deliveryBill['b_id']);

            unset($filter['package_bn']);
        }

        return parent::_filter($filter, $tableAlias, $baseWhere);
    }

    /**
     * 搜索Options
     * @return mixed 返回值
     */
    public function searchOptions()
    {

        $columns                = parent::searchOptions();
        $columns['delivery_bn'] = '发货单号';
        $columns['package_bn']  = '包裹号';

        return $columns;
    }
}
