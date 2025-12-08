<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: yaokangming
 * Date: 2019/5/10
 * Time: 17:08
 */
class console_mdl_useful_life_log extends dbeav_model
{

    /**
     * modifier_type_id
     * @param mixed $col col
     * @return mixed 返回值
     */

    public function modifier_type_id($col) {
        $ioType = kernel::single('ome_iostock')->get_iostock_types();
        return $ioType[$col]['info'];
    }
}