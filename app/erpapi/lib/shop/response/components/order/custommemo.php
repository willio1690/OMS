<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
* 客户备注
*
* @author chenping<chenping@shopex.cn>
* @version $Id: custommemo.php 2013-3-12 17:23Z
*/
class erpapi_shop_response_components_order_custommemo extends erpapi_shop_response_components_order_abstract
{
    const _APP_NAME = 'ome';

    /**
     * 订单格式转换
     *
     * @return void
     * @author 
     **/
    public function convert()
    {
        $custom_mark = $this->_platform->_ordersdf['custom_mark'];

        if (!is_null($custom_mark) && $custom_mark !== '') {
            $custommemo[] = array(
                'op_name' => $this->_platform->__channelObj->channel['name'],
                'op_time' => date("Y-m-d H:i:s"),
                'op_content' => htmlspecialchars($custom_mark),
            );
        }

        if (in_array($this->_platform->__channelObj->channel['node_type'],array('taobao','paipai')) 
            && 'true' ==  app::get('ome')->getConf('ome.checkems')
            && 'ems' == strtolower($this->_platform->_ordersdf['shipping']['shipping_name'])) {
            $custommemo[] = array(
                'op_name'    =>$this->_platform->__channelObj->channel['name'],
                'op_time'    =>date("Y-m-d H:i:s",time()),
                'op_content' =>'系统：用户选择了 EMS 的配送方式',
            );
        }

        if ($custommemo)
            $this->_platform->_newOrder['custom_mark'] = serialize($custommemo);
    }

    /**
     * 更改客户备注
     *
     * @return void
     * @author 
     **/
    public function update()
    {
        $old_custom_mark = array();
        if ($this->_platform->_tgOrder['custom_mark'] && is_string($this->_platform->_tgOrder['custom_mark'])) {
            $old_custom_mark = unserialize($this->_platform->_tgOrder['custom_mark']);
        }

        $last_custom_mark = array();
        foreach ((array) $old_custom_mark as $key => $value) {
            if ( strstr($value['op_time'], "-") ) $value['op_time'] = strtotime($value['op_time']);

            if ( intval($value['op_time']) > intval($last_custom_mark['op_time']) && ($value['op_name'] == $this->_platform->__channelObj->channel['name'] || in_array($this->_platform->__channelObj->channel['node_type'],ome_shop_type::shopex_shop_type()))) {
                $last_custom_mark = $value;
            }
        }

        $custom_mark = $this->_platform->_ordersdf['custom_mark'];
        if (!is_null($custom_mark) && $custom_mark !== '' && $last_custom_mark['op_content'] != $custom_mark) {
            $custom = (array) $old_custom_mark;
            $custom[] = array(
                'op_name'    => $this->_platform->__channelObj->channel['name'],
                'op_content' => $custom_mark,
                'op_time'    => date('Y-m-d H:i:s')
            );
        }

        if ($custom) {
            $this->_platform->_newOrder['custom_mark'] = serialize($custom);
        }
    }

}