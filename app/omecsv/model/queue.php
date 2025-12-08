<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 任务队列模型类
 * Class omecsv_mdl_queue
 */
class omecsv_mdl_queue extends dbeav_model
{
    
    public function getRow($cols = '*', $filter = array())
    {
        $sql = "SELECT $cols FROM " . $this->table_name(true) . " WHERE " . $this->filter($filter);
        return $this->db->selectrow($sql);
    }
    
    public function modifier_error_msg($error_msg,$list,$row)
    {
        return $error_msg ? implode("；", unserialize($error_msg)).';' : '';
    }
    
}