<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_branch_flow extends dbeav_model{

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function modifier_content($val, $list, $row)
    {

        try {
            $class_name = 'ome_branch_flow_'.$row['flow_type'];

            $obj = kernel::single($class_name);

            return call_user_func_array([$obj, 'translateContent'], [$val]);
        } catch (Exception $e) {
            return '';
        }
    }
}
