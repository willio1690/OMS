<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_aftersale extends erpapi_store_response_abstract
{

    static public $return_status = array(
        'WAIT_SELLER_AGREE','CLOSED','SUCCESS','WAIT_SELLER_CONFIRM_GOODS'
      
    );

    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function add($params)
    {
        // 参数校验
        $this->__apilog['title']       = $this->__channelObj->store['name'] .'('.$params['store_bn'].')门店售后单' . $params['tid'].'|'.microtime(true);
        $this->__apilog['original_bn'] = $params['tid'];

        $store_bn = $params['store_bn'];

        if (empty($store_bn)) {

            $this->__apilog['result']['msg'] = "下单门店编码不可以为空";
            return false;
        }

        if(!in_array($params['status'],self::$return_status)){
            $this->__apilog['result']['msg'] = $params['status'].":状态不处理";
            return false;
        }
        $shops_detail = app::get('ome')->model('shop')->dump(array('shop_bn' => $store_bn));

        if (!$shops_detail) {
            $this->__apilog['result']['msg'] = $store_bn . ":门店不存在";
            return false;
        }
        $this->_dealSavePos($params);
        $params['return_product_items'] = json_decode($params['return_product_items'], true);
        $data                           = $this->_formatReturnProduct($params);

     

        $data['shop_type'] = $shops_detail['node_type'];
        $data['node_id']   = $shops_detail['node_id'];
        return $data;
    }

    private function _formatReturnProduct($params)
    {
        $cfgObj = app::get('ome')->model('payment_cfg');

        # 转换并模拟售后申请数据
        $data = [
            'refund_phase'    => 'aftersale', //退款阶段 onsale 售中 aftersale 售后
            'tid'             => $params['tid'], //订单号
            'refund_id'       => $params['refund_id'], //售后单据编号

            'has_good_return' => 'true', //是否有货品退回, 暂时写死
            'status'          => $params['status'], //状态,暂时写死
            'reason'          => '', //原因
            'refund_fee'      => $params['refund_fee'], //退款金额
            'created'         => $params['add_time'], //申请时间
            'warehouse_code'  => $params['warehouse_code'], //退货仓库
            'modified'        => $params['modified'],
            'apply_remark'    => $params['remind_type'], 
            'logistics_company'=> $params['logistics_company'],
            'logistics_no'     => $params['logistics_no'],
            'refund_shipping_fee'=> $params['cost_freight'],
            'md_guider'         =>  $params['md_guider'],
        ];

        # 获取请求门店编码
        $orderObj = app::get('ome')->model('orders');
        $order    = $orderObj->dump(array('order_bn' => $params['tid']), 'order_id,shop_id');
        if (!$order) {
            throw new Exception('订单不存在');
        }

        $shopObj = app::get('ome')->model('shop');
        $shop    = $shopObj->dump(array('shop_id' => $order['shop_id'], 's_type' => '2'), 'shop_bn,shop_type');
        if (!$shop) {
            throw new Exception('门店不存在');
        }
        $data['store_bn'] = $shop['shop_bn'];
        $data['shop_type'] = $shop['shop_type'];
        $return_item  = $refund_item  = [];
        $return_money = $refund_money = 0;
        foreach ($params['return_product_items'] as $item) {
            $barcode = $item['barcode'];
            $item['bn'] = kernel::single('material_codebase')->getBnBybarcode($barcode);
            if(empty($item['oid'])){
                throw new Exception('oid不可以为空');
            }
            
            $tmpItem = [
                'price'    => $item['price'],
                'oid'      => $item['oid'],
                'num'      => $item['num'],
                'outer_id' => $item['bn'],
                'sn_list'  => $item['sn_list'], 
            ];

            $return_item[] = $tmpItem;

        }

        if ($return_item) {
            $data['refund_item_list']['return_item'] = $return_item;
            $data['refund_item_list']                = json_encode($data['refund_item_list']);
        }

        return $data;
    }

    /**
     * _dealSavePos
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function _dealSavePos($params)
    {
        $aftersaleMdl  = app::get('pos')->model('aftersale');
        $aftersaleData = [
            'return_bn'  => $params['return_bn'],
            'order_bn'   => $params['tid'],
            'store_bn'   => $params['store_bn'],
            'refund_fee' => $params['refund_fee'],
            'status'     => $params['status'],
            'params'     => json_encode($params['params']),

        ];
        $aftersales = $aftersaleMdl->db_dump(array('return_bn' => $params['return_bn'], 'order_bn' => $params['tid']), 'id');
        if ($aftersales) {
            $filter = array();
            $id     = $aftersaleMdl->update($aftersaleData);
        } else {
            $id = $aftersaleMdl->insert($aftersaleData);
        }

    }
}
