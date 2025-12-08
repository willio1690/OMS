<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 店铺
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_channel_shop extends erpapi_channel_abstract 
{
    public $channel;

    private static $shop_mapping = array(
        'shopex_b2b'  => 'shopex_fxw',
        'shopex_b2c'  => 'shopex_485',
        'ecos.b2c'    => 'shopex_ecstore',
        'ecos.dzg'    => 'shopex_dzg',
        'ecos.taocrm' => 'shopex_taocrm',
        'bbc'         => 'shopex_bbc',
        'qq_buy'      => 'qqbuy',
        'ecshop_b2c'  => 'shopex_ecshop',
        'public_b2c'  => 'shopex_publicb2c',
        'shopex_fy'     => 'shopex_fy',
        'shopex_penkrwd'=>'shopex_penkrwd',
        'ecos.b2b2c.stdsrc'=>'shopex_bbc',
        'ecos.ecshopx' => 'shopex_ecshopx',
        'pekon'             => 'pos_pekon',
        'website_v2'         => 'website',

    );

    private static $versionm = array(
        'shopex_b2c' => array(      // 485   前端版本 => 淘管版本
            '1' => '1',
            '2' => '2'
        ),
        'shopex_b2b' => array(      // b2b
            '1' => '1',
            '3.2' => '2',
        ),
        'ecos.b2c' => array(        // ecstore
            '1' => '1',
            '2' => '2',
            '3' => '3',
        ),
        'ecos.dzg' => array(        // 店掌柜
            '1' => '1',
            '2' => '2',
        ),
        'shopex_fy' => array(        // 全民分销
            '1.0' => '2',
            '2.0' => '2',
        ),
        'ecos.ecshopx' => array(        //源源客
            '1.0' => '2',
            '2.0' => '2',
        ),
    );

    /**
     * 初始化
     * @param mixed $node_id ID
     * @param mixed $shop_id ID
     * @return mixed 返回值
     */

    public function init($node_id,$shop_id)
    {
        // $shopModel = app::get('ome')->model('shop');

        $filter = $shop_id ? array('shop_id'=>$shop_id) : array('node_id'=>$node_id);

        // $shop = $shopModel->dump($filter);
        $shop = $this->get_shop($filter);
        if($shop['node_type']=='shopex_fy'){
            $shop['api_version']='1.0';//全民分销默认1.0
        }
        if (!$shop || !$shop['node_id']) return false;

        if ($shop['node_type'] == 'bbc') {
            $shop['matrix_api_v'] = '2.0';
        }

        $this->channel = $shop;

        $this->__adapter = 'matrix';
        if (self::$shop_mapping[$shop['node_type']]) {
            $this->__platform = self::$shop_mapping[$shop['node_type']];
        } else {
            $this->__platform = $shop['node_type'];

            if ($shop['node_type'] == 'taobao' && $shop['tbbusiness_type'] == 'B') {
                $this->__platform = 'tmall';
            }
        }
        $this->__platform_business = $shop['business_type'];

        $this->set_ver($shop['node_type'],$shop['api_version']);

        return true;
    }

    /**
     * 获取淘管对应版本
     * 
     * @param String $node_type 店铺类型
     * @param String $api_version 前端店铺版本
     **/
    private function set_ver($node_type,$api_version)
    {
        if(isset(self::$versionm[$node_type])) {
            $mapping = self::$versionm[$node_type];
            krsort($mapping);

            foreach ($mapping as $s_ver => $t_ver) {
                if (version_compare($api_version, $s_ver,'>=')) {
                    $this->__ver = $t_ver; break;
                }
            }

        }
    }

    private function get_shop($filter)
    {
        static $shops;

        $key = sprintf('%u',crc32(serialize($filter)));

        if ($shops[$key]) return $shops[$key];

        $shops_detail = app::get('ome')->model('shop')->dump($filter);
        if ($shops_detail['config']){
            $shops_detail['config'] = @unserialize($shops_detail['config']);
        }
        if (is_string($shops_detail['addon']) && $shops_detail['addon']){
            $shops_detail['addon'] = @unserialize($shops_detail['addon']);
        }
        $shops[$key] = $shops_detail;
        return $shops[$key];
    }
}