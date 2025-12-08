<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单标签规则抽象类
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
abstract class omeauto_order_label_abstract
{
    /**
     * 保存规则内容
     * 
     * @var Array
     */
    protected $content;
    
    /**
     * 设置已经创建好的配置内容
     */
    public function setRole($params)
    {
        $this->content = $params;
    }
}