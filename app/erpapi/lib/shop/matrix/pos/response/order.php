<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 *POS订单处理 抽象类
 *
 * @author
 * @version
 */
class erpapi_shop_matrix_pos_response_order extends erpapi_shop_response_order
{
    /**
     * 订单obj明细唯一标识
     * 
     * @var string
     * */
    public $object_comp_key = 'bn-oid-obj_type';

    /**
     * 订单item唯一标识
     * 
     * @var string
     * */
    public $item_comp_key = 'bn-shop_product_id-item_type';
    /**
     * 数据解析
     * 
     * @return void
     * @author
     * */

    protected function _analysis()
    {
        parent::_analysis();

        //订单扩展信息
        if ($this->_ordersdf['other_list']) {
            foreach ($this->_ordersdf['other_list'] as $key => $val) {

                //导购门店编码(来源门店编码)
                if ($val['source_store_bn']) {
                    $this->_ordersdf['order_extend']['o2o_store_bn'] = $val['source_store_bn'];
                }

                //导购员编码(来源门店营业员编码)
                if ($val['source_store_salesman']) {
                    $this->_ordersdf['order_extend']['md_guider'] = $val['source_store_salesman'];
                }

                //发货自提门店编码
                if ($val['designated_store']) {
                    $this->_ordersdf['order_extend']['store_bn'] = $val['designated_store'];
                }

                //门店订单配送方式
                //OMS配送方式：1(o2o_pickup门店自提)、2(o2o_fastship门店闪送)、3(o2o_ship门店配送)
                if ($val['designated_shipping_type']) {
                    $this->_ordersdf['order_extend']['store_dly_type'] = intval($val['designated_shipping_type']);
                }

                if ($val['store_bn']) {
                    $this->_ordersdf['order_extend']['store_bn'] = $val['store_bn'];
                }

            }
        }

        if($this->_ordersdf['is_delivery']){
            $this->_ordersdf['is_delivery'] = $this->_ordersdf['is_delivery'];
        }
       
        
    }

    /**
     * 创建订单的插件
     * 
     * @return void
     * @author
     * */
    protected function get_create_plugins()
    {
        $plugins = parent::get_create_plugins();

        $plugins[] = 'orderextend';
     

        return $plugins;
    }

    public function _canAccept()
    {

        foreach ($this->_ordersdf['order_objects'] as $key => $val) {
            if ($val['status'] == 'finish' || $val['ship_status'] == '1') {
                if ($this->_ordersdf['order_sort']!='maintain' && (empty($val['store_code']) || !$val['store_code']) ) {
                    $this->__apilog['result']['msg'] = '缺失门店编码，订单不接收';
                    return false;
                }
            }
        }
        return parent::_canAccept();
    }

    /**
     * 创建接收
     * 
     * @return void
     * @author
     * */
    protected function _canCreate()
    {
        if(!parent::_canCreate()){
            return false;
        }
        try{
            $this->_docheck();

        }catch (Exception $e){
            $this->__apilog['result']['msg'] = $e->getMessage();
            return false;
        }
    }

    /**
     * 更新接收
     * 
     * @return void
     * @author
     * */
    protected function _canUpdate()
    {
        if($this->_ordersdf['is_delivery'] == 'true' && $this->_tgOrder['is_delivery'] == 'N'){
            return true;
        }

        
        if (!parent::_canUpdate()) {
            return false;
        }
        try {
            $this->_docheck('update');

        } catch (Exception $e) {
            $this->__apilog['result']['msg'] = $e->getMessage();
            return false;
        }

        return true;
    }

