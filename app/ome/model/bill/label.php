<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 单据标签
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class ome_mdl_bill_label extends dbeav_model
{
    /**
     * 获取单据标记列表
     * 
     * @param array $orderId
     * @return mixed
     */
    public function getBIllLabelList($billIds, $billType = 'order')
    {
        if(empty($billIds)){
            return array();
        }
        
        $sql = "SELECT a.*, b.label_code, b.label_color FROM sdb_ome_bill_label AS a LEFT JOIN sdb_omeauto_order_labels AS b ON a.label_id=b.label_id ";
        $sql .= " WHERE a.bill_type='".$billType."' and a.bill_id IN ('". implode("','", $billIds) ."') ";
        $labelList = $this->db->select($sql);

        foreach ($labelList as $lk => $lv) {
            if ($lv['label_value']) {
                $labelValuePreset = kernel::single('ome_bill_label')->labelValuePreset[$lv['label_code']];
                $label_name = [];
                foreach ($labelValuePreset as $pk => $pv) {
                    if ($lv['label_value'] & $pk) { // &位运算符
                        $label_name[] = $pv['label_name'];
                    }
                }
                if ($label_name) {
                    $labelsPreset = kernel::single('ome_bill_label')->orderLabelsPreset[$lv['label_code']];
                    if ($labelsPreset['label_thumb']) {
                        $labelList[$lk]['label_name'] = $labelsPreset['label_thumb'];
                    }
                    $labelList[$lk]['label_name'] .= '('.implode('/', $label_name).')';
                }
            }
        }
        
        return $labelList;
    }
}