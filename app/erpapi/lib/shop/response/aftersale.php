<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @desc    售后单数据处理
 * @author: jintao
 * @since: 2016/7/21
 */
class erpapi_shop_response_aftersale extends erpapi_shop_response_abstract {

    protected function _formatAddParams($params) {
        // 售后物流
        if(is_string($params['logistics_info'])) {
            $logistics_info = json_decode($params['logistics_info'], true);
            if ($logistics_info) {
                $arrProcessData = array(
                    'shipcompany' => $logistics_info['logi_company'],
                    'logino' => $logistics_info['logi_no'],
                );
                $process_data = serialize($arrProcessData);
            }
        }
        $sdf = array(
            'return_bn' => $params['return_bn'],
            'order_bn' => $params['order_bn'],
            'status' => $params['status'],
            'member_uname' => $params['member_uname'],
            'return_product_items' => is_string($params['return_product_items']) ? json_decode($params['return_product_items'], true) : $params['return_product_items'],
            'process_data' => $process_data,
            'attachment' => $params['attachment'] ? $params['attachment'] : null,
            'title' => $params['title'],
            'content' => $params['content'],
            'comment' => $params['comment'],
            'memo' => $params['memo'],
            'add_time' => $params['add_time'] ? kernel::single('ome_func')->date2time($params['add_time']) : time(),
            'source'          => 'matrix',
            'shop_type'         =>$this->__channelObj->channel['shop_type'],
            'org_id' => $this->__channelObj->channel['org_id'],
            'refund_version_change' => false,
        );
        return $sdf;
    }

