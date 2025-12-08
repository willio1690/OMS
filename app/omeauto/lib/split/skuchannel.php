<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 按京东开普勒商品关联渠道ID拆,并自动发货
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class omeauto_split_skuchannel extends omeauto_split_abstract
{
    //拆单规则配置获取数据
    /**
     * 获取Special
     * @return mixed 返回结果
     */

    public function getSpecial()
    {
        return array();
    }
    
    //拆单规则保存前处理
    /**
     * preSaveSdf
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function preSaveSdf(&$sdf)
    {
        $sql = "SELECT sid FROM `sdb_omeauto_order_split` WHERE split_type='skuchannel'";
        $splitInfo = kernel::database()->selectrow($sql);
        if($splitInfo){
            if(empty($sdf['sid']) || $sdf['sid'] != $splitInfo['sid']){
                return array(false, '已存在虚拟商品拆单规则,不能重复添加。');
            }
        }
        
        return array(true, '保存成功');
    }

    /**
     * 执行拆分订单
     * 
     * @param object $group
     * @param array $splitConfig 默认空值
     * @return array
     */
    public function splitOrder(&$group, $splitConfig)
    {
        $orderObj = app::get('ome')->model('orders');
        
        //获取订单信息
        $arrOrder = $group->getOrders();
        
        //订单商品明细
        $order_ids = array();
        $bnList = array();
        foreach ($arrOrder as $orderKey => $orderVal)
        {
            $order_ids[] = $orderVal['order_id'];
            
            foreach ($orderVal['objects'] as $objKey => $objVal)
            {
                foreach ($objVal['items'] as $itemKey => $itemVal)
                {
                    //过滤已经拆分生成发货单的SKU货品
                    $itemVal['nums'] = $itemVal['nums'] - $itemVal['split_num'];
                    if($itemVal['nums'] < 1) {
                        continue;
                    }
                    
                    $bn = $itemVal['bn'];
                    $bnList[$bn] = $bn;
                }
            }
        }
        
        if(empty($bnList)){
            return array(false, '自动审单失败：没有可审核的SKU货品');
        }
        
        
        //获取SKU货品关联的京东云交易渠道ID
        $error_msg = '';
        $channelBns = $this->getBnsChannelIds($order_ids, $bnList, $error_msg);
        
        //所有SKU货品都没有渠道ID
        if($channelBns == 'empty'){
            return array(true); //直接返回：走整单审核流程
        }
        
        if(!$channelBns){
            return array(false, '自动审单失败：'.$error_msg);
        }
        
        
        //循环渠道ID进行拆单(只找第一个可拆单的,后面会继续放MQ进行下一次拆单)
        foreach ($channelBns as $channel_id => $product_bns)
        {
            //按SKU货品渠道ID拆分订单明细&&检查库存
            $splitOrder = array();
            $reOrderIds = array();
            $branch_id = 0;
            $isCheck = $this->getChannelSplitOrders($arrOrder, $product_bns, $splitOrder, $reOrderIds, $branch_id);
            if($isCheck['rsp'] != 'succ'){
                continue;
            }
            
            //当前渠道没有可拆分的明细
            if(empty($splitOrder)){
                continue;
            }
            
            //设置使用的仓库编号
            if($branch_id){
                $group->setBranchId(array($branch_id));
            }
            
            //设置本次拆单使用的渠道ID
            foreach ($splitOrder as $oderKey => $orderVal)
            {
                $splitOrder[$oderKey]['channel_id'] = $channel_id;
            }
            
            //已经检查了库存
            if($isCheck['check_store'] === true){
                //本次需要拆单的货品明细
                $group->updateOrderInfo($splitOrder);
                
                //继续拆单
                if($reOrderIds) {
                    $group->setSplitOrderId($reOrderIds);
                }
                
                //已验证库存,设置打标(后面meauto_auto_plugin_store插件,就不会再验证库存)
                $group->setConfirmBranch(true);
                
                return array(true);
            }else{
                //本次需要拆单的货品明细
                $group->updateOrderInfo($splitOrder);
                
                //继续拆单
                if($reOrderIds) {
                    $group->setSplitOrderId($reOrderIds);
                }
                
                return array(true);
            }
        }
        
        return array(false, '没有找到SKU货品可以按渠道ID拆单');
    }
    
    /**
     * 获取SKU货品关联的京东云交易渠道ID
     * 
     * @param array $order_ids
     * @param array $bnList
     * @param string $error_msg
     * @return array
     */
    public function getBnsChannelIds($order_ids, $bnList, &$error_msg=null)
    {
        $orderObj = app::get('ome')->model('orders');
        
        //获取货品关联的京东云交易渠道ID
        $channelBns = array();
        $checkBns = array();
        $abnormalSale = array();
        $noChannelBns = array();
        $empty_channel = false;
        foreach ($bnList as $bnKey => $product_bn)
        {
            //获取上架状态：只拿最新2条数据
            $sql = "SELECT * FROM sdb_material_basic_material_channel WHERE material_bn='". $product_bn ."' ORDER BY approve_status,id DESC LIMIT 0,2";
            $channelList = $orderObj->db->select($sql);
            if(empty($channelList)){
                continue;
            }
            
            //标记SKU货品有关联渠道
            $empty_channel = true;
            
            //检查商品关联的渠道ID
            $is_flag = false;
            foreach ($channelList as $key => $val)
            {
                $channel_id = $val['channel_id'];
                
                if($val['approve_status']=='1'){
                    if(empty($checkBns[$product_bn])){
                        $checkBns[$product_bn] = 1;
                    }else{
                        $checkBns[$product_bn]++;
                    }
                    
                    //商品在多个渠道上架,则异常
                    if($checkBns[$product_bn] > 1){
                        $abnormalSale[] = $product_bn;
                    }
                    
                    $is_flag = true;
                    $channelBns[$channel_id][] = $product_bn;
                }
            }
            
            //没有渠道ID
            if(!$is_flag){
                $noChannelBns[] = $product_bn;
            }
        }
        
        //所有SKU货品都没有渠道ID
        if(!$empty_channel){
            return 'empty';
        }
        
        //[商品有多个渠道ID]设置订单为异常,后面人工处理
        if($abnormalSale){
            $opInfo = kernel::single('ome_func')->getDesktopUser();
            $error_msg = '货号：'.implode(',', $abnormalSale).' 在多个渠道在架;';
            
            foreach ($order_ids as $key => $order_id)
            {
                $abnormal_data = array();
                $abnormal_data['order_id'] = $order_id;
                $abnormal_data['op_id'] = $opInfo['op_id'];
                $abnormal_data['group_id'] = 0;
                $abnormal_data['abnormal_type_id'] = 999; //订单异常类型
                $abnormal_data['is_done'] = 'false';
                $abnormal_data['abnormal_memo'] = $error_msg;
                
                $orderObj->set_abnormal($abnormal_data);
            }
            
            return false;
        }
        
        //[商品没有渠道ID]设置订单为异常,后面人工处理
        if($noChannelBns){
            $opInfo = kernel::single('ome_func')->getDesktopUser();
            $error_msg = '货号：'.implode(',', $noChannelBns).' 没有找到渠道ID;';
            
            foreach ($order_ids as $key => $order_id)
            {
                $abnormal_data  = array();
                $abnormal_data['order_id'] = $order_id;
                $abnormal_data['op_id'] = $opInfo['op_id'];
                $abnormal_data['group_id'] = 0;
                $abnormal_data['abnormal_type_id'] = 999; //订单异常类型
                $abnormal_data['is_done'] = 'false';
                $abnormal_data['abnormal_memo'] = $error_msg;
                
                $orderObj->set_abnormal($abnormal_data);
            }
            
            return false;
        }
        
        unset($checkBns, $abnormalSale, $noChannelBns, $empty_channel);
        
        return $channelBns;
    }
    
    /**
     * 获取按SKU货品渠道ID拆分好的订单明细
     * 
     * @param array $orderList
     * @param array $product_bns
     * @param array $reOrderIds 本次需要拆分的订单明细
     * @param array $reOrderIds 剩余需要审单的order_id
     * @return array
     */
    public function getChannelSplitOrders($orderList, $product_bns, &$splitOrder=null, &$reOrderIds=null, &$branch_id=0)
    {
        $result = array('rsp'=>'fail', 'check_store'=>false, 'error_msg'=>'');
        
        if(empty($orderList) || empty($product_bns)){
            $result['error_msg'] = '订单或者SKU渠道货品不能为空;';
            return $result;
        }
        
        $braProductObj = app::get('ome')->model('branch_product');
        $basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');
        
        //指定发货的仓库
        $store_code = '';
        
        //按渠道ID拆分订单明细
        $splitOrder = array();
        $reOrderIds = array();
        $product_ids = array();
        $item_bns = array();
        foreach ($orderList as $orderKey => $orderVal)
        {
            $splitObjects = array();
            foreach ($orderVal['objects'] as $objKey => $objVal)
            {
                $splitItems = array();
                foreach ($objVal['items'] as $itemKey => $itemVal)
                {
                    $item_bn = $itemVal['bn'];
                    $product_id = $itemVal['product_id'];
                    
                    //对应渠道的SKU货品
                    if(in_array($item_bn, $product_bns)){
                        $splitItems[$itemKey] = $itemVal;
                        
                        //发货的数量
                        $item_nums = $itemVal['nums'] - $itemVal['split_num'];
                        $product_ids[$product_id] += $item_nums;
                        
                        $item_bns[$product_id] = $item_bn;
                    }else{
                        $reOrderIds[] = $orderVal['order_id'];
                    }
                }
                
                if($splitItems){
                    unset($objVal['items']);
                    $splitObjects[$objKey] = $objVal;
                    $splitObjects[$objKey]['items'] = $splitItems;
                    
                    //指定发货的仓库
                    if(empty($store_code)){
                        $store_code = $objVal['store_code'];
                    }
                }
            }
            
            if($splitObjects){
                unset($orderVal['objects']);
                
                //可拆单的明细
                $orderVal['objects'] = $splitObjects;
                
                $splitOrder[$orderKey] = $orderVal;
            }
        }
        
        //没有有指定仓库
        if(empty($store_code)){
            $result['rsp'] = 'succ';
            return $result;
        }
        
        //获取指定仓库branch_id
        $sql = "SELECT branch_id FROM sdb_ome_branch WHERE branch_bn='". $store_code ."'";
        $branchInfo = kernel::database()->selectrow($sql);
        if(empty($branchInfo)){
            $result['rsp'] = 'succ';
            return $result;
        }
        
        $branch_id = $branchInfo['branch_id'];
        
        //没有指定仓关联库存
        $tempList = $braProductObj->getList('product_id,branch_id,store', array('product_id'=>array_keys($product_ids), 'branch_id'=>$branch_id));
        if(empty($tempList)){
            $result['rsp'] = 'succ';
            return $result;
        }
        
        //检查指定仓的库存
        foreach ($tempList as $key => $val)
        {
            $product_id = $val['product_id'];
            $store = $val['store'];
            
            //根据仓库ID、基础物料ID获取该物料仓库级的预占
            $store_freeze = $basicMStockFreezeLib->getBranchFreeze($product_id, $val['branch_id']);
            
            //可用库存
            $store = ($store < $store_freeze) ? 0 : ($store - $store_freeze);
            
            //检查库存
            $item_nums = $product_ids[$product_id];
            if($item_nums > $store){
                $result['error_msg'] = 'bn：'.$item_bns[$product_id].'库存不足;';
                return $result;
            }
        }
        
        unset($product_ids, $item_bns, $store_code, $branchInfo, $tempList);
        
        $result['rsp'] = 'succ';
        $result['check_store'] = true;
        return $result;
    }
}