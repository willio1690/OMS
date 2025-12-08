<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_order_products{
    /**
     * 获取订单编辑时每种objtype的显示内容定义
     * @access public
     * @param int $reship_id 退货单ID
     */
    public function get_view_list(){
        $conf_list = array(
            'pkg'       => $this->view_pkg(),
            'gift'      => $this->view_gift(),
            'goods'     => $this->view_goods(),
            'giftpackage'   => $this->view_giftpackage(),
            'lkb'   => $this->view_lkb(),
            'pko'   => $this->view_pko(),
        );
        return $conf_list;
    }

    public function view_pkg(){
        $config = array(
           'app' => 'ome',
           'html' => 'admin/order/products/pkg_view.html',
        );
        return $config;
    }
    
    public function view_gift(){
        $config = array(
           'app' => 'ome',
           'html' => 'admin/order/products/gift_view.html',
        );
        return $config;
    }
    
    /**
     * view_goods
     * @return mixed 返回值
     */
    public function view_goods(){
        $config = array(
           'app' => 'ome',
           'html' => 'admin/order/products/goods_view.html',
        );
        return $config;
    }
    
    /**
     * view_giftpackage
     * @return mixed 返回值
     */
    public function view_giftpackage(){
        $config = array(
           'app' => 'ome',
           'html' => 'admin/order/products/giftpackage_view.html',
        );
        return $config;
    }
    
    /**
     * view_lkb
     * @return mixed 返回值
     */
    public function view_lkb(){
        $config = array(
           'app' => 'ome',
           'html' => 'admin/order/products/lkb_view.html',
        );
        return $config;
    }
    
    /**
     * view_pko
     * @return mixed 返回值
     */
    public function view_pko(){
        $config = array(
                'app' => 'ome',
                'html' => 'admin/order/products/pko_view.html',
        );
        return $config;
    }
    
}