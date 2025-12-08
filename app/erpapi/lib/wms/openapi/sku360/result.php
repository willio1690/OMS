<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * RESULT DEAL
 *
 * @category 
 * @package 
 * @author yaokangming<yaokangming@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_openapi_sku360_result extends erpapi_result
{
    function set_response($resp, $format){
        $tmpResponse = kernel::single('erpapi_format_'.$format)->data_decode($resp);
        if($tmpResponse['success'] === true) {
            $response['rsp'] = 'succ';
            if($tmpResponse['msg']) {
                $response['err_msg'] = $tmpResponse['msg'];
            } elseif(isset($tmpResponse['errors'])) {
                $response['err_msg'] = '推送成功';
            }
        } elseif($tmpResponse['success'] === false) {
            $response['rsp'] = 'fail';
            if($tmpResponse['msg']) {
                $response['err_msg'] = $tmpResponse['msg'];
            } elseif(isset($tmpResponse['errors'])) {
                $response['err_msg'] = '推送失败';
                $response['data'] = $tmpResponse['errors'];
                $res = '';
                foreach($tmpResponse['errors'] as $key => $val){
                    $res = $val['purchase_code'] . ':' . $val['reason'] . ', ';
                }
                $response['res'] = $res;
            }
        } else {
            $response['err_msg'] = $resp;
            $response['rsp'] = 'fail';
        }
        $this->response = $response;

        return $this;
    }
}