    protected function _dealReturnProductItems($returnProductItems, $order) {
        $returnNum = array();
        foreach($returnProductItems as $val) {
            $returnNum[$val['bn']] = $val;
        }
        $orderNumPrice = kernel::single('ome_order_object_item')->getNumPrice(array($order['order_id']));
        $deliOrderModel = app::get('ome')->model('delivery_order');
        $deliOrder = $deliOrderModel->dump(array('order_id'=>$order['order_id']));
        if ($deliOrder) {
            $delivery_id = $deliOrder['delivery_id'];
        }
        $oDelivery = app::get('ome')->model('delivery');
        $delivery = $oDelivery->dump(array('delivery_id'=>$delivery_id),'branch_id');
        $return = $productSend = array();

      
        foreach($orderNumPrice[$order['order_id']] as $obj) {
            
            //申请售后的销售物料
            if($returnNum[$obj['bn']])
            {
                //item层基础物料明细
                $aftersale_num    = $returnNum[$obj['bn']]['num'];
                foreach($obj['order_items'] as $item)
                {
                    $radio    = ($item['nums'] / $obj['quantity']);
                    $num      = intval($aftersale_num * $radio);
               
                    $price    = $returnNum[$obj['bn']]['price'];
                    if($obj['obj_type'] == 'pkg')
                    {
                        //当PKG组合下面有多个商品时，这个price金额是错误的
                        //$price    = $price/$num;
                        
                        //PKG退货单价 = 申请退款金额 / obj层下面的所有item的数量之和
                        $price = sprintf('%.2f', $price / $obj['item_nums']);
                    }
                    
                    $return[] = array(
                            'bn'            => $item['bn'],
                            'price'         => $price,
                            'num'           => $num,
                            'branch_id'     => $delivery['branch_id'],
                            'order_item_id' => $item['item_id'],
                    );
                    
                    $productSend[$item['bn']] += $item['sendnum'];
                }
                
                unset($returnNum[$obj['bn']]);
                continue;
            }
            
            /***
            if($obj['obj_type'] == 'pkg') {
                if($returnNum[$obj['bn']]) {
                    $radio = $returnNum[$obj['bn']]/$obj['quantity'];
                    foreach($obj['order_items'] as $item) {
                        $num = (int) $radio * $item['nums'];
                        $price = $obj['sale_price']/$obj['item_nums'];
                        $return[] = array(
                            'bn' => $item['bn'],
                            'price' => sprintf('%.2f', $price),
                            'num' => $num,
                            'branch_id'=>$delivery['branch_id'],
                        );
                        $productSend[$item['bn']] += $item['sendnum'];
                    }
                    unset($returnNum[$obj['bn']]);
                    continue;
                }
            }
            foreach($obj['order_items'] as $item) {
                if($returnNum[$item['bn']]) {
                    $return[] = array(
                        'bn' => $item['bn'],
                        'price' => sprintf('%.2f', $item['sale_price'] / $item['nums']),
                        'num' => $returnNum[$item['bn']],
                        'branch_id'=>$delivery['branch_id'],
                    );
                    $productSend[$item['bn']] += $item['sendnum'];
                    unset($returnNum[$item['bn']]);
                }
            }
            ***/
        }

     
        if($returnNum) {
            return array('result'=>false, 'bn' => array_keys($returnNum));
        }
        $productData = kernel::single('material_basic_select')->getlist('material_name,material_bn,bm_id', array('material_bn'=>array_keys($productSend)));

        
        $product = array();
        foreach($productData as $pd) {
            $product[$pd['bn']] = array(
                'product_id'    => $pd['product_id'],
                'bn'            => $pd['bn'],
                'name'          => $pd['name'],
            );
        }
        $items = array();
        foreach($return as $value) {
            if($items[$value['bn']]) {
                $itemNum = $items[$value['bn']]['num'];
                $items[$value['bn']]['num'] += $value['num'];
                $itemPrice = ($items[$value['bn']]['price'] * $itemNum + $value['price'] * $value['num'])/$items[$value['bn']]['num'];
                $items[$value['bn']]['price'] = sprintf('%.2f', $itemPrice);
            } else {
                $items[$value['bn']] = $value;
                $items[$value['bn']]['sendNum'] = $productSend[$value['bn']];
                $items[$value['bn']]['product_id'] = $product[$value['bn']]['product_id'];
                $items[$value['bn']]['name'] = $product[$value['bn']]['name'];
            }
        }


        return array('result'=>true, 'items'=>array_values($items));
    }

    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function add($params) {
        $sdf = $this->_formatAddParams($params);
        $this->__apilog['title'] = '前端店铺售后申请V1[售后单号：'.$sdf['return_bn'].' ]';
        $this->__apilog['original_bn'] = $sdf['order_bn'];
        $shopId = $sdf['shop_id'] = $this->__channelObj->channel['shop_id'];
        $sdf['shop_type'] = $this->__channelObj->channel['shop_type'];
        $sdf['node_type'] = $this->__channelObj->channel['node_type'];
        // 售后单
        $returnModel = app::get('ome')->model('return_product');
        $tgReturn = $returnModel->getList('return_id,status', array('shop_id'=>$shopId,'return_bn'=>$sdf['return_bn']));
        if ($this->_checkeditAftersale($tgReturn[0],$sdf['refund_version_change'])){
            $this->__apilog['result']['msg'] = '售后单已经存在';
            return false;
        }
        // 订单
        $orderModel = app::get('ome')->model('orders');
        $tgOrder = $orderModel->getList('order_id,order_bn,ship_status,member_id', array('shop_id'=>$shopId,'order_bn'=>$sdf['order_bn']), 0 , 1);
        if (empty($tgOrder[0])) {
            $this->__apilog['result']['msg'] = '订单不存在';
            return false;
        }
        unset($sdf['order_bn']);
        $sdf['order'] = $tgOrder[0];
        if($sdf['return_product_items'] && is_array($sdf['return_product_items'])) {
            $rpi = $this->_dealReturnProductItems($sdf['return_product_items'], $sdf['order']);
            if($rpi['result']) {
                $sdf['return_product_items'] = $rpi['items'];
            } else {
                $this->__apilog['result']['msg'] = '订单明细中没有销售物料：' . implode(',', $rpi['bn']);
                return false;
            }
        }
        $sdf['member_id'] = (int)$tgOrder[0]['member_id'];
        $deliOrderModel = app::get('ome')->model('delivery_order');
        $deliOrder = $deliOrderModel->dump(array('order_id'=>$tgOrder[0]['order_id']));
        if ($deliOrder) {
            $sdf['delivery_id'] = $deliOrder['delivery_id'];
        }
        return $sdf;
    }

    protected function _checkeditAftersale($tgReturn,$refund_version_change) {
        return $tgReturn;
    }

