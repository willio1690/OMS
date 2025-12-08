<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @Author: xueding@shopex.cn
 * @Vsersion: 2022/8/26
 * @Describe: 获取盘点单列表数据
 */
class openapi_data_original_inventory{
    /**
     * 获取List
     * @param mixed $filter filter
     * @param mixed $start_time start_time
     * @param mixed $end_time end_time
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回结果
     */

    public function getList($filter,$start_time,$end_time,$offset=0,$limit=100){
        if(empty($start_time) || empty($end_time)){
            return false;
        }
        $sqlstr = '';
        
        //盘点单号
        if ($filter['inventory_bn']) {
            $sqlstr .= " AND inventory_bn='". $filter['inventory_bn'] ."'";
        }
        //仓库编码
        if ($filter['branch_bn']) {
            $sqlstr .= " AND branch_bn='". $filter['branch_bn'] ."'";
        }
        
        $formatFilter=kernel::single('openapi_format_abstract');
        $countList = kernel::database()->selectrow("select count(inventory_id) as _count from sdb_console_inventory where inventory_date >=".$start_time." and inventory_date <".$end_time.$sqlstr);
        
        if(intval($countList['_count']) >0){
            $branchObj = app::get('ome')->model('branch');

            $branchInfos = array();
            $branch_arr = $branchObj->getList('branch_id,name,branch_bn', array(), 0, -1);
            foreach ($branch_arr as $k => $branch){
                $branchInfos[$branch['branch_id']]['name'] = $branch['name'];
                $branchInfos[$branch['branch_id']]['branch_bn'] = $branch['branch_bn'];
            }

            $inventoryLists = kernel::database()->select("select inventory_id,inventory_bn,branch_bn,inventory_date,create_date,memo from sdb_console_inventory where inventory_date >=".$start_time." and inventory_date <".$end_time.$sqlstr." order by inventory_date asc limit ".$offset.",".$limit."");
            
            $inventoryInfos = array();
            foreach ($inventoryLists as $k => $item)
            {
                $inventoryIds[] = $item['inventory_id'];
                $inventoryInfos[$item['inventory_id']] = $item;
                $inventoryInfos[$item['inventory_id']]['inventory_date'] = date('Y-m-d H:i:s', $item['inventory_date']);
                $inventoryInfos[$item['inventory_id']]['create_date'] = date('Y-m-d H:i:s', $item['create_date']);
                $inventoryInfos[$item['inventory_id']]['memo'] = $formatFilter->charFilter($this->get_mark_text($item['memo']));
                //items
                $inventoryInfos[$item['inventory_id']]['items'] = array();
                unset($inventoryInfos[$item['inventory_id']]['inventory_id']);
            }

            if(count($inventoryIds) == 1){
                $_where_sql = " inventory_id =".$inventoryIds[0]."";
            }else{
                $_where_sql = " inventory_id in(".implode(',', $inventoryIds).")";
            }
            
            //盘点单明细
            $inventoryItems = kernel::database()->select("select inventory_id,bn,name,quantity,memo,total_qty from sdb_console_inventory_items where ".$_where_sql."");
            
            foreach ($inventoryItems as $key => $val) {
                $inventoryId = $val['inventory_id'];
                $val['memo'] = $formatFilter->charFilter($this->get_mark_text($val['memo']));
                unset($val['inventory_id']);
                $inventoryInfos[$inventoryId]['items'][] = $val;
            }

            return array(
                'lists' => $inventoryInfos,
                'count' => $countList['_count'],
            );
        }else{
            return array(
                'lists' => array(),
                'count' => 0,
            );
        }
    }
    
    /**
     * 返回备注
     * @Author: xueding
     * @Vsersion: 2022/8/26 上午11:29
     * @param $mark_text
     * @return mixed|string
     */
    function get_mark_text($mark_text)
    {
        $mark = unserialize($mark_text);
        $memo = '';
        if (is_array($mark) || !empty($mark)){
            $memo = array_pop($mark);
        }
        return $memo['op_content'];
    }

    /**
     * 获取ApplyList
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回结果
     */
    public function getApplyList($filter,$offset=0,$limit=100)
    {
        $applyMdl = app::get('console')->model('inventory_apply');

        $count = $applyMdl->count($filter);

        if (!$count) {
            return ['lists' => [], 'count'=> 0];
        }

        $lists = [];

        $rows = $applyMdl->getList('*',$filter,$offset,$limit);

        foreach ($rows as $key => $val) {
            $l = [
                'inventory_apply_bn' => $val['inventory_apply_bn'],
                'inventory_date' => date('Y-m-d H:i:s', $val['inventory_date']),
                'memo' => $val['memo'],
                'sku_count' => $val['sku_hang'],
                'total_qty' => $val['sku_total'],
                'warehouse' => $val['out_id'],
                'status' => $val['status'],
            ];

            $lists[] = $l;
        }
        
        return ['lists' => $lists, 'count'=> $count];
    }

