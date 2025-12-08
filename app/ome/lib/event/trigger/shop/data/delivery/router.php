<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 获取数据
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class ome_event_trigger_shop_data_delivery_router
{
    private $__shop_id;

    private static $shop_mapping = array(
        'shopex_b2b'  => 'shopex',
        'shopex_b2c'  => 'shopex',
        'ecos.b2c'    => 'shopex',
        'ecos.dzg'    => 'shopex',
        'ecos.taocrm' => 'shopex',
        'bbc'         => 'shopex',
        'qq_buy'      => 'qqbuy',
        'ecshop_b2c'  => 'shopex',
        'public_b2c'  =>'shopex',
        'shopex_fy'   => 'shopex',
        'shopex_penkrwd'=>'shopex',
        'ecos.b2b2c.stdsrc'=>'shopex',
        'ecos.ecshopx' => 'shopex',
        'website_d1m'  => 'websited1m',
        'website_v2'   => 'website',
    );

    public function set_shop_id($shop_id)
    {
        $this->__shop_id = $shop_id;

        return $this;
    }

    public function __call($method,$args)
    {
        $platform = kernel::single('ome_event_trigger_shop_data_delivery_common');

        if ($this->__shop_id) {
            $shop = $this->get_shop();

            try {
                $node_type = $shop['node_type'];

                if (self::$shop_mapping[$node_type]) $node_type = self::$shop_mapping[$node_type];

                $platform = kernel::single('ome_event_trigger_shop_data_delivery_'.$node_type);

                
            } catch (Exception $e) {}
        }

        return call_user_func_array(array($platform,$method), $args);
    }

    private function get_shop()
    {
        static $shops;

        if ($shops[$this->__shop_id]) return $shops[$this->__shop_id];

        $shops[$this->__shop_id] = app::get('ome')->model('shop')->dump($this->__shop_id);

        return $shops[$this->__shop_id];
    }
}
