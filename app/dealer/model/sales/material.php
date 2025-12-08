<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 销售物料
 */
class dealer_mdl_sales_material extends dbeav_model
{

    /**
     * 搜索Options
     * @param mixed $value value
     * @return mixed 返回值
     */

    public function searchOptions($value = '')
    {
        $arr = parent::searchOptions();
        return array_merge($arr, array(
            'betc_name' => __('所属贸易公司'),
        ));
    }

    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter, $tableAlias = null, $baseWhere = null)
    {
        $where = ' 1 ';
        if ($filter['shop_id']) {
            //$where .= " AND shop_id IN (SELECT shop_id FROM sdb_ome_shop WHERE delivery_mode='shopyjdf' AND shop_id='" . $filter['shop_id'] . "')";
            //unset($filter['shop_id']);
            
            //查询经销店铺(支持一次查询多个)
            $shopMdl = app::get('ome')->model('shop');
            $tempList = $shopMdl->getList('shop_id', array('shop_id'=>$filter['shop_id']), 0, -1);
            $shopIds = ($tempList ? array_column($tempList, 'shop_id') : '-1');
            
            //shop_id
            $filter['shop_id'] = $shopIds;
            
            //unset
            unset($tempList, $shopIds);
        }

        if ($filter['betc_name']) {
            $betcMdl  = app::get('dealer')->model('betc');
            $betcInfo = $betcMdl->db_dump(['betc_name' => $filter['betc_name']]);
            if ($betcInfo && $betcInfo['cos_id']) {
                $treeList = kernel::single('organization_cos')->getCosList($betcInfo['cos_id']);
                if ($treeList[0]) {
                    $cosIdArr = array_unique(array_column($treeList[1], 'cos_id'));
                    $where .= " AND cos_id IN ('" . implode("','", $cosIdArr) . "')";
                } else {
                    $where .= " AND cos_id=0";
                }
            } else {
                $where .= " AND cos_id=0";
            }
            unset($filter['betc_name']);
        }
        return parent::_filter($filter, $tableAlias, $baseWhere) . " AND " . $where;
    }

    /**
     * modifier_shop_id
     * @param mixed $shop_id ID
     * @param mixed $list list
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_shop_id($shop_id, $list, $row)
    {
        // if ($this->is_export_data) {
        //     return $tel;
        // }
        static $shopList;
        if (!isset($shopList[$shop_id])) {
            $shopMdl   = app::get('ome')->model('shop');
            $shopIdArr = array_column($list, 'shop_id');
            $shopList  = $shopMdl->getList('*', ['shop_id|in' => $shopIdArr]);
            $shopList  = array_column($shopList, null, 'shop_id');
        }
        return $shopList[$shop_id]['name'];
    }

    /**
     * modifier_sales_material_type
     * @param mixed $sales_material_type sales_material_type
     * @return mixed 返回值
     */
    public function modifier_sales_material_type($sales_material_type)
    {
        switch ($sales_material_type) {
            case '1':
                $info = '普通';
                break;
            case '2':
                $info = '组合';
                break;
            case '3':
                $info = '赠品';
                break;
            default:
                $info = '';
                break;
        }
        return $info;
    }
}
