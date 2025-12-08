<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_order_fail extends ome_mdl_orders{
    var $export_name = '失败订单';
    
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real=false){
        $table_name = 'orders';
        if($real){
            return kernel::database()->prefix.'ome_'.$table_name;
        }else{
            return $table_name;
        }
    }
    
    /**
     * 根据查询条件获取导出数据
     * @param $fields
     * @param $filter
     * @param $has_detail
     * @param $curr_sheet
     * @param $start
     * @param $end
     * @param $op_id
     * @return bool
     * @date 2024-04-26 2:56 下午
     */
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end, $op_id)
    {
        $params        = [
            'fields'     => $fields,
            'filter'     => $filter,
            'has_detail' => $has_detail,
            'curr_sheet' => $curr_sheet,
            'op_id'      => $op_id,
        ];
        $orderListData = kernel::single('ome_func')->exportDataMain(__CLASS__, $params);
        if (!$orderListData) {
            return false;
        }
        //根据选择的字段定义导出的第一行标题
        if ($curr_sheet == 1) {
            $data['content']['main'][] = $this->getCustomExportTitle($orderListData['title']);
        }
        
        $orderObjectsMdl = app::get('ome')->model('order_objects');
        
        $order_items_columns = array_values($this->orderItemsExportTitle());
        $items_fields        = implode(',', $order_items_columns);
        
        
        $main_columns = array_values($orderListData['title']);
        $orderList    = $orderListData['content'];
        foreach ($orderList as $order_data) {
            $order_objects          = $orderObjectsMdl->getList('*', ['order_id' => $order_data['order_id']]);
            $order_data['order_bn'] = $order_data['order_bn'] . "\t";
            
            if ($order_objects) {
                foreach ($order_objects as $object_k => $object_v) {
                    $orderItemObjRow            = array();
                    $orderItemObjRow['sm_bn']   = $object_v['bn'];
                    $orderItemObjRow['sm_name'] = str_replace("\n", " ", $object_v['name']);
                    $orderItemObjRow['nums']    = $object_v['quantity'];
                    $orderItemObjRow['oid']     = mb_convert_encoding("\t" . $object_v['oid'], 'GBK', 'UTF-8');
                    $orderItemObjRow['shop_goods_id'] = mb_convert_encoding("\t" . $object_v['shop_goods_id'], 'GBK', 'UTF-8');
                    
                    $orderdataRow = array_merge($order_data, $orderItemObjRow);
                    $all_fields   = implode(',', $main_columns) . ',' . $items_fields;
                    
                    $exptmp_data = [];
                    foreach (explode(',', $all_fields) as $key => $col) {
                        if (isset($orderdataRow[$col])) {
                            $orderdataRow[$col] = mb_convert_encoding($orderdataRow[$col], 'GBK', 'UTF-8');
                            $exptmp_data[]      = $orderdataRow[$col];
                        } else {
                            $exptmp_data[] = '';
                        }
                    }
                    $data['content']['main'][] = implode(',', $exptmp_data);
                }
            }
        }
        
        return $data;
    }
    
    /**
     * 获取CustomExportTitle
     * @param mixed $main_title main_title
     * @return mixed 返回结果
     */
    public function getCustomExportTitle($main_title)
    {
        $main_title        = array_keys($main_title);
        $order_items_title = array_keys($this->orderItemsExportTitle());
        $title             = array_merge($main_title, $order_items_title);
        return mb_convert_encoding(implode(',', $title), 'GBK', 'UTF-8');
    }
    
    /**
     * orderItemsExportTitle
     * @return mixed 返回值
     */
    public function orderItemsExportTitle()
    {
        $items_title = array(
            '销售物料编码' => 'sm_bn',
            '销售物料名称' => 'sm_name',
            '购买数量'   => 'nums',
            '子单号'    => 'oid',
            '平台商品ID' => 'shop_goods_id',
        );
        return $items_title;
    }
}
?>