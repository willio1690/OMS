<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: yaokangming
 * Date: 2019/4/30
 * Time: 15:57
 */
class console_mdl_useful_life extends dbeav_model
{
    /**
     * 更新Num
     * @param mixed $id ID
     * @param mixed $num num
     * @param mixed $op op
     * @return mixed 返回值
     */

    public function updateNum($id, $num, $op='-') {
        if(!$id || !$num || !in_array($op, array('-','+'))) {
            return false;
        }
        if($op == '+') {
            $sql = 'update sdb_console_useful_life set num = num+' . $num . ' where life_id = ' . $id;
        } else {
            $sql = 'update sdb_console_useful_life set num = num-' . $num . ' where life_id = ' . $id;
        }
        if($this->db->exec($sql)){
            if($rs = $this->db->affect_row()){
                return $rs;
            }else{
                return true;
            }
        }else{
            return false;
        }
    }
}