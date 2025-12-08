<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单业务
 *
 * @author wangbiao@shopex.cn
 * @version 2024.04.11
 */
class erpapi_dealer_response_process_order
{
    /**
     * Lib类
     **/
    public $platformOrderLib = null;
    
    function __construct()
    {
        $this->platformOrderLib = kernel::single('dealer_platform_orders');
    }
    
    /**
     * 订单接收
     *
     * @return array
     **/

    public function add($ordersdf)
    {
        //接收的平台订单数据
        $originalSdf = array();
        if(isset($ordersdf['originalSdf'])){
            $originalSdf = $ordersdf['originalSdf'];
            unset($ordersdf['originalSdf']);
        }
        
        //operation
        $is_create = false;
        if ($ordersdf['plat_order_id']) {
            if ($ordersdf['flag'] == 'close'){
                //取消订单
                $result = $this->_closeorder($ordersdf);
            }else{
                //更新订单
                $result = $this->_updateOrder($ordersdf);
            }
        } else {
            /**
             * @todo：创建订单注意事项；
             * 1、创建订单之前组织订单明细就转换好是否代发货、自发货、贸易公司ID；
             * 2、创建订单完成时，库存只冻结：代发货方式的基础物料；
             * 3、创建订单完成后，根据配置项进行hold单(默认6小时)，防止几小时内有退款申请；
             * 4、订单不是已支付状态，则不允许处理，判断pay_status='1'；
             */
            $result = $this->_createOrder($ordersdf);
            if($result['rsp'] == 'succ' && isset($result['data'])){
                $ordersdf['plat_order_id'] = $result['data']['plat_order_id'];
            }
            
            $is_create = true;
        }
        
        //后续处理
        if($result['rsp'] == 'succ' && $originalSdf){
            if($ordersdf['plat_order_id'] && $ordersdf['plat_order_bn']){
                //保存平台原始订单信息
                $saveSdf = array(
                    'plat_order_id' => $ordersdf['plat_order_id'],
                    'plat_order_bn' => $ordersdf['plat_order_bn'],
                    'extend_info' => json_encode($originalSdf),
                    'last_modified' => time(),
                );
                
                //订单创建时间
                if($is_create){
                    $saveSdf['create_time'] = time();
                }
                
                app::get('dealer')->model('platform_order_extend')->save($saveSdf);
                
                //创建订单完成后
                if($is_create && $ordersdf['is_fail'] != 'true'){
                    //设置hold单，防止避免发货前退款；并且放入队列任务里,延迟自动审单；
                    $this->platformOrderLib->setOrderHoldTime($ordersdf['plat_order_id']);
                }
            }
        }
        
        return $result;
    }
    
    /**
     * 创建订单
     * 
     * @return array
     * */
    private function _createOrder($ordersdf)
    {
        //check
        if (!$ordersdf){
            return array('rsp'=>'fail','msg'=>'创建失败：格式化数据为空');
        }
        
        if (empty($ordersdf['plat_order_bn'])){
            return array('rsp'=>'fail', 'msg'=>'平台订单号为空,请检查');
        }
        
        //create
        $error_msg = '';
        $rs = $this->platformOrderLib->create_order($ordersdf, $error_msg);
        if (!$rs) {
            $errorinfo = kernel::database()->errorinfo();
            $error_msg = $error_msg . ($errorinfo ? ','.$errorinfo : '');
            
            return array('rsp'=>'fail', 'msg'=>$error_msg, 'data'=>array('plat_order_bn'=>$ordersdf['order_bn']));
        }
        
        $msg = '返回值：平台订单创建成功！订单ID：'. $ordersdf['plat_order_id'];
        
        $data = array('plat_order_id'=>$ordersdf['plat_order_id'], 'plat_order_bn'=>$ordersdf['plat_order_bn']);
        
        return array('rsp'=>'succ', 'msg'=>$msg, 'data'=>$data);
    }
    
