<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_finder_ar_statistics{
    private $publicData;
    var $addon_cols = "addon";
    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct(){
        $this->publicData['ar_statistics_mdl'] = app::get('finance')->model('ar_statistics');
    }
    var $column_fee_money = '运费收入';
    /**
     * column_fee_money
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_fee_money($row){
        $addon = unserialize($row[$this->col_prefix.'addon']);
        return "￥".number_format($addon['fee_money'],2,'.','');
    }

    var $column_items_nums = '商品总数量';
    /**
     * column_items_nums
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_items_nums($row){
        $sql = "SELECT SUM(num) as nums FROM sdb_finance_ar_items WHERE ar_id =".$row['ar_id'];
        $nums = kernel::database()->select($sql);
        return is_null($nums[0]['nums'])? 0 : $nums[0]['nums'];
    }

    var $column_qcys = '期初应收';
    /**
     * column_qcys
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_qcys($row){
        $time_from = strtotime($_POST['time_from'].' 00:00:00');
        $money = $this->publicData['ar_statistics_mdl']->get_qcys($row['ar_id'],$time_from);
        return "￥".number_format($money,2,'.','');
    }

    var $column_bqys = '本期应收';
    /**
     * column_bqys
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_bqys($row){
        $money = $this->publicData['ar_statistics_mdl']->get_bqys($row['ar_id']);
        return "￥".number_format($money,2,'.','');
    }

    var $column_bqss = '本期实收';
    /**
     * column_bqss
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_bqss($row){
        $money = $this->publicData['ar_statistics_mdl']->get_bqss($row['ar_id']);
        return "￥".number_format($money,2,'.','');
    }

    var $column_qmys = '期末应收';
    /**
     * column_qmys
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_qmys($row){
        $money = $this->publicData['ar_statistics_mdl']->get_qmys($row['ar_id']);
        return "￥".number_format($money,2,'.','');
    }

}
?>