<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 京准通账单[分片]保存数据任务
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: Z
 */
class financebase_autotask_task_type_cainiaoJztImport extends financebase_autotask_task_init
{
    //账单类型
    static $_import_type = 'jzt';
    
    /**
     * 处理
     * @param mixed $task_info task_info
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */

    public function process($task_info, &$error_msg)
    {
        $oProcess = kernel::single('financebase_data_jingzhuntong_jzt');
        
        $this->oFunc->writelog('京准通账单导入任务-开始', 'settlement', '任务ID:' . $task_info['queue_id']);
        
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
            $this->oFunc->writelog('京准通账单导入任务-失败', 'settlement', '任务ID:' . $task_info['queue_id']."缺少文件{$remote_url},{$local_file}");
        }
        
        //check
        if (empty($task_info['queue_data']['data'])) {
            return true;
        }
        
        //title
        $titleList = $oProcess->getTitle();
        $titleList = array_values($titleList);
        
        //data
        $data = array();
        $errorData = array();
        $offset = intval($task_info['queue_data']['offset']) + 1;
        foreach ($task_info['queue_data']['data'] as $k => $row)
        {
            //[兼容]去除标题行BOM头(第一行标题)
            if($k == 0){
                $row[0] = trim($row[0], "\xEF\xBB\xBF");
                if($row[0] == $titleList[0]){
                    continue; //过滤标题
                }
            }
            
            //获取导入的每行数据
            $res = $oProcess->getSdf($row, $offset, $task_info['queue_data']['title']);
            if ($res['status'] && $res['data']) {
                $data[] = $res['data'];
            } else {
                array_unshift($row,$res['msg']);
                $errorData[] = $row;
                array_push($errmsg, $res['msg']);
            }
            
            $offset++;
        }
        
        //保存数据
        $this->saveSdf($data, $errorData, $task_info['queue_data']['import_id'], $errmsg);
        
        //提示消息
        if ($errmsg) {
            $error_msg = $errmsg;
            $this->oFunc->writelog('京准通账单导入任务-部分成功', 'settlement', '任务ID:' . $task_info['queue_id']);
        } else {
            $this->oFunc->writelog('京准通账单导入任务-完成', 'settlement', '任务ID:' . $task_info['queue_id']);
        }
        
