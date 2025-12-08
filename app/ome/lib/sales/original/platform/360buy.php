<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_sales_original_platform_360buy extends ome_sales_original_platform_factory {
    protected $platfomAmountType = [
        'pingTaiChengDanYouHuiQuan',
    ];
    protected $platfomPayAmountType = [
        'calcPlatformPayDiscounts',
    ];

}