<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 售后退货接口
 *
 * @version 2024.04.11
 */
class erpapi_dealer_response_aftersalev2 extends erpapi_dealer_response_abstract
{
    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function add($params)
    {
        $params['order_bn'] = $params['tid'];
        
        //前置处理平台数据（例如：去除抖音平台订单A字母）
        $params = $this->preFormatData($params);
        
        //apilog
        $this->__apilog['title'] = '店铺(' . $this->__channelObj->channel['name'] . ')经销商原始售后单[订单：' . $params['order_bn'] . ']';
        $this->__apilog['original_bn'] = $params['order_bn'];
        $this->__apilog['result']['data'] = array('order_bn'=>$params['order_bn'],'aftersale_id'=>$params['refund_id'],'retry'=>'false');
       
        $sdf = $this->_formatAddParams($params);

        $shopId = $sdf['shop_id'] = $this->__channelObj->channel['shop_id'];


        $sdf['shop_type'] = $this->__channelObj->channel['shop_type'];
        $field = 'plat_order_id,plat_order_bn';
        $tgOrder = $this->getOrder($field, $shopId, $sdf['order_bn']);
        $sdf['platform_orders'] = $tgOrder;

       
        if(empty($sdf) || !is_array($sdf)) {
            if(!$this->__apilog['result']['msg']) {
                $this->__apilog['result']['msg'] = '没有数据,不接收售后单';
            }
            return false;
        }
        
        $refundItemList = $this->_formatAddItemList($sdf);

        $sdf['refund_item_list'] = $refundItemList;


        //判断已存在
        
        $modelAftersale = app::get('dealer')->model('platform_aftersale');

        $platform_aftersale = $modelAftersale->getList('plat_aftersale_id,plat_aftersale_bn,plat_order_id,plat_order_bn,erp_order_bn,erp_order_id,return_type,status,outer_lastmodify,return_logi_no', array('plat_aftersale_bn'=>$sdf['refund_bn'],'shop_id'=>$shopId), 0, 1);

        if($platform_aftersale){
            $sdf['platform_aftersale'] = $platform_aftersale[0];

            if(in_array($sdf['platform_aftersale'],array('SUCCESS'))){

                $this->__apilog['result']['msg'] = '状态已完成不更新';
                return false;
            }
            if($sdf['modified'] <= $sdf['platform_aftersale']['outer_lastmodify']) {

                $this->__apilog['result']['msg'] = '更新时间未变化不更新';
                return false;
            }

        }
        return $sdf;
    }
    

