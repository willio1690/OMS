<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 店铺商品处理工厂类
 * 
 * @author wangbiao@shopex.cn
 * @version 0.1
 */
class tbo2o_shop_service_factory
{
    function __construct(&$app)
    {
        $this->app = $app;
    }
    
    /**
     * 工厂方法
     * @todo 只支持淘宝类型店铺
     *
     * @return void
     * @author 
     */
    public static function createFactory($shop_type, $business_type='zx')
    {
        switch ($shop_type)
        {
            case 'taobao':
                if ($business_type == 'fx')
                {
                    return kernel::single('tbo2o_shop_service_tbfx');
                }
                else
                {
                    return kernel::single('tbo2o_shop_service_taobao');
                }
                break;
            default:
                return false;
                break;
        }
    }
}