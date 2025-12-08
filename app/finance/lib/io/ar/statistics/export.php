<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_io_ar_statistics_export{

    /**
     * export_params
     * @param mixed $mdl mdl
     * @return mixed 返回值
     */
    public function export_params($mdl){
        $filter = $mdl->export_filter;
        $filter = $filter['_params'];
        if($filter['channel_id'] == '' || $filter['channel_id'] == '0') unset($filter['channel_id']);
        $params = array(
            'filter' => $filter,
            'limit' => 2000,
            'get_data_method' => 'get_ar_statistics',
            'single'=> array(
                'ar_statistics'=> array(
                    'filename' => '销售到账明细导出',
                ),
            ),
        );
        return $params;
    }
    
    /**
     * 获取_ar_statistics_title
     * @return mixed 返回结果
     */
    public function get_ar_statistics_title(){
        $title['ar_statistics'] = array(
            '*:单据编号',
            '*:账单日期',
            '*:客户/会员',
            '*:业务类型',
            '*:订单号',
            '*:店铺',
            '*:明细数量',
            '*:货款',
            '*:运费',
            '*:期初应收',
            '*:本期应收',
            '*:本期实收',
            '*:期末应收',
        );
        return $title;
    }

    /**
     * 获取_ar_statistics
     * @param mixed $mdl mdl
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @param mixed $data 数据
     * @return mixed 返回结果
     */
    public function get_ar_statistics($mdl,$filter,$offset,$limit,&$data){
        $ar_statistics_mdl = app::get('finance')->model('ar_statistics');
        $ar_statistics = $ar_statistics_mdl->getList('*',$filter,$offset,$limit);
        $time_from = strtotime($_POST['time_from'].' 00:00:00');
        if($ar_statistics){
            foreach($ar_statistics as $v){
                $addon = unserialize($v['addon']);
                $fee_money = number_format($addon['fee_money'],2,'.','0');
                $sql = "SELECT SUM(num) as nums FROM sdb_finance_ar_items WHERE ar_id =".$v['ar_id'];
                $nums = kernel::database()->select($sql);
                $itemNums = is_null($nums[0]['nums'])? 0 : $nums[0]['nums'];
                $tmp = array(
                    '*:单据编号' => $v['ar_bn'],
                    '*:账单日期' => date('Y-m-d H:i:s',$v['trade_time']),
                    '*:客户/会员' => $v['member'],
                    '*:业务类型' => finance_ar::get_name_by_type($v['type']),
                    '*:订单号' => $v['order_bn'],
                    '*:店铺' => $v['channel_name'],
                    '*:明细数量' => $itemNums,
                    '*:货款' => $v['money'],
                    '*:运费' => $fee_money,
                    '*:期初应收' => $ar_statistics_mdl->get_qcys($v['ar_id'],$time_from),
                    '*:本期应收' => $ar_statistics_mdl->get_bqys($v['ar_id']),
                    '*:本期实收' => $ar_statistics_mdl->get_bqss($v['ar_id']),
                    '*:期末应收' => $ar_statistics_mdl->get_qmys($v['ar_id']),
                );
                $data['ar_statistics'][] = $tmp;
            }
        }
    }



}
