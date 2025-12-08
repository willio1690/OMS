<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 华强宝
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_channel_unionpay extends erpapi_channel_abstract 
{
    public $channel;

    /**
     * 初始化
     * @param mixed $node_id ID
     * @param mixed $hqepay_id ID
     * @return mixed 返回值
     */

    public function init($node_id,$hqepay_id)
    {
        $this->__adapter = '';
        $this->__platform = '';



        return true;
    }
}