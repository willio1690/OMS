<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 发货单
 *
 */
class ome_service_template_delivery {
    public function getElements() {
        return kernel::single('ome_delivery_template_delivery')->defaultElements();
    }
}