<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 补发订单业务处理
 *
 * @category 
 * @package 
 * @author 
 * @version $Id: Z
 */
class erpapi_shop_response_process_reissue
{
        /**
     * 补发订单查询业务处理
     */
    public function query($sdf)
    {
        // 实现补发订单查询业务逻辑
        // 参数: platform_order_bn, oids, seller_nick, auto_confirm, good_is_gift, original_warehouse

        // 验证订单是否存在
        $orderModel = app::get('ome')->model('orders');
        $filter = array(
            'shop_id' => $sdf['shop_id'],
            'order_bn' => $sdf['order_bn']
        );

        $orderInfo = $orderModel->dump($filter, 'order_id,order_bn,platform_order_bn,shop_id,status,process_status,ship_status,pay_status');

        if (empty($orderInfo)) {
            return array('rsp' => 'fail', 'msg' => '订单不存在，订单号：' . $sdf['order_bn'], 'data' => array());
        }

        // 查询order_objects表获取oid信息
        $orderObjectsModel = app::get('ome')->model('order_objects');
        $filter = array(
            'order_id' => $orderInfo['order_id'],
            'oid' => $sdf['oids']
        );
        
        $orderObjects = $orderObjectsModel->getList('obj_id,oid,shop_goods_id,name', $filter);
        
        if (empty($orderObjects)) {
            return array('rsp' => 'fail', 'msg' => '未找到指定的子单商品', 'data' => array());
        }
        
        // 获取obj_id列表
        $objIds = array_column($orderObjects, 'obj_id');
        
        // 查询order_items表获取商品详情
        $orderItemsModel = app::get('ome')->model('order_items');
        $itemFilter = array(
            'obj_id' => $objIds
        );
        
        $orderItems = $orderItemsModel->getList('obj_id,product_id,product_name,bn,nums,price,amount,shop_product_id,item_type,sendnum', $itemFilter);
        
        // 构建obj_id到order_objects信息的映射
        $objToOrderObject = array();
        foreach ($orderObjects as $obj) {
            $objToOrderObject[$obj['obj_id']] = $obj;
        }
        
        // 构建商品信息
        $filteredItems = array();
        foreach ($orderItems as $item) {
            $orderObject = $objToOrderObject[$item['obj_id']];
            $filteredItems[] = array(
                'oid' => $orderObject['oid'],
                'shop_goods_id' => $orderObject['shop_goods_id'],
                'name' => $orderObject['name'],
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'bn' => $item['bn'],
                'nums' => $item['nums'],
                'price' => $item['price'],
                'amount' => $item['amount'],
                'shop_product_id' => $item['shop_product_id'],
                'item_type' => $item['item_type'],
                'sendnum' => $item['sendnum']
            );
        }

        if (empty($filteredItems)) {
            return array('rsp' => 'fail', 'msg' => '未找到指定的子单商品', 'data' => array());
        }

        // 构建返回数据
        $shippingList = array();
        foreach ($filteredItems as $item) {
            $shippingList[] = array(
                'quantity' => $item['sendnum'], // 发货数量
                'goodName' => $item['name'], // 货品名称
                'subBizOrderId' => $item['oid'], // 子订单id
                'goodId' => $item['shop_goods_id'], // 货品id
                'goodSkuId' => $item['shop_product_id'], // 货品sku id
                'goodType' => ($item['item_type'] == 'gift') ? '1' : '0', // 0：主商品；1：赠品
                'goodPicUrl' => '', // 货品图片URL，暂时为空
            );
        }

        $resultData = array(
            'bizOrderId' => $sdf['order_bn'], // 主订单id
            'shippingAppkey' => '商派订单管理系统', // 发货软件
            'shippingList' => $shippingList, // 发货单据列表
        );

        return array('rsp' => 'succ', 'msg' => '查询成功', 'data' => $resultData);
    }
    
    /**
     * 补发订单取消业务处理
     */
    public function cancel($sdf)
    {
        // 实现补发订单取消业务逻辑
        // 参数: seller_nick, platform_order_bn, order_bn
        // 调用相应的模型和服务类
        
        // 验证订单是否存在
        $orderModel = app::get('ome')->model('orders');
        $filter = array(
            'shop_id' => $sdf['shop_id'],
            'order_bn' => $sdf['order_bn'],
            'platform_order_bn' => $sdf['platform_order_bn']
        );
        
        $orderInfo = $orderModel->dump($filter, 'order_id,order_bn,platform_order_bn,shop_id,status,process_status,ship_status,pay_status');
        
        if (empty($orderInfo)) {
            return array('rsp' => 'fail', 'msg' => '订单不存在，订单号：' . $sdf['order_bn'] . '，平台订单号：' . $sdf['platform_order_bn'], 'data' => array());
        }
        
        // 判断订单是否为可取消状态
        if ($orderInfo['status'] == 'dead') {
            return array('rsp' => 'fail', 'msg' => '订单已取消，无法重复取消', 'data' => array());
        }
        
        if ($orderInfo['ship_status'] != '0') {
            return array('rsp' => 'fail', 'msg' => '订单已发货，无法取消', 'data' => array());
        }
        
        if (!in_array($orderInfo['process_status'], array('unconfirmed', 'confirmed', 'splited', 'splitting'))) {
            return array('rsp' => 'fail', 'msg' => '订单状态不允许取消', 'data' => array());
        }
        
        // 调用订单取消方法
        $orderModel = app::get('ome')->model('orders');
        $memo = '补发订单取消';
        $result = $orderModel->cancel($orderInfo['order_id'], $memo, false, 'async', true);
        
        if ($result['rsp'] == 'success') {
            return array('rsp' => 'succ', 'msg' => '取消成功', 'data' => array(
                'tid' => $sdf['platform_order_bn'],
                'bizId' => $sdf['order_bn']
            ));
        } else {
            return array('rsp' => 'fail', 'msg' => $result['msg'] ? $result['msg'] : '取消失败', 'data' => array());
        }
    }
} 