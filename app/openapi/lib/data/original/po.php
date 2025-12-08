<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class openapi_data_original_po{

    /**
     * 添加
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function add($data){
        $result = array('rsp'=>'succ');

        $supplier_mdl = app::get('purchase')->model('supplier');
        $branch_mdl = app::get('ome')->model('branch');
        $po_mdl = app::get('purchase')->model('po');
        $formatFilter=kernel::single('openapi_format_abstract');
        $po_type = array('1'=>'cash','2'=>'credit');

        // 供应商编号
        if ($data['vendor_bn']) {
            $supplier= $supplier_mdl->dump(array('bn'=>$data['vendor_bn']), 'supplier_id');
        }

        if (!$supplier && $data['vendor']) {
            $supplier= $supplier_mdl->dump(array('name'=>$data['vendor']), 'supplier_id');
        }

        $_branch = $branch_mdl->getList('branch_id',array('branch_bn'=>$data['branch_bn']));

        $sdf['supplier_id'] = $supplier['supplier_id'];
        $sdf['operator'] = 'system';
        $sdf['po_type'] = $po_type[$data['type']];
        $sdf['name'] = $formatFilter->charFilter($data['name']);
        $sdf['branch_id'] = $_branch[0]['branch_id'];
        $sdf['arrive_time'] = $data['arrive_time'] ?: 0;
        $sdf['deposit'] = $data['deposit_balance'];
        $sdf['deposit_balance'] = $data['deposit_balance'];
        $sdf['delivery_cost'] = $data['delivery_cost'] ?: 0;
        $sdf['operator'] = $data['operator'];
        $sdf['memo'] = $formatFilter->charFilter($data['memo']);
        $sdf['po_bn'] = $data['po_bn'];
        $sdf['items'] = $data['items'];

        $rs = $po_mdl->savePo($sdf);
        if($rs['status'] == 'success'){
            $result['data'] = $rs['data'];

            // 自动审核
            if ($data['confirm'] == 'Y' && $sdf['po_id']) {
                kernel::single('console_po')->do_check($sdf['po_id']);
            }

        }else{
            $result['rsp'] = 'fail';
            $result['msg'] = $rs['msg'];
        }

        return $result;
    }
    
    /**
     * 获取List
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回结果
     */
    public function getList($filter,$offset=0,$limit=100){
    	$po_mdl = app::get('purchase')->model('po');
    	$poItems_mdl = app::get('purchase')->model('po_items');
        $taoguanIsoMdl = app::get('taoguaniostockorder')->model('iso');
        $taoguanIsoItemsMdl = app::get('taoguaniostockorder')->model('iso_items');
    	$supplier_mod = app::get('purchase')->model('supplier');
    	$branch_mod = app::get('ome')->model('branch');
    	$isostockMdl = app::get('ome')->model('iostock');
        $formatFilter=kernel::single('openapi_format_abstract');
    	if(isset($filter['supplier'])){
            $supplierName = $filter['supplier'];
            $supplier_id = $supplier_mod->getList('supplier_id',array('name'=>$supplierName));
            $supplier_id = $supplier_id[0]['supplier_id'];
            unset($filter['supplier']);
            $filter['supplier_id'] = $supplier_id;
    	}

        $count = $po_mdl->count($filter);
        
        if (!$count) {
            return ['lists' => [], 'count' => 0];
        }

    	$data = $po_mdl->getList('po_id,name as po_name,po_bn,supplier_id as supplier,purchase_time as po_time,amount,operator,branch_id as branch,
    							po_status,statement as statement_status,check_status,eo_status,delivery_cost as logistic_fee,
    							product_cost as item_cost,deposit,deposit_balance,po_species,accos_po_bn',
    							$filter,($offset-1)*$limit,$limit);

        $result = ['lists' => [], 'count' => $count];

        foreach ($data as $k => $v){
            $v['po_time'] = date('Y-m-d H:i:s',$v['po_time']);
            $supplier_row = $supplier_mod->getList('bn,name',array('supplier_id'=>$v['supplier']));
            $supplier_bn = $supplier_row[0]['bn'];
            $supplier_name = $supplier_row[0]['name'];
            $branch = $branch_mod->getList('name,branch_bn',array('branch_id'=>$v['branch']));
            $branch_name = $branch[0]['name'];
            $branch_bn = $branch[0]['branch_bn'];

            $v['supplier'] = $supplier_name;
            $v['supplier_bn'] = $supplier_bn;
            $v['branch'] = $branch_name;
            $v['branch_bn'] = $branch_bn;
            
            //eo list
            $isoList = $taoguanIsoMdl->getList('iso_id,name,iso_bn,memo,branch_id,product_cost as cost,arrival_no,create_time', array('original_id' => $v['po_id'],'original_bn' => $v['po_bn']));
            if (!empty($isoList)) {
                foreach ($isoList as $key => $value) {
                    if ($isoList[$key]['memo']) {
                        
                        $memo = @unserialize($isoList[$key]['memo']);
                        if ($memo){
                            $isoList[$key]['memo'] = str_replace(PHP_EOL, '', implode('、', array_column($memo, 'op_content')));
                        }
                        
                    }
                    $isoItemsList = $taoguanIsoItemsMdl->getList('product_id,product_name,bn as product_bn,price,nums,normal_num,defective_num',
                        array('iso_id' => $value['iso_id']));
                    
                    $branchLib = kernel::single('ome_branch');
                    $isoList[$key]['branch_bn'] = $branchLib->getBranchBnById($isoList[$key]['branch_id']);
                    
                    unset($isoList[$key]['branch_id'],$isoList[$key]['iso_id']);
                    $isoList[$key]['items'] = $isoItemsList;
                    $isoList[$key]['arrival_no'] = empty($isoList[$key]['arrival_no']) ? '' : $isoList[$key]['arrival_no'];
                    $isoList[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
                }
            }
            $v['eo_list'] = $isoList;
            $itemInfos = $poItems_mdl->getList('bn as product_bn,name as product_name, price,num, in_num, defective_num as bad_num,status', array('po_id'=>$v['po_id']));
            $v['po_bn']= $formatFilter->charFilter($v['po_bn']);
            if(!empty($itemInfos)){
                foreach ($itemInfos as $itemInfo){
                    $itemInfo['product_bn']= $formatFilter->charFilter($itemInfo['product_bn']);
                    $itemInfo['product_name']= $formatFilter->charFilter($itemInfo['product_name']);
                }
                $v['items']= $itemInfos;
                $result['lists'][] =$v;
            }
    	}
    	return $result;
    }
    
}
