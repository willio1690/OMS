<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 包裹异常查询和更新处理类
 *
 * @author system
 * @Time: 2024-12-19
 * @version 0.1
 */

class ome_delivery_bill
{
    /**
     * 查询包裹异常状态并更新本地数据
     * @param array $params 查询参数，包含以下字段：
     *                      - filter: array 查询条件
     *                        - shop_id: string 店铺ID
     *                        - exception_code: string 异常类型代码
     *                        - start_time: string 开始时间
     *                        - end_time: string 结束时间
     *                      - page_no: int 页码
     *                      - page_size: int 每页数量
     *                      - action: string 操作类型（如：count）
     * @param string $shop_id 店铺ID
     * @return array [rsp, msg, data]
     */
    public static function queryExceptionAndUpdate($params, $shop_id = '')
    {
        try {
            // 调用ERPAPI查询异常包裹
            $result = kernel::single('erpapi_router_request')->set('shop', $shop_id)->logistics_exception_query($params['filter']);
            
            if ($result['rsp'] != 'succ') {
                return $result;
            }
            
            // 更新本地delivery_bill表
            $update_result = self::updateDeliveryBillException($result['data'] ?? []);
            
            return [
                'rsp' => 'succ',
                'msg' => '查询成功，已更新' . $update_result['updated_count'] . '条记录'
            ];
            
        } catch (Exception $e) {
            return ['rsp' => 'fail', 'msg' => '查询异常：' . $e->getMessage()];
        }
    }
    
    /**
     * 更新delivery_bill表的异常状态
     * @param array $exception_data 异常数据，数据结构如下：
     *                              array (
     *                                'package_exception_result' => array (
     *                                  0 => array (
     *                                    'exception_code' => 'GOT_EXCEPTION',           // 异常代码
     *                                    'sub_exception_code' => 'CONSIGN_CLICKED_FAKE', // 子异常代码
     *                                    'trade_details' => array (
     *                                      'trade_details' => array (
     *                                        0 => array (
     *                                          'mail_no' => 'SF7444498036028',        // 运单号
     *                                          'expect_collect_time' => 1754968510000,  // 期望揽收时间(毫秒时间戳)
     *                                          'cp_code' => 'SF',                      // 快递公司代码
     *                                          'trade_id' => '2863290505805435058',    // 交易ID
     *                                          'sub_trade_id' => '2863290505807435058'  // 子交易ID
     *                                        ),
     *                                      ),
     *                                    ),
     *                                    'exception_type' => 'EXCEPTION',               // 异常类型
     *                                    'exception_id' => '2863290505807435058#2#CONSIGN_GOT_TIMEOUT_ILLEGAL_24', // 异常ID
     *                                    'over_time' => 1754559473000,                 // 超时时间(毫秒时间戳)
     *                                    'create_time' => 1754559484000,               // 创建时间(毫秒时间戳)
     *                                    'detail_type' => 1                           // 详情类型
     *                                  ),
     *                                ),
     *                              )
     * @return array [updated_count, error_count]
     */
    private static function updateDeliveryBillException($exception_data)
    {
        $model = app::get('ome')->model('delivery_bill');
        $updated_count = 0;
        $error_count = 0;
        
        // 检查数据结构，获取package_exception_result数组
        $package_exception_result = $exception_data['package_exception_result'] ?? $exception_data;
        
        foreach ($package_exception_result as $package_info) {
            try {
                // 检查trade_details是否存在
                if (!isset($package_info['trade_details']['trade_details']) || !is_array($package_info['trade_details']['trade_details'])) {
                    $error_count++;
                    continue;
                }
                
                // 遍历所有trade_details，处理每个包裹
                foreach ($package_info['trade_details']['trade_details'] as $trade_detail) {
                    $mail_no = $trade_detail['mail_no'] ?? '';
                    
                    if (empty($mail_no)) {
                        $error_count++;
                        continue;
                    }
                    
                    // 构建更新数据
                    $update_data = [
                        'exception_status' => 1, // 有异常信息，设置为异常状态
                        'exception_code' => $package_info['exception_code'] ?? '',
                        'sub_exception_code' => $package_info['sub_exception_code'] ?? '',
                        'exception_type' => $package_info['exception_type'] ?? '',
                        'exception_create_time' => intval($package_info['create_time'] / 1000), // 毫秒转秒
                        'exception_over_time' => intval($package_info['over_time'] / 1000), // 毫秒转秒
                        'exception_id' => $package_info['exception_id'] ?? '',
                        'detail_type' => $package_info['detail_type'] ?? 0,
                    ];
                    
                    // 根据运单号更新delivery_bill表
                    $result = $model->update($update_data, ['logi_no' => $mail_no]);
                    if ($result) {
                        $updated_count++;
                    } else {
                        $error_count++;
                    }
                }
                
            } catch (Exception $e) {
                $error_count++;
                continue;
            }
        }
        
        return [
            'updated_count' => $updated_count,
            'error_count' => $error_count
        ];
    }
    

} 