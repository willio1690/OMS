<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class invoice_order_front_router {
    private $obj;

    public function __construct($source)
    {
        try {
            $objName = 'invoice_order_front_' . $source;
            if (class_exists($objName)) {
                $this->obj = kernel::single($objName);
            }
        } catch (Exception $e) {}
    }

    #获取主表特殊信息
    public function getMain($main) {
        $data = array();
        if ($this->obj) {
            $data = $this->obj->getMain($main);
        }
        return $data;
    }
    #获取明细信息
    public function getItems($main) {
        $data = array();
        if ($this->obj) {
            $data = $this->obj->getItems($main);
        }
        return $data;
    }
    #人工操作
    public function operateTax($arr) {
        $ret = array(true, ['msg'=>'操作完成']);
        if ($this->obj) {
            $ret = $this->obj->operateTax($arr);
        }
        return $ret;
    }
}