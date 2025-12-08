<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 京准通账单导入
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: Z
 */
class financebase_data_jingzhuntong_jzt extends financebase_abstract_bill
{
    public $order_bn_prefix = 'T200P';
    
    /**
     * 获取Title
     * @return mixed 返回结果
     */

    public function getTitle()
    {
        $title = array(
            'pay_serial_number' => '流水单号',
            'account' => '账号',
            'launchtime' => '投放日期',
            'trade_type' => '交易类型',
            'plan_id' => '计划ID',
            'amount' => '支出',
        );
        
        return $title;
    }
    
    /**
     * 检查文件是否有效
     * 
     * @param String $file_name 文件名
     * @param String $file_type 文件类型
     * @return Boolean
     */
    public function checkFile($file_name, $file_type)
    {
        $ioType = kernel::single('financebase_io_'.$file_type);
        
        $row = $ioType->getData($file_name, 0, 1);
        
        //去除标题行BOM头
        $row[0][0] = trim($row[0][0], "\xEF\xBB\xBF");
        
        //check
        if('流水单号' != $row[0][0]){
            return array (false, '京准通文件模板标题错误：'.var_export($row,true));
        }
        
        $title = array_values($this->getTitle());
        
        sort($title);
        
        $aliTitle = $row[0];
        sort($aliTitle);
        
        if ($title == $aliTitle) {
            return array (true, '京准通文件模板匹配', $row[0]);
        }
        
        return array (false, '京准通文件模板内容错误：'.var_export($row,true));
    }
    
    /**
     * 获取导入的每行数据
     * 
     * @param unknown $row
     * @param number $offset
     * @param unknown $title
     * @return array
     */
    public function getSdf(&$row, $offset=1, $title)
    {
        $row = array_map('trim', $row);
        
        if (!$this->ioTitle) $this->ioTitle = $this->getTitle();
        
        $titleKey = array ();
        foreach ($title as $k => $t)
        {
            $titleKey[$k] = array_search($t, $this->getTitle());
            
            if ($titleKey[$k] === false) {
                return array ('status' => false, 'msg' => '未定义字段`'.$t.'`');
            }
        }
        
        $res = array('status'=>true,'data'=>array(),'msg'=>'');
        
        $row_num = count($row);
        
        $tmp = array_combine($titleKey, $row);
        
        foreach ($tmp as $k => $v)
        {
            //检查数据不能为空
            if (in_array($k, array('pay_serial_number'))) {
                if (!$v) {
                    $res['status'] = false;
                    $res['msg'] = sprintf("LINE %d : %s 不能为空！", $offset, $this->ioTitle[$k]);
                    return $res;
                } else {
                    $val = substr($v,0,1);
                    if ($val == "'") {
                        $tmp[$k] = str_replace("'", "", $v);
                    }
                }
            }
            
            if (in_array($k, array('launchtime'))) {
                $result = finance_io_bill_verify::isDate($v);
                if ($result['status'] == 'fail') {
                    $res['status'] = false;
                    $res['msg'] = sprintf("LINE %d : %s 时间格式有错误！", $offset, $this->ioTitle[$k]);
                    return $res;
                }
                
                //格式化时间戳
                $tempTime = explode(' ', $v);
                if(empty($tempTime[1])){
                    $v .= ' 00:00:00'; //添加时-分-秒
                }
                $tmp[$k] = strtotime($v);
            }
            
            if (in_array($k, array('amount'))) {
                $result = finance_io_bill_verify::isPrice($v);
                if ($result['status'] == 'fail') {
                    $res['status'] = false;
                    $res['msg'] = sprintf("LINE %d : %s 金额格式有错误！", $offset, $this->ioTitle[$k]);
                    return $res;
                }
            }
        }
        
        /***
        if (empty($tmp['pay_serial_number'])) {
            $res['status'] = false;
            $res['msg'] = sprintf("LINE %d : 流水单号不存在！", $offset);
            return $res;
        }
        ***/
        
        $res['data'] = $tmp;
        
        return $res;
    }

    /**
     * 获取具体类别
     */
    public function getBillCategory($params) {
        //没有类别
    }
    
    /**
     * 设置格式化导入日期字段
     * @todo：当导入Excel文件时,日期字段必须设置此项,否则存储为空值;
     * 
     * @return array
     */
    public function getImportDateColunm($title=null)
    {
        $timezone = defined('DEFAULT_TIMEZONE') ? DEFAULT_TIMEZONE : 0;
        
        //return array('column'=>array(3),'time_diff'=>$timezone * 3600);
        
        return '';
    }
}