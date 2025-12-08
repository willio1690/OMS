<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

abstract class invoice_order_front_abstract {

    #获取主表特殊信息
    abstract public function getMain($main);
    #获取明细信息
    abstract public function getItems($main);
    #人工操作
    abstract public function operateTax($arr);
}