    /**
     * 更新订单
     * 
     * @return void
     * */
    private function _updateOrder($ordersdf)
    {
        $jxOrderMdl = app::get('dealer')->model('platform_orders');
        
        //sdf
        $newOrderSdf = $ordersdf;
        $plat_order_id = $ordersdf['plat_order_id'];
        
        //unset
        unset($newOrderSdf['status'], $newOrderSdf['plat_order_id'], $newOrderSdf['plat_order_bn']);
        
        //update
        if ($newOrderSdf && $plat_order_id) {
            //暂停订单&&取消OMS订单
            if ($newOrderSdf['pay_status'] == '6' && $newOrderSdf['pause'] == 'true'){
                $stopResult = $this->platformOrderLib->pauseDealerOrder($plat_order_id);
            }
            
            //更新订单主信息
            $plainData = $jxOrderMdl->sdf_to_plain($newOrderSdf);
            $rs = $jxOrderMdl->update($plainData, array('plat_order_id'=>$plat_order_id));
            
            //更新订单明细
            $saveRs = $jxOrderMdl->_save_depends($newOrderSdf);
        }
        
        //是否撤回发货单
        if ($ordersdf){
            $this->_afterUpdate($ordersdf);
        }
        
        $msg = $ordersdf['status'] == 'dead' ? '经销订单取消成功' : '经销订单更新成功,影响行数:'.intval($rs);
        
        return array('rsp'=>'succ', 'msg'=>$msg);
    }
    
    /**
     * 更新后，是否撤回发货单
     * 
     * @return void
     * @author
     * */
    private function _afterUpdate($ordersdf)
    {
        //订单信息
        $filter = array('plat_order_id'=>$ordersdf['plat_order_id']);
        $tgorder = $this->platformOrderLib->getOrderDetail($filter);
        
        //是否允许自动审核订单
        $is_review_order = false;
        $is_modify_consignee = false;
        $write_log = array();
        
        //订单收货人信息被修改
        if ($ordersdf['consignee']) {
            $write_log[] = array(
                'obj_id'    => $tgorder['plat_order_id'],
                'obj_name'  => $tgorder['plat_order_bn'],
                'operation' => 'order_modify@dealer',
                'memo'      => '订单收货人信息被修改',
            );
            
            $is_review_order = true;
            $is_modify_consignee = true;
        }
        
        //订单商家备注被修改
        if ($ordersdf['mark_text']) {
            $write_log[] = array(
                'obj_id'    => $tgorder['plat_order_id'],
                'obj_name'  => $tgorder['plat_order_bn'],
                'operation' => 'order_modify@dealer',
                'memo'      => '订单商家备注被修改',
            );
            
            $is_review_order = false;
        }
        
        //前端订单商品信息修改
        if ($ordersdf['order_objects']) {
            $write_log[] = array(
                'obj_id'    => $tgorder['plat_order_id'],
                'obj_name'  => $tgorder['plat_order_bn'],
                'operation' => 'order_modify@dealer',
                'memo'      => '前端订单商品信息修改',
            );
            
            $is_review_order = false;
        }
        
        //批量写日志
        if ($write_log) {
            $logObj = app::get('ome')->model('operation_log');
            $logObj->batch_write_log2($write_log);
        }
        
        // 非活动订单，已发货，部分发货不做处理
        if ($tgorder['status'] != 'active' || !in_array($tgorder['ship_status'],array('0','2')) ){
            return true;
        }
        
        //检查订单状态
        if ($ordersdf['pay_status'] == '5' || $ordersdf['status'] == 'dead') {
            if($tgorder['ship_status'] == 0){
                //未发货进行取消订单
                $cancelRs = $this->platformOrderLib->canceldealerOrder($tgorder);
            } elseif($tgorder['ship_status'] == 2) {
                //部分发货也要取消订单
                $cancelRs = $this->platformOrderLib->canceldealerOrder($tgorder);
            }
            
            return true;
        }
        
        // 如果已经拆分
        if (in_array($tgorder['process_status'], array('splited','splitting'))) {
            $cancel_oms_order = false;
            
            //收货人信息发生变更
            //@todo：删除了 $ordersdf['consignee']['telephone'] 字段；
            if ($ordersdf['consignee']['name'] || $ordersdf['consignee']['area'] || $ordersdf['consignee']['addr'] || $ordersdf['consignee']['mobile']) {
                $is_modify_consignee = true;
            }elseif ($ordersdf['pay_status'] == '4') {
                //部分退款
                $cancel_oms_order = true;
            }elseif ($ordersdf['order_objects']) {
                //明细发生变更
                $checkObj  = array('bn','quantity');
                $checkItem = array('bn','quantity','delete');
                
                //objects
                foreach ($ordersdf['order_objects'] as $order_object)
                {
                    if (array_intersect($checkObj, array_keys($order_object))) {
                        $cancel_oms_order = true;
                        break;
                    }
                    
                    if ($order_object['order_items']){
                        foreach ($order_object['order_items'] as $order_item) {
                            if (array_intersect($checkItem, array_keys($order_item))) {
                                $cancel_oms_order = true;
                                break 2;
                            }
                        }
                    }
                }
            }
            
            //取消OMS订单&&撤消OMS发货单
            if ($cancel_oms_order) {
                //取消OMS订单、发货单
                $cancelRs = $this->platformOrderLib->cancelOmsOrder($tgorder);
                
                //订单恢复暂停状态
                $renewRs = $this->platformOrderLib->renewDealerOrder($tgorder['plat_order_id']);
                
                return true;
            }
            
            //有备注暂停订单
            //$orderPauseAllow = app::get('ome')->getConf('ome.orderpause.to.syncmarktext');
            //if ($ordersdf['mark_text'] && $orderPauseAllow !== 'false') {
            //    $stopResult = $this->platformOrderLib->pauseDealerOrder($tgorder['plat_order_id']);
            //    return true;
            //}
        }
        
        //平台原订单顾客修改地址(仅撤消OMS发货单,不用取消OMS订单)
        if($is_modify_consignee){
            $result = $this->platformOrderLib->pausePlatformOrder($tgorder['plat_order_id']);
        }
        
        //千牛修改收货人地址是否自动审核订单
        if($is_review_order && in_array($tgorder['process_status'], array('unconfirmed', 'confirmed'))){
            if($tgorder['pay_status'] == '1' && $tgorder['status'] == 'active' && $tgorder['ship_status'] == '0'){
                //订单恢复暂停状态
                $this->platformOrderLib->renewDealerOrder($tgorder['plat_order_id']);
                
                //设置hold单，防止避免发货前退款；并且放入队列任务里,延迟自动审单；
                $this->platformOrderLib->setOrderHoldTime($tgorder['plat_order_id']);
            }
        }
        
        return true;
    }
    
