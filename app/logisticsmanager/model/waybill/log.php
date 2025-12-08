<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_mdl_waybill_log extends dbeav_model{
    function gen_id(){
        return uniqid();
    }
}