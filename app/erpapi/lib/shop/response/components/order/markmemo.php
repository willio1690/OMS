<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
* 商家备注
*
* @author chenping<chenping@shopex.cn>
* @version $Id: markmemo.php 2013-3-12 17:23Z
*/
class erpapi_shop_response_components_order_markmemo extends erpapi_shop_response_components_order_abstract
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
        $mark_text = $this->_platform->_ordersdf['mark_text'];

        if (!is_null($mark_text) && $mark_text !== '') {
           $markmemo[] = array(
                'op_name' => $this->_platform->__channelObj->channel['name'],
                'op_time' => date("Y-m-d H:i:s",time()),
                'op_content' => htmlspecialchars($mark_text),
            );
            $this->_platform->_newOrder['mark_text'] = serialize($markmemo);
        }
    }

    /**
     * 更新订单备注
     *
     * @return void
     * @author 
     **/
    public function update()
    {
        $old_mark_text = array();
        if ($this->_platform->_tgOrder['mark_text'] && is_string($this->_platform->_tgOrder['mark_text'])) {
            $old_mark_text = unserialize($this->_platform->_tgOrder['mark_text']);
        }

        $last_mark_text = array();
        foreach ((array) $old_mark_text as $key => $value) {
            if ( strstr($value['op_time'], "-") ) $value['op_time'] = strtotime($value['op_time']);

            if ( intval($value['op_time']) > intval($last_mark_text['op_time']) && ($value['op_name'] == $this->_platform->__channelObj->channel['name'] || in_array($this->_platform->__channelObj->channel['node_type'],ome_shop_type::shopex_shop_type()) ) ) {
                $last_mark_text = $value;
            }
        }

        $mark_text = $this->_platform->_ordersdf['mark_text'];
        if (!is_null($mark_text) && $mark_text !== '' && $last_mark_text['op_content'] != $mark_text) {
            $mark = (array) $old_mark_text;
            $mark[] = array(
                'op_name'    => $this->_platform->__channelObj->channel['name'],
                'op_content' => $mark_text,
                'op_time'    => date('Y-m-d H:i:s')
            );

            //备注去掉换行比
            $order_mark_text = preg_split("/[\n]--/",$mark_text);
            if (count($order_mark_text)>1) {
                $mark_data = preg_split('/[\n]/',$order_mark_text[0]);
                if ($mark_data[1] == $last_mark_text['op_content']) {
                    unset($mark);
                }
            }
        }
        
        if($mark){
            $this->_platform->_newOrder['mark_text'] = serialize($mark);
        }
    }
}