        return true;
    }
    
    /**
     * 准备保存数据
     * 
     * @param unknown $data
     * @param unknown $errorData
     * @param unknown $importId
     * @param unknown $errmsg
     * @return boolean
     */
    public function saveSdf($data, $errorData, $importId, &$errmsg)
    {
        try {
            $jztObj = app::get('financebase')->model('bill_import_jzt');
            $summaryMdl = app::get('financebase')->model("bill_import_summary");
            $importMdl = app::get('financebase')->model("bill_import");
            
            $importRes = $importMdl->getRow('*', array('id' => $importId));
            if (!$importRes) {
                return false;
            }
            
            //导入账单的类型
            $type = self::$_import_type;
            
            //总金额
            $money = ($importRes['money'] ? $importRes['money'] : 0);
            
            //未确认数量
            $not_confirm_num = ($importRes['not_confirm_num'] ? $importRes['not_confirm_num'] : 0);
            
            //未对账数量
            $not_reconciliation_num = ($importRes['not_reconciliation_num'] ? $importRes['not_reconciliation_num'] : 0);
            
            //data
            foreach ($data as $v)
            {
                //保存明细
                $res = $this->saveRow($v, $importRes, $summaryMdl, $jztObj, $type);
                if ($res['status'] == true) {
                    $not_confirm_num += $res['num'];
                    $not_reconciliation_num += $res['num'];
                    
                    //支出金额(保留三位小数)
                    $money = bcadd($money, $v['amount'], 3);
                } else {
                    array_unshift($v, $res['msg']);
                    $errorData[] = $v;
                    array_push($errmsg, $res['msg']);
                }
            }
            
            $importRes['error_data'] = !empty($importRes['error_data']) ? unserialize($importRes['error_data']) : array();
            
            $error_data = array_merge($importRes['error_data'], $errorData);
            
            //params
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
     * 保存行数据及统计数据
     * 
     * @param unknown $data
     * @param unknown $importRes
     * @param unknown $summaryMdl
     * @param unknown $jztObj
     * @param unknown $type
     * @return multitype:boolean string |Ambigous <保存的数据条数, multitype:boolean string , multitype:boolean number , \Exception>|multitype:boolean Ambigous <boolean, string, boolean, number>
     */
    public function saveRow($data, $importRes, $summaryMdl, $jztObj, $type)
    {
        try {
            $db = kernel::database();
            $transaction_status = $db->beginTransaction();
            
            //导入数据汇总
            $summaryRes = $this->saveSummary($summaryMdl, $data, $importRes, $type);
            
            if (empty($summaryRes['id'])) {
                $db->rollback();
                return array('status' => false, 'msg'=>'流水号['. $data['pay_serial_number'] .']导入数据汇总错误');
            }
            
            //保存账单数据
            $saveRes = $this->saveBill($data, $summaryRes, $jztObj, $importRes);
            
            if ($saveRes['status'] == false) {
                $db->rollback();
                return $saveRes;
            }

            $db->commit($transaction_status);
            
            return array('status'=>true,'num' => $saveRes['num']);
        } catch (\Exception $e) {
            return array('status' => false, 'msg' => '系统错误:' . $e->getMessage());
        }
    }

    /**
     * 保存汇总数据
     * 
     * @param $summaryMdl
     * @param $data
     * @param $importRes
     * @param $type
     * @return $summaryRes
     */
    public function saveSummary($summaryMdl, $data, $importRes, $type)
    {
        $summaryRes = $summaryMdl->getRow('*', array('pay_serial_number'=>$data['pay_serial_number'], 'import_id'=>$importRes['id'], 'type'=>$type));
        if (!$summaryRes) {
            //insert
            $params = array(
                'import_id' => $importRes['id'],
                'confirm_status' => 0,
                'type' => $type,
                'pay_serial_number' => $data['pay_serial_number'], //流水单号
                'expenditure_money' => $data['amount'], //支出总额
                'op_id' => intval($importRes['op_id']),
            );
            
            $summaryRes['id'] = $summaryMdl->insert($params);
        } else {
            //update
            $expenditure_money = bcadd($summaryRes['expenditure_money'], $data['amount'], 3); //总金额(保留三位小数)
            $params = array(
                'expenditure_money' => $expenditure_money,
            );
            $summaryMdl->update($params, array('id'=>$summaryRes['id']));
        }
        
        return $summaryRes;
    }

    /**
     * 保存数据
     * 
     * @param $data
     * @param $summaryRes
     * @param $jztObj
     * @param $importRes
     * @return 保存的数据条数
     */
    public function saveBill($data, $summaryRes, $jztObj, $importRes)
    {
        try {
            $sdfData = array(
                'pay_serial_number' => $data['pay_serial_number'], //流水单号
                'account' => $data['account'], //账号
                'launchtime' => $data['launchtime'], //投放日期(时间戳)
                'trade_type' => $data['trade_type'], //交易类型
                'plan_id' => $data['plan_id'], //计划ID
                'amount' => $data['amount'], //支出
            );
            
            //唯一编号
            $sdfData['crc_unique'] = crc32(implode('', $sdfData));
            
            //检查数据是否存在
            $flag = $jztObj->dump(array('crc_unique'=>$sdfData['crc_unique']), 'id');
            if ($flag) {
                return array('status'=>false, 'msg' => $sdfData['crc_unique'] .' 该记录已存在');
            }
            
            //insert
            $sdfData['import_id'] = $importRes['id']; //导入记录ID
            $sdfData['summary_id'] = $summaryRes['id']; //导入数据汇总ID
            $sdfData['at_time'] = time(); //创建时间
            $sdfData['up_time'] = time(); //更新时间
            $sdfData['op_id'] = intval($importRes['op_id']); //操作人
            
            $saveRes = $jztObj->insert($sdfData);
            if (!$saveRes) {
                return array('status' => false, 'msg' => '流水号['. $sdfData['pay_serial_number'] .']添加失败');
            }
            
            //succ
            return array('status' => true,'num'=> 1);
        } catch (\Exception $e) {
            return new \Exception($e->getMessage());
        }
    }
}