    /**
     * 更新订单状态
     * 
     * @param $sdf
     * @return array|false
     */
    public function status_update($sdf)
    {
        $jxOrderMdl = app::get('dealer')->model('platform_orders');
        
        if (!$sdf['plat_order_id']){
            return array('rsp'=>'fail', 'msg'=>'经销订单ID不存在');
        }
        
        //update
        $affect_row = $jxOrderMdl->update($sdf, array('plat_order_id'=>$sdf['plat_order_id']));
        
        //订单是取消状态
        if ($sdf['status'] == 'dead') {
            $logObj = app::get('ome')->model('operation_log');
            $jxOrderLib = kernel::single('dealer_platform_orders');
            
            //获取订单信息
            $filter = array('plat_order_id'=>$sdf['plat_order_id']);
            $orderInfo = $jxOrderLib->getOrderMainInfo($filter);
            
            //cancel
            $cancelResult = $jxOrderMdl->canceldealerOrder($orderInfo);
            if($cancelResult['rsp'] != 'succ'){
                $error_msg = '前端平台订单已取消，OMS取消失败：'. $cancelResult['error_msg'];
            }else{
                $error_msg = '前端平台订单已取消，OMS取消成功';
            }
            
            //logs
            $logObj->write_log('order_back@dealer', $sdf['plat_order_id'], $error_msg);
        }
        
        return array('rsp'=>'succ', 'msg'=>'更新订单成功，影响行数：'.$affect_row);
    }
    
    /**
     * 取消订单
     * 
     * @param $sdf
     * @return string[]
     */
    private function _closeorder($sdf)
    {
        //创建退款单、更新订单支付状态、撤消OMS订单
        $result = $this->platformOrderLib->closeOrder($sdf);
        
        $msg = '关闭经销订单成功'. ($result['rsp'] != 'succ' ? '：'. $result['error_msg'] : '');
        return array('rsp'=>'succ', 'msg'=>$msg);
    }
}