<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 获取对账单数据
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_rpc_request_bill{

    /**
     * __construct
     * @return mixed 返回值
     */

    public function __construct(){
        $this->oFunc = kernel::single('financebase_func');
    }

	/**
	 * 获取账号入口
	 * @Author YangYiChao
	 * @Date   2019-06-06
	 * @param  [String]     $type           类型  [ alipay | jd ]
	 * @param  [Array]      $params         参数
	 * @return [Array]                
	 */
	public function process($type,$params = array()){

		$func = sprintf("bill_%s",$type);
		if (method_exists($this,$func)){
			return $this->$func($params);
		}else{
			return array();
		}
	}

	private function bill_alipay($params)
	{
		if(!$params['shop_id'] || !$params['node_id'] || !$params['node_type']  ) return array();

		$query_params = array();
        $query_params['bill_date']  = $params['bill_date'];
        $query_params['bill_type']  = 'signcustomer';

        $res = kernel::single('erpapi_router_request')->set('ipay', $params['channel_id'])->bill_downloadurl($query_params);

		if('succ' == $res['rsp'] && $data = json_decode($res['data'],true)){
			$msg = json_decode($data['msg'],1);

			return $msg;
		}

		return array ();
	}

	private function bill_360buy($params)
	{
		if(!$params['shop_id'] || !$params['node_id'] || !$params['node_type']) return array ();

        $zip_dir = DATA_DIR.'/financebase/settlement';

        if(!is_dir($zip_dir)) utils::mkdir_p($zip_dir);

        $title = array (
            'orderId'       => '订单编号',
            'detailNo'      => '单据编号',
            'rfBusiType'    => '单据类型',
            'skuId'         => '商品编号',
            'outTradeNo'    => '商户订单号',
            'skuName'       => '商品名称',
            'detailStatus'  => '结算状态',
            'happenTime'    => '费用发生时间',
            'billingTime'   => '费用计费时间',
            'finishTime'    => '费用结算时间',
            'feeName'       => '费用项',
            'bal'           => '金额',
            'currency'      => '币种',
            'yfys'          => '商家应收/应付',
            'remark'        => '钱包结算备注',
            'venderId'      => '店铺号',
            'jdShopId'      => '京东门店编号',
            'shopId'        => '品牌门店编号',
            'shopName'      => '门店名称',
            'memo'          => '备注',
            'direction'     => '收支方向',
            'skuNum'        => '商品数量',
            'billDate'      => '对账日期',
        );

		// $method = 'store.qianbao.bill.detail.query';

        $bill_date = strtotime($params['bill_date']);
		$query_params = array();
		$query_params['start_date'] = date('Y-m-d H:i:s',$bill_date);
		$query_params['end_date']   = date('Y-m-d H:i:s',$bill_date+86399);
		$query_params['type']       = '2';
		$query_params['node_type']  = $params['node_type'];
		$query_params['to_node_id'] = $params['node_id'];
		$query_params['from_api_v'] = '2.2';
		$query_params['to_api_v']   = '1.0';
        $query_params['page']       = $params['page'] ? $params['page'] : '1';

        $shop = app::get('ome')->model("shop")->dump($params['shop_id'],'shop_id,config,node_type');
        $shop_config = @unserialize($shop['config']);

        $query_params['member_id']  = $shop_config['member_id']; // 110960260003

        if (!$query_params['member_id']) {
            return array ('downloadurl' => false,'rsp' => 'fail','err_msg'=>'二级商户号必填');
        }

		// 测试用
		// $query_params['from_node_id'] = '1605116239';

        $res = array (); $csv_files = array ();
        do {
            $result = kernel::single('erpapi_router_request')->set('ipay', $params['channel_id'])->bill_query($query_params);

            $res[] = array (
                'bill_date' => $params['bill_date'],
                'page'      => $query_params['page'],
                'rsp'       => $result['rsp'],
                'msg_id'    => $result['msg_id'],
                'err_msg'   => $result['err_msg'],
            );

            if ($result['rsp'] != 'succ') {
                // 如果超时重新加队列
                if ($result['err_msg'] == '请求超时') {
                    $queueMdl = app::get('financebase')->model('queue');

                    $queueData = array();
                    $queueData['queue_mode']         = 'billApiDownload';
                    $queueData['queue_no']           = $params['queue_no'];
                    $queueData['create_time']        = time();
                    $queueData['queue_name']         = sprintf("%s_%s_%s下载任务",$params['shop_name'],$params['bill_date'],$query_params['page']);
                    $queueData['queue_data']         = $params;
                    $queueData['queue_data']['page'] = $query_params['page'];

                    $queue_id = $queueMdl->insert($queueData);
                    $queue_id and financebase_func::addTaskQueue(array('queue_id'=>$queue_id),'billapidownload');
                }
                //超出页数未取到数据 success = 'false'， resultCode = 0，resultCode = 10011
                //未拉取到数据 success = 'false'，resultCode = 0，resultCode = 10012
                //矩阵返回均是rsp='fail'，直接跳出循环即可
                break;
            }

            // 写文件
            $file = $zip_dir.'/'.md5(KV_PREFIX.serialize($query_params)).'.csv';

            $fp = fopen($file, 'w');
            fputcsv($fp, $title);

            $result['data'] = json_decode($result['data'],true);
            foreach ($result['data']['data'] as $value) {
                $fields = array ();
                foreach ($title as $k => $n) {
                    switch ($k) {
                        case 'rfBusiType':
                            $rfBusiType = array (
                                '1001' => '订单',
                                '1002' => '售后服务单',
                                '1003' => '取消退款单',
                                '1102' => '非销售单',
                            );
                            $fields[] = $rfBusiType[$value[$k]];
                            break;
                        case 'detailStatus':
                            $detailStatus = array (
                                '11' => '付款中/待确认',
                                '15' => '结算完成',
                                '17' => '付款失败',
                                '1'  => '数据作废',
                            );

                            // 有finishTime就当结算完成，大促期间京东会对状态做延时处理
                            $fields[] = $value['finishTime'] ? '结算完成' : $detailStatus[$value[$k]];
                            break;
                        case 'finishTime':
                        case 'billingTime':
                        case 'deliveredTime':
                        case 'happenTime':
                            $fields[] = $value[$k] ?  date('Y-m-d H:i:s',substr(strval($value[$k]),0,-3)) : '';
                            break;
                        case 'yfys':
                            $fields[] = $value['direction'] == '1' ? '应收' : '应付';
                            break;
                        case 'direction':
                            $fields[] = $value[$k] == '1' ? '收入' : '支出';
                            break;
                        case 'skuNum':
                            $fields[] = $value['rfBusiType'] == '1002' ? ($value['refundSkuNum'] ?? 0) : ($value['skuNum'] ?? 0);//销skuNum、退refundSkuNum
                            break;
                        default:
                            $fields[] = $value[$k];
                            break;
                    }
                }

                fputcsv($fp, $fields);
            }
            fclose($fp);

            $query_params['page']++;

            $csv_files[] = $file;
        } while (true);

		return array ('downloadurl' => false,'rsp' => 'succ','csv_files' => $csv_files, 'err_msg'=>'');
	}
    
    /**
     * 抖音账单
     * 资金流水明细下载请求
     * 资金流水明细文件下载
     * @param $params
     * @return array
     * @date 2024-10-22 5:27 下午
     */
    public function bill_luban($params)
    {
        if (!$params['shop_id'] || !$params['node_id'] || !$params['node_type']) return array();
        
        $bill_date                    = strtotime($params['bill_date']);
        $query_params                 = array();
        $query_params['account_type'] = '0';//动账账户 0: 所有 1: 微信 2:支付宝 3:合众支付 4:聚合支付
        $query_params['biz_type']     = '0';//计费类型 0:全部 1:鲁班广告 2:精选联盟 3:值点商城 4:小店自卖 5:橙子建站 6:POI 7:抖+ 8:穿山甲 9:服务市场 10:服务市场外包客服 11:学浪
        $query_params['start_time']   = date('Y-m-d H:i:s', $bill_date);
        $query_params['end_time']     = date('Y-m-d H:i:s', $bill_date + 86399);
        $query_params['time_type']    = '0';
        $query_params['node_type']    = $params['node_type'];
        
        if (empty($params['download_id'])) {
            $res = $this->bill_luban_query($params, $query_params);
        } else {
            $res = $this->bill_luban_downloadurl($params);
        }
        return $res;
        
    }
    
    /**
     * 获取抖音账单下载ID
     * @param $params
     * @param $query_params
     * @return array
     * @date 2024-11-01 4:39 下午
     */
    public function bill_luban_query($params,$query_params)
    {
        $res = kernel::single('erpapi_router_request')->set('ipay', $params['channel_id'])->bill_query($query_params);
    
        $result = array('downloadurl' => false, 'rsp' => 'fail', 'csv_files' => [], 'err_msg' => '平台账单文件还未生成成功');
    
        if ('succ' != $res['rsp']) {
            $result['rsp']     = 'fail';
            $result['err_msg'] = '获取账单下载ID请求失败！';
            return $result;
        }
        $data = json_decode($res['data'], true);
    
        $download_id = $data['results']['data']['download_id'] ?? '';
        if (!$download_id) {
            $result['rsp']     = 'fail';
            $result['err_msg'] = '缺少账单下载ID！';
            return $result;
        }
        
        //更新download_id
        $queueMdl = app::get('financebase')->model('queue');
        $queueMdl->update(['download_id'=>$download_id, 'is_file_ready' => '0'],['queue_id'=>$params['queue_id']]);
    
        return $result;
    }
    
    /**
     * 获取抖音账单下载URL
     * @param $params
     * @return array
     * @date 2024-11-01 4:40 下午
     */
    public function bill_luban_downloadurl($params)
    {
        $queueMdl = app::get('financebase')->model('queue');
    
        $download_id = $params['download_id'];
        $query_params_url = ['download_id' => $download_id, 'node_type' => $params['node_type']];
        $resUrl           = kernel::single('erpapi_router_request')->set('ipay', $params['channel_id'])->bill_downloadurl($query_params_url);
        
        $result = array('downloadurl' => false, 'rsp' => 'succ', 'csv_files' => [], 'err_msg' => '');
        if ($resUrl['rsp'] != 'succ') {
            $result['rsp']     = 'fail';
            $result['err_msg'] = $result['err_msg'] ?: '获取账单文件下载URL失败！';
            //检测是否需要重新生成download_id
            if (in_array($result['err_msg'], ['下载记录不存在', '文件已经失效'])) {
                $queueMdl->update(['download_id' => '', 'is_file_ready' => '2'], ['queue_id' => $params['queue_id']]);
            }
            return $result;
        }
        $resData      = json_decode($resUrl['data'], true);
        $download_url = $resData['results']['data']['url'] ?? '';
        if (!$download_url) {
            $result['rsp']     = 'fail';
            $result['err_msg'] = '缺少账单下载URL！';
            return $result;
        }

        $queueMdl->update(['download_file' => $download_url, 'is_file_ready' => '1'], ['queue_id' => $params['queue_id']]);
    
        $result['downloadurl']       = true;
        $result['bill_download_url'] = $download_url;
        return $result;
    }
}