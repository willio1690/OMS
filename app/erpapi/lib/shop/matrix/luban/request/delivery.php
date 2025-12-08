<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * [抖音]订单发货回写Lib类
 */
class erpapi_shop_matrix_luban_request_delivery extends erpapi_shop_request_delivery
{
    /**
     * 发货请求参数
     * 
     * @param array $sdf
     * @return array
     */

    protected function get_confirm_params($sdf)
    {
        //组织参数
        $param = parent::get_confirm_params($sdf);
        $param['package_type'] = 'normal';
        //拆单子单回写
        if($sdf['is_split'] == 1 && $sdf['delivery_items']){
            //发货单明细
            $is_split = true;
            $logicList = array();
            $packageList = array();
            foreach ($sdf['delivery_items'] as $key => $val)
            {
                // 拆单要过滤掉赠品
                if ($val['shop_goods_id'] == '-1') {
                    continue;
                }

                $order_oid = $val['oid'];
                $logi_no = $val['logi_no'];
                
                //@todo：可以不用判断,组织发货明细时,已经有判断oid是否被删除
                if(empty($order_oid)){
                    $is_split = false; //没有oid,代表编辑过订单
                    continue;
                }
                
                $logicList[$logi_no] = array(
                        'company_code' => $val['logi_type'], //物流公司编码
                        'company_name' => $val['logi_name'], //物流公司名称
                        'logistics_no' => $val['logi_no'], //物流单号
                );
                
                //[兼容]PKG捆绑商品并且按数量拆分有小数位
                if($val['item_type'] == 'pkg'){
                    $val['number'] = ceil($val['number']);
                    
                    if($val['nums'] && $val['nums'] < $val['number']){
                        $val['number'] = $val['nums'];
                    }
                }
                
                //package
                $packageList[$logi_no][] = array(
                        'oid' => $order_oid, //子单号
                        'amount' => $val['number'], //order_object明细上的实际发货数量
                );
            }
            
            //拆单回写
            if($is_split && $packageList){
                $dataList = array();
                foreach ($packageList as $logi_no => $oidVal)
                {
                    $logicInfo = $logicList[$logi_no];
                    $logicInfo['package_product_list'] = $oidVal;
                    
                    $dataList[] = $logicInfo;
                }
                
                $param['package_type'] = 'break';
                $param['packages'] = json_encode($dataList);
            }
        }
        
        return $param;
    }

    /**
     * confirm
     * @param mixed $sdf sdf
     * @param mixed $queue queue
     * @return mixed 返回值
     */
    public function confirm($sdf, $queue = false)
    {
        $result = parent::confirm($sdf,$queue);
        $deliveryId = $sdf['delivery_id'];
        $orderBn = $sdf['orderinfo']['order_bn'];
        $serialMdl = app::get('ome')->model('product_serial_history');
        $serialList = $serialMdl->getList('serial_number',['bill_type'=>'1','bill_id'=>$deliveryId]);
        if ($serialList) {
            sleep(5);
            kernel::single('erpapi_router_request')->set('shop',$this->__channelObj->channel['shop_id'])->order_serial_sync($serialList,$orderBn);
        }
        return $result;
    }
}