<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 菜鸟根据SKU导入任务
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_autotask_task_type_cainiaoSkuImport extends financebase_autotask_task_init
{
    //sku导入费用是否计算
    public $isMoneyCount = true;
    /**
     * 处理
     * @param mixed $task_info task_info
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */

    public function process($task_info, &$error_msg)
    {
        $this->oFunc->writelog('菜鸟根据SKU导入任务-开始', 'settlement', '任务ID:' . $task_info['queue_id']);

        $storageLib = kernel::single('taskmgr_interface_storage');
        $remote_url = $task_info['queue_data']['remote_url'];
        $local_file = DATA_DIR . '/financebase/tmp_local/' . basename($remote_url);
        $getfile_res = $storageLib->get($remote_url, $local_file);
        $task_info['queue_data']['data'] = array();
        if ($getfile_res) {
            $task_info['queue_data']['data'] = json_decode(file_get_contents($local_file), 1);
            unlink($local_file);
            $storageLib->delete($remote_url);
        }

        $oProcess = kernel::single('financebase_data_cainiao_sku');
        if (empty($task_info['queue_data']['data'])) {
            return true;
        }

        $data = array();
        $errorData = array();
        $offset = intval($task_info['queue_data']['offset']) + 1;

        foreach ($task_info['queue_data']['data'] as $k => $row) {

            if ($row[0] == '支付流水号') {
                continue;
            }

            $result = finance_io_bill_verify::isDate($row[1]);
            if ($result['status'] == 'fail') {
                $time = finance_io_bill_verify::getDateByFloatValue($row[1]);
                $date = gmdate('Y-m-d H:i:s', $time);
                $row[1] = $date;
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
         $this->save($data, $errorData, $task_info['queue_data']['import_id'], $errmsg);
        if ($errmsg) {
            $error_msg = $errmsg;
            $this->oFunc->writelog('菜鸟根据SKU导入任务-部分成功', 'settlement', '任务ID:' . $task_info['queue_id']);
        } else {
            $this->oFunc->writelog('菜鸟根据SKU导入任务-完成', 'settlement', '任务ID:' . $task_info['queue_id']);
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
            $skuMdl = app::get('financebase')->model("bill_import_sku");
            $summaryMdl = app::get('financebase')->model("bill_import_summary");
            $importMdl = app::get('financebase')->model("bill_import");

            $importRes = $importMdl->getRow('*', array('id' => $importId));
            if (!$importRes) {
                return false;
            }
            $type = 'sku';
            //总金额
            $money = $importRes['money'];
            //未确认数量
            $not_confirm_num = $importRes['not_confirm_num'];
            //未对账数量
            $not_reconciliation_num = $importRes['not_reconciliation_num'];
            foreach ($data as $v) {
                $res = $this->saveRow($v, $importRes, $summaryMdl, $skuMdl, $type);
                if ($res['status'] == true) {
                    $not_confirm_num += 1;
                    $not_reconciliation_num += 1;
                    if ($this->isMoneyCount) {
                        $money += $v['expenditure_money'];
                    }
                } else {
                    array_unshift($v,$res['msg']);
                    $errorData[] = $v;
                    array_push($errmsg, $res['msg']);
                }
            }

            $importRes['error_data'] = !empty($importRes['error_data']) ? unserialize($importRes['error_data']) : array();
            $error_data = array_merge($importRes['error_data'], $errorData);


            $params = array(
                'money'                  => $importRes['money'] + $money,
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
     * @param mixed $skuMdl skuMdl
     * @param mixed $type type
     * @return mixed 返回操作结果
     */
    public function saveRow($data, $importRes, $summaryMdl, $skuMdl, $type)
    {
        try {
            $db = kernel::database();
            $transaction_status = $db->beginTransaction();

            $summaryRes = $this->saveSummary($summaryMdl, $data, $importRes, $type, $skuMdl);
            if (empty($summaryRes['id'])) {
                $db->rollback();
                return array('status' => false, 'msg' => '分组统计错误');
            }

            $skuRes = $this->saveSku($summaryRes, $data, $skuMdl, $importRes);
            if ($skuRes['status'] == false) {
                $db->rollback();
                return $skuRes;
            }

            $db->commit($transaction_status);

            return array('status' => true);
        } catch (\Exception $e) {
            return array('status' => false, 'msg' => '系统错误:' . $e->getMessage());
        }

    }

    /**
     * 保存Sku
     * @param mixed $summaryRes summaryRes
     * @param mixed $data 数据
     * @param mixed $skuMdl skuMdl
     * @param mixed $importRes importRes
     * @return mixed 返回操作结果
     */
    public function saveSku($summaryRes, $data, $skuMdl, $importRes)
    {
        try {
            $basicMaterialObj = app::get('material')->model('basic_material');
            $bmRow = $basicMaterialObj->getList('*',array('material_bn' => $data['sku']));
            if (!$bmRow) {

                $foreignSkuObj = app::get('console')->model('foreign_sku');
                $sku = $foreignSkuObj->getList('*', array('oms_sku' => $data['sku']));
                if (!$sku) {
                    return array('status' => false, 'msg' => $data['sku'] . '商品编码不存在');
                }
                $bmRow = $basicMaterialObj->getList('*',array('material_bn' => $sku[0]['inner_sku']));
                if (!$bmRow) {
                    return array('status' => false, 'msg' => $data['sku'] . '商品编码不存在');
                }
                $data['sku'] = $sku[0]['inner_sku'];
            }

            $skuData = array(
                //'summary_id'           => $summaryRes['id'],
                // 'import_id'        => $importRes['id'],
                'confirm_status'       => 0,
                'relation_sn'          => $data['relation_sn'],
                'increment_service_sn' => $data['increment_service_sn'],
                'service_provider'     => $data['service_provider'],
                'submit_time'          => $data['submit_time'],
                'status'               => $data['status'],
                'bm_id'                => $bmRow[0]['bm_id'],
                'sku'                  => $data['sku'],
                'pay_serial_number'    => $data['pay_serial_number'],
                'expenditure_time'     => strtotime($data['expenditure_time']),
                'expenditure_money'    => $data['expenditure_money'],
                'cost_project'         => trim($data['cost_project']),
                'product_name'         => $data['product_name'],
                'plan_operation_num'   => $data['plan_operation_num'],
                'actual_operation_num' => $data['actual_operation_num'],
                'confirm_time'         => 0,
                'op_id'                => intval($importRes['op_id'])
            );

            $skuData['crc_unique'] = crc32(implode('', $skuData));
            $flag = $skuMdl->getRow('id', array('crc_unique' => $skuData['crc_unique']));
            if ($flag) {
                return array('status' => false, 'msg' => $skuData['crc_unique'] . ' 该记录已存在');
            }
            $skuData['summary_id'] = $summaryRes['id'];
            $skuData['import_id'] = $importRes['id'];
            $skuRes = $skuMdl->insert($skuData);
            if (!$skuRes) {
                return array('status' => false, 'msg' => $data['transaction_sn'] . ' 添加失败');
            }
            return array('status' => true);
        } catch (\Exception $e) {
            return new \Exception($e->getMessage());
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
    public function saveSummary($summaryMdl, $data, $importRes, $type, $skuMdl)
    {

        $summaryRes = $summaryMdl->getRow('*', array('increment_service_sn' => $data['increment_service_sn'], 'import_id' => $importRes['id'], 'type' => $type));
        if (!$summaryRes) {
            $this->isMoneyCount = true;
            $params = array(
                'import_id'            => $importRes['id'],
                'confirm_status'       => 0,
                'type'                 => $type,
                'expenditure_money'    => $data['expenditure_money'],
                'increment_service_sn' => $data['increment_service_sn'],
                'op_id'                => intval($importRes['op_id'])
            );

            $summaryRes['id'] = $summaryMdl->insert($params);
        } else {

            /**
             *  增值服务单号一样, 费用不一样， 则计算总费用
             */
            $res = $skuMdl->getRow('*',array('increment_service_sn' => $data['increment_service_sn'], 'import_id' => $importRes['id'], 'expenditure_money' => $data['expenditure_money']));
            if ($res) {
                $this->isMoneyCount = false;
            } else {
                $this->isMoneyCount = true;
                $params = array(
                    'expenditure_money' => $data['expenditure_money'] += $summaryRes['expenditure_money'],
                );
                $summaryMdl->update($params, array('id' => $summaryRes['id']));
            }


        }

        return $summaryRes;
    }
}


