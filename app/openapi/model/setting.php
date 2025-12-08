<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_mdl_setting extends dbeav_model{

    /**
     * modifier_status
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_status($row){
        $ret = ($row == 1) ? '开启' : '关闭';
        return $ret;
    }

    /**
     * modifier_is_data_mask
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_is_data_mask($row){
        $ret = ($row == 1) ? 'Y' : 'N';
        return $ret;
    }

}

?>