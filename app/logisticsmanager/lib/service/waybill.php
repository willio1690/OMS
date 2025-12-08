<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_service_waybill {

    const _APP_NAME = 'logisticsmanager';


    /**
     * 回收电子面单
     * 
     * @access public
     * @param array $params
     * @return void
     */
    public function recycle_waybill($waybill_number,$channel_id,$delivery_id,$delivery_bn = '') {

        if($waybill_number && $channel_id) {

            kernel::single('erpapi_router_request')->set('logistics', $channel_id)->electron_recycleWaybill($waybill_number,$delivery_bn);

        }
        return true;
    }



    /**
     * 获取面单扩展信息
     * @param Arrar $params 面单参数
     */
    public function getWayBillExtend($params) {

        $channelObj = app::get("logisticsmanager")->model("channel");
        $channel = $channelObj->dump($params['channel_id']);
        if (!$channel) die('电子面单厂商不存在');
        $result = array();
        $zlList = array('yunda', 'taobao','sto');
        if ($channel && in_array($channel['channel_type'],$zlList)) {
            $class = 'logisticsmanager_service_common';
            $obj = kernel::single($class);
            $result = $obj->getWayBillExtend($params);
        }
        return $result;
    }


    /**
     * 获取订购地址
     * @param
     * @return
     * @access  public
     * @author sunjing@shopex.cn
     */
    function get_ship_address($channel_id)
    {
        $channelObj = &app::get("logisticsmanager")->model("channel");
        $channel = $channelObj->dump($channel_id);
        $channel_type = $channel['channel_type'];
        if ($channel_type && in_array($channel_type,array('taobao'))) {

            $result = kernel::single('erpapi_router_request')->set('logistics', $channel_id)->electron_shipAddress();

            return $result;
        }
    }

    /**
     * 是否回收面单类型
     * @param
     * @return
     * @access  public
     * @author sunjing@shopex.cn
     */
    function recycle_channel($channel_id)
    {
        $channelObj = app::get("logisticsmanager")->model("channel");
        $cFilter = array(
                'channel_id' => $channel_id,
                'status'=>'true',
            );
        $channel = $channelObj->dump($cFilter);
        $channel_type = $channel['channel_type'];
        return $channel_type;

    }


    /**
     * 取消面单号.
     * @param
     * @return
     * @access  public
     * @author sunjing@shopex.cn
     */
    function cancel_waybill($params)
    {
        $channel_type = $params['channel_type'];
        $channel_id = $params['channel_id'];
        //指定来源的才取消
        if ($channel_id){

            kernel::single('erpapi_router_request')->set('logistics', $channel_id)->electron_recycleWaybill($params['billno']);

        }
    }

    function getChannelType($logi_id){
        if(empty($logi_id)) {
            return '';
        }
        $db = kernel::database();
        $sql = "SELECT c.channel_type FROM sdb_ome_dly_corp as d LEFT JOIN sdb_logisticsmanager_channel as c ON d.channel_id=c.channel_id WHERE d.corp_id=".$logi_id;
        $channel = $db->selectrow($sql);
        return $channel['channel_type'];
    }
}
