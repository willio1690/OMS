<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 按单号导入
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_data_cainiao_order extends financebase_abstract_bill
{
    public $order_bn_prefix = 'T200P';
    public $column_num = 6;

    // 处理数据
    /**
     * 获取Sdf
     * @param mixed $row row
     * @param mixed $offset offset
     * @param mixed $title title
     * @return mixed 返回结果
     */

    public function getSdf(&$row,$offset=1,$title)
    {
        $row = array_map('trim',$row);

        if (!$this->ioTitle) $this->ioTitle = $this->getTitle();
        $titleKey = array ();
        foreach ($title as $k => $t) {
            $titleKey[$k] = array_search($t, $this->getTitle());

            if ($titleKey[$k] === false) {
                return array ('status' => false, 'msg' => '未定义字段`'.$t.'`');
            }
        }


        $res = array('status'=>true,'data'=>array(),'msg'=>'');

        $row_num = count($row);

        if ($this->column_num <= $row_num and $row[1] != '费用项') {
            $tmp = array_combine($titleKey, $row);

            foreach ($tmp as $k => $v) {
                if (in_array($k, array('pay_serial_number', 'cost_project','expenditure_time','expenditure_money'))) {

                    if ($k == 'expenditure_money' && $v == '0'){

                    } else if (!$v) {
                        $res['status'] = false;
                        $res['msg'] = sprintf("LINE %d : %s 不能为空！", $offset, $this->ioTitle[$k]);
                        return $res;
                    } else {
                        $val = substr($v,0,1);
                        if ($val == "'") {
                            $tmp[$k] = str_replace("'","",$v);
                        }
                    }

                }

                if (in_array($k, array('expenditure_time'))) {
                    $result = finance_io_bill_verify::isDate($v);
                    if ($result['status'] == 'fail') {
                        $res['status'] = false;
                        $res['msg'] = sprintf("LINE %d : %s 时间格式错误！", $offset, $this->ioTitle[$k]);
                        return $res;
                    }
                }

                if (in_array($k, array('expenditure_money'))) {
                    $result = finance_io_bill_verify::isPrice($v);
                    if ($result['status'] == 'fail') {
                        $res['status'] = false;
                        $res['msg'] = sprintf("LINE %d : %s 金额格式错误！", $offset, $this->ioTitle[$k]);
                        return $res;
                    }
                }

                if (in_array($k, array('logistics_sn')) && !empty($v)) {
                    $val = substr($v,0,1);
                    if ($val == "'") {
                        $tmp[$k] = str_replace("'","",$v);
                    }
                }

            }

            if (empty($tmp['transaction_sn']) && empty($tmp['logistics_sn'])) {
                $res['status'] = false;
                $res['msg'] = sprintf("LINE %d : 交易订单号与运单号不存在！", $offset);
                return $res;
            }

            $res['data'] = $tmp;
        }
        return $res;
    }

    /**
     * 获取Title
     * @return mixed 返回结果
     */
    public function getTitle()
    {
        $title = array(
            'expenditure_time' => '支付时间',
            'cost_project' => '费用项',
            'pay_serial_number' => '支付流水号',
            'expenditure_money' => '支付金额',

            'transaction_sn' => '交易订单号',
            'logistics_sn' => '运单号',
        );

    	return $title;
    }

    /**
     * 获取BillCategory
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function getBillCategory($params) {

    }

    /**
     * 检查文件是否有效
     * @Author YangYiChao
     * @Date   2019-06-25
     * @param  String     $file_name 文件名
     * @param  String     $file_type 文件类型
     * @return Boolean
     */
    public function checkFile($file_name,$file_type){
        $ioType = kernel::single('financebase_io_'.$file_type);
        $row = $ioType->getData($file_name,0,1);
        if('支付时间' != $row[0][0])
        {
            return array (false, '文件模板错误：'.var_export($row,true));
        }

        $title = array_values($this->getTitle()); sort($title);

        $aliTitle = $row[0]; sort($aliTitle);
        if ($title == $aliTitle) {
            return array (true, '文件模板匹配', $row[0]);
        }

        return array (false, '文件模板错误：'.var_export($row,true));
    }





    /**
     * 获取ImportDateColunm
     * @param mixed $title title
     * @return mixed 返回结果
     */
    public function getImportDateColunm($title=null)
    {
        $timezone = defined('DEFAULT_TIMEZONE') ? DEFAULT_TIMEZONE : 0;
        return array('column'=>array(1),'time_diff'=>$timezone * 3600);
    }


}