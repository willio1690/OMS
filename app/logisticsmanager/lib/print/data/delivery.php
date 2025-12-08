<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-22
 * @describe delivery打印数据整理
 */
class logisticsmanager_print_data_delivery  {
    public $isJDMD  = false;
    public $jsKDNMD = false;

    /**
     * delivery
     * @param mixed $oriData ID
     * @param mixed $corp corp
     * @param mixed $field field
     * @param mixed $type type
     * @return mixed 返回值
     */

    public function delivery(&$oriData, $corp, $field, $type) {
        $pre = __FUNCTION__ . '.';

        $cFilter = array(
            'channel_id' => $corp['channel_id'],'status'=>'true',
        );
        $channelInfo = app::get("logisticsmanager")->model("channel")->dump($cFilter, 'channel_type,service_code,logistics_code');
        $channelInfo['service_code'] = @json_decode($channelInfo['service_code'], true);

        switch ($channelInfo['channel_type']) {
            case '360buy':$this->isJDMD = true;break;
            case 'hqepay':$this->jsKDNMD = true;break;
        }

        foreach($oriData as $k => &$val) {
            foreach($field as $f) {
                if(method_exists($this, $f)) {
                    $val[$pre . $f] = $this->$f($val,$channelInfo);
                } elseif(isset($val[$f])) {
                    $val[$pre . $f] = $val[$f];
                } else {
                    $val[$pre . $f] = '';
                }
            }
        }
    }

    private function bracket_memo($row) {
        if(!empty($row['memo'])) {
            return '  (' . $row['memo'] . ')';
        }
        return '';
    }

    private function package_number($row) {
        $num = $row['logi_number'] ? $row['logi_number'] : 1;
        return '1/' . $num;
    }

    private function virtual_number_memo($row) {
        list($mobile, $virtual_number) = explode('-', $row['ship_mobile']);

        if ($virtual_number){
            return sprintf('[配送请拨打%s转%s]', $mobile, $virtual_number);
        } 

        return '';
    }

    private function batch_logi_no($row,$channel) {
        $num = $row['logi_number'] ? $row['logi_number'] : 1;

        if ($this->isJDMD) {
            return $row['logi_no'].'-1-'.$num.'-';
        }

        if ($this->jsKDNMD && $channel['logistics_code'] == 'ANEKY') {
            return $row['logi_no'].str_pad($num, 4, 0, STR_PAD_LEFT).'0001';
        }


        return '';
    }

    private function sfcity_code($row) {
        $key = md5($row['ship_province'] . '|' . $row['ship_city']);
        static $sfCitycode = array();
        if(!empty($sfCitycode[$key])) {
            return $sfCitycode[$key];
        }
        $sfcityCodeObj = app::get('logisticsmanager')->model('sfcity_code');
        $area_crc32 = sprintf('%u',crc32($row['ship_city']));
        $sfcity_code = $sfcityCodeObj->dump(array('city_crc32'=>$area_crc32,'province|head'=>$row['ship_province']),'city_code');
        $sfCitycode[$key] = $sfcity_code['city_code'] ? $sfcity_code['city_code'] : '';
        return $sfCitycode[$key];
    }

    private function ship_mobile($row,$channel)
    {
        $ship_mobile = $row['ship_mobile'];
        if ($this->jsKDNMD && $row['waybill.json_packet'] && $json_packet = @json_decode($row['waybill.json_packet'],true)) {
            if ($json_packet['ReceiverSafePhone']) $ship_mobile = $json_packet['ReceiverSafePhone'];
        }

        if ($channel['channel_type'] == 'sf' && $row['waybill.json_packet'] && $json_packet = @json_decode($row['waybill.json_packet'],true)) {
            if ($json_packet['rls_detail']) $ship_mobile = kernel::single('base_view_helper')->modifier_cut($ship_mobile,-1,'****',false,true);
        }

        return $ship_mobile;
    }

    private function ship_name($row,$channel)
    {
        $ship_name = $row['ship_name'];

        if ($channel['channel_type'] == 'sf' && $row['waybill.json_packet'] && $json_packet = @json_decode($row['waybill.json_packet'],true)) {
            if ($json_packet['rls_detail']) $ship_name = kernel::single('base_view_helper')->modifier_cut($ship_name,-1,'*',false,true);
        }


        return $ship_name;
    }

}
