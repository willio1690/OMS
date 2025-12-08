<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 京东钱包流水[分片]保存数据任务
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: Z
 */
class financebase_autotask_task_type_cainiaoJdbillImport extends financebase_autotask_task_init
{
    //账单类型
    static $_import_type = 'jdbill';
    
    /**
     * 处理
     * @param mixed $task_info task_info
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */

    public function process($task_info, &$error_msg)
    {
        $oProcess = kernel::single('financebase_data_jingzhuntong_jdbill');
        
        $this->oFunc->writelog('京东钱包流水导入任务-开始', 'settlement', '任务ID:' . $task_info['queue_id']);
        
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
            $this->oFunc->writelog('京东钱包流水导入任务-失败', 'settlement', '任务ID:' . $task_info['queue_id']."缺少文件{$remote_url},{$local_file}");
        }
        
        //check
        if (empty($task_info['queue_data']['data'])) {
            return true;
        }
        
        //title
        $titleList = $oProcess->getTitle();
        $titleList = array_values($titleList);
        
        //[兼容]标题行从第7行开始
        $isTitle = false;
        $lineNum = 0;
        $implodeTitle = $task_info['queue_data']['data'][6];
        $implodeTitle[0] = trim($implodeTitle[0], "\xEF\xBB\xBF");
        
        //data
        $data = array();
        $errorData = array();
        $offset = intval($task_info['queue_data']['offset']) + 1;
        foreach ($task_info['queue_data']['data'] as $k => $row)
        {
            $lineNum++;
            
            //[兼容]去除标题行BOM头(第一行标题)
            if($k == 0){
                $row[0] = trim($row[0], "\xEF\xBB\xBF");
                if($row[0] == $titleList[0]){
                    $isTitle = true;
                    continue; //过滤标题
                }
            }
            
            //[兼容]标题行从第7行开始
            if(!$isTitle && $lineNum <= 7){
                if($implodeTitle[0] == $titleList[0]){
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
            $this->oFunc->writelog('京东钱包流水导入任务-部分成功', 'settlement', '任务ID:' . $task_info['queue_id']);
        } else {
            $this->oFunc->writelog('京东钱包流水导入任务-完成', 'settlement', '任务ID:' . $task_info['queue_id']);
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
            $jdbillObj = app::get('financebase')->model('bill_import_jdbill');
            $summaryMdl = app::get('financebase')->model("bill_import_summary");
            $importMdl = app::get('financebase')->model("bill_import");
            
            $importRes = $importMdl->getRow('*', array('id'=>$importId));
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
                $res = $this->saveRow($v, $importRes, $summaryMdl, $jdbillObj, $type);
                if ($res['status'] == true) {
                    $not_confirm_num += $res['num'];
                    $not_reconciliation_num += $res['num'];
                    
                    //支出金额(保留三位小数)
                    $money = bcadd($money, $v['outgo_fee'], 3);
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
     * @param unknown $jdbillObj
     * @param unknown $type
     * @return multitype:boolean string |Ambigous <保存的数据条数, multitype:boolean string , multitype:boolean number , \Exception>|multitype:boolean Ambigous <boolean, string, boolean, number>
     */
    public function saveRow($data, $importRes, $summaryMdl, $jdbillObj, $type)
    {
        try {
            $db = kernel::database();
            $transaction_status = $db->beginTransaction();
            
            /***
            //导入数据汇总
            $summaryRes = $this->saveSummary($summaryMdl, $data, $importRes, $type);
            
            if (empty($summaryRes['id'])) {
                $db->rollback();
                return array('status' => false, 'msg'=>'商户号['. $data['member_id'] .']导入数据汇总错误');
            }
            ***/
            
            //京东钱包流水没有唯一的流水单号,无法创建汇总数据
            $summaryRes['id'] = 0;
            
            //保存账单数据
            $saveRes = $this->saveBill($data, $summaryRes, $jdbillObj, $importRes);
            
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
        $summaryRes = $summaryMdl->getRow('*', array('pay_serial_number'=>$data['crc_unique'], 'import_id'=>$importRes['id'], 'type'=>$type));
        if (!$summaryRes) {
            //insert
            $params = array(
                'import_id' => $importRes['id'],
                'confirm_status' => 0,
                'type' => $type,
                'pay_serial_number' => $data['crc_unique'], //流水单号
                'expenditure_money' => $data['outgo_fee'], //支出总额
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
     * @param $jdbillObj
     * @param $importRes
     * @return 保存的数据条数
     */
    public function saveBill($data, $summaryRes, $jdbillObj, $importRes)
    {
        try {
            $sdfData = array(
                'member_id' => $data['member_id'], //商户号
                'account_no' => $data['account_no'], //账户代码
                'account_name' => $data['account_name'], //账户名称
                'trade_time' => $data['trade_time'], //交易日期
                'trade_no' => $data['trade_no'], //商户订单号
                'account_balance' => $data['account_balance'], //账户余额(元)
                'income_fee' => $data['income_fee'], //收入金额(元)
                'outgo_fee' => $data['outgo_fee'], //支出金额(元)
                'remark' => $data['remark'], //交易备注
                'bill_time' => $data['bill_time'], //账单日期
            );
            
            //唯一编号
            $sdfData['crc_unique'] = crc32(implode('', $sdfData));
            
            //检查数据是否存在
            $flag = $jdbillObj->dump(array('crc_unique'=>$sdfData['crc_unique']), 'id');
            if ($flag) {
                return array('status'=>false, 'msg' => $sdfData['crc_unique'] .' 该记录已存在');
            }
            
            //insert
            $sdfData['import_id'] = $importRes['id']; //导入记录ID
            $sdfData['summary_id'] = $summaryRes['id']; //导入数据汇总ID
            $sdfData['at_time'] = time(); //创建时间
            $sdfData['up_time'] = time(); //更新时间
            $sdfData['op_id'] = intval($importRes['op_id']); //操作人
            
            $saveRes = $jdbillObj->insert($sdfData);
            if (!$saveRes) {
                return array('status' => false, 'msg' => '商户号['. $sdfData['member_id'] .'],商户订单号['. $sdfData['trade_no'] .']添加失败');
            }
            
            //succ
            return array('status' => true,'num'=> 1);
        } catch (\Exception $e) {
            return new \Exception($e->getMessage());
        }
    }
}