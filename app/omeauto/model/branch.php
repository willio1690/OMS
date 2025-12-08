<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeauto_mdl_branch extends ome_mdl_branch {
    function __construct($app){
        parent::__construct(app::get('ome'));
    }
}
?>