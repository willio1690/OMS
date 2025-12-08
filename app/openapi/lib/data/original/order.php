<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_data_original_order
{
   
    /**
     *订单是否可退款  PS: 订单未审核的，都可退款，审核了就不能退款
     * @param
     * @return
     * @access  public
     * @author custom@shopex.cn
     */
    public function refundagree($params)
    {
        $order_bn    = $params['order_bn'];
        $order_items = $params['refund_items'];
        $rs          = $this->_getOrderRefund($order_bn, $order_items);

        return [
            'data' => $rs['data'],
            'rsp'  => $rs['rsp'],
            'msg'  => $rs['msg'] ? $rs['msg'] : '请求成功',
        ];
    }

    /**
     *订单是否可退货  PS: 订单有可退数量的，都可以退货
     * @param
     * @return
     * @access  public
     * @author custom@shopex.cn
     */
    public function returnagree($params)
    {
        $order_bn    = $params['order_bn'];
        $order_items = $params['return_items'];
        $rs          = $this->_getOrderReturn($order_bn, $order_items);
        return [
            'data' => $rs['data'],
            'rsp'  => $rs['rsp'],
            'msg'  => $rs['msg'] ? $rs['msg'] : '请求成功',
        ];
    }

    /**
     *订单是否可换货   PS: 订单有可换数量的，都可换货
     * @param
     * @return
     * @access  public
     * @author custom@shopex.cn
     */
    public function exchangeagree($params)
    {
        $order_bn    = $params['order_bn'];
        $order_items = $params['return_items'];
        $rs          = $this->_getOrderReturn($order_bn, $order_items,'exchange');
        return [
            'data' => $rs['data'],
            'rsp'  => $rs['rsp'],
            'msg'  => $rs['msg'] ? $rs['msg'] : '请求成功',
        ];
    }

    private function _getOrderReturn($order_bn, $order_items,$return_type = 'return')
    {
        $result = array(
            'data' => array(
                'order_bn'    => $order_bn,
                'refundagree' => true,
            ),
            'rsp'  => 'success',
        );
        $items = [];
        try {
            $base_filter = array(
                'disabled' => 'false',
                'is_fail'  => 'false',
                'order_bn' => $order_bn,
            );

            $order = app::get('ome')->model('orders');
            $orderObjMdl = app::get('ome')->model('order_objects');
            $orderItemsMdl = app::get('ome')->model('order_items');

            $data = $order->dump($base_filter, 'order_id,order_bn,ship_status,pay_status');
            if (!$data || empty($data)) {
                throw new Exception('未找到对应订单信息');
            }

           

            //判断发货状态
            $is_ship = array('1', '2', '3');       //已发货，部分发货，部分退货

            if (!in_array($data['ship_status'], $is_ship)) {
                $errMsg = '订单发货状态不符合退换货条件,当前发货状态：';
                switch ($data['ship_status']) {
                    case '0' :
                        $errMsg .= '未发货';
                        break;
                    case '4' :
                        $errMsg .= '已退货';
                        break;
                }
                throw new Exception($errMsg);
            }

            //判断支付状态
            $is_pay = array('1', '4', '5');        //已支付，部分退款,全额退款
            if (!in_array($data['pay_status'], $is_pay)) {
                $errMsg = '订单支付状态不符合退换货条件,当前支付状态：：';
                switch ($data['pay_status']) {
                    case '0' :
                        $errMsg .= '未支付';
                        break;
                    case '3' :
                        $errMsg .= '部分付款';
                        break;
                    case '6' :
                        $errMsg .= '退款申请中';
                        break;
                }
                throw new Exception($errMsg);
            }

            //判断子订单是否存在
            $order_items = json_decode($order_items, 1);


            //如果子信息不传，报错
            if (!$order_items || empty($order_items)) {
                throw new Exception("请传入校验明细");
            }

            //取子订单判断
            foreach ($order_items as $item) {

              

                $objInfo = $orderObjMdl->dump(array('order_id' => $data['order_id'], 'oid' => $item['oid']), 'obj_id,bn');
                if (!$objInfo) {
                    $errMsg = sprintf("子订单%s不存在,货品编码:%s", $item['oid'], $item['bn']);
                    throw new Exception($errMsg);
                }
                print_r(array('order_id' => $data['order_id'], 'oid' => $item['oid']));
                $itemInfo = $orderItemsMdl->dump(array('bn'     => $item['bn'],
                                                       'obj_id' => $objInfo['obj_id']
                                                 ), 'item_id,bn,nums,return_num,sendnum');

                if (!$itemInfo) {
                    $errMsg = sprintf("子订单%s货品明细不存在,货品编码:%s", $item['oid'], $item['bn']);
                    throw new Exception($errMsg);
                    $result['msg'] = '子订单不存在';
                    continue;
                }

                //取关连云仓订单
                //退换货数量判断
                $orderInfo = $order->dump(array('order_bn' => $order_bn), 'order_id');

                // 明细退货类型
                $item_return_type = 0;
                // 如果是退货,则允许未发货退款
                if($return_type == 'return'){

                    $count = $this->Get_effective_return_count($orderInfo['order_id'], $item['bn'], '', $itemInfo['item_id']);
                    
                }
                // 如果是换货,则只校验已发货数量
                else{
                    $item_return_type = 1;
                    $count = $this->Get_effective_return_count($orderInfo['order_id'], $item['bn'], '', $itemInfo['item_id']);
                }

                if ($item['num'] > $count) {
                    $items[] = array(
                        'oid'         => $item['oid'],
                        'bn'          => $itemInfo['bn'],
                        'refundagree' => false,
                        'refundnum'   => $count,
                    );
                    $err_msg = '可退换数量错误.';

                    if($item_return_type == 2){
                        $err_msg = "请将未发货商品单独进行售后提交";
                    }
                    throw new Exception($err_msg);
                } else {
                    $items[] = array(
                        'oid'         => $item['oid'],
                        'bn'          => $itemInfo['bn'],
                        'refundagree' => true,
                        'refundnum'   => $count,
                    );
                }
            }

        } catch (\Exception $e) {
            $result['data']['refundagree'] = false;
            $result['msg'] = $e->getMessage();
            $result['rsp'] = 'fail';
            $result['data']['items'] = $items;
            return $result;
        }

        $result['data']['items'] = $items;
        return $result;
    }

    private function _getOrderRefund($order_bn, $order_items)
    {
        $base_filter = array(
            'disabled'    => 'false',
            'is_fail'     => 'false',
            'status'      => 'active',
            'order_bn'    => $order_bn,
        );
        $order         = app::get('ome')->model('orders');
        $orderObjMdl   = app::get('ome')->model('order_objects');
        $orderItemsMdl = app::get('ome')->model('order_items');
        $data          = $order->dump($base_filter,'order_id,order_bn,ship_status,pay_status');
        $order_id = $data['order_id'];
        $result = array(
            'data' => array(
                'order_bn'    => $order_bn,
                'refundagree' => false,
            ),
            'rsp'  => 'fail',
        );

        //判断订单状态
        $is_ship = array('0', '2', '3');       //未发货，部分发货，部分退货
        $is_pay = array('1', '3', '4');        //已支付，部分付款，部分退款
        if (!in_array($data['ship_status'], $is_ship)) {
            $result['msg'] = '订单发货状态不正确：';
            switch ($data['ship_status']) {
                case '1' :
                    $result['msg'] .= '已发货';
                    break;
                case '4' :
                    $result['msg'] .= '已退货';
                    break;
            }
            return $result;
        }
        if (!in_array($data['pay_status'], $is_pay)) {
            $result['msg'] = '订单支付状态不正确：';
            switch ($data['pay_status']) {
                case '0' :
                    $result['msg'] .= '未支付';
                    break;
                case '5' :
                    $result['msg'] .= '全额退款';
                    break;
                case '6' :
                    $result['msg'] .= '退款申请中';
                    break;
            }
            return $result;
        }

        
        //判断子订单是否存在
        $order_items = json_decode($order_items, 1);
        $items       = [];
        foreach ($order_items as $item) {

            $objInfo = $orderObjMdl->dump(array('order_id'=>$order_id,'oid' => $item['oid']), 'obj_id,bn');
            if (!$objInfo) {
                $result['msg'] = '子订单不存在';
                continue;
            }
            $itemInfo = $orderItemsMdl->dump(array('bn' => $item['bn'], 'obj_id' => $objInfo['obj_id']), 'item_id,bn,sendnum,nums,return_num');
            if (!$itemInfo) {
                $result['msg'] = '子订单不存在';
                continue;
            }

            $rs = $order->pauseOrder($order_id, false, '');

           
            if ($rs['rsp'] == 'fail') {
                $items[] = array(
                    'oid'         => $item['oid'],
                    'bn'          => $itemInfo['bn'],
                    'refundagree' => false,
                    'refundnum'   => 0,
                );
                $result['msg'] = '订单暂停失败';
                continue;

            }
            //退款数量判断
            $orderInfo = $order->dump(array('order_bn' => $order_bn), 'order_id');

            //
            if ($itemInfo['sendnum']>0){
                $count     = $this->Get_effective_refund_count($orderInfo['order_id'], $item['bn'], $itemInfo['item_id']);
            }else{
                $count     = $itemInfo['quantity']-$itemInfo['return_num'];
            }

            if ($item['num'] > $count) {
                $items[] = array(
                    'oid'         => $item['oid'],
                    'bn'          => $itemInfo['bn'],
                    'refundagree' => false,
                    'refundnum'   => $count,
                );
                $result['data']['items'] = $items;
                $result['msg']           = '可退款数量错误';
                continue;
            }
            if ($result['rsp'] == 'fail') {
                $items[] = array(
                    'oid'         => $item['oid'],
                    'bn'          => $itemInfo['bn'],
                    'refundagree' => true,
                    'refundnum'   => $count,
                );
                $result['data']['refundagree'] = true;
                $result['data']['items']       = $items;
                $result['rsp']                 = 'success';
            }
        }
        return $result;
    }

    /*
     * 统计某订单货号生成可退换货数量
     *
     * @param int $order_id ,varchar $bn
     *
     * @return int
     */
    public function Get_effective_return_count($order_id, $bn, $reship_id = '', $item_id = '')
    {

        $sql = "SELECT sum(sendnum) as count FROM sdb_ome_order_items WHERE  order_id='" . $order_id . "' AND bn='" . $bn . "' AND `delete`='false' ";

        if ($item_id) {
            $sql .= " AND item_id=" . $item_id;
        }

        $order = kernel::database()->selectrow($sql);

        $sql = "SELECT sum(i.normal_num) as normal_count,sum(i.defective_num) as defective_count FROM sdb_ome_reship as r left join sdb_ome_reship_items as i on r.reship_id=i.reship_id WHERE i.return_type='return' AND r.is_check in ('11','7') AND r.order_id='" . $order_id . "' AND i.bn='" . $bn . "'";
        if ($item_id) {
            $sql .= " AND order_item_id=" . $item_id;
        }
        if ($reship_id != '') {
            $sql .= ' AND r.reship_id!=' . $reship_id;
        } //已收获的取入库数量
        $refund = kernel::database()->selectrow($sql);
        $sql1   = "SELECT sum(i.num) as nums FROM sdb_ome_reship as r left join sdb_ome_reship_items as i on r.reship_id=i.reship_id WHERE i.return_type='return' AND r.is_check not in ('5','7','11') AND r.order_id='" . $order_id . "' AND i.bn='" . $bn . "' "; //未入仓库的取申请数量
        if ($item_id) {
            $sql1 .= " AND order_item_id=" . $item_id;
        }
        if ($reship_id != '') {
            $sql1 .= ' AND r.reship_id!=' . $reship_id;
        }
        $refund1 = kernel::database()->selectrow($sql1);

        return $order['count'] - $refund['normal_count'] - $refund['defective_count'] - $refund1['nums'];
    }

    /*
     * 统计某订单货号生成可退款数量
     *
     * @param int $order_id ,varchar $bn
     *
     * @return int
     */
    public function Get_effective_refund_count($order_id, $bn, $item_id = '')
    {

        $sql = "SELECT sum(nums - split_num) as count FROM sdb_ome_order_items WHERE  order_id='" . $order_id . "' AND bn='" . $bn . "' AND `delete`='false' ";
        if ($item_id) {
            $sql .= " AND item_id=" . $item_id;
        }
        $order = kernel::database()->selectrow($sql);
        return $order['count'];
    }
}
