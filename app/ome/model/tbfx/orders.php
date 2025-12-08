<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_tbfx_orders extends dbeav_model{
    var $has_many = array(
       'tbfx_order_objects' => 'tbfx_order_objects',
    );

}
?>