    /**
     * 获取ApplyDetail
     * @param mixed $inventory_apply_bn inventory_apply_bn
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回结果
     */
    public function getApplyDetail($inventory_apply_bn,$offset=0,$limit=100)
    {
        $applyMdl = app::get('console')->model('inventory_apply');

        $inventory_apply = $applyMdl->db_dump(['inventory_apply_bn' => $inventory_apply_bn]);
        if (!$inventory_apply) {
            return ['lists' => [], 'count'=> 0];
        }

        $itemMdl = app::get('console')->model('inventory_apply_items');

        $filter = ['inventory_apply_id' => $inventory_apply['inventory_apply_id']];

        $count = $itemMdl->count($filter);

        $rows = $itemMdl->getList('*', $filter, $offset, $limit);

        $inventory_apply = [
            'inventory_apply_bn' => $inventory_apply['inventory_apply_bn'],
            'inventory_date' => date('Y-m-d H:i:s', $inventory_apply['inventory_date']),
            'memo' => $inventory_apply['memo'],
            'sku_count' => $inventory_apply['sku_hang'],
            'total_qty' => $inventory_apply['sku_total'],
            'warehouse' => $inventory_apply['out_id'],
            'status' => $inventory_apply['status'],
        ];

        $lists = [
        ];

        foreach ($rows as $key => $val) {   
            $l = [
                'bn'            => $val['material_bn'],
                'wms_stores'    => $val['wms_stores'],
                'oms_stores'    => $val['oms_stores'],
                'diff_stores'   => $val['diff_stores'],
                'm_type'        => $val['m_type'],
                'is_confirm'    => $val['is_confirm'],
                'memo'          => $val['memo'],
                'item_id'       => $val['item_id'],
            ];

            $lists[] = $l;
        }

        return ['lists' => $lists, 'count' => $count, 'inventory_apply' => $inventory_apply];
    }

    /**
     * 获取ShopSkuList
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回结果
     */
    public function getShopSkuList($filter,$offset=0,$limit=100)
    {
        $mdlShopSku = app::get('inventorydepth')->model('shop_skus');
        $count = $mdlShopSku->count($filter);
        if($count < 1) {
            return ['lists' => [], 'count' => 0];
        }
        $field = 'shop_bn,shop_name,shop_type,shop_sku_id,shop_iid,shop_product_bn,shop_properties,shop_properties_name,shop_title,shop_price,shop_barcode,at_time,up_time';
        $rows = $mdlShopSku->getList($field, $filter, $offset, $limit);
        $lists = [];
        foreach ($rows as $key => $val) {
            $val = array_map('trim', $val);
            $l = [
                'shop_bn' => $val['shop_bn'],
                'shop_name' => $val['shop_name'],
                'shop_type' => $val['shop_type'],
                'sku_id' => $val['shop_sku_id'],
                'iid' => $val['shop_iid'],
                'shop_product_bn' => $val['shop_product_bn'],
                'shop_properties' => $val['shop_properties'],
                'shop_properties_name' => $val['shop_properties_name'],
                'title' => $val['shop_title'],
                'price' => $val['shop_price'],
                'barcode' => $val['shop_barcode'],
                'at_time' => $val['at_time'],
                'up_time' => $val['up_time'],
            ];
            $lists[] = $l;
        }
        return ['lists' => $lists, 'count' => $count];
    }

    /**
     * 获取ShopStockList
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回结果
     */
    public function getShopStockList($filter,$offset=0,$limit=100)
    {
        $mdlStockLog = app::get('ome')->model('api_stock_log');
        $count = $mdlStockLog->count($filter);
        if($count < 1) {
            return ['lists' => [], 'count' => 0];
        }
        $field = 'log_id,shop_id,shop_name,product_bn,product_name,store,status,msg,createtime,last_modified';
        $rows = $mdlStockLog->getList($field, $filter, $offset, $limit);
        $shop = app::get('ome')->model('shop')->getList('shop_id,shop_bn', ['shop_id'=>array_column($rows, 'shop_id')]);
        $shop = array_column($shop, 'shop_bn', 'shop_id');
        $lists = [];
        foreach ($rows as $key => $val) {
            $val = array_map('trim', $val);
            $l = [
                'item_id' => $val['log_id'],
                'shop_bn' => $shop[$val['shop_id']],
                'shop_name' => $val['shop_name'],
                'material_bn' => $val['product_bn'],
                'material_name' => $val['product_name'],
                'nums' => $val['store'],
                'status' => $mdlStockLog->schema['columns']['status']['type'][$val['status']],
                'msg' => $val['msg'],
                'at_time' => date('Y-m-d H:i:s', $val['createtime']),
                'up_time' => date('Y-m-d H:i:s', $val['last_modified']),
            ];
            $lists[] = $l;
        }
        return ['lists' => $lists, 'count' => $count];
    }
}
