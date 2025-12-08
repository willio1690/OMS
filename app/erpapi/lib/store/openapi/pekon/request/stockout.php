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
 * @author sunjing
 * @version $Id: Z
 */
class erpapi_store_openapi_pekon_request_stockout extends erpapi_store_request_stockout
{
    protected function _format_stockout_create_params($sdf)
    {
      
        $bill_type = $sdf['bill_type'];

        if($bill_type == 'replenishment'){

           
        }else if($bill_type == 'o2otransfer'){
            return $this->get_o2otransfer_params($sdf);
        }else if($bill_type == 'returnnormal'){
            return $this->get_returnnormal_params($sdf);
        }
    }


    protected function get_o2otransfer_params($sdf){

        $branch_bn      = $sdf['branch_bn'];
        $ext_branch_bn  = $sdf['ext_branch_bn'];

        if(strpos($branch_bn, '_')){
            $branch_bn = explode('_',$branch_bn);
            $warehouseCode = $branch_bn[1];

        }else{
            $warehouseCode = POS_DEFAULT_BRANCH;
        }

        if(strpos($ext_branch_bn, '_')){
            $ext_branch_bn = explode('_',$ext_branch_bn);
            $toWarehouseCode = $ext_branch_bn[1];
        }else{
            $toWarehouseCode = POS_DEFAULT_BRANCH;
        }

        $params = array(
            'thirdPartyDocNo'   =>  $sdf['io_bn'],//第三方系统单号
            'referenceOrderNo'  =>  $sdf['appropriation_no'],//关联上游订货单据
            'orgCode'           =>  $sdf['from_physics'],//调出方单位编码
            'toOrgCode'         =>  $sdf['to_physics'],//调入方单位编码
            'warehouseCode'     =>  $warehouseCode,//调出方仓库编码
            'toWarehouseCode'   =>  $toWarehouseCode,//调入方仓库编码
            'docDate'           =>  date('Y-m-d H:i:s',$sdf['create_time']),//单据日期
          
        );
        $items = [];
        $line_i = 0;
        if ($sdf['items']){
            foreach ((array) $sdf['items'] as $k => $v){
                $line_i++;
                $items[] = [
                    'number'                => $line_i,//明细行序号
                    //'thirdPartyItemNo'      => $v['iso_items_id'],//第三方系统明细编号
                    'skuCode'               => $v['bn'],//商品编码
                    'barCode'               => $v['bn'],//商品条码
        
                    'quantity'              => $v['num'],//发货数量
                   
                ];
            }
        }
        $params['items'] = $items;
        return $params;
    }

    
    protected function get_returnnormal_params($sdf){
        $params = array(
            'thirdPartyDocNo'   =>$sdf['io_bn'],//第三方系统单号
            'docNo'             =>$sdf['appropriation_no'],//关联上游订货单据
           
        );
        $items = [];
        $line_i = 0;
        if ($sdf['items']){
            foreach ((array) $sdf['items'] as $k => $v){
                $line_i++;
                $items[] = [
                    'number'                => $line_i,//明细行序号
                    'barCode'               => $v['bn'],//商品条码
                    'quantity'              => $v['num'],//发货数量
                   
                ];
            }
        }
        $params['items'] = $items;
        
        return $params;
    }


    protected function get_stockout_create_apiname($bill_type)
    {
        if($bill_type == 'o2otransfer'){
            return 'CreateAllocateOutDocument';

        }else if($bill_type == 'replenishment'){
            
        }else if($bill_type == 'returnnormal'){
            return 'ConfirmStockReturnDocument';
        }
        
    }

    
}