<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

abstract class erpapi_front_response_o2o_abstract extends erpapi_front_response_abstract
{
    const MAX_LIMIT = 100;

    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct($app)
    {
        parent::__construct($app);

        if (!$_SESSION['branch_id']) {
            throw new Exception("无权查看：管理员未绑定门店");
        }
    }
}
