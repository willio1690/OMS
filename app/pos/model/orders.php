<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_mdl_orders extends dbeav_model {
    public $has_export_cnf = true;
    public $export_name = '订单';

    private $bill_types = array(
        '销售' => 'sales',
        '退货' => 'reship',   
    );
    private $templateColumn = array(
        '订单日期'         => 'created',
        '大区'            => 'area',
        '省份'            => 'province',
        '代理商编码'       => 'agent_code',
        '代理商名称'       => 'agent_name',
        '门店编码'        => 'store_code',
        '门店名称'      => 'store_name',
        '单位统计编码'    =>'unit_code',
        '单位统计名称'    =>'unit_name',
        '主单BA编号'    =>'main_ba_code',
        '主单BA名称'    =>'main_ba_name',
        '明细BA编号'    =>'item_ba_code',
        '明细BA名称'    =>'item_ba_name',
        '会员卡号'      =>'member_code',
        '会员等级'      =>'member_level',
        '会员手机号'     =>'member_phone',
        '会员名称'         =>'member_name',
        '会员归属导购编码'  =>'guide_code',
        '会员归属导购名称'=>'guide_name',
        '商品系列'      =>'商品系列',
        '商品品类'      =>'商品品类',
        '商品分类'      =>'商品分类',
        '商品条码'      =>'barcode',
        '商品编码'      =>'bn',
        '商品名称'      =>'material_name',
        '批次'         =>'batch',
        '生产日期'      =>'product_date',
        '保质期'       =>'保质期',
        '有效期至'      =>'有效期至',
        '商品规格'      =>'spec',
        '商品品牌'      =>'brand',
        '零售单价'      =>'retrial_price',
        '会员单价'      =>'member_price',
        '活动编码'      =>'pmt_describe',
        '活动名称'      =>'pmt_memo',
        '订单编号'      =>'order_bn',
        '订单类型'      =>'bill_type',
        '终端单据号'     =>'payment_bn',
        '第三方单据号1'   =>'third_bill1',
        '第三方单据号2'   =>'third_bill2',
        '前置订单编号'    =>'relate_order_bn',
        '前置订单类型'    =>'relate_order_type',
        '订单状态'          =>'order_status',
        '配送方式'      =>'shipping_name',
        '销售数量'      =>'items_num',
        '订单商品单价'    =>'price',
        '订单商品金额'    =>'total_item_fee',
        '折前金额'      =>'折前金额',
        '优惠金额'       =>'discount_fee',
        '商品实付金额'    =>'divide_order_fee',
    );

    /**
     * prepared_import_csv
     * @return mixed 返回值
     */
    public function prepared_import_csv(){
        $this->import_data =[];
        $this->import_data_bm_bn =[];
        $this->ioObj->cacheTime = time();
    }

    /**
     * prepared_import_csv_row
     * @param mixed $row row
     * @param mixed $title title
     * @param mixed $tmpl tmpl
     * @param mixed $mark mark
     * @param mixed $newObjFlag newObjFlag
     * @param mixed $msg msg
     * @return mixed 返回值
     */
    public function prepared_import_csv_row($row,&$title,&$tmpl,&$mark,&$newObjFlag,&$msg)
    {
        if(empty($row) || empty(array_filter($row))) return false;
        if( $row[0] == '订单日期' ){
            $this->nums = 1;
            $title = array_flip($row);
            foreach($this->templateColumn as $k => $val) {
                if(!isset($title[$k])) {
                    $msg['error'] = '请使用正确的模板';
                    return false;
                }
            }
            return false;
        }
        $this->nums++;
        if (empty($title)) {
            $msg['error'] = "请使用正确的模板格式！";
            return false;
        }
        $arrRequired = ['order_bn','bill_type'];
        $arrData = array();
        foreach($this->templateColumn as $k => $val) {
            $arrData[$val] = trim($row[$title[$k]]);
            if(in_array($val, $arrRequired) && empty($arrData[$val])) {
                $msg['warning'][] = 'Line '.$this->nums.'：'.$k.'不能都为空！';
                return false;
            }
        }
        if($this->nums > 10000){
            $msg['error'] = "导入的数据量过大，请减少到10000条以下！";
            return false;
        }
        $main = [
            'order_bn'          =>  $arrData['order_bn'],
            'created'           =>  $arrData['created'],
            'store_code'        =>  $arrData['store_code'],
            'order_status'      =>  $arrData['order_status'],
            'relate_order_bn'   =>  $arrData['relate_order_bn'],
        ];
        $main['order_objects'][$arrData['bn']] = [
            'bn'                => $arrData['bn'],
            'name'              => $arrData['material_name'],
            'price'             => $arrData['price'],
            'amount'            => $arrData['total_item_fee'],
            'quantity'          => $arrData['items_num'],
            'sale_price'        => $arrData['total_item_fee'],
            'part_mjz_discount' => $arrData['discount_fee'],
            'divide_order_fee'  => $arrData['divide_order_fee'],


        ];

        $bill_type = $this->bill_types[$arrData['bill_type']];

        $this->import_data['main'][$bill_type][$arrData['order_bn']]= $main;
        $mark = 'contents';
        return true;
    }

    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){
        if(empty($this->import_data)) {
            return null;
        }

        $main =$this->import_data['main'];
        
        if(isset($main['reship'])){
            $reshipData = $this->format_reships($main['reship']);


        }
        if(isset($main['sales'])){
            $ordersData = $this->format_orders($main['sales']);

            
        }

        if(!$rs) {
            $msg['error'] = $rsData['msg'];
            return false;
        }
        return null;
    }


    /**
     * finish_import_csv
     * @return mixed 返回值
     */
    public function finish_import_csv(){
        return null;
    }

    /**
     * test
     * @return mixed 返回值
     */
    public function test(){
        $arrData['bill_type'] = '销售';
        $bill_type = $this->bill_types[$arrData['bill_type']];

        echo $bill_type;
    }

    /**
     * format_orders
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function format_orders($sdf){
        foreach($sdf as $k=>$v){
            
            $order = [
                    'tid'           => $v['order_bn'],
                    'store_bn'      => $v['store_code'],
                    'currency'       => 'CNY',
                    'title'          => $v['order_bn'],
                    'created'       => $v['created'],
                    'paytime'        => strtotime($v['created']),
                    'modified'      => time(),
                    'confirm'        => 'N',
                    'status'         => 'active',
                    'pay_status'     => 'PAY_FINISH',
                    'ship_status'   =>'SHIP_FINISH',
                    'is_delivery'    => 'Y',
                    'shop_id'        => $shop['shop_id'],
                    'org_id'         => $shop['org_id'],
                  
                    'shipping'       => array(
                        'is_cod'        => 'false',
                        'shipping_name' => '门店发货',
                       
                    ),
                    'consignee'      => array(
                        'name'      => '门店',
                        
                    ),
                    'shop_type'      => $shop["shop_type"],
                   
                    'order_source'   => 'import',
                    
            ];
            $total_amount = 0;
            $order_obj = [];
            $total_goods_fee=$total_trade_fee = $discount_fee=$orders_discount_fee=0;
            foreach ($v['order_objects'] as $key => $iv) {
                $order_items=[];
                $order_items[]  = array(

                    'bn'    =>  $iv['bn'],
                    'name'  =>  $iv['name'],
                    'price' =>  $iv['price'],
                    'num'   =>  $iv['quantity'],
                    'sale_price'=>$iv['amount'],
                    'total_item_fee'=>$iv['amount'],
                    'part_mjz_discount'=>$iv['part_mjz_discount'],
                    'divide_order_fee'=>$iv['divide_order_fee'],

                );
                $order_obj[] = [
                    'bn'                =>  $iv['bn'],
                    'sale_price'        =>  $iv['amount'],
                    'total_order_fee'   =>  $iv['amount'],
                    'items_num'         =>  $iv['quantity'],
                    'divide_order_fee'  =>  $iv['divide_order_fee'],
                    'part_mjz_discount' =>  $iv['part_mjz_discount'],
                    'store_code'        =>  $v['store_code'],
                    'ship_status'       =>  1,
                    'status'            =>  'TRADE_FINISHED',
                    'order_items'       =>  $order_items,
                ];
                $total_goods_fee+=$iv['amount'];
                $orders_discount_fee+=$iv['part_mjz_discount'];
                $total_trade_fee+=$iv['divide_order_fee'];
            }
            $order['total_goods_fee'] = $total_goods_fee;
            $order['total_trade_fee'] = $total_trade_fee;
            $order['payed_fee']       = $total_trade_fee;   
            $order['orders_discount_fee'] = $orders_discount_fee;
            $order['orders'] = $order_obj;
            kernel::single('erpapi_router_response')->set_node_id('pekon')->set_api_name('store.order.add')->dispatch($sdf);
        }

        return  [true];
    }


    /**
     * format_reships
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function format_reships($sdf){
        foreach($sdf as $k=>$v){
            $aftersale = [
                'tid'       =>  $v['relate_order_bn'],
                'refund_id' =>  $v['order_bn'],
                'store_bn'  =>  $v['store_bn'],
                'add_time'  =>  $v['created'],
                'modified'  =>  $v['created'],
                'status'    =>  'SUCCESS',
            ];
            $return_product_items = [];
            $refund_fee = 0;
            foreach ($v['order_objects'] as $key => $iv) {
                $price = abs($iv['divide_order_fee'])/abs($iv['quantity']);
                $price = sprintf('%.2f',$price);
                $return_product_items[] = [
                    'bn'    =>  $iv['bn'],
                    'num'   =>  abs($iv['quantity']),
                    'price' =>  $price,
                ];
                $refund_fee+=abs($iv['divide_order_fee']);
            }
            $aftersale['refund_fee'] = $refund_fee;
            $aftersale['return_product_items'] = json_encode($return_product_items);
            kernel::single('erpapi_router_response')->set_node_id('pekon')->set_api_name('store.aftersale.add')->dispatch($aftersale);
        }
    }
}