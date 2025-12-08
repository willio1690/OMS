<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 出库单推送
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_ilc_request_stockout extends erpapi_wms_request_stockout
{
    protected $_stockout_pagination = false;
    
    protected function _format_stockout_create_params($sdf)
    {
        $params = parent::_format_stockout_create_params($sdf);

        $items = array('item'=>array());
        if ($sdf['items']){
            foreach ((array) $sdf['items'] as $k=>$v){
                $barcode = kernel::single('material_codebase')->getBarcodeBybn($v['bn']);

                $items['item'][] = array(
                    'item_code'      => $barcode,
                    'item_name'      => $v['name'],
                    'item_quantity'  => (int)$v['num'],
                    'item_price'     => $v['price'] ? (float)$v['price'] : 0,// TODO: 商品价格
                    'item_line_num'  => ($k + 1),// TODO: 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'trade_code'     => '',//可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号) 
                    'item_id'        => $v['bn'],// 商品ID
                    'is_gift'        => '0',// TODO: 判断是否为赠品0:不是1:是
                    'item_remark'    => '',// TODO: 商品备注
                    'inventory_type' => '1',// TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                );
            }
        }

        $params['items']               = json_encode($items);
        $params['uniqid']              =  substr(self::uniqid(),0,25);
        $params['shipping_type']       = 'EMS';
        $params['remark']              =  $sdf['memo'] ? $sdf['memo'] : '';
        $params['receiver_zip']        =  $sdf['receiver_zip'] ? $sdf['receiver_zip'] : '200000';
        $params['receiver_country']    =  '中国';
        $params['is_cod']              =  'false';
        $params['platform_order_code'] = '';

        if ($sdf['branch_type'] == 'damaged') {
            $params['order_type'] = '残损出库';
        }

        return $params;
    }

    protected function _format_stockout_cancel_params($sdf)
    {
        $params = array(
             'out_order_code' => $sdf['io_bn'],
             'order_type'     => parent::transfer_stockout_type($sdf['io_type']),
             'uniqid'         =>self::uniqid(),
        );
        return $params;
    }

    protected function transfer_stockout_type($io_type)
    {
        $stockout_type = array(
            'PURCHASE_RETURN' => '采购退货',// 采购退货
            'ALLCOATE'        => '调拨出库',// 调拨出库
            'DEFECTIVE'       => '残损出库',// 残损出库
        );

        //唯品会出库类型
        $vop_io_type    = purchase_purchase_stockout::_io_type;
        $stockout_type[$vop_io_type]    = '唯品会出库';
        
        return isset($stockout_type[$io_type]) ? $stockout_type[$io_type] : '一般出库';
    }
    
    /**
     * 唯品会出库参数格式化
     */

    protected function _vop_format_stockout_create_params($sdf)
    {
        $stockout_bn = $sdf['io_bn'];
        
        //出库单明细
        $total_amount = 0;
        $items = array('item'=>array());
        if ($sdf['items'])
        {
            foreach ($sdf['items'] as $k => $v)
            {
                $items['item'][] = array(
                        'po_bn'      => $v['po_bn'],//采购单单号
                        'pick_bn'    => $v['pick_bn'],//拣货单单号
                        'item_code'  => $v['bn'],
                        'item_name'  => $v['name'],
                        'size'       => $v['size'],
                        'item_quantity'  => (int)$v['num'],
                        'unit_price'     => $v['unit_price'] ? (float)$v['unit_price'] : 0,// TODO: 成本价
                        'market_price'   => $v['market_price'] ? (float)$v['market_price'] : 0,// TODO: 市场价
                        'is_gift'        => '0',// TODO: 判断是否为赠品0:不是1:是
                        'item_remark'    => '',// TODO: 商品备注
                        'inventory_type' => '1',// TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                        'item_line_num'  => ($k + 1),// TODO: 订单商品列表中商品的行项目编号，即第n行或第n个商品
                );
                
                $total_amount    += $v['market_price'];
            }
        }
        
        $wms_branch_bn    = $this->get_wms_branch_bn($this->__channelObj->wms['channel_id'], $sdf['branch_bn']);
        $create_time      = preg_match('/-|\//',$sdf['create_time']) ? $sdf['create_time'] : date("Y-m-d H:i:s",$sdf['create_time']);
        $params = array(
                'uniqid'            => substr(self::uniqid(),0,25),
                'out_order_code'    => $stockout_bn,
                'wms_order_code'    => $stockout_bn,
                
                'delivery_no'       => $sdf['storage_no'],//入库单号
                'shipping_type'     => '',//配送方式 1空运 2汽运
                'total_amount'      => $total_amount,
                'carrier_code'      => $sdf['carrier_code'],//承运商编码
                'carrier_name'      => $sdf['carrier_name'],//承运商名称
                'arrival_time'      => $sdf['arrival_time'],//要求到货时间
                'to_branch_no'      => $sdf['to_branch_no'],//唯品会入库仓编码
                
                'order_type'        => $this->transfer_stockout_type($sdf['io_type']),//出库类型
                'created'           => $create_time,
                'remark'            => $sdf['memo'],
                'wms_branch_bn'     => $wms_branch_bn,//OMS仓库编号
                'storage_code'      => $sdf['storage_code'],// 库内存放点编号
                'items'             => json_encode($items),
                'sku_pick_num'      => $sdf['sku_pick_num'],//拣货货品的种类数量(整单维护统计)
                'item_pick_num'     => $sdf['item_pick_num'],//总拣货数量(整单维护统计)
                
                'customer_id'       => '',// 客户编码
                'logistics_code'    => '',// TODO: 快递公司（如果是汇购传递快递公司，则该项目不能为空，否则可以为空处理）
        );
        
        //外部仓库信息
        $params['is_cod']              =  'false';
        $params['platform_order_code'] = '';
        
        $params['receiver_name']       = ($sdf['receiver_name'] ? $sdf['receiver_name'] : '未知');
        $params['receiver_phone']      = ($sdf['receiver_phone'] ? $sdf['receiver_phone'] : '11111111111');// TODO: 收货人电话号码（如有分机号用“-”分隔）(电话和手机必选一项)
        $params['receiver_mobile']     = ($sdf['receiver_mobile'] ? $sdf['receiver_mobile'] : '11111111111');// TODO: 收货人手机号码(电话和手机必选一项)
        $params['receiver_email']      = ($sdf['receiver_email'] ? $sdf['receiver_email'] : 'demo@demo.com');// TODO: 收货人手机号码(电话和手机必选一项)
        $params['receiver_zip']        = ($sdf['receiver_zip'] ? $sdf['receiver_zip'] : '200000');// TODO: 收货人邮政编码
        
        $params['receiver_country']    = '中国';
        $params['receiver_state']      = ($sdf['receiver_state'] ? $sdf['receiver_state'] : '未知');// TODO: 退货人所在省
        $params['receiver_city']       = ($sdf['receiver_city'] ? $sdf['receiver_city'] : '未知');// TODO: 退货人所在市
        $params['receiver_district']   = ($sdf['receiver_district'] ? $sdf['receiver_district'] : '未知');// TODO: 退货人所在县（区），注意有些市下面是没有区的
        $params['receiver_address']    = ($sdf['receiver_address'] ? $sdf['receiver_address'] : '未知');// TODO: 收货地址（出库时非空）
        
        $params['receiver_time']       = '';
        $params['sign_standard']       = '';// TODO: 签收标准（如：身仹证150428197502205130）
        $params['source_plan']         = '';// TODO: 来源计划点
        
        //新增来源店铺编码
        $params['shop_code'] = $sdf['shop_code'];
        
        if ($sdf['delivery_no']) {
            $params['user_def2'] = $sdf['delivery_no'];
        }

        return $params;
    }
}