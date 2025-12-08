<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_group_type{
    function group_type(){
        $type = array(
          'confirm' => '订单确认组',
          'process' => '订单处理组',
        );
        return $type;
    }
}
?>