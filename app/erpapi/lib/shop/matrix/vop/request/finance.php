<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 唯品会JIT相关接口
 */
class erpapi_shop_matrix_vop_request_finance extends erpapi_shop_request_finance
{
    
    /**
     * 获取BillNumber
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */

    public function getBillNumber($sdf) {
        $param = [
            'start_time' => date('Y-m-d H:i:s', $sdf['start_time']),
            'end_time' => date('Y-m-d H:i:s', $sdf['end_time']),
            'page_no' => $sdf['page_no'],
            'page_size' => $sdf['page_size'],
        ];
        $title = '获取账单列表';
        $i = 0;
        do {
            $rs = $this->__caller->call(SHOP_BILL_LIST_GET, $param, array(), $title, 10, 'getBillNumber');


            if($rs['rsp'] == 'succ') {
                break;
            }
            $i++;
            if($i > 3) {
                break;
            }
        } while(true);
        if($rs['data']) {
            $data = json_decode($rs['data'], 1);
            $data['msg'] = json_decode($data['msg'], 1);
            $rs['data'] = $data['msg']['result']['billNumbers'];
        }
        return $rs;
    }

    /**
     * 获取BillDetail
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getBillDetail($sdf) {
        $param = [
            'bill_number' => $sdf['bill_number'],
            'start_time' => date('Y-m-d H:i:s', $sdf['start_time']),
            'end_time' => date('Y-m-d H:i:s', $sdf['end_time']),
            'page_no' => $sdf['page_no'],
            'page_size' => $sdf['page_size'],

        ];
        $title = '获取账单明细';
        $i = 0;
        do {
            $rs = $this->__caller->call(SHOP_BILL_DETAIL_GET, $param, array(), $title, 10, $param['bill_number']);
            if($rs['rsp'] == 'succ') {
                break;
            }
            $i++;
            if($i > 3) {
                break;
            }
        } while(true);
        if($rs['data']) {
            $data = json_decode($rs['data'], 1);
            $data['msg'] = json_decode($data['msg'], 1);
            $rs['data'] = [
                'count' => $data['msg']['result']['count'],
                'items' => $data['msg']['result']['billDetails']
            ];
            
        }
        return $rs;
    }


    /**
     * fetchBillGoodsDetail
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function fetchBillGoodsDetail($sdf) {
        $addon = $this->__channelObj->channel['addon'];
        $param = [
            'bill_number' => $sdf['bill_number'],
            'start_time' => date('Y-m-d H:i:s', $sdf['start_time']),
            'end_time' => date('Y-m-d H:i:s', $sdf['end_time']),
            'page_no' => $sdf['page_no'],
            'page_size' => $sdf['page_size'],
            
            'minId'     =>  $sdf['minid'],
            'maxId'     =>  $sdf['maxid'],
            'version'   =>  '2.0',
        ];
        $title = '获取账单明细';
        $i = 0;
        do {
            $rs = $this->__caller->call(SHOP_BILL_DETAIL_GET, $param, array(), $title, 10, $param['bill_number']);
            if($rs['rsp'] == 'succ') {
                break;
            }
            $i++;
            if($i > 3) {
                break;
            }
        } while(true);
        if($rs['data']) {
            $data = json_decode($rs['data'], 1);
            $data['msg'] = json_decode($data['msg'], 1);
            $rs['data'] = [
                'count' => $data['msg']['result']['count'],
                'items' => $data['msg']['result']['dataList'],
                'hasNext'=>$data['msg']['result']['hasNext'],
            ];
            unset($rs['response']);
        }
        return $rs;
    }
    

    /**
     * 获取BillDiscountDetail
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getBillDiscountDetail($sdf){
        $addon = $this->__channelObj->channel['addon'];
        $title = '获取账单满减详情数据';
        $pager = [
            'page'      =>  $sdf['page_no'],
            'size'      =>  $sdf['page_size'],
        ];
        $param = [
            'billNumber'    => $sdf['bill_number'],
            'stQueryTime'   => $sdf['start_time']*1000,
            'etQueryTime'   => $sdf['end_time']*1000,

            'pager'         =>  $pager,
           
            'vendorCode'    =>  $addon['vendor_code'],
        ];
        $data = [
            'reqItem'       =>$param,
            'vendor_code'   =>$addon['vendor_code'],

        ];

        $params = array(
            'data'              =>  json_encode($data),
            'vop_method'        =>  'getBillDiscountDetailPage',
            'vop_service'       =>  'vipapis.fcs.ap.service.VspVendorBillAndDiscountService', 
            'is_multiple_params'=>  '1',

        );
        $rs = $this->__caller->call(SHOP_COMMONS_VOP_BILL, $params, array(), $title, 10, $sdf['bill_number']);


        if($rs['data']) {
            $data = json_decode($rs['data'], 1);
            $data['msg'] = json_decode($data['msg'], 1);
            $rs['data'] = [
                'count' => $data['msg']['result']['count'],
                'items' => $data['msg']['result']['billDetails'],

            ];
            unset($rs['response']);
        }
        return $rs;


    }

    /**
     * fetchBillDiscountDetail
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function fetchBillDiscountDetail($sdf){

        $addon = $this->__channelObj->channel['addon'];
        $title = '获取账单满减详情数据';
       
        $params = [
            'bill_number'    => $sdf['bill_number'],
            'start_time'    => date('Y-m-d H:i:s', $sdf['start_time']),
            'end_time'      => date('Y-m-d H:i:s', $sdf['end_time']),
            'page_no'       =>  $sdf['page_no'],
            'page_size'     =>  $sdf['page_size'],
           
            'version'       =>  '2.0',
            'minId'         =>  $sdf['minid'],
            'maxId'         =>  $sdf['maxid'],
        ];
       
       
        $rs = $this->__caller->call(SHOP_BILL_BOOK_BILL_GET, $params, array(), $title, 10, $sdf['bill_number']);
       

        if($rs['data']) {
            $data = json_decode($rs['data'], 1);
            if (!$data || !is_array($data)) {
                $rs['rsp'] = 'fail';
                return $rs;
            }
            $data['msg'] = json_decode($data['msg'], 1);
            $rs['data'] = [
                'count' => $data['msg']['result']['count'],
                'items' => $data['msg']['result']['dataList'],
                'hasNext'=>$data['msg']['result']['hasNext'],

            ];
            unset($rs['response']);
        }
        return $rs;


    }

    /**
     * 获取ItemSourceDetail
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getItemSourceDetail($sdf){
        $addon = $this->__channelObj->channel['addon'];
        
        $title = '获取费用项明细数据';
        $pager = [
            'page'      =>  $sdf['page_no'],
            'size'      =>  $sdf['page_size'],
        ];
        $param = [
            'billNumber'    => $sdf['bill_number'],
            'stQueryTime'   => $sdf['start_time']*1000,
            'etQueryTime'   => $sdf['end_time']*1000,

            'pager'         =>  $pager,
           
            'vendorCode'    =>  $addon['vendor_code'],
        ];
        $data = [
            'reqItem'       =>$param,
            'vendor_code'   =>$addon['vendor_code'],

        ];


        $params = array(

            'data'              =>  json_encode($data),
            
            'vop_method'        =>  'getItemSourceDetailPage',
            'vop_service'       =>  'vipapis.fcs.ap.service.VspVendorBillAndDiscountService', 
            'is_multiple_params'=>  '1',

        );
        $rs = $this->__caller->call(SHOP_COMMONS_VOP_BILL, $params, array(), $title, 10, $sdf['bill_number']);

        if($rs['data']) {
            $data = json_decode($rs['data'], 1);
            if (!$data || !is_array($data)) {
                $rs['rsp'] = 'fail';
                return $rs;
            }
            $data['msg'] = json_decode($data['msg'], 1);
            $rs['data'] = [
                'count' => $data['msg']['result']['count'],
                'items' => $data['msg']['result']['detailList']
            ];
        }
        return $rs;

    }


}
