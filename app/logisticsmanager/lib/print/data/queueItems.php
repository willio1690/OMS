<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-25
 * @describe print_queue_items打印数据整理
 */
class logisticsmanager_print_data_queueItems{
    private $mField = array(
        'ident',
        'delivery_id',
        'ident_dly',
    );

    /**
     * queueItems
     * @param mixed $oriData ID
     * @param mixed $corp corp
     * @param mixed $field field
     * @param mixed $type type
     * @return mixed 返回值
     */

    public function queueItems(&$oriData, $corp, $field, $type) {
        $pre = __FUNCTION__ . '.';
        $itemModel = app::get('ome')->model('print_queue_items');
        $strField = kernel::single('logisticsmanager_print_data')->getSelectField($this->mField, $field, $itemModel);
        $item = $itemModel->getList($strField, array('delivery_id'=>array_keys($oriData)));
        $itemData = array();
        foreach($item as $row) {
            $itemData[$row['delivery_id']] = $row;
        }
        foreach($oriData as $key => $value) {
            foreach($field as $f) {
                if(isset($itemData[$value['delivery_id']][$f])) {
                    $oriData[$key][$pre . $f] = $itemData[$value['delivery_id']][$f];
                } elseif(method_exists($this, $f)) {
                    $oriData[$key][$pre . $f] = $this->$f($itemData[$value['delivery_id']]);
                } else {
                    $oriData[$key][$pre . $f] = '';
                }
            }
        }
    }

    private function print_no($itemRow) {
        return $itemRow ? $itemRow['ident'] . '_' . $itemRow['ident_dly'] : '';
    }
}