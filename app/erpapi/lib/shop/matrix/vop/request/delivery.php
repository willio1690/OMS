<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 发货单处理
 *
 * @category
 * @package
 * @author liuzecheng<liuzecheng@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_vop_request_delivery extends erpapi_shop_request_delivery
{
    /**
     * 发货请求参数
     * 
     * @return void
     * @author
     * */

    protected function get_confirm_params($sdf)
    {

        $param = parent::get_confirm_params($sdf);
        unset($param['company_name'], $param['logistics_no']);
        //拆单多个物流单发货
        if($sdf['is_split']){
           unset($param['company_code']);
        }

        $product_list  = array();
        $company_codes = array(); 
        foreach ($sdf['delivery_items'] as $key => $value) {
            if ($value['shop_goods_id'] && $value['shop_goods_id'] != '-1') {
                $product_list[$value['logi_no']][] = array (
                    'barcode' => $value['shop_goods_id'],
                    'amount'  => $value['number'],
                );
               if($sdf['is_split']){
                  $company_codes[$value['logi_no']]['logi_type'] = $value['logi_type'];
               }
            }
        }

        if (kernel::single('ome_order_bool_type')->isJITX($sdf['orderinfo']['order_bool_type'])) {
            // 把删除的也加进去
            if (!$sdf['is_split']) {
                foreach ($sdf['orderinfo']['order_objects'] as $object) {
                    $item = current($object['order_items']);
                    if ($item['delete'] == 'true' && $object['shop_goods_id'] != '-1') {
                        $product_list[$sdf['logi_no']][] = array (
                            'barcode' => $object['shop_goods_id'],
                            'amount'  => $object['quantity'],
                        );
                    }
                }
            }

            $param['store_sn'] = $this->getVopBranch($sdf['store_code']);
            $param['package_num'] = 1;
            $param['tid'] = $sdf['orderinfo']['order_bn'];
            $packages = array ();
            foreach ($product_list as $logiNo => $value) {
                $detail = array();
                foreach ($value as $v) {
                    $detail[] = array(
                        'barcode' => $v['barcode'],
                        'quantity' => $v['amount'],
                    );
                }
                // 判断发货单是否有合单
                if ($sdf['vop_merge_list']) {
                    $detail                    = $sdf['vop_merge_list'];
                    $param['merged_order_sns'] = json_encode(
                        array_values(array_unique(array_column($detail, 'trade_id')))
                    );
                }
                $packages[] = array(
                    'transport_no' => $logiNo,
                    'box_no' => 1,
                    'oqc_date' => $sdf['delivery_time'] ? $sdf['delivery_time'] : time(),
                    'package_no' => 1,
                    'details' =>$detail
                );
            }
            $param['packages'] = json_encode($packages);
        } else {
            if($sdf['delivery_bill_items']){
                foreach($sdf['delivery_bill_items'] as $bv){
                    if ($bv['shop_goods_id'] && $bv['shop_goods_id'] != '-1') {
                        $product_list[$bv['logi_no']][] = array (
                            'barcode' => $bv['shop_goods_id'],
                            'amount'  => $bv['number'],
                        );
                        if($sdf['is_split']){
                           $company_codes[$bv['logi_no']]['logi_type'] = $bv['logi_type'];
                        }
                    }
                }
            }
            $packages = array ();
            foreach ($product_list as $logiNo => $value) {
                $package_detail['package_product_list'] = $value;
                $package_detail['transport_no']         = $logiNo;
                if($sdf['is_split']){
                    $package_detail['company_code']     = $company_codes[$logiNo]['logi_type'];
                }
                $packages[] = $package_detail;
            }
            if(empty($packages)) {
                $packages[] = array(
                    'package_product_list' => array(),
                    'transport_no' => $sdf['logi_no'],
                );
            }
            $param['package_type'] = count($packages) > 1 ? 2 : 1;
            $param['packages'] = json_encode($packages);
        }
        return $param;
    }

        /**
     * printThirdBill
     * @param mixed $sdf sdf
     * @param mixed $branchBn branchBn
     * @return mixed 返回值
     */
    public function printThirdBill($sdf, $branchBn)
    {
        $orders = array();
        foreach ($sdf as $dly) {
            list($ident, $num) = explode('_', $dly['ident']);
            foreach ($dly['orders'] as $order) {
                $orders[] = array(
                    'seq_no' => str_pad($num, 5, '0', STR_PAD_LEFT),
                    'order_id' => $order['order_bn']
                );
            }
        }
        $title = '唯品会打印三单';
        $params = array();
        $params['store_sn'] = $branchBn;
        $params['batch_no'] = $branchBn . '-' . $ident;
        $params['print_type'] = 1;
        $params['orders'] = json_encode($orders);
        $rsp = $this->__caller->call(SHOP_PRINT_THIRD_BILL,$params, array(),$title,10,$ident);
        if($rsp['data']){
            $rsp['data'] = json_decode($rsp['data'], true);
        }
        return $rsp;
    }

    /**
     * 获取DeliveryInfo
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getDeliveryInfo($sdf)
    {
        $title = '唯品会获取发货单信息';
        $params = array();
        $params['store_sn'] = $sdf['branch_bn'];
        $params['orders'] = json_encode($sdf['order_bn']);
        $rsp = $this->__caller->call(SHOP_GET_DLY_INFO,$params, array(),$title,10,$sdf['primary_bn']);
        if($rsp['data']){
            $rsp['data'] = json_decode($rsp['data'], true);
            if($rsp['data']['msg']) {
                $rsp['data'] = json_decode($rsp['data']['msg'], true);
                $rsp['data'] = $rsp['data']['result'];
                foreach ($rsp['data'] as $k=>$val) {
                    $rsp['data'][$k]['order_bn'] = $val['order_id'];
                }
            }
        }
        return $rsp;
    }

    /**
     * 获取VopBranch
     * @param mixed $branch_bn branch_bn
     * @return mixed 返回结果
     */
    public function getVopBranch($branch_bn)
    {
        $branchMdl         = app::get('ome')->model('branch');
        $branchRelationMdl = app::get('ome')->model('branch_relation');

        $branchInfo = $branchMdl->db_dump(['check_permission'=>'false', 'branch_bn'=>$branch_bn]);
        if (!$branchInfo) {
            return $branch_bn;
        }

        $relationInfo = $branchRelationMdl->db_dump(['branch_id'=>$branchInfo['branch_id'], 'type'=>'vopjitx']);
        if (!$relationInfo) {
            return $branch_bn;
        }
        return $relationInfo['relation_branch_bn'];
    }
}
