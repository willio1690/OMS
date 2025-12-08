<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_format_json extends erpapi_format_abstract{
    
    /**
     * data_encode
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function data_encode($data){
        // qimen接口返回数据
        if(in_array($_REQUEST['method'], ['qimen.taobao.erp.order.add', 'qimen.taobao.erp.order.update'])){
            $qimenResult = [
                'flag' => 'failure',
                'code' => '0',
                'message' => '',
            ];
            
            // succ
            if($data['rsp'] == 'succ' || $data['rsp'] == 'success'){
                $qimenResult['flag'] = 'success';
            }
            
            // msg_code
            if(isset($data['msg_code'])){
                $qimenResult['code'] = $data['msg_code'];
            }
            
            // message
            if(isset($data['msg']) || isset($data['message'])){
                $qimenResult['message'] = ($data['msg'] ? $data['msg'] : $data['message']);
            }
            
            // data
            if(isset($data['data'])){
                $qimenResult['data'] = $data['data'];
            }
            
            // 重置
            $data = $qimenResult;
        }
        
        return json_encode($data);
     }

    /**
     * data_decode
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function data_decode($data){
        return json_decode($data,true);
     }
}