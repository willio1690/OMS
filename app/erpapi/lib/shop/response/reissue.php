<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 补发订单响应处理
 *
 * @category
 * @package
 * @author
 * @version $Id: Z
 */
class erpapi_shop_response_reissue extends erpapi_shop_response_abstract
{
    /**
     * 补发订单查询响应处理
     */

    public function query($params)
    {
        // 数据转换和参数验证
        // 参数: tid, oids, seller_nick, auto_confirm, good_is_gift, original_warehouse

        $this->__apilog['title'] = '店铺(' . $this->__channelObj->channel['name'] . ')补发订单查询';
        $this->__apilog['original_bn'] = $params['tid'] ? $params['tid'] : '';
        $this->__apilog['result']['data'] = array();

        // 检查必要参数是否存在
        if (empty($params['tid'])) {
            $this->__apilog['result']['msg'] = '订单号不能为空';
            return false;
        }
        if (empty($params['oids'])) {
            $this->__apilog['result']['msg'] = '子单号不能为空';
            return false;
        }

        // 解析oids参数
        $oids = json_decode($params['oids'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->__apilog['result']['msg'] = '子单号格式错误';
            return false;
        }
        if (empty($oids)) {
            $this->__apilog['result']['msg'] = '子单号不能为空';
            return false;
        }

        $sdf = array(
            'order_bn' => $params['tid'], // tid转换为order_bn
            'oids' => $oids, // 子单号列表（已解码）
            'seller_nick' => $params['seller_nick'],
            'auto_confirm' => $params['auto_confirm'],
            'good_is_gift' => $params['good_is_gift'],
            'original_warehouse' => $params['original_warehouse'],
            'shop_id' => $this->__channelObj->channel['shop_id'], // 添加shop_id
        );
        return $sdf;
    }
    
    /**
     * 补发订单取消响应处理
     */
    public function cancel($params)
    {
        // 数据转换和参数验证
        // 参数: seller_nick, tid, bizId
        
        $this->__apilog['title'] = '店铺(' . $this->__channelObj->channel['name'] . ')补发订单取消[补发单：' . $params['bizId'] . ']';
        $this->__apilog['original_bn'] = $params['tid'];
        $this->__apilog['result']['data'] = array('tid' => $params['tid'], 'bizId' => $params['bizId']);
        
        // 检查必要参数是否存在
        if (empty($params['tid'])) {
            $this->__apilog['result']['msg'] = '主订单id不能为空';
            return false;
        }
        if (empty($params['bizId'])) {
            $this->__apilog['result']['msg'] = '补发单据id不能为空';
            return false;
        }
        
        $sdf = array(
            'seller_nick' => $params['seller_nick'],
            'platform_order_bn' => $params['tid'], // tid转换为platform_order_bn
            'order_bn' => $params['bizId'], // bizId转换为order_bn
            'shop_id' => $this->__channelObj->channel['shop_id'], // 添加shop_id
        );
        return $sdf;
    }
} 