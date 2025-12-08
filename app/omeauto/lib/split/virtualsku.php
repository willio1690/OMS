<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * [按虚拟商品拆单]拆出虚拟商品自动发货
 */
class omeauto_split_virtualsku extends omeauto_split_abstract
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
        $sql = "SELECT sid FROM `sdb_omeauto_order_split` WHERE split_type='virtualsku'";
        $splitInfo = kernel::database()->selectrow($sql);
        if($splitInfo && empty($sdf['sid'])){
            return array(false, '已存在虚拟商品拆单规则,不能重复添加。');
        }elseif($splitInfo && $sdf['sid'] != $splitInfo['sid']){
            return array(false, '已存在虚拟商品拆单规则,不能重复添加!');
        }
        
        if(empty($sdf['split_config']['branch_id'])) {
            return array(false, '请选择发货的仓库!');
        }
        
        return array(true, '保存成功');
    }

    /**
     * 执行拆分订单
     * 
     * @param object $group
     * @param array $splitConfig
     * @return array
     */
    public function splitOrder(&$group, $splitConfig)
    {
        //获取订单信息
        $arrOrder = $group->getOrders();
        
        //订单商品明细
        $arrProductId = array();
        foreach ($arrOrder as $orderKey => $orderVal)
        {
            foreach ($orderVal['objects'] as $objKey => $objVal)
            {
                foreach ($objVal['items'] as $itemKey => $itemVal)
                {
                    $product_id = $itemVal['product_id'];
                    $arrProductId[$product_id] = $product_id;
                }
            }
        }
        
        //获取虚拟商品
        $basicMaterialObj = app::get('material')->model('basic_material');
        $tempList = $basicMaterialObj->getList('*', array('bm_id'=>$arrProductId));
        
        $virtualBns = array();
        foreach ($tempList as $key => $val)
        {
            $bm_id = $val['bm_id'];
            
            if($val['type'] == 5){
                $virtualBns[$bm_id] = $val['material_bn'];
            }
        }
        
        //拆分虚拟商品
        $virtualOrder = array();
        $noOrder = array();
        $arrOrderId = array();
        foreach ($arrOrder as $orderKey => $orderVal)
        {
            $virtualItem = array();
            $noItem = array();
            foreach ($orderVal['objects'] as $objKey => $objVal)
            {
                //@todo：如果捆绑商品、福袋类型中有虚拟商品,则不进行拆分
                if(!in_array($objVal['obj_type'], array('pkg', 'lkb')))
                {
                    $is_virtual = false;
                    foreach ($objVal['items'] as $itemKey => $itemVal)
                    {
                        $product_id = $itemVal['product_id'];
                        
                        if($virtualBns[$product_id]){
                            $is_virtual = true;
                        }
                    }
                    
                    if($is_virtual){
                        $virtualItem[$objKey] = $objVal;
                    }else{
                        $noItem[$objKey] = $objVal;
                    }
                }
                else 
                {
                    $noItem[$objKey] = $objVal;
                }
            }
            
            //不拆分订单明细
            if($virtualItem) {
                $virtualOrder[$orderKey] = $orderVal;
                $virtualOrder[$orderKey]['objects'] = $virtualItem;
            }
            
            //不拆分订单明细
            if($noItem) {
                $arrOrderId[] = $orderVal['order_id'];
                
                $noOrder[$orderKey] = $orderVal;
                $noOrder[$orderKey]['objects'] = $noItem;
            }
        }
        
        //拆分订单
        if($virtualOrder){
            //设置发货仓库
            $branch_id = $splitConfig['branch_id'];
            if($branch_id){
                $group->setBranchId(array($branch_id));
                $group->setConfirmBranch(true);
            }
            
            //设置发货物流公司
            $branchIdCorpId = array();
            $corp_id = $splitConfig['corp_id'];
            if($corp_id && $branch_id){
                //读取拆单规则里设置的物流公司
                $branchIdCorpId[$branch_id] = $corp_id;
                $group->setBranchIdCorpId($branchIdCorpId);
            }elseif($branch_id){
                //默认读取仓库关联的第一个物流公司
                $branchCorpObj = app::get('ome')->model('branch_corp');
                $corpInfo = $branchCorpObj->dump(array('branch_id'=>$branch_id), '*');
                if($corpInfo){
                    $branchIdCorpId[$branch_id] = $corpInfo['corp_id'];
                    $group->setBranchIdCorpId($branchIdCorpId);
                }
            }
            
            //设置物流运单号
            //if($branchIdCorpId){
            //    $logi_no = 'V'.uniqid();
            //    $group->setWaybillCode($logi_no);
            //}
            
            //设置自动发货标识
            $group->setAutoDelivery();
            
            //还有其它订单明细,设置继续拆单
            if($arrOrderId){
                $group->setSplitOrderId($arrOrderId);
            }
            
            $group->updateOrderInfo($virtualOrder);
        } else {
            //不拆分订单
            if($noOrder) {
                $group->updateOrderInfo($noOrder);
            } else {
                return array(false, '没有可拆单的订单');
            }
        }
        
        return array(true);
    }
}