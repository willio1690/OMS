<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2020/9/4 16:15:27
 * @describe: 经销商
 * ============================
 */
class dealer_mdl_business extends dbeav_model {
    public $has_many = array(
        'business_branch' => 'business_branch'
    );
}