<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * Created by PhpStorm.
 * User: yaokangming
 * Date: 2018/9/14
 * Time: 10:06
 */

class ome_order_bool_extendstatus
{
    #是否修改订单地址
    const __MODIFY_ADDRESS        = 0x0001;
    #是否编辑过商品 或 修改订单折扣金额
    const __GOODS_PRICE           = 0x0002;
    #是否修改了配送费
    const __MODIFY_SHIPPING       = 0x0004;
    #是否导出过了订单信息
    const __EXPORT_ORDER          = 0x0008;
    #是否需要更换sku
    const __UPDATESKU_ORDER       = 0x10000;
}