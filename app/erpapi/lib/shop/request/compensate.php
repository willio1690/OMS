<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_request_compensate extends erpapi_shop_request_abstract {

    /**
     * syncRecord
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function syncRecord($sdf) {
        $title = $this->__channelObj->channel['name'].'-赔付单获取';
        $params = $this->_formatSyncRecord($sdf);
        $apiName = $this->_getSyncRecordApi();
        $rsp = $this->__caller->call($apiName, $params, array(), $title, 10, 'compensate');
        return $this->_formatSyncRecordResult($rsp);
    }

    protected function _formatSyncRecord($sdf) {
        $params = [
            'business_type' => 'compensate',
            'start_modified' => $sdf['start_modified'],
            'end_modified' => $sdf['end_modified'],
            'page_no' => $sdf['page_no'],
            'page_size' => $sdf['page_size'],
        ];
        return $params;
    }

    protected function _getSyncRecordApi() {
        return SHOP_COMPENSATE_REFUND_GET;
    }

    protected $type = [
        '10' => '延迟发货赔付', 
        '20' => '商家直赔',
        '30' => '先行赔付运费', 
        '40' => '先行赔付非运费',
    ];
    protected $compensate_type = [
        '10' => '积分', 
        '20' => '余额',
        '30' => '优惠券', 
        '40' => '京豆',
    ];
    protected $order_type = [
        #21、fbp 22、sop 23、lbp 25、sopl
        '21' => 'ftp', 
        '22' => 'sop',
        '23' => 'lbp', 
        '25' => 'sopl',
    ];
    protected $accountability = [
        #11：客服审核为京东责任 12：客服审核为商家责任 21：运营审核为商家责任 22：运营审核为商家责任
        '11' => '客服审核为京东责任', 
        '12' => '客服审核为商家责任',
        '21' => '运营审核为京东责任', 
        '22' => '运营审核为商家责任',
    ];
    protected function _formatSyncRecordResult($rsp){
        if($rsp['data']) {
            $data = json_decode($rsp['data'], 1);
            $rsp['data'] = [];
            if(is_array($data) && is_array($data['data']) && is_array($data['data']['result'])) {
                foreach($data['data']['result'] as $v) {
                    $rsp['data'][] = [
                        'compensate_bn' => $v['compensate_id'],
                        'compensate_keyid' => $v['compensate_keyid'],
                        'type' => $this->type[$v['type']],
                        'compensate_type' => $this->compensate_type[$v['compensate_type']],
                        'order_bn' => $v['order_id'],
                        'order_type' => $this->order_type[$v['order_type']],
                        'shouldpay' => $v['shouldpay'],
                        'compensateamount' => $v['compensateamount'],
                        'check_status' => $v['check_status'],
                        'reason' => $v['compensate_reason'],
                        'accountability' => $this->accountability[$v['erp_check_status']],
                        'can_second_appeal' => $v['can_second_appeal'],
                        'outer_created' => date('Y-m-d H:i:s', $v['created']/1000),
                        'outer_modified' => date('Y-m-d H:i:s', $v['modified']/1000),
                    ];
                }
            }
        }
        return $rsp;
    }

    /**
     * syncIndemnity
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function syncIndemnity($sdf) {
        $title = $this->__channelObj->channel['name'].'-小额赔付单获取';
        $params = $this->_formatSyncIndemnity($sdf);
        $apiName = $this->_getSyncIndemnityApi();
        $rsp = $this->__caller->call($apiName, $params, array(), $title, 10, 'compensate');
        return $this->_formatSyncIndemnityResult($rsp);
    }

    protected function _formatSyncIndemnity($sdf) {
        $params = [
            'business_type' => 'indemnity',
            'start_modified' => $sdf['start_modified'],
            'end_modified' => $sdf['end_modified'],
            'page_no' => $sdf['page_no'],
            'page_size' => $sdf['page_size'],
        ];
        return $params;
    }

    protected function _getSyncIndemnityApi() {
        return SHOP_COMPENSATE_REFUND_GET;
    }

    protected $indemityType = [
        '101' => '运费补偿',
        '103' => '货款类补偿',
        '104' => '商品质量问题补偿',
        '105' => '差价补偿',
        '107' => '其他类补偿',
        '108' => '活动返现类补偿'
    ];
    protected function _formatSyncIndemnityResult($rsp){
        if($rsp['data']) {
            $data = json_decode($rsp['data'], 1);
            $rsp['data'] = [];
            if(is_array($data) && is_array($data['data']) && is_array($data['data']['data']['microTransferDetailDtos'])) {
                foreach($data['data']['data']['microTransferDetailDtos'] as $v) {
                    $rsp['data'][] = [
                        'compensate_bn' => $v['microTransferId'],
                        'compensate_keyid' => '',
                        'type' => $this->indemityType[$v['reason']] ? : '小额赔付',
                        'compensate_type' => '',
                        'order_bn' => $v['orderId'],
                        'order_type' => '',
                        'shouldpay' => $v['paid'],
                        'compensateamount' => $v['paid'],
                        'check_status' => $v['status'],
                        #101、运费补偿； 103、货款类补偿； 104、商品质量问题补偿； 105、差价补偿； 107、其他类补偿； 108、活动返现类补偿；
                        'reason' => $v['reason'].'-'.$v['reasonName'],
                        'accountability' => '',
                        'can_second_appeal' => '1',
                        'outer_created' => date('Y-m-d H:i:s', $v['applyTime']/1000),
                        'outer_modified' => '',
                    ];
                }
            }
        }
        return $rsp;
    }
}