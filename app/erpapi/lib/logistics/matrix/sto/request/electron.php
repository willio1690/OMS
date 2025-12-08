<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-15
 * @describe 申通请求电子面单类
 */
class erpapi_logistics_matrix_sto_request_electron extends erpapi_logistics_request_electron
{
    public $node_type = 'sto';

    public $to_node = '1064384233';

    public $shop_name = '申通官方电子面单';
    
    /**
     * bufferRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function bufferRequest($sdf){
        $wbFilter = array(
            'channel_id'=>$this->__channelObj->channel['channel_id'],
            'status'=>0,
        );
        $waybillObj = app::get('logisticsmanager')->model('waybill');
        $count = $waybillObj->count($wbFilter);
        if($count < $this->cacheLimit) {
            $this->title = '获取sto官方电子面单';
            $this->timeOut = 1;
            $this->primaryBn = 'STOGetWaybill';
            $params = array(
                'len' => $this->everyNum, //单据数量
            );
            $callback = array(
                'class' => get_class($this),
                'method' => 'bufferRequestCallBack',
                'params' => array('channel_id' => $this->__channelObj->channel['channel_id']),
            );
            
            $this->requestCall(STORE_WAYBILL_MAILNO_GET, $params, $callback);
        }
        return true;
    }

    /**
     * directRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function directRequest($sdf){
        return false;
    }

    protected function bufferBackToRet($rlt) {
        $data = empty($rlt['data']) ? '' : json_decode($rlt['data'], true);
        if(empty($data['assignId'])) {
            return array();
        }
        $arrWaybill = array();
        $assignId = explode(',', $data['assignId']);
        foreach($assignId as $val){
            $arrWaybill[] = $val;
        }
        return $arrWaybill;
    }

    /**
     * waybillExtend
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function waybillExtend($sdf) {
        $delivery = $sdf['delivery'];
        $shopInfo = $sdf['shop'];
        $productName = $sdf['product_name'];
        $this->title = 'STO官方电子面单物流回传';
        $this->primaryBn = $delivery['delivery_bn'];
        $op_name = kernel::single('desktop_user')->get_name();
        $Account = explode('|||',$this->__channelObj->channel['shop_id']);
        $params = array(
            'sendsite' => $Account[1], //网点名称
            'sendcus' => $Account[0], //客户名称
            'cuspwd'=>$Account[2],
            'inputsite'=>$Account[1],//录入网点
            'billno' => $delivery['logi_no'],
            'delivery_id' => $delivery['delivery_id'],
            'senddate'=>date('Y-m-d H:i:s'),//寄件日期yyyy-mm-dd
            'sendperson'=>$shopInfo['default_sender'] ? $shopInfo['default_sender'] : '_SYSTEM',//寄件人
            'sendtel'=>$shopInfo['mobile'] ? $shopInfo['mobile'] : ($shopInfo['tel'] ? $shopInfo['tel'] : '_SYSTEM'),//寄件人电话
            'receivecus'=>'',//收件客户
            'receiveperson'=>$delivery['ship_name'],//收件人
            'receivetel'=>$delivery['ship_tel'],//收件人电话
            'goodsname'=>$productName,//内件品名
            'inputperson'=>$op_name ? $op_name : '_SYSTEM',//录入人
            'lasteditdate'=>'',//最后编辑时间
            'lasteditperson'=>'',//最后编辑人
            'lasteditsite'=>'',//最后编辑网点
            'remark'=>'',//备注
            'receiveprovince'=>$delivery['ship_province'],//收件省份
            'receivecity'=>$delivery['ship_city'],//收件城市
            'receivearea'=>$delivery['ship_district'],//收件地区
            'receiveaddress'=>$delivery['ship_addr'],//收件地址
            'sendprovince'=>$shopInfo['province'],//寄件省份
            'sendcity'=>$shopInfo['city'],//寄件城市
            'sendarea'=>$shopInfo['area'],//寄件地区
            'sendaddress'=>$shopInfo['address_detail'] ? $shopInfo['province'] . $shopInfo['city'] . $shopInfo['area'] . $shopInfo['address_detail'] : '_SYSTEM',//寄件地址
            'weight'=>'',//重量
            'productcode'=>'',//产品代码
            'sendpcode'=>'',//寄件省份编号
            'sendccode'=>'',//寄件城市编号
            'sendacode'=>'',//寄件地区编号
            'receivepcode'=>'',//收件省份编号
            'receiveccode'=>'',//收件城市编号
            'receiveacode'=>'',//
            'bigchar'=>'',//
            'orderno'=>'',//
        );
        $result = $this->requestCall(STORE_WAYBILL_DATA_ADD,$params);
        return $this->waybillExtendBack($result, $delivery);
    }

    /**
     * waybillExtendBack
     * @param mixed $result result
     * @param mixed $delivery delivery
     * @return mixed 返回值
     */
    public function waybillExtendBack($result, $delivery) {
        $data = empty($result['data']) ? '' : json_decode($result['data'], true);
        $waybill_extObj =  &app::get("logisticsmanager")->model("waybill_extend");
        if (!empty($data[0])) {
            if ($data[0]['expno']=='') {
                $data[0]['expno']=$data['billno'];
            }
            $waybill_extObj->save_position($data[0]);
            return array('rsp' => 'succ');
        }
        $notExtend = array($delivery['delivery_id'] => $delivery['delivery_bn']);
        return array('rsp'=>'fail', 'not_extend' => $notExtend);
    }
}