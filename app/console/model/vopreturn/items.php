<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Class console_mdl_vopreturn_items
 */
class console_mdl_vopreturn_items extends dbeav_model
{
    
    /**
     * 更新SplitNum
     * @param mixed $itemId ID
     * @param mixed $num num
     * @param mixed $op op
     * @return mixed 返回值
     */

    public function updateSplitNum($itemId, $num, $op = '+')
    {
        $updateSql = 'update sdb_console_vopreturn_items set split_num = ';
        if ($op == '+') {
            $updateSql .= "(split_num+{$num})";
            $filter    = "split_num+{$num}<=qty";
        } elseif ($op == '-') {
            $updateSql .= "(split_num-{$num})";
            $filter    = "split_num>={$num}";
        } else {
            return 0;
        }
        $updateSql .= ' where id = "' . $itemId . '" and ' . $filter;
        $this->db->exec($updateSql);
        return $this->db->affect_row();
    }
    
    /**
     * 更新ReturnNum
     * @param mixed $itemId ID
     * @param mixed $num num
     * @param mixed $op op
     * @return mixed 返回值
     */
    public function updateReturnNum($itemId, $num, $op = '+')
    {
        $updateSql = 'update sdb_console_vopreturn_items set num = ';
        if ($op == '+') {
            $updateSql .= "(num+{$num})";
            $filter    = "num+{$num}<=qty";
        } elseif ($op == '-') {
            $updateSql .= "(num-{$num})";
            $filter    = "num>={$num}";
        } else {
            return 0;
        }
        $updateSql .= ' where id = "' . $itemId . '" and ' . $filter;
        $this->db->exec($updateSql);
        return $this->db->affect_row();
    }
}