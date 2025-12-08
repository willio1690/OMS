<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_iostock extends dbeav_model
{


    function modifier_bill_type($col){
        $isoMdl = app::get('taoguaniostockorder')->model('iso');
        $bill_types = $isoMdl::$bill_type;
        return $bill_types[$col] ? : $col;
    }
}
