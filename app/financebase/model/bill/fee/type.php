<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
* 费用项模型类
* @author 334395174@qq.com
* @version 0.1
*/
class financebase_mdl_bill_fee_type extends dbeav_model
{

    /**
     * modifier_bill_type
     * @param mixed $val val
     * @return mixed 返回值
     */

    public function modifier_bill_type($val)
    {
        return $val ? '支出' : '收入';
    }

}