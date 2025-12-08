<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-25
 * @describe 处理快递单打印waybill相关数据
 */
class logisticsmanager_print_data_waybill {
    private $mField = array(
        'waybill_number',
        'logistics_code',
        'json_packet',
        'channel_id',
        // 'channel_type',
    );

    /**
     * waybill
     * @param mixed $oriData ID
     * @param mixed $corp corp
     * @param mixed $field field
     * @param mixed $type type
     * @return mixed 返回值
     */

    public function waybill(&$oriData, $corp, $field, $type) {
        $pre = __FUNCTION__ . '.';
        $middle = array();
        foreach ($oriData as $k => $val) {
            if($corp['tmpl_type'] != 'electron') {
                foreach($field as $f) {
                    $oriData[$k][$pre . $f] = '';
                }
            } else {
                $middle[$k] = $val['logi_no'];
            }
        }
        if(empty($middle)) {
            return true;
        }
        $waybillModel = app::get('logisticsmanager')->model('waybill');
        $waybillExtendModel = app::get('logisticsmanager')->model('waybill_extend');
        $strFieldWB = kernel::single('logisticsmanager_print_data')->getSelectField($this->mField, $field, $waybillModel, 'w');
        $strFieldWBE = kernel::single('logisticsmanager_print_data')->getSelectField($this->mField, $field, $waybillExtendModel, 'e');
        $strField = trim($strFieldWB . ',' . $strFieldWBE, ',');
        $sql = 'select ' . $strField . ' from sdb_logisticsmanager_waybill w left join sdb_logisticsmanager_waybill_extend e on(w.id = e.waybill_id) where ' . $waybillModel->_filter(array('waybill_number' => $middle), 'w');
        $waybillRows = $waybillModel->db->select($sql);
        $waybill = array();
        foreach ($waybillRows as $row) {
            $waybill[$row['waybill_number']] = $this->parseJsonPacket($row);
        }
        foreach ($middle as $key => $value) {

            if ($oriData[$key]['status'] != 'succ' && $waybill[$value]['channel_id'] != $corp['channel_id']) {
                unset($waybill[$value]);
            }

            foreach ($field as $f) {
                if (isset($waybill[$value][$f])) {
                    $oriData[$key][$pre . $f] = $waybill[$value][$f];
                } elseif (method_exists($this, $f)) {
                    $oriData[$key][$pre . $f] = $this->$f($waybill[$value]);
                } else {
                    $oriData[$key][$pre . $f] = '';
                }
            }
        }
    }
    private function parseJsonPacket($row) {
        if(!empty($row['json_packet'])) {
            $data =  json_decode($row['json_packet'],true);
            
            //转换为大写
            $row['logistics_code'] = strtoupper($row['logistics_code']);
            
            if (in_array($row['logistics_code'], array('SOP', 'CS'))) {
                $row['jdsourcet_sort_center_name']            = $data['resultInfo']['sourcetSortCenterName'];
                $row['jdoriginal_cross_tabletrolley_code']    = $data['resultInfo']['originalCrossCode'] . '-' . $data['resultInfo']['originalTabletrolleyCode'];
                $row['jdtarget_sort_center_name']             = $data['resultInfo']['targetSortCenterName'];
                $row['jddestination_cross_tabletrolley_code'] = $data['resultInfo']['destinationCrossCode'] . '-' . $data['resultInfo']['destinationTabletrolleyCode'];
                $row['jdsite_name']                           = $data['resultInfo']['siteName'];
                $row['jdroad']                                = $data['resultInfo']['road'];
                $row['jdaging_name']                          = $data['resultInfo']['agingName'];
                $row['jdsourcet_sort_center_id']              = $data['resultInfo']['sourcetSortCenterId'];
                $row['jdtarget_sort_center_id']               = $data['resultInfo']['targetSortCenterId'];

                $promiseTimeType = '';
                switch ($data['resultInfo']['promiseTimeType']) {
                    case '1': $promiseTimeType = '特惠送' ; break;
                    case '2': $promiseTimeType = '特快送' ; break;
                    // case '3': $promiseTimeType = '' ; break;
                    case '4': $promiseTimeType = '城际闪送' ; break;
                    case '7': $promiseTimeType = '微小件' ; break;
                    case '8': $promiseTimeType = '生鲜专送' ; break;
                    case '16': $promiseTimeType = '生鲜特快' ; break;
                    case '17': $promiseTimeType = '生鲜特惠' ; break;
                    case '20': $promiseTimeType = '函数达' ; break;
                    case '21': $promiseTimeType = '特惠包裹' ; break;

                }
                $row['jdpromise_time_type'] = $promiseTimeType;


                $row['jdtrans_type'] = $data['resultInfo']['transType'] == '2' ? '航' : '';
            }

            if ($row['logistics_code'] == 'ZYKD') {
                $row['jdtarget_sort_center_name']             = $data['toBranchName'];
                $row['jdsourcet_sort_center_name']            = $data['fromBranchName'];
                $row['jddestination_cross_tabletrolley_code'] = $data['toCrossCode'].'-'.$data['toTabletrolleyCode'];
                $row['jdoriginal_cross_tabletrolley_code']    = $data['fromCrossCode'].'-'.$data['fromTabletrolleyCode'];
                // $row['jdtarget_cross_code']                   = $data['toCrossCode'];
                // $row['jdsourcet_cross_code']                  = $data['fromCrossCode'];
                $row['jdsite_name']                           = $data['branchName'];
                $row['jdpackage_no']                          = $data['packageNo'];
                $row['jdorder_sign']                          = $data['orderSign'];
                $row['jdroad']                                = $data['road'];
            }


            // 快递鸟
            if ($row['logistics_code'] == 'ANEKY' && $data['KDNOrderCode']) {
                $row['jdtarget_sort_center_name']             = $data['DestinatioName'];
                $row['jdsourcet_sort_center_name']            = $data['OriginName'];

                $row['jdtarget_sort_center_id']             = $data['DestinatioCode'];
                $row['jdsourcet_sort_center_id']            = $data['OriginCode'];
            }






            if ($data['DialPage']) $row['dialpage']         = $data['DialPage'];
            if ($data['MarkDestination']) $row['markdest']  = $data['MarkDestination'];
            if ($data['mapping_mark']) $row['mapping_mark'] = $data['mapping_mark'];

            if ($data['rls_detail']) {
                $row['sf_proCode']          = (string)$data['rls_detail']['@proCode'];
                $row['sf_destRouteLabel']   = (string)$data['rls_detail']['@destRouteLabel'];
                $row['sf_destTeamCode']     = (string)$data['rls_detail']['@destTeamCode'];
                $row['sf_codingMapping']    = (string)$data['rls_detail']['@codingMapping'];
                $row['sf_abFlag']           = (string)$data['rls_detail']['@abFlag'];
                $row['sf_codingMappingOut'] = (string)$data['rls_detail']['@codingMappingOut'];
                $row['sf_twoDimensionCode'] = (string)$data['rls_detail']['@twoDimensionCode'];

            }

            if ($row['logistics_code']==='shunfeng') {
                if ($data['proCode']) $row['sf_proCode'] = $data['proCode'];
                if ($data['qrCode'])  $row['sf_twoDimensionCode'] = $data['qrCode'];

                $row['sf_prePackageLabel'] = $data['prePackageLabel'];
                $row['sf_changeLabel']     = $data['changeLabel'];
                $row['sf_codingMapping']   = $data['codemapping'];
            }
            // 得物品牌直发电子面单打印的数据
            if ($data['dewu_express']) {
                //特快送标签
                $row['limit_type_code']  = $data['dewu_express'][0]['site_info']['limit_type_code']; 
                // 路由信息
                $row['dest_route_label'] = $data['dewu_express'][0]['site_info']['dest_route_label'];
                // 进港映射码
                $row['coding_mapping']   = $data['dewu_express'][0]['site_info']['coding_mapping']; 
                // 承运商产品名称
                $row['logistics_product_name'] = $data['dewu_express'][0]['logistics_product_name']; 
                // 托寄物名称
                $row['consignment_name']  = $data['dewu_express'][0]['consignment_name']; 
                // 下物流单日期
                $row['make_waybill_time'] = $data['dewu_express'][0]['make_waybill_time']; 
                // 得物二维码为运单号
                $row['dewu_qrcode']       = $data['dewu_express'][0]['waybill_no']; 
            }
        }
        return $row;
    }

}
