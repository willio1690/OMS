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
class financebase_data_cainiao_sku extends financebase_abstract_bill
{
    public $order_bn_prefix = 'T200P';
    public $column_num = 13;

    // 处理数据
    /**
     * 获取Sdf
     * @param mixed $row row
     * @param mixed $offset offset
     * @param mixed $title title
     * @return mixed 返回结果
     */

    public function getSdf($row,$offset=1,$title)
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
        if ($this->column_num <= $row_num and $row[0] != '支付流水号') {
            $tmp = array_combine($titleKey, $row);

            foreach ($tmp as $k => $v) {
                if (in_array($k, array('pay_serial_number','expenditure_time','expenditure_money', 'cost_project','sku','actual_operation_num'))) {

                    if ($k == 'expenditure_money' && $v == '0'){

                    } else if (!$v) {
                        $res['status'] = false;
                        $res['msg'] = sprintf("LINE %d : %s 不能为空！", $offset, $this->ioTitle[$k]);
                        return $res;
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

                if (in_array($k, array('submit_time')) && !empty($v)) {
                     $tmp[$k] = strtotime($v);
                }

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
            'pay_serial_number' => '支付流水号',
            'expenditure_time' => '支付时间',
            'expenditure_money' => '支付金额',
            'increment_service_sn' => '增值服务单号',
            'relation_sn' => '关联单据号',

            'cost_project'       => '增值服务类型', //费用项
            'service_provider'       => '服务商',
            'submit_time'       => '提交时间',
            'status'       => '状态',
            'sku'       => '商品编码',

            'product_name'       => '商品名称',
            'plan_operation_num'       => '计划操作数量',
            'actual_operation_num'       => '实际操作数量',

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
        if('支付流水号' != $row[0][0])
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
        return array('column'=>array(2),'time_diff'=>$timezone * 3600);
    }


}