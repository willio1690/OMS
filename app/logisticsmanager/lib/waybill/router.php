<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 电子面单平台路由
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: 2014-01-28 10:46Z
 */
class logisticsmanager_waybill_router
{
    // 电子面单渠道ID
    private $_channel_id = NULL;

    private $_shop_id = NULL;

    const _APP_NAME = 'logisticsmanager';

    /**
     * 设置ChannelId
     * @param mixed $channel_id ID
     * @return mixed 返回操作结果
     */

    public function setChannelId($channel_id)
    {
        $this->_channel_id = $channel_id;

        return $this;
    }

    /**
     * 设置ShopId
     * @param mixed $shop_id ID
     * @return mixed 返回操作结果
     */
    public function setShopId($shop_id)
    {
        $this->_shop_id = $shop_id;

        return $this;
    }

    private function getChannel()
    {
        static $channel;

        if($channel[$this->_channel_id]) return $channel[$this->_channel_id];

        $channel[$this->_channel_id] = app::get(self::_APP_NAME)->model('channel')->dump(array('channel_id'=>$this->_channel_id,'status' => 'true'));

        return $channel[$this->_channel_id];
    }

    private function getShop()
    {
        static $shop;

        if (!$this->_shop_id) return array();

        if ($shop[$this->_shop_id]) return $shop[$this->_shop_id];

        $shop[$this->_shop_id] = app::get('ome')->model('shop')->dump(array('shop_id' => $this->_shop_id));

        return $shop[$this->_shop_id];
    }

    /**
     * 获取Platform
     * @param mixed $channel channel
     * @return mixed 返回结果
     */
    public function getPlatform($channel)
    {
        $classname = sprintf("logisticsmanager_waybill_%s",$channel['channel_type']);

        try {

            if (class_exists($classname)){
                $platform = kernel::single($classname); 

                if (!$platform instanceof logisticsmanager_waybill_interface) {
                    return false;
                }

                return $platform;
            } 

        } catch (Exception $e) {
            // do nothing
        }

        return false;
    }

    /**
     * __call
     * @param mixed $method method
     * @param mixed $args args
     * @return mixed 返回值
     */
    public function __call($method,$args)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        
        if (!$this->_channel_id) {
            $rs['msg'] = '未设置电子面单渠道ID';
            return $rs;
        }

        $channel = $this->getChannel();
        if (!$channel) {
            $rs['msg'] = '电子面单渠道不存在';
            return $rs;
        }

        $platform = $this->getPlatform($channel);
        
        if (!$platform) {
            $rs['msg'] = 'initial platform error';
            return $rs;
        }
        $platform->setChannel($channel);

        $shop = $this->getShop();
        $platform->setShop($shop);

        return call_user_func_array(array($platform,$method), $args);
    }
}