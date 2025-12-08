<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_preprocess_tbgift {

    static private $__local_gifts = array();
    static private $__tb_gifts = array();
    static private  $_tb_gift_order_id = null;
    public $_apiGiftLog = array();

    /**
     * 订单接收保存订单优惠赠品信息
     */
    public function save($orderid, $gift_info){
        if(!$orderid || !$gift_info || !is_array($gift_info)){
            return false;
        }
        $is_update = false;
        $orderObj = app::get('ome')->model('orders');
        $tbgiftOrderItemsObj = app::get('ome')->model('tbgift_order_items');
        $tmp_arr = $gift_info;
        foreach((array)$tmp_arr as $info){
            if($info['type'] == 'gift'){
                $data = array(
                    'order_id' => $orderid,
                    'outer_item_id' => $info['id'],
                    'name' => $info['name'],
                	'nums' => $info['num'],
                );
                if($tbgiftOrderItemsObj->save($data)){
                    $is_update = true;
                }
            }
        }

        if($is_update){
            $orderObj->update(array('abnormal_status'=>ome_preprocess_const::__HASGIFT_CODE),array('order_id'=>$orderid));
        }
    }

    /**
     * 处理订单追加优惠信息中的赠品
     */
    public function process($order_id,&$msg){
        if(!$order_id){
            $msg = '缺少处理参数';
            return false;
        }

        $orderObj = app::get('ome')->model('orders');
        $tbgiftOrderItemsObj = app::get('ome')->model('tbgift_order_items');
        $operationLogObj = app::get('ome')->model('operation_log');
        $opinfo = kernel::single('ome_func')->get_system();
        $basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');
        
        $orderInfo = $orderObj->dump(array('order_id'=>$order_id,'shop_type'=>'taobao'),'order_bn,shop_type,shop_id,abnormal_status,order_type');
        if(!$orderInfo || ($orderInfo['abnormal_status'] & ome_preprocess_const::__HASGIFT_CODE) != ome_preprocess_const::__HASGIFT_CODE){
            return true;
        }
        
        //补发订单
        if ($orderInfo['order_type'] == 'bufa') {
            $msg = '补发订单不需要执行淘宝赠品';
            return true;
        }
        
        $branchBatchList = [];
        $this->_apiGiftLog['order_bn'] = $orderInfo['order_bn'];
        $is_update = true;
        $local_tbgiftInfos = $tbgiftOrderItemsObj->getList('*',array('order_id'=>$order_id),0,-1);
        if($local_tbgiftInfos){
            $orderItemObj  = app::get('ome')->model("order_items");
            $orderObjectObj  = app::get('ome')->model("order_objects");

            foreach ($local_tbgiftInfos as $local_tbgiftInfo){
                if(empty(self::$_tb_gift_order_id )){
                    self::$_tb_gift_order_id  = $order_id;
                }

                $tbgiftBn = $this->getGiftBnFromTb($local_tbgiftInfo['outer_item_id'],$orderInfo['shop_id']);
                if($tbgiftBn){
                    $tbgiftbn = $tbgiftBn;
                }else{
                    $is_update = false;
                    continue;
                }

                //获取本地赠品信息
                $productInfo = $this->findTheGift($tbgiftbn);
                if(!$productInfo){
                    $is_update = false;
                    continue;
                }

                #库存不足判断
                if($productInfo['store'] == 0){
                    $msg = '赠品库存不足';
                    $operationLogObj->write_log('order_preprocess@ome',$order_id,$msg,time(),$opinfo);
                    return false;
                }
                
                #普通商品处理流程
                $tmp_obj = array(
                    'order_id' => $local_tbgiftInfo['order_id'],
                    'obj_type' => $productInfo['obj_type'],
                    'obj_alias' => $productInfo['obj_alias'],
                    'shop_goods_id' => '-1',
                    'goods_id' => $productInfo['sm_id'],
                    'bn' => $productInfo['sales_material_bn'],
                    'name' => $productInfo['sales_material_name'],
                    'price' => 0.00,
                    'sale_price' => 0.00,
                    'pmt_price' => 0.00,
                    'amount' => 0.00,
                    'quantity' => $local_tbgiftInfo['nums'],
                );

                if($orderObjectObj->save($tmp_obj)){

                    foreach($productInfo['order_items'] as $item){
                        $tmp_items = array(
                            'order_id' => $local_tbgiftInfo['order_id'],
                            'obj_id' => $tmp_obj['obj_id'],
                            'shop_goods_id' => '-1',
                            'product_id' => $item['bm_id'],
                            'shop_product_id' => '-1',
                            'bn' => $item['material_bn'],
                            'name' => $item['material_name'],
                            'cost' => 0.00,
                            'price' => 0.00,
                            'amount' => 0.00,
                            'sale_price'=> 0.00,
                            'pmt_price' => 0.00,
                            'quantity' => $local_tbgiftInfo['nums']*$item['number'],
                            'sendnum' => 0,
                            'item_type' => $item['item_type'],
                        );

                        if($orderItemObj->save($tmp_items)){

                            $freezeData = [];
                            $freezeData['bm_id'] = $tmp_items['product_id'];
                            $freezeData['sm_id'] = $productInfo['sm_id'];
                            $freezeData['obj_type'] = material_basic_material_stock_freeze::__ORDER;
                            $freezeData['bill_type'] = 0;
                            $freezeData['obj_id'] = $order_id;
                            $freezeData['shop_id'] = $orderInfo['shop_id'];
                            $freezeData['branch_id'] = 0;
                            $freezeData['bmsq_id'] = material_basic_material_stock_freeze::__SHARE_STORE;
                            $freezeData['num'] = $tmp_items['quantity'];
                            $freezeData['obj_bn'] = $orderInfo['order_bn'];

                            $branchBatchList[] = $freezeData;
                        }else{
                            $is_update = false;
                            continue;
                        }
                    }
                }else{
                    $is_update = false;
                    continue;
                }
            }

            $basicMStockFreezeLib->freezeBatch($branchBatchList, __CLASS__.'::'.__FUNCTION__, $err);

            if($is_update){
                $status = $orderInfo['abnormal_status'] ^ ome_preprocess_const::__HASGIFT_CODE;
                $orderObj->update(array('abnormal_status'=>$status),array('order_id'=>$order_id));
                $operationLogObj->write_log('order_preprocess@ome',$order_id,'订单预处理优惠赠品信息',time(),$opinfo);
                return true;
            }else{
                $msg = '赠品无法匹配';
                $operationLogObj->write_log('order_preprocess@ome',$order_id,$msg,time(),$opinfo);
                return false;
            }
        }else{
            $msg = '没有找到优惠赠品信息';
            $operationLogObj->write_log('order_preprocess@ome',$order_id,$msg,time(),$opinfo);
            return false;
        }
    }

    /**
     * 根据赠品num_iid和shop_id获取赠品外部商家编码
     */
    private function getGiftBnFromTb($num_iid,$shop_id){
        $operationLogObj = app::get('ome')->model('operation_log');
        $opinfo = kernel::single('ome_func')->get_system();
        if(!$num_iid || !$shop_id){
            return false;
        }

        if(isset(self::$__tb_gifts[$shop_id][$num_iid])){
            return self::$__tb_gifts[$shop_id][$num_iid];
        }

        $api_name ='store.item.get';
        $param = array(
            'iid' => $num_iid,
        );
        $timeout = 5;

        $result = kernel::single('ome_rpc_request')->call($api_name, $param, $shop_id, $timeout);
        if($result){
            if($result->rsp == 'succ'){
                $msg = '获取赠品成功';
                $this->_apiGiftLog['title'] = '请求淘宝赠品';
                $this->_apiLog['info'][] = $msg;
                $this->_apiGiftLog['msg_id'] = $result->msg_id;
                $this->_apiGiftLog['param'] = $param;
                $this->get_taobaoGift_log('success');
                $operationLogObj->write_log('order_preprocess@ome',self::$_tb_gift_order_id,$msg,time(),$opinfo);
                $tmp = json_decode($result->data,true);
                self::$__tb_gifts[$shop_id][$num_iid] = $tmp['item']['outer_id'];

                return self::$__tb_gifts[$shop_id][$num_iid];
            }else{
                $msg = '获取赠品失败';
                $this->_apiGiftLog['title'] = '请求淘宝赠品';
                $this->_apiGiftLog['info']['msg'] =  ($result->res == 'e00090')?'响应超时':$result->err_msg;
                $this->_apiGiftLog['msg_id'] = $result->msg_id;
                $this->_apiGiftLog['param'] = $param;
                $this->get_taobaoGift_log('fail');
                $operationLogObj->write_log('order_preprocess@ome',self::$_tb_gift_order_id,$msg,time(),$opinfo);
                return false;
            }
        }else{
            $msg = '没有赠品信息';
            $this->_apiGiftLog['title'] = '请求淘宝赠品';
            $this->_apiGiftLog['info']['msg'] =  ($result->res == 'e00090')?'响应超时':$result->err_msg;
            $this->_apiGiftLog['msg_id'] = $result->msg_id;
            $this->_apiGiftLog['param'] = $param;
            $this->get_taobaoGift_log('fail');
            $operationLogObj->write_log('order_preprocess@ome',self::$_tb_gift_order_id,$msg,time(),$opinfo);
            return false;
        }
    }

    /**
     * 根据淘宝赠品商家编码找到本地对应的赠品
     */
    private function findTheGift($bn){
        if(!$bn){
            return false;
        }

        $tbgiftGoodsObj = app::get('ome')->model('tbgift_goods');
        $tbgiftProductObj = app::get('ome')->model('tbgift_product');
        $salesMLib = kernel::single('material_sales_material');
        $salesMStockLib = kernel::single('material_sales_material_stock');

        if(isset(self::$__local_gifts[$bn])){
            $tmp_local_giftInfo = self::$__local_gifts[$bn];
        }else{
            $tmp_local_giftInfo = $tbgiftGoodsObj->dump(array('gift_bn'=>$bn,'status'=>1),'*');
        }

        if($tmp_local_giftInfo){
            if(self::$__local_gifts[$bn]['products']){
                $tmp_local_productInfos = self::$__local_gifts[$bn]['products'];
            }else{
                $tmp_local_productInfos = $tbgiftProductObj->getList('*',array('goods_id'=>$tmp_local_giftInfo['goods_id']),0,-1);
            }

            if($tmp_local_productInfos){

                if(!isset(self::$__local_gifts[$bn])){
                    self::$__local_gifts[$bn] = $tmp_local_giftInfo;
                    self::$__local_gifts[$bn]['products'] = $tmp_local_productInfos;
                }
                
                $maxstore = -1;
                $tmp = array();
                foreach($tmp_local_productInfos as $tmp_local_productInfo){
                    $salesMInfo = $salesMLib->getSalesMById('_ALL_', $tmp_local_productInfo['product_id']);
                    if($salesMInfo['is_bind'] == 1){
                        $basicMInfos = $salesMLib->getBasicMBySalesMId($salesMInfo['sm_id']);
                        $store = $salesMStockLib->getSalesMStockById($salesMInfo['sm_id']);

                        $tmp_productInfo = $salesMInfo;
                        $tmp_productInfo['obj_type'] = 'gift';
                        $tmp_productInfo['obj_alias'] = '赠品';
                        $tmp_productInfo['store'] = $store;

                        foreach($basicMInfos as $k => $basicMInfo){
                            $tmp_productInfo['order_items'][]  = array_merge($basicMInfo,array('item_type'=>'gift'));
                        }

                        if($tmp_productInfo['store'] > $maxstore){
                            $tmp = $tmp_productInfo;
                            $maxstore = $tmp_productInfo['store'];
                        }

                    }else{
                        return false;
                    }
                }

                return $tmp;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    function get_taobaoGift_log($status){
        $oApilog = app::get('ome')->model('api_log');
        $log_id = $oApilog->gen_id();
        $params = $this->_apiGiftLog['param'];
        $params['msg_id'] = $this->_apiGiftLog['msg_id'];
        $oApilog->write_log($log_id,$this->_apiGiftLog['title'],'ome_preprocess_tbgift','rpc_request',array('store.item.get', $params, $callback),'','request',$status,implode('<hr/>',$this->_apiGiftLog['info']),'','api.store.gift.rule',$this->_apiGiftLog['order_bn']);
        return true;
    }
}
