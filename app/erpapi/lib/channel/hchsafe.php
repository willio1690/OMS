<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_channel_hchsafe extends erpapi_channel_abstract {
    public $channel = array();

    // 暂不开放:360buy
    static private $__support = array('taobao','360buy','luban');

    /**
     * 初始化
     * @param mixed $node_id ID
     * @param mixed $node_type node_type
     * @return mixed 返回值
     */
    public function init($node_id,$node_type){
        if (!in_array($node_type,self::$__support)) return false;

        $this->__adapter = 'matrix';
        $this->__platform = $node_type;

        return true;
    }
}