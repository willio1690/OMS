<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 库存同步处理
 * Class erpapi_shop_matrix_website_d1m_request_product
 */
class erpapi_shop_matrix_website_d1m_request_product extends erpapi_shop_request_product
{
    
    protected function getUpdateStockApi()
    {
        return D1M_OPEN_UPDATE_STORE_POST;
    }
    
    /**
     * 更新Stock
     * @param mixed $stocks stocks
     * @param mixed $dorelease dorelease
     * @return mixed 返回值
     */

    public function updateStock($stocks, $dorelease = false)
    {
        $rs = array('rsp' => 'fail', 'msg' => '', 'data' => '');
        if (!$stocks) {
            $rs['msg'] = 'no stocks';
            return $rs;
        }
        $shop_id = $this->__channelObj->channel['shop_id'];
        $skuIds  = array_keys($stocks);
        sort($stocks);
        
        //保存库存同步管理日志
        $oApiLogToStock = kernel::single('ome_api_log_to_stock');
        $oApiLogToStock->save($stocks, $shop_id);

        //待更新库存BN
        $params = array(
            'date' => date('Y-m-d H:i:s',time()),
//            'node_id' => '',//客户说可以不传
        );
        $list_quantity = [];
        foreach ($stocks as $k => $v) {
            $list_quantity[] = [
                'bn' => $v['bn'],
                'quantity'  => $v['quantity'],
            ];
        }
        $params['list_quantity'] = $list_quantity;
    
        $paramsJson = [
            'json_data' => json_encode($params)
        ];
        
        
        $stockApi = $this->getUpdateStockApi();
        
        $callback_params = array(
            'params' => array(
                'shop_id'        => $shop_id,
                'request_params' => $params,
                'api_name'       => $stockApi,
            ),
        );
        $callback        = []; // 接口不走异步请求
        
        $title      = '批量更新店铺(' . $this->__channelObj->channel['name'] . ')的库存(共' . count($stocks) . '个)';
        $primaryBn  = $this->__channelObj->channel['shop_bn'] . 'UpdateStock';
        $return_res = $this->__caller->call($stockApi, $paramsJson, $callback, $title, 10, $primaryBn);
    
        // token 异常,发起重试
        if ($return_res['rsp'] == 'fail' && in_array($rs['err_msg'],  $this->__resultObj->retryErrorMsgList())) {
            kernel::single('erpapi_router_request')->set('shop', $this->__channelObj->channel['shop_id'])->base_get_access_token();
            $return_res = $this->__caller->call($stockApi, $paramsJson, $callback, $title, 10, $primaryBn);
        }
        
        $this->updateStockCallback($return_res, $callback_params['params']);
    
        $return = array(
            'rsp'    => $return_res['rsp'] == 'succ' ? 'success' : 'fail',
            'msg'    => $return_res['Msg'],
            'msg_id' => $return_res['StatusCode'],
            'data'   => $return_res['Result'],
        );
        
        return $return;
    }
    
    /**
     * 更新StockCallback
     * @param mixed $ret ret
     * @param mixed $callback_params 参数
     * @return mixed 返回值
     */
    public function updateStockCallback($ret, $callback_params)
    {
        $data          = $ret['data'];
        $list_quantity = $callback_params['request_params']['list_quantity'];
        //更新失败的bn会返回，然后下次retry时，只执行失败的bn更新库存
        $succ_item_bn = $data['sku'] ? $data['sku'] : [];
        $error_response = [];
        foreach ($list_quantity as $k => $v) {
            if (!in_array($v['bn'], $succ_item_bn)) {
                $error_response[] = $v;
            }
        }
        $data['error_response']                             = $error_response;
        $data['true_bn']                                    = $succ_item_bn;
        $ret['data']                                        = json_encode($data);
        $callback_params['request_params']['list_quantity'] = json_encode($list_quantity);
        return parent::updateStockCallback($ret, $callback_params);
    }
    
}
