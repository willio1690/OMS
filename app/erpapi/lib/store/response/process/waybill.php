<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_process_waybill
{
    /**
     * 获取
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function get($params){

        $package_type = $params['package_type'];

        
        if($package_type == 'iostock'){

            return $this->ready($params);
        }else{

            
            return  $this->getWayBill(array('delivery_bn'=>$params['delivery_bn']));

        }
    }


    /**
     * ready
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function ready($params)
    {
        $isoMdl     = app::get('taoguaniostockorder')->model('iso');
        $isoItemMdl = app::get('taoguaniostockorder')->model('iso_items');

        if ($iso['logi_no']) {
            return array('rsp' => 'succ', 'data' => array());
        }

        kernel::database()->beginTransaction();
  
        // 明细
        $item_list = $isoItemMdl->getList('*', array('iso_id' => $iso['iso_id']));

        $branchMdl = app::get('ome')->model('branch');
        // 发货地址
        $from_branch                                    = $branchMdl->db_dump(array('branch_id' => $iso['branch_id'], 'check_permission' => 'false'));
        list(, $from_area)                              = explode(':', $from_branch['area']);
        list($from_provice, $from_city, $from_district) = explode('/', $from_area);

        // 收货地址
        $to_branch                                = $branchMdl->db_dump(array('branch_id' => $iso['extrabranch_id'], 'check_permission' => 'false'));
        list(, $to_area)                          = explode(':', $to_branch['area']);
        list($to_provice, $to_city, $to_district) = explode('/', $to_area);

        $sdf = array(
            'primary_bn' => $iso['iso_bn'],
            'delivery'   => array(
                'delivery_id'   => $iso['iso_id'],
                'delivery_bn'   => $iso['iso_bn'],
                'ship_province' => $to_provice,
                'ship_city'     => $to_city,
                'ship_district' => $to_district,
                'ship_addr'     => $to_branch['address'],
                'ship_name'     => $to_branch['uname'],
                'ship_mobile'   => $to_branch['mobile'],
                'ship_tel'      => $to_branch['phone'],
                'create_time'   => time(),
            ),
            'shop'       => array(
                'shop_name'      => $from_branch['name'],
                'province'       => $from_provice,
                'city'           => $from_city,
                'area'           => $from_district,
                'address_detail' => $from_branch['address'],
                'default_sender' => $from_branch['uname'],
                'mobile'         => $from_branch['mobile'],
                'tel'            => $from_branch['phone'],
                'zip'            => $from_branch['zip'],
            ),
        );

        foreach ($item_list as $key => $item) {
            $sdf['delivery_item'][$key] = array(
                'product_name' => $item['product_name'],
                'number'       => $item['nums'],
            );
        }
        $rsp = kernel::single('erpapi_router_request')->set('logistics', $iso['corp']['channel_id'])->electron_directRequest($sdf);

     
        if (is_string($rsp)) {
            kernel::database()->rollBack();

            return array('rsp' => 'fail', 'msg' => $rsp, 'data' => array());
        }

        if (!$rsp[0]['succ']) {
            kernel::database()->rollBack();

            return array('rsp' => 'fail', 'msg' => '呼叫物流失败', 'data' => array());
        }

        if ($rsp[0]['succ'] && $iso['iso_id'] == $rsp[0]['delivery_id']) {
            $isoMdl->update(array('logi_no' => $rsp[0]['logi_no']), array('iso_id' => $iso['iso_id']));
        }
   

        kernel::database()->commit();

        return array('rsp' => 'succ', 'data' => array());
    }


    /**
     * 获取WayBill
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function getWayBill($params)
    {

        $dlyMdl        = app::get('ome')->model('delivery');
        $dlyCorpObj    = app::get('ome')->model('dly_corp');
        $waybillMdl    = app::get('logisticsmanager')->model('waybill');
        $waybillExtMdl = app::get('logisticsmanager')->model('waybill_extend');
        $channelMdl    = app::get('logisticsmanager')->model('channel');

        # 查询发货单号
        $filter = [
            'delivery_bn' => $params['delivery_bn'],
        ];

        $dly = $dlyMdl->dump($filter, 'delivery_id,branch_id');

        #调用获取
        $rs = kernel::single('ome_event_trigger_logistics_electron')->directGetWaybill($dly['delivery_id']);

        #再次查询
        $filter = [
            'delivery_id' => $dly['delivery_id'],
        ];
        $dly = $dlyMdl->dump($filter, 'delivery_id,delivery_bn,branch_id,logi_no,logi_id,net_weight');

        $dlyCorpInfo       = $dlyCorpObj->dump($dly['logi_id'], 'type,channel_id,name');
        $data['logi_code'] = isset($dlyCorpInfo['type']) ? $dlyCorpInfo['type'] : '';

        #查询物流单号表
        $filter = [
            'logi_no'    => $dly['logi_no'],
            'channel_id' => $dlyCorpInfo['channel_id'],
        ];

        $waybill = $waybillMdl->dump($filter);

        #查询物流单号扩展表
        $filter      = array('waybill_id' => $waybill['id']);
        $waybillExt  = $waybillExtMdl->dump($filter);
        $json_packet = json_decode($waybillExt['json_packet'], true);

        #查询付款方式
        $filter = [
            'channel_id' => $waybill['channel_id'],
        ];
        $channel = $channelMdl->dump($filter);

        $pay_method = "";
        if ($channel['channel_type'] == 'sf') {
            $sfinfo = explode('|||', $channel['shop_id']);
            #$channel['sfbusinesscode'] = $sfinfo[0];
            #$channel['sfpassword'] = $sfinfo[1];
            $pay_method = kernel::single('logisticsmanager_waybill_sf')->pay_method($sfinfo[2]);
            $pay_method = $pay_method['name'];
            #$channel['sfcustid'] = $sfinfo[3];
        }

        $data = [
            'delivery_bn'   => $dly['delivery_bn'],
            'logi_name'     => $dlyCorpInfo['name'],
            'logi_code'     => $dlyCorpInfo['type'],
            'logi_no'       => $dly['logi_no'],
            'service_code'  => '',
            'pay_method'    => $pay_method,
            'position_name' => $waybillExt['position'],
            'position_code' => $waybillExt['position_no'],
            'package_name'  => $waybillExt['package_wdjc'],
            'package_code'  => $waybillExt['package_wd'],
            'net_weight'    => $dly['net_weight'],
            'print_data'    => $json_packet ? $json_packet : new stdClass(),
        ];

        $result = [
            'data' => $data,
            'rsp'  => 'succ',
            'msg'  => '请求成功',
        ];
        return $result;
    }
    
}

?>