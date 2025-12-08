<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Date: 2023/4/11
 * @Describe: 轮训发票信息
 */

class invoice_autotask_timer_queryinvoicelist
{
    public function process($params, &$error_msg = '')
    {
        set_time_limit(0);
        ignore_user_abort(1);
        @ini_set('memory_limit', '512M');
        
        $shopList = app::get('ome')->model('shop')->getList('shop_id,node_id', ['s_type' => 1, 'node_type' => 'luban', 'filter_sql' => 'node_id is not null and node_id!=""']);
        if (isset($params['filter'])) {
            $filter = json_decode($params['filter'], 1);
            foreach ($shopList as $key => $val) {
                $this->getInvoiceList($filter, $val);
            }
        } else {
            $time = time();
            base_kvstore::instance('invoice/query_invoice_list')->fetch('query-invoice-info-lastexectime', $lastExecTime);
            base_kvstore::instance('invoice/query_invoice_list')->store('query-invoice-info-lastexectime', $time);
            $params['end_time'] = $time;
            if ($lastExecTime) {
                $params['start_time'] = $lastExecTime;
            }
            foreach ($shopList as $key => $val) {
                $this->invoiceListPushTaskmggr($params, $val);
            }
        }
    }
    
    /**
     * 分片插入队列进行查询
     * @Author: xueding
     * @Vsersion: 2023/4/15 上午11:21
     * @param $params
     * @param $shopInfo
     */
    public function invoiceListPushTaskmggr($params, $shopInfo)
    {
        //开票状态：1待开票、2已开票、3已关闭
        $params['status'] = $params['status'] ?: '1';
        //查询抖音发票信息
        $offset         = 0;
        $limit          = 50;
        $params['page'] = $offset;
        $params['size'] = $limit;
        
        $invoiceRes = kernel::single('erpapi_router_request')->set('shop', $shopInfo['shop_id'])->invoice_getApplyInfo($params);
        if ($invoiceRes['rsp'] == 'succ' && $invoiceRes['data']['total']) {
            do {
                if ($invoiceRes['data']['total'] == '0' || $offset > $invoiceRes['data']['total']) {
                    break;
                }
                //第一页的数据已经查询出来了，直接处理，如果是多页的进行分片
                if ($offset == 0 && $invoiceList = $invoiceRes['data']['invoice_list'] ) {
                    foreach ($invoiceList as $val) {
                        $val['invoice_amount'] = sprintf("%.2f", $val['invoice_amount'] / 100);
                        if ($val['invoice_amount'] > 0) {
                            $val['method'] = 'ome.invoice.message_push';
                            kernel::single('erpapi_router_response')->set_node_id($shopInfo['node_id'])->set_api_name('ome.invoice.message_push')->dispatch($val);
                        }
                    }
                }else{
                    $push_params = array(
                        'data' => array(
                            'task_type' => 'invoice_queryinvoicelist',
                            'filter'    => json_encode([
                                'page'       => $offset,
                                'size'       => $limit,
                                'start_time' => $params['start_time'],
                                'end_time'   => $params['end_time'],
                                'status'     => $params['status'],
                            ]),
                        ),
                        'url'  => kernel::openapi_url('openapi.autotask', 'service'),
                    );
                    kernel::single('taskmgr_interface_connecter')->push($push_params);
                }
                
                $offset += $limit;
            } while (true);
        }
    }
    
    /**
     * 根据条件获取发票信息
     * @Author: xueding
     * @Vsersion: 2023/4/15 上午11:22
     * @param $params
     * @param $shopInfo
     */
    public function getInvoiceList($params, $shopInfo)
    {
        //查询抖音发票信息
        $invoiceRes = kernel::single('erpapi_router_request')->set('shop', $shopInfo['shop_id'])->invoice_getApplyInfo($params);
        if ($invoiceRes['rsp'] == 'succ' && $invoiceList = $invoiceRes['data']['invoice_list']) {
            foreach ($invoiceList as $val) {
                $val['invoice_amount'] = sprintf("%.2f", $val['invoice_amount'] / 100);
                if ($val['invoice_amount'] > 0) {
                    $val['method'] = 'ome.invoice.message_push';
                    kernel::single('erpapi_router_response')->set_node_id($shopInfo['node_id'])->set_api_name('ome.invoice.message_push')->dispatch($val);
                }
            }
        }
    }
    
    
}