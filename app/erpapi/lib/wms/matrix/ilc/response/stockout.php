<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 出库单
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_ilc_response_stockout extends erpapi_wms_response_stockout
{
    /**
     * status_update
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function status_update($params){
        
        //唯品会出库单回传
        $stockout_bn    = ($params['stockout_bn'] ? $params['stockout_bn'] : $params['out_order_code']);
        $vop_type       = substr($stockout_bn, 0, 3);
        
        //唯品会出库单数据
        if($vop_type == 'VOP')
        {
            $params    = $this->vop_status_update($params);
            
            return $params;
        }
        
        //组织数据
        $params = parent::status_update($params);
        
        if ($params['items']){
            foreach ((array) $params['items'] as $key=>$item){
                $barcode = $item['bn'] ? $item['bn'] : $item['product_bn'];

                // 条码转货号
                if ($barcode) {
                    $bn = kernel::single('material_codebase')->getBnBybarcode($barcode);
                    $params['items'][$key]['bn'] = $params['items'][$key]['product_bn'] = $bn ? $bn : $barcode;    
                }
            }
        }

        return $params;
    }
    
    /**
     * 获取唯品会出库单数据
     */
    public function vop_status_update($params)
    {
        $this->__apilog['title']       = $this->__channelObj->wms['channel_name'].'出库单'.$params['stockout_bn'];
        $this->__apilog['original_bn'] = $params['stockout_bn'];
        
        //运单号
        $logi_no    = ($params['logistics_code'] ? $params['logistics_code'] : $params['logi_no']);
        
        $data = array(
                'io_bn'           => $params['stockout_bn'],
                'branch_bn'       => $params['warehouse'],
                'io_status'       => $params['status'] ? $params['status'] : $params['io_status'],
                'memo'            => $params['remark'],
                'operate_time'    => $params['operate_time'] ? $params['operate_time'] : date('Y-m-d H:i:s'),
                'logi_no'         => $logi_no,
                'out_delivery_bn' => $params['out_delivery_bn'],
                'logi_id'         => $params['logistics'],
                'wms_id'         => $this->__channelObj->wms['channel_id'],
        );
        
        //出库类型
        $stockoutLib        = kernel::single('purchase_purchase_stockout');
        $data['io_type']    = $stockoutLib::_io_type;
        
        //装箱明细
        $boxItem    = array();
        $items      = isset($params['item']) ? json_decode($params['item'], true) : array();
        if($items)
        {
            foreach($items as $key=>$val)
            {
                $barcode    = $val['product_bn'] ? $val['product_bn'] : $val['bn'];
                
                if(empty($barcode) || empty($val['pick_bn']) || empty($val['box_no']))
                {
                    //没有传pick_bn、box_no、$barcode直接报错返回
                    $error_msg = '';
                    if(empty($barcode)){
                        $error_msg .= "没有条形码;";
                    }
                    if(empty($val['pick_bn'])){
                        $error_msg .= "没有拣货单号;";
                    }
                    if(empty($val['box_no'])){
                        $error_msg .= "没有装箱号;";
                    }
                    
                    return array('rsp'=>'fail', 'err_msg'=>$error_msg);
                }
                
                //条码转货号
                if ($barcode)
                {
                    $product_bn    = kernel::single('material_codebase')->getBnBybarcode($barcode);
                    $val['product_bn']    = $product_bn ? $product_bn : $barcode;
                }
                
                //组织数据
                $boxItem[]    = array(
                        'po_bn'=>$val['po_bn'],//采购单单号
                        'pick_bn'=>$val['pick_bn'],//拣货单单号
                        'box_no'=>$val['box_no'],//装箱箱号
                        'bn'=>$val['product_bn'],//货品编码
                        'num'=>$val['num'],//数量
                );
            }
        }
        $data['items'] = $boxItem;
        
        //保存出库单和装箱信息(如果是取消则跳过)
        if(strtoupper($data['io_status']) != 'CANCEL'){
            $result    = $this->saveStockout($data);
            if(!$result)
            {
                return false;
            }
        }
        
        return $data;
    }
    
    /**
     * [唯品会]保存装箱信息和运单号
     */
    function saveStockout($data)
    {
        $stockoutObj     = app::get('purchase')->model('pick_stockout_bills');
        $stockitemObj    = app::get('purchase')->model('pick_stockout_bill_items');
        $pickObj         = app::get('purchase')->model('pick_bills');
        $logObj          = app::get('ome')->model('operation_log');
        $boxLib          = kernel::single('purchase_purchase_box');
        
        if(empty($data['items']))
        {
            return false;
        }
        
        //出库单信息
        $stockoutDetail  = $stockoutObj->dump(array('stockout_no'=>$data['io_bn']), '*');
        $stockout_id     = $stockoutDetail['stockout_id'];
        $branch_out_num  = 0;
        
        //检查出库单状态(单据已完成 || 已全部出库 )
        if($stockoutDetail['status']==3 || $stockoutDetail['o_status']==3)
        {
            return true;
        }
        
        //保存装箱明细
        $boxItem    = $data['items'];
        foreach ($boxItem as $key => $val)
        {
            $po_bn          = $val['po_bn'];
            $pick_bn        = $val['pick_bn'];
            $bn             = $val['bn'];
            
            //拣货单信息
            $pickInfo    = $pickObj->dump(array('pick_no'=>$pick_bn), 'bill_id, po_id');
            $bill_id     = $pickInfo['bill_id'];
            
            //出库单明细
            $row_item    = $stockitemObj->dump(array('stockout_id'=>$stockout_id, 'bill_id'=>$bill_id, 'bn'=>$bn), 'stockout_item_id, bill_id, po_id');
            $val['stockout_id']      = $stockout_id;
            $val['stockout_item_id'] = $row_item['stockout_item_id'];
            $val['bill_id']          = $row_item['bill_id'];
            $val['po_id']            = $row_item['po_id'];
            
            $val['box_num']   = $val['num'];
            $boxItem[$key]    = $val;
            
            //仓库出库数量
            $branch_out_num    += $val['num'];
        }
        
        //批量保存
        $result    = $boxLib->batch_create($boxItem);
        
        //更新运单号、仓库出库数量
        $delivery_no    = $data['logi_no'];
        $stockoutObj->update(array('delivery_no'=>$delivery_no, 'branch_out_num'=>$branch_out_num), array('stockout_id'=>$stockout_id));
        
        //logs
        $log_msg    = $this->__apilog['title'].',响应出库成功';
        $logObj->write_log('check_stockout_bills@ome', $stockout_id, $log_msg);
        
        return $result;
    }
}