    /**
     * 订单报文校验
     * @param string $source 报文来源
     * @throws Exception
     */
    public function _docheck($source = 'create',$excludeCheckList = [])
    {

        // 支付手续费总额
        $paycost_amount = $this->_ordersdf['payments'] ? array_sum(array_column($this->_ordersdf['payments'], 'paycost')) : 0;

        // 验证订单金额是否正确
        $total_amount = (float) $this->_ordersdf['cost_item'] + (float) $this->_ordersdf['shipping']['cost_shipping'] + (float) $this->_ordersdf['shipping']['cost_protect'] + (float) $this->_ordersdf['discount'] + (float) $this->_ordersdf['cost_tax'] + (float) $paycost_amount - (float) $this->_ordersdf['pmt_goods'] - (float) $this->_ordersdf['pmt_order'];

        if (0 != bccomp($total_amount, $this->_ordersdf['total_amount'], 3)) {
            throw new Exception('订单总金额不正确');
        }

        //验证明细金额是否正确
        // 重新计算优惠金额
        $amount = $pmt_price = $part_mjz_discount = $divide_order_fee = 0;

        foreach ($this->_ordersdf['order_objects'] as $objkey => $obj) {
            //订单是否关单
            if ($obj['status'] == 'close') {
                continue;
            }
            # price *  quantity  = amount
            if (bccomp($obj['amount'], bcmul($obj['price'], $obj['quantity'], 3), 3)) {
                throw new Exception('商品单价*数量不等于商品原价金额小计！');
            }
            # amount - pmt_price = sale_price
            if (bccomp($obj['sale_price'], bcsub($obj['amount'], $obj['pmt_price'], 3), 3)) {
                throw new Exception('商品原价金额-商品优惠金额不等于商品销售金额！');
            }
            # sale_price - part_mjz_discount = divide_order_fee
            if (bccomp($obj['divide_order_fee'], bcsub($obj['sale_price'], $obj['part_mjz_discount'], 3))) {
                throw new Exception('商品销售金额-订单均摊优惠不等于商品实付金额！');
            }

            $amount            = bcadd($amount, $obj['amount'], 3);
            $pmt_price         = bcadd($pmt_price, $obj['pmt_price'], 3);
            $part_mjz_discount = bcadd($part_mjz_discount, $obj['part_mjz_discount'], 3);
            $divide_order_fee  = bcadd($divide_order_fee, $obj['divide_order_fee'], 3);

            //商品是赠品时movement_code必填
            if ($obj['obj_type'] == 'gift' && (!isset($obj['movement_code']) || !$obj['movement_code'])) {
                throw new Exception('赠品明细请填写movement_code！');
            }
        }
        //验证明细金额是否正确
        # sum(amount ) = cost_item
        if (0 != bccomp($amount, $this->_ordersdf['cost_item'], 3)) {
            throw new Exception('商品原价金额总和不等于商品总额！');
        }
        # sum(pmt_price ) = pmt_goods
        if (0 != bccomp($pmt_price, $this->_ordersdf['pmt_goods'], 3)) {
            throw new Exception('子订单商品优惠金额总和不等于商品优惠总金额！');
        }
        # sum(part_mjz_discount ) = pmt_order
        if (0 != bccomp($part_mjz_discount, $this->_ordersdf['pmt_order'], 3)) {
            throw new Exception('子订单均摊优惠总和不等于订单优惠总金额！');
        }

        # 明细层汇总金额与 主层校验,公式sum(divide_order_fee) = total_amount
        # 明细实付金额汇总 + 配送费 + 保价费 + 手动调价金额 + 税金 + 支付手续费总额 = 订单总额

        $check_total_amount = (float) $divide_order_fee + (float) $this->_ordersdf['shipping']['cost_shipping'] + (float) $this->_ordersdf['shipping']['cost_protect'] + (float) $this->_ordersdf['discount'] + (float) $this->_ordersdf['cost_tax'] + (float) $paycost_amount;

        if (0 != bccomp($check_total_amount, $this->_ordersdf['total_amount'], 3)) {
            throw new Exception('子订单商品实付金额总和不等于订单总金额');
        }

        // if ($this->_ordersdf['pmt_detail'] && !empty($this->_ordersdf['pmt_detail'])) {
        //     foreach ($this->_ordersdf['pmt_detail'] as $pmt) {
        //         if (!$pmt['discount_code'] || !isset($pmt['discount_code'])) {
        //             throw new Exception('优惠信息中优惠编码必填');
        //         }
        //     }
        // }
    }

    protected function get_update_plugins()
    {
        $plugins = parent::get_update_plugins();
        if($this->_ordersdf['is_delivery'] == 'true' && $this->_tgOrder['is_delivery'] == 'N'){
            $plugins[] = 'offline';
        }

        return $plugins;
    }

    protected function get_update_components()
    {


        $components = array('master', 'shipping', 'consignee', 'consigner', 'custommemo', 'markmemo', 'marktype', 'member', 'tax');


        return $components;
    }
}
