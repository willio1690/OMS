<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_operation_organization extends dbeav_model{

    /**
     * modifier_status
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_status($row)
    {
        if ($row == '1') {
            $row = "开启";
        }else if($row == '2'){
           $row = "关闭";
        }
        return $row;
    }
}