<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_event_trigger_logistics_data_electron_360buy extends wms_event_trigger_logistics_data_electron_common {

    
    private function getPayMoney($orderIds, $orderIdExtend) {
        $money = 0;
        foreach ($orderIds as $orderId) {
            $money += $orderIdExtend[$orderId]['receivable'];
        }
        return $money;
    }

    /**
     * 获取DirectSdf
     * @param mixed $arrDelivery arrDelivery
     * @param mixed $arrBill arrBill
     * @param mixed $shop shop
     * @return mixed 返回结果
     */
    public function getDirectSdf($arrDelivery, $arrBill, $shop) {
        $delivery = $arrDelivery[0];
        //获取是否京配判断是否配置了打印项
        if(empty($arrBill)) {
            $objExpress = kernel::single('logisticsmanager_print_express');
            $corp = app::get('ome')->model('dly_corp')->dump(array('corp_id'=>$delivery['logi_id']),'prt_tmpl_id');
            $rs = $objExpress->getExpressTpl($corp['prt_tmpl_id']);
            
            if($rs) {
                $jpField = array('jdsourcet_sort_center_name','jdoriginal_cross_tabletrolley_code','jdtarget_sort_center_name','jddestination_cross_tabletrolley_code','jdsite_name','jdroad','jdaging_name');
                $hasJpField = array_intersect($objExpress->printField, $jpField);
                if(count($hasJpField)) {
                    $data = $this->jdEtmsRangeCheck($delivery);
                    if (empty($data)) {
                        $this->directRet['fail'][] = array(
                            'delivery_id' => $delivery['delivery_id'],
                            'delivery_bn' => $delivery['delivery_bn'],
                            'msg' => '发货单不能京配'
                        );
                        return false;
                    }
                }
            }
            $this->needRequestId[] = $delivery['delivery_id'];
        } else { // 京东没有补打单
            return false;
        }
        $sdf = parent::getDirectSdf($arrDelivery, $arrBill, $shop);
        $sdf['primary_bn'] = $delivery['delivery_bn'];
        $sdf['preNum'] = 1;
        $sdf['delivery'] = $delivery;
        $sdf['etms_check'] = $data;
        return $sdf;
    }

    /**
     * 检查是否可以京配
     * @access public
     * @param
     * @param
     * @param
     * @return 接口响应结果
     */
    public function  jdEtmsRangeCheck($delivery) {
        $result = array();

        $ship_province = $this->_formate_receiver_province($delivery['ship_province'],$delivery['ship_district']);
        $receiveAddress = $ship_province.$delivery['ship_city'].$delivery['ship_district'].$delivery['ship_addr'];

        $branchBn = $this->get_branch_bn($delivery['branch_id']);
        $params = array(
            'tid' => $delivery['delivery_bn'], //订单号'19350587929',//
            'goodsType' => 1, //配送业务类型
            'wareHouseCode' => $branchBn, //仓库编码（需要客户在jd青龙系统维护）
            'receiveAddress' =>  $receiveAddress, //收件人地址
            'sendTime' => '', //发货时间
            'isCod' => $delivery['is_cod']==='true' ? 1 : 0, //是否货到付款
//            'senderProvinceId' => 1, //发货人省编码
//            'senderCityId' => 72, //发货人市编码
            'ship_province' => $ship_province,
            'ship_city' => $delivery['ship_city'],
            'ship_district' => $delivery['ship_district'],
            'ship_addr' => $delivery['ship_addr'],
            'delivery' => $delivery,
        );
        $data = kernel::single('erpapi_router_request')->set('logistics', $this->channel['channel_id'])->electron_etmsRangeCheck($params);
        if('succ'==$data['rsp']){
            $etms_check =  json_decode($data['data'], true);
            if(intval($etms_check['resultInfo']['rcode'])!==200){
                $result = $etms_check;
            }
        }
        return $result;
    }

    /**
     * 通过branch_id获取仓库编码
     */
    public function get_branch_bn($branch_id) {
        $branchObj = app::get('ome')->model("branch");
        $branch = $branchObj->dump($branch_id,'branch_bn');
        return $branch['branch_bn'];
    }
    //获取京东配送业务类型  先返回1
    public function goodsType() {
        $goodsType = array(
            1=> '普通',
            3=> '填仓',
            4=> '特配',
            5=> '鲜活',
            6=> '控温',
            7=> '冷藏',
            8=> '冷冻',
            9=> '深冷',
        );
        return 1;
    }
}