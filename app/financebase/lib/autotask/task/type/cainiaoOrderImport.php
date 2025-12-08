<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 菜鸟根据单号导入任务
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_autotask_task_type_cainiaoOrderImport extends financebase_autotask_task_init
{

    /**
     * 处理
     * @param mixed $task_info task_info
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */

    public function process($task_info, &$error_msg)
    {
        $this->oFunc->writelog('菜鸟根据单号导入任务-开始', 'settlement', '任务ID:' . $task_info['queue_id']);

        $storageLib = kernel::single('taskmgr_interface_storage');
        $remote_url = $task_info['queue_data']['remote_url'];
        $local_file = DATA_DIR . '/financebase/tmp_local/' . basename($remote_url);
        $getfile_res = $storageLib->get($remote_url, $local_file);
        $task_info['queue_data']['data'] = array();
        if ($getfile_res) {
            $task_info['queue_data']['data'] = json_decode(file_get_contents($local_file), 1);
            unlink($local_file);
            $storageLib->delete($remote_url);
        } else {
            $this->oFunc->writelog('菜鸟根据单号导入任务-失败', 'settlement', '任务ID:' . $task_info['queue_id']."缺少文件{$remote_url},{$local_file}");
        }

        $oProcess = kernel::single('financebase_data_cainiao_order');
        if (empty($task_info['queue_data']['data'])) {
            return true;
        }

        $data = array();
        $errorData = array();
        $offset = intval($task_info['queue_data']['offset']) + 1;
        foreach ($task_info['queue_data']['data'] as $k => $row) {

            if ($row[1] == '费用项') {
                continue;
            }

            //去除金额为0的数据
            $result = finance_io_bill_verify::isDate($row[0]);
            if ($result['status'] == 'fail') {
                $time = finance_io_bill_verify::getDateByFloatValue($row[0]);
                $date = gmdate('Y-m-d H:i:s', $time);
                $row[0] = $date;
            }

            $res = $oProcess->getSdf($row, $offset, $task_info['queue_data']['title']);
            if ($res['status'] and $res['data']) {
                if ($res['data']['expenditure_money'] == 0) {
                    continue;
                }
                $data[] = $res['data'];

            } else {
                array_unshift($row,$res['msg']);
                $errorData[] = $row;
                array_push($errmsg, $res['msg']);
            }

            $offset++;
        }
//p($data);p($errorData,1);
        $this->save($data, $errorData, $task_info['queue_data']['import_id'], $errmsg);

        if ($errmsg) {
            $error_msg = $errmsg;
            $this->oFunc->writelog('对账单导入任务-部分成功', 'settlement', '任务ID:' . $task_info['queue_id']);
        } else {
            $this->oFunc->writelog('对账单导入任务-完成', 'settlement', '任务ID:' . $task_info['queue_id']);
        }

        return true;
    }

    /**
     * 保存
     * @param mixed $data 数据
     * @param mixed $errorData 数据
     * @param mixed $importId ID
     * @param mixed $errmsg errmsg
     * @return mixed 返回操作结果
     */
    public function save($data, $errorData, $importId, &$errmsg)
    {
        try {
            $orderMdl = app::get('financebase')->model("bill_import_order");
            $summaryMdl = app::get('financebase')->model("bill_import_summary");
            $importMdl = app::get('financebase')->model("bill_import");

            $importRes = $importMdl->getRow('*', array('id' => $importId));
            if (!$importRes) {
                return false;
            }
            $type = 'order';
            //总金额
            $money = $importRes['money'];
            //未确认数量
            $not_confirm_num = $importRes['not_confirm_num'];
            //未对账数量
            $not_reconciliation_num = $importRes['not_reconciliation_num'];
            foreach ($data as $v) {
                $res = $this->saveRow($v, $importRes, $summaryMdl, $orderMdl, $type);
                if ($res['status'] == true) {
                    $not_confirm_num += $res['num'];
                    $not_reconciliation_num += $res['num'];
                    $money += $v['expenditure_money'];
                } else {
                    array_unshift($v, $res['msg']);
                    $errorData[] = $v;
                    array_push($errmsg, $res['msg']);
                }
            }

            $importRes['error_data'] = !empty($importRes['error_data']) ? unserialize($importRes['error_data']) : array();

            $error_data = array_merge($importRes['error_data'], $errorData);
            $params = array(
                'money'                  => $money,
                'error_data'             => $error_data,
                'not_confirm_num'        => $not_confirm_num,
                'not_reconciliation_num' => $not_reconciliation_num,
            );
            $importMdl->update($params, array('id' => $importRes['id']));
            return true;
        } catch (\Exception $e) {

        }
        return false;

    }


    /**
     * 保存Row
     * @param mixed $data 数据
     * @param mixed $importRes importRes
     * @param mixed $summaryMdl summaryMdl
     * @param mixed $orderMdl orderMdl
     * @param mixed $type type
     * @return mixed 返回操作结果
     */
    public function saveRow($data, $importRes, $summaryMdl, $orderMdl, $type)
    {
        try {
            $db = kernel::database();
            $transaction_status = $db->beginTransaction();

            $summaryRes = $this->saveSummary($summaryMdl, $data, $importRes, $type);
            if (empty($summaryRes['id'])) {
                $db->rollback();
                return array('status' => false, 'msg' => '分组统计错误');
            }

            $orderRes = $this->saveOrder($data, $summaryRes, $orderMdl, $importRes);
            if ($orderRes['status'] == false) {
                $db->rollback();
                return $orderRes;
            }

            $db->commit($transaction_status);
            return array('status' => true,'num' => $orderRes['num']);
        } catch (\Exception $e) {
            return array('status' => false, 'msg' => '系统错误:' . $e->getMessage());
        }

    }

    /**
     * 保持分类主表
     * @param $summaryMdl
     * @param $data
     * @param $importRes
     * @param $type
     * @return $summaryRes
     */
    public function saveSummary($summaryMdl, $data, $importRes, $type)
    {
        $summaryRes = $summaryMdl->getRow('*', array('pay_serial_number' => $data['pay_serial_number'], 'import_id' => $importRes['id'], 'type' => $type));
        if (!$summaryRes) {
            $params = array(
                'import_id'         => $importRes['id'],
                'confirm_status'    => 0,
                'type'              => $type,
                'pay_serial_number' => $data['pay_serial_number'],
                'expenditure_money' => $data['expenditure_money'],
                'op_id'             => intval($importRes['op_id'])
            );

            $summaryRes['id'] = $summaryMdl->insert($params);

        } else {
            $params = array(
                'expenditure_money' => $data['expenditure_money'] += $summaryRes['expenditure_money'],
            );
            $summaryMdl->update($params, array('id' => $summaryRes['id']));
        }

        return $summaryRes;
    }

    /**
     * 保存详情
     * @param $data
     * @param $summaryRes
     * @param $orderMdl
     * @param $importRes
     * @return 保存的数据条数
     */
    public function saveOrder($data, $summaryRes, $orderMdl, $importRes)
    {

        try {
            $ordersModel = app::get('ome')->model('orders');
            //验证
            if($data['transaction_sn']) {
                $orderRow = $ordersModel->getRow(array('order_bn' => $data['transaction_sn']));
                if (!$orderRow) {
                    return array('status' => false, 'msg' => $data['transaction_sn'] . ' 单号未找到');
                }
            } else {
                $logistics_sns = explode(',', $data['logistics_sn']);
                $dlyBillModel = app::get('ome')->model('delivery');
                $logiRow = $dlyBillModel->getList('*',array('logi_no' => $logistics_sns));
                if (!$logiRow) {
                    return array('status' => false, 'msg' => $logistics_sns . ' 物流单号未找到');
                }
                $order = app::get('ome')->model('delivery')->getOrderBnbyDeliveryId($logiRow[0]['delivery_id']);
//                if (!$order) {
//                    return array('status' => false, 'msg' => $data['logistics_sn'] . ' 根据物流单号找订单未找到');
//                }
//                $data['transaction_sn'] = $order['order_bn'];
                $data['transaction_sn'] = isset($order['order_bn']) ? $order['order_sn'] : '';
            }

            $orderData = array(
                'confirm_status'    => 0,
                'pay_serial_number' => $data['pay_serial_number'],
                'expenditure_time'  => strtotime($data['expenditure_time']),
                'expenditure_money' => $data['expenditure_money'],
                'cost_project'      => $data['cost_project'],
                'transaction_sn'    => $data['transaction_sn'],
                'logistics_sn'      => $data['logistics_sn'],
                'confirm_time'      => 0,
                'op_id'             => intval($importRes['op_id'])
            );

            $orderData['crc_unique'] = crc32(implode('',$orderData));
            $flag = $orderMdl->getRow('id',array('crc_unique' => $orderData['crc_unique']));

            if ($flag) {
                return array('status' => false, 'msg' => $orderData['crc_unique'] . ' 该记录已存在');
            }

            $orderData['summary_id'] =  $summaryRes['id'];
            $orderData['import_id'] =  $importRes['id'];
            $orderRes = $orderMdl->insert($orderData);
            if (!$orderRes) {
                return array('status' => false, 'msg' => $data['transaction_sn'] . ' 添加失败');
            }
            return array('status' => true,'num'=> 1);

        } catch (\Exception $e) {
            return new \Exception($e->getMessage());
        }
    }
}