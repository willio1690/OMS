<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
* @author ykm 2015-12-25
* @describe member打印数据整理
*/
class logisticsmanager_print_data_member{
    private $mField = array(
        'member_id',
    );

    /**
     * member
     * @param mixed $oriData ID
     * @param mixed $corp corp
     * @param mixed $field field
     * @param mixed $type type
     * @return mixed 返回值
     */

    public function member(&$oriData, $corp, $field, $type) {
        $pre = __FUNCTION__ . '.';
        $memberIds = array();
        foreach($oriData as $k => $val) {
            $memberIds[] = $val['member_id'];
        }
        $memberModel = app::get('ome')->model('members');
        $strField = kernel::single('logisticsmanager_print_data')->getSelectField($this->mField, $field, $memberModel);
        $member = $memberModel->getList($strField, array('member_id'=>array_unique($memberIds)));
        $memberData = array();
        foreach($member as $row) {
            $memberData[$row['member_id']] = $row;
        }
        foreach($oriData as $key => $value) {
            foreach($field as $f) {
                if(isset($memberData[$value['member_id']][$f])) {
                    $oriData[$key][$pre . $f] = $memberData[$value['member_id']][$f];
                } elseif(method_exists($this, $f)) {
                    $oriData[$key][$pre . $f] = $this->$f($memberData[$value['member_id']]);
                } else {
                    $oriData[$key][$pre . $f] = '';
                }
            }
        }
    }
}