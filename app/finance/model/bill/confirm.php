<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_mdl_bill_confirm extends dbeav_model{

    function delete($filter,$tableAlias='',$baseWhere=''){
        $dbeav_filter = kernel::single('dbeav_filter');
        $dbeav_filter_ret = $dbeav_filter->dbeav_filter_parser($filter,$tableAlias,$baseWhere,$this);
        $sql = 'DELETE FROM `'.$this->table_name(1).'` where '.$dbeav_filter_ret;
        return $this->db->exec($sql);
    }


}