    protected function _formatAddParams($params)
    {
        $sdf = array(
            'order_bn'          => $params['order_bn'],
            'refund_bn'         => $params['refund_id'],
            'status'            => $params['status'],
            'refund_fee'        => $params['refund_fee'] ? sprintf('%.2f', $params['refund_fee']) : 0,
            'refund_type'       => $params['refund_type'],
            'reason'            => $params['reason'],
            'modified'          => $params['modified'] ? kernel::single('ome_func')->date2time($params['modified']) : '',
            'created'           => $params['created'] ? kernel::single('ome_func')->date2time($params['created']) : time(),
            't_begin'           => $params['t_begin'] ? kernel::single('ome_func')->date2time($params['t_begin']) : '',
            'cur_money'         => $params['cur_money'],
            'pay_type'          => $params['pay_type'] ? $params['pay_type'] : 'online',
            'alipay_no'         => $params['alipay_no'],
            'payment'           => $params['payment'],
            'account'           => $params['account'],
            'bank'              => $params['bank'],
            'buyer_nick'        => $params['buyer_nick'],
            'desc'              => $params['desc'],
            'shipping_type'     => $params['shipping_type'],
            'logistics_company' => $params['logistics_company'] ? $params['logistics_company'] : $params['company_name'],
            'logistics_no'      => $params['logistics_no'] ? $params['logistics_no'] : $params['sid'],
            'pay_account'       => $params['pay_account'],
            'refund_item_list'  => $params['refund_item_list'] ? json_decode($params['refund_item_list'],true) : '',
            
            'json_data' =>json_encode($params),
        );
      
       
        self::trim($sdf);
        
        return $sdf;
    }
    
    
    protected function _formatAddItemList($sdf, $convert = array()) {

        $convert = array(
            'sdf_field'=>'oid',
            'order_field'=>'plat_oid',
            'default_field'=>'outer_id'
        );

        $itemList = $sdf['refund_item_list']['return_item'];

        $sdfField = $convert['sdf_field'];
        $orderField = $convert['order_field'];
        $defaultField = $convert['default_field'];
        $arrOrderField = array();
        foreach($itemList as $val) {
            if($val[$sdfField]) {
                $arrOrderField[] = $val[$sdfField];
            }
        }

        $plat_order_id = $sdf['platform_orders']['plat_order_id'];
        $filter = array(
            $orderField => $arrOrderField,
            'plat_order_id' => $plat_order_id,
        );


        $object = app::get('dealer')->model('platform_order_objects')->getList($orderField . ', bn, quantity,plat_obj_id,is_shopyjdf_step,goods_id', $filter);

        $omeobjectMdl = app::get('ome')->model('order_objects');
        $omeorderMdl = app::get('ome')->model('orders');
        $omeitemMdl = app::get('ome')->model('order_items');
        $arrBn = array();
        $arrQuantity = array();
        $arrObjids = array();
        foreach($object as $oVal) {
            $arrBn[$oVal[$orderField]] = $oVal['bn'];
            $arrQuantity[$oVal[$orderField]] = $oVal['quantity'];
            $arrObjids[$oVal[$orderField]] = $oVal['plat_obj_id'];
        }


        $platobjs = array_column($object,null,'plat_oid');


        $arrItems = $arrObjs = array();
        foreach ($itemList as $item) {
            $oid = $item['oid'];
            $plat_obj_id = $arrObjids[(string)$item[$sdfField]];
            $item['bn'] = $arrBn[(string)$item[$sdfField]] ? $arrBn[(string)$item[$sdfField]] : $item[$defaultField];
            $item['obj_bn'] = (string) $item['bn'];
            $item['goods_id']  = $platobjs[$oid]['goods_id'];
            $objitems = app::get('dealer')->model('platform_order_items')->getList('*', array('plat_order_id' => $plat_order_id,'plat_obj_id'=>$plat_obj_id));
           
            $quantity = $platobjs[$oid]['quantity'];
            $objitemids = array_column($objitems,'plat_item_id');
            $omeobjs = $omeobjectMdl->getlist('obj_id,oid,obj_line_no,order_id',array('obj_line_no'=>$plat_obj_id));

            $omeitems = $omeitemMdl->getlist('item_id,obj_id,divide_order_fee,nums,item_line_no,order_id',array('item_line_no'=>$objitemids));

            $tmpomeitems = array();
            foreach($omeitems as $iv){
                $tmpomeitems[$iv['order_id']][$iv['item_line_no']] = $iv;
            }
            $radio = $item['num']/$quantity;
            foreach($objitems as $iv){

                $divide_order_fee = $iv['divide_order_fee'];
              
                $erp_price = sprintf('%.3f',$divide_order_fee/$iv['nums']);
                $tmpomeitem = $tmpomeitems[$iv['erp_order_id']][$iv['plat_item_id']];

                $num = (int) ($radio * $iv['nums']);//pkg
                $arritem = array(

                    'shop_goods_bn' =>  $item['obj_bn'],
                    'bn'            =>  $iv['bn'],
                    'name'          =>  $iv['name'],
                    'oid'           =>  $oid,
                    'num'           =>  $num,
                    'price'         =>  $sdf['refund_fee'],
                    'product_id'    =>  $iv['product_id'],
                    'erp_num'       =>  $iv['nums'],
                    'erp_price'     =>  $erp_price,
                    'betc_id'       =>  $iv['betc_id'],
                    'plat_obj_id'   =>  $iv['plat_obj_id'],
                    'plat_item_id'  =>  $iv['plat_item_id'],
                    'erp_order_bn'  =>  $iv['erp_order_bn'],
                    'erp_order_id'  =>  $iv['erp_order_id'] ? $iv['erp_order_id'] : 0,
                    'erp_obj_id'    =>  $tmpomeitem ? $tmpomeitem['obj_id'] : 0,
                    'erp_item_id'   =>  $tmpomeitem ? $tmpomeitem['item_id'] : 0,
                );

                $arrItems[] = $arritem;
            }
            //oid    plat_obj_id
            $arrObj = $item;
            $arrObj['items'] = $arrItems;
            $arrObjs[] = $arrObj;

        }

        return $arrObjs;
    }
    


}
