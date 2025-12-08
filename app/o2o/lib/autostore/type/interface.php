<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

interface o2o_autostore_type_interface {
    /**
     * 检查传入参数
     * 
     * @param array $params
     * @return boolean
     */
    public function checkParams(&$params);

    /**
     * 处理门店优选返回门店仓ID
     * 
     * @param array $filter
     * @return boolean
     */
    public function process($filter, &$error_msg);
}