    protected function _formatStatusUpdateParams($params) {
        $sdf = array(
            'return_bn' => $params['return_bn'],
            'order_bn' => $params['order_bn'],
            'status' => $params['status'],
        );
        return $sdf;
    }

    /**
     * statusUpdate
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function statusUpdate($params) {
        $sdf = $this->_formatStatusUpdateParams($params);
        $this->__apilog['title'] = '前端店铺更新售后申请单状态V1[售后单号：'.$sdf['return_bn'].' ]';
        $this->__apilog['original_bn'] = $sdf['order_bn'];
        $shopId = $this->__channelObj->channel['shop_id'];
        // 订单
        $orderModel = app::get('ome')->model('orders');
        $tgOrder = $orderModel->getList('order_id', array('shop_id'=>$shopId,'order_bn'=>$sdf['order_bn']), 0, 1);
        if (empty($tgOrder)) {
            $this->__apilog['result']['msg'] = '没有订单' . $sdf['order_bn'];
            return false;
        }
        // 售后单
        $returnModel = app::get('ome')->model('return_product');
        $tgReturn = $returnModel->getList('return_id', array('return_bn'=>$sdf['return_bn'],'shop_id'=>$shopId), 0, 1);
        if (empty($tgReturn)) {
            $this->__apilog['result']['msg'] = '没有售后申请单' . $sdf['return_bn'];
            return false;
        }
        $sdf['return_id'] = $tgReturn[0]['return_id'];
        $returnItemModel = app::get('ome')->model('return_product_items');
        $tgReturnItems = $returnItemModel->getList('item_id, bn, num',array('return_id'=>$sdf['return_id']));
        if (empty($tgReturnItems)) {
            $this->__apilog['result']['msg'] = '售后申请单没有明细';
            return false;
        }
        $sdf['return_items'] = $tgReturnItems;
        if($sdf['status'] == 4) {
            $processFilter = array(
                'order_id' => $tgOrder[0]['order_id'],
                'return_id' => $sdf['return_id']
            );
            $processItems = app::get('ome')->model('return_process_items')->getList('item_id,product_id,branch_id,bn,name,num', $processFilter);
            if(empty($processItems)) {
                $this->__apilog['result']['msg'] = '售后单(process)缺少明细';
                return false;
            }
            $sdf['return_items'] = $processItems;
        }
        return $sdf;
    }

    protected function _formatLogisticsUpdate($params) {
        if(is_string($params['logistics_info'])) {
            $logistics_info = json_decode($params['logistics_info'], true);
            $process_data = array();
            $process_data['shipcompany'] = $logistics_info['logi_company'];
            $process_data['logino'] = $logistics_info['logi_no'];
        }
        $sdf = array(
            'order_bn' => $params['order_bn'],
            'return_bn' => $params['return_bn'],
            'process_data' => $process_data
        );
        return $sdf;
    }

    /**
     * logisticsUpdate
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function logisticsUpdate($params) {
        $sdf = $this->_formatLogisticsUpdate($params);
        $this->__apilog['title'] = '前端店铺更新物流信息V1[售后单号：'.$sdf['return_bn'].' ]';
        $this->__apilog['original_bn'] = $sdf['order_bn'];
        $shopId = $this->__channelObj->channel['shop_id'];
        $sdf['node_type'] = $this->__channelObj->channel['node_type'];
        // 订单
        $orderModel = app::get('ome')->model('orders');
        $tgOrder = $orderModel->getList('order_id', array('order_bn'=>$sdf['order_bn'],'shop_id'=>$shopId));
        if (!$tgOrder) {
            $this->__apilog['result']['msg'] = '没有订单' . $sdf['order_bn'];
            return false;
        }
        $returnModel = app::get('ome')->model('return_product');
        $tgReturn = $returnModel->getList('return_id, process_data', array('return_bn'=>$sdf['return_bn'],'order_id'=>$tgOrder[0]['order_id']));
        if (!$tgReturn) {
            $this->__apilog['result']['msg'] = '没有售后申请单' . $sdf['return_bn'];
            return false;
        }
        $sdf['return_id'] = $tgReturn[0]['return_id'];
        $sdf['old_process_data'] = unserialize($tgReturn[0]['process_data']);
        return $sdf;
    }
}