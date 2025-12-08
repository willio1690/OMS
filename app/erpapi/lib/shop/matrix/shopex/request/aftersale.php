<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @desc
 * @author: jintao
 * @since: 2016/7/21
 */
class erpapi_shop_matrix_shopex_request_aftersale extends erpapi_shop_request_aftersale{
    static public $aftersale_status = array (
        '1' => '1',#申请中',
        '2' => '2',#审核中',
        '3' => '3',#接受申请',
        '4' => '4',#完成',
        '5' => '5',#拒绝',
        '6' => '6',#已收货',
        '7' => '7',#已质检',
        '8' => '8',#补差价',
        '9' => '9',#已拒绝退款',
    );

    /**
     * 添加AfterSale
     * @param mixed $returninfo returninfo
     * @return mixed 返回值
     */

    public function addAfterSale($returninfo){
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if(!$returninfo) {
            $rs['msg'] = 'no return';
            return $rs;
        }

        // 售后明细
        $returnItemModel = app::get('ome')->model('return_product_items');
        $return_items = $returnItemModel->getList('name as sku_name,bn as sku_bn,num as number',array('return_id'=>$returninfo['return_id']));

        // 订单信息
        $orderModel = app::get('ome')->model('orders');
        $order = $orderModel->dump($returninfo['order_id'],'member_id,order_id,order_bn');

        // 会员信息
        $memberModel = app::get('ome')->model('members');
        $member = $memberModel->dump($order['member_id'],'uname,member_id');

        //退货附件
        $attachment = $returninfo['attachment'];
        if (is_numeric($attachment)){
            $attachment = kernel::single('base_storager')->getUrl($attachment);
        }

        $params['aftersale_items'] = json_encode($return_items);
        $params['attachment']      = $attachment;
        $params['tid']             = $order['order_bn'];
        $params['aftersale_id']    = $returninfo['return_bn'];
        $params['title']           = $returninfo['title'] ? $returninfo['title'] : '';
        $params['content']         = $returninfo['content'] ? $returninfo['content'] : '';
        $params['messager']        = $returninfo['comment'] ? $returninfo['comment'] : '';
        $params['memo']            = $returninfo['memo'] ? $returninfo['memo'] : '';
        $params['status']          = self::$aftersale_status[$returninfo['status']];
        $params['buyer_id']        = $member['member_id'];
        $params['buyer_name']      = $member['account']['uname'];
        $params['modify']          = $returninfo['last_modified'] ?  date("Y-m-d H:i:s",$returninfo['last_modified']) : date("Y-m-d H:i:s");
        $params['created']         = $returninfo['add_time'] ? date("Y-m-d H:i:s",$returninfo['add_time']) : date("Y-m-d H:i:s");

        $callback = array(
            'class' => get_class($this),
            'method' => 'callback',
        );

        $title = '店铺('.$this->__channelObj->channel['name'].')售后申请(订单号:'.$order['order_bn'].',申请单号:'.$returninfo['return_bn'].')';

        $rs = $this->__caller->call(SHOP_ADD_AFTERSALE_RPC,$params,$callback,$title,10,$order['order_bn']);
        return $rs;
    }

    protected function __afterSaleApi($status, $returnInfo=null) {
        if($status == '2') {
            return '';
        }
        return SHOP_UPDATE_AFTERSALE_STATUS_RPC;
    }

    protected function __formatAfterSaleParams($returninfo,$status) {
        $returnModel = app::get('ome')->model('return_product');
        if ($status == '4'){
            $product_detail = $returnModel->product_detail($returninfo['return_id']);
            if ($product_detail['check_data']){
                foreach ($product_detail['check_data'] as $item){
                    $tmp = array(
                        'bn'          => $item['bn'],//货品货号
                        'name'        => $item['name'],//货品名称
                        'memo'        => $item['memo'],//备注
                        'need_money'  => $item['need_money'],//应退金额
                        'other_money' => $item['other'],//折旧（其他金额）
                        'status'      => $item['status'],//1：退货、2：换货、3：拒绝
                    );
                    $addon[] = $tmp;
                }
            }
        }
        if ($returninfo['memo']){
            $params['content'] = $params['memo'] = $returninfo['memo'];
        }
        $params['aftersale_id'] = $returninfo['return_bn'];
        $params['status']       = self::$aftersale_status[$returninfo['status']];
        $params['modify']       = date('Y-m-d H:i:s');
        $params['addon']        = json_encode($addon);
        return $params;
    }

    /**
     * 添加Reship
     * @param mixed $reship reship
     * @return mixed 返回值
     */
    public function addReship($reship) {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$reship) {
            $rs['msg'] = 'no reship';
            return $rs;
        }
        // 订单
        $orderModel = app::get('ome')->model('orders');
        $order = $orderModel->dump($reship['order_id'], 'order_bn,member_id');
        // 会员
        $memberModel = app::get('ome')->model('members');
        $member = $memberModel->dump($order['member_id'],'uname,name,member_id');
        //发货品信息
        $reshipItemModel = app::get('ome')->model('reship_items');
        $reship_items = $reshipItemModel->getList('product_name,bn,num',array('reship_id'=>$reship['reship_id'],'return_type'=>array('return','refuse')));
        $reshipitems = array();
        if ($reship_items){
            foreach ($reship_items as $k=>$v){
                $v['sku_type'] = 'goods';
                $v['name'] = $v['product_name'];
                $v['number'] = $v['num'];
                unset($v['product_name']);
                unset($v['num']);
                $reshipitems[] = $v;
            }
        }
        $area = $reship['ship_area'];
        if (strpos($area, ":")){
            $area = explode(":", $area);
            $area = explode("/", $area[1]);
        }
        $params = array();
        $params['tid']               = $order['order_bn'];
        $params['reship_fee']        = $reship['money'];
        $params['reship_id']         = $reship['reship_bn'];
        $params['buyer_id']          = $member['account']['uname'];
        $params['buyer_uname']       = $member['account']['uname'];
        $params['create_time']       = $reship['t_begin'] ? date("Y-m-d H:i:s",$reship['t_begin']) : date("Y-m-d H:i:s");
        $params['is_protect']        = $reship['is_protect'];
        $params['status']            = strtoupper($reship['status']);
        $params['reship_type']       = $reship['delivery']?$reship['delivery']:'';
        $params['logistics_id']      = $reship['logi_id']?$reship['logi_id']:'';
        $params['logistics_company'] = $reship['logi_name']?$reship['logi_name']:'';
        $params['logistics_no']      = $reship['logi_no']?$reship['logi_no']:'';
        $params['receiver_name']     = $reship['ship_name']?$reship['ship_name']:'';
        $params['receiver_state']    = $area[0]?$area[0]:'';#省
        $params['receiver_city']     = $area[1]?$area[1]:'';#市
        $params['receiver_district'] = $area[2]?$area[2]:'';#县
        $params['receiver_address']  = $reship['ship_addr']?$reship['ship_addr']:'';
        $params['receiver_zip']      = $reship['ship_zip']?$reship['ship_zip']:'';
        $params['receiver_mobile']   = $reship['ship_mobile']?$reship['ship_mobile']:'';
        $params['receiver_email']    = $reship['ship_email']?$reship['ship_email']:'';
        $params['receiver_phone']    = $reship['ship_tel']?$reship['ship_tel']:'';
        $params['memo']              = $reship['memo']?$reship['memo']:'';
        $params['t_begin']           = $reship['t_begin'] ? date("Y-m-d H:i:s",$reship['t_begin']) : date("Y-m-d H:i:s");
        $params['t_end']             = $reship['t_end'] ? date("Y-m-d H:i:s",$reship['t_end']) : date("Y-m-d H:i:s");
        $params['reship_operator']   = kernel::single('desktop_user')->get_login_name();
        $params['reship_items']      = json_encode($reshipitems);
        $params['ship_type']         = 'return';
        $params['modify']            = $reship['t_end'] ? date("Y-m-d H:i:s",$reship['t_end']) : date("Y-m-d H:i:s");
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback',
        );
        $title = '店铺('.$this->__channelObj->channel['name'].')添加[交易退货单](订单号:'.$order['order_bn'].'退货单号:'.$reship['reship_bn'].')';
        $rs = $this->__caller->call(SHOP_ADD_RESHIP_RPC,$params,$callback,$title,10,$order['order_bn']);
        return $rs;
    }

    /**
     * 更新ReshipStatus
     * @param mixed $reship reship
     * @return mixed 返回值
     */
    public function updateReshipStatus($reship) {
        $orderModel = app::get('ome')->model('orders');
        $order = $orderModel->dump(array('order_id'=>$reship['order_id']), 'order_bn,shop_id');
        $params = array();
        $params['tid']       = $order['order_bn'];
        $params['reship_id'] = $reship['reship_bn'];
        $params['oid ']      = '';#子订单id
        $params['status']    = strtoupper($reship['status']);
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback',
        );
        $title = '店铺('.$this->__channelObj->channel['name'].')更新[交易退货状态]:'.$params['status'].'(订单号:'.$order['order_bn'].'退货单号:'.$reship['reship_bn'].')';
        $rs = $this->__caller->call(SHOP_UPDATE_RESHIP_STATUS_RPC,$params,$callback,$title,10,$order['order_bn']);
        return $rs;
    }
}