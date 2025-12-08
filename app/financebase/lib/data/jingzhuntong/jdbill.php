<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 京东钱包流水导入
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: Z
 */
class financebase_data_jingzhuntong_jdbill extends financebase_abstract_bill
{
    public $order_bn_prefix = 'T200P';
    public $_beginTitleLine = 0; //标题行在第几行(默认0)
    
    /**
     * 获取Title
     * @return mixed 返回结果
     */

    public function getTitle()
    {
        $title = array(
            'member_id' => '商户号',
            'account_no' => '账户代码',
            'account_name' => '账户名称',
            'trade_time' => '日期', //交易日期
            'trade_no' => '商户订单号',
            'account_balance' => '账户余额(元)',
            'income_fee' => '收入金额(元)',
            'outgo_fee' => '支出金额(元)',
            'remark' => '交易备注',
            'bill_time' => '账单日期',
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
        
        $row = $ioType->getData($file_name, 0, 7);
        
        //去除标题行BOM头
        $row[0][0] = trim($row[0][0], "\xEF\xBB\xBF");
        
        //check
        $this->_beginTitleLine = 0;
        if('商户号' != $row[0][0]){
            //[兼容]标题行从第7行开始
            $row[6][0] = trim($row[6][0], "\xEF\xBB\xBF");
            $this->_beginTitleLine = 6;
            if($row[6][0] != '商户号'){
                return array (false, '京东钱包流水模板标题错误：'.var_export($row[$this->_beginTitleLine], true));
            }
        }
        
        $title = array_values($this->getTitle());
        
        sort($title);
        
        $aliTitle = $row[$this->_beginTitleLine];
        
        sort($aliTitle);
        
        if ($title == $aliTitle) {
            return array (true, '京东钱包流水文件模板匹配', $row[$this->_beginTitleLine]);
        }
        
        return array (false, '京东钱包流水文件模板内容错误：'.var_export($row[$this->_beginTitleLine],true));
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
            if (in_array($k, array('member_id', 'trade_no'))) {
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
            
            if (in_array($k, array('trade_time', 'bill_time'))) {
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
                
                //[兼容]导入账单日期字段为空值
                if(empty($v) || empty($tempTime[0])){
                    $tmp[$k] = 0;
                }else{
                    $tmp[$k] = strtotime($v);
                }
            }
            
            if (in_array($k, array('account_balance', 'income_fee', 'outgo_fee'))) {
                
                //[兼容]填写的金额是--两个横杠
                if($v == '--' || $v == '-'){
                    $v = 0;
                    $tmp[$k] = $v;
                }
                
                //check
                $result = finance_io_bill_verify::isPrice($v);
                if ($result['status'] == 'fail') {
                    $res['status'] = false;
                    $res['msg'] = sprintf("LINE %d : %s 金额格式有错误！", $offset, $this->ioTitle[$k]);
                    return $res;
                }
            }
        }
        
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
        
        //column：日期字段所属列,第一列为:1;
        //return array('column'=>array(4, 10),'time_diff'=>$timezone * 3600);
        
        return '';
    }
}