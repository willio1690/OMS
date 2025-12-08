<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 备货单
 *
 */
class ome_service_template_stock {
    public function getElements() {
        return kernel::single('ome_delivery_template_stock')->defaultElements();
    }
}