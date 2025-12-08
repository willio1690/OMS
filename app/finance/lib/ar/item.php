<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_ar_item extends eccommon_analysis_abstract implements eccommon_analysis_interface{

	public $detail_options = array(
        'hidden' => true,
    );
    public $graph_options = array(
        'hidden' => true,
    );
    public $type_options = array(
        'display' => 'true',
    );

    function __construct(&$app)
    {
        parent::__construct($app);
        $this->_render = kernel::single('desktop_controller');

        for($i=0;$i<=5;$i++){
            if ($i == 1) continue;
            $val = $i+1;
            $this->_render->pagedata['time_shortcut'][$i] = $val;
        }

        $shopObj = &app::get('ome')->model('shop');
        $shopdata = $shopObj->getList('name,shop_id');

        $statistics['qcys'] = $this->statistics_qcys($_POST);#期初应收统计
        $statistics['bqys'] = $this->statistics_bqys($_POST);#本期应收统计
        $statistics['bqss'] = $this->statistics_bqss($_POST);#本期实收统计
        $statistics['qmys'] = sprintf("%01.2f",($statistics['qcys']+$statistics['bqys'])-$statistics['bqss']);#期末应收统计

        $this->_render->pagedata['statistics']= $statistics;
        $this->_render->pagedata['shopdata']= $shopdata;
        $this->_render->pagedata['shop_id']= $_POST['shop_id'] ? $_POST['shop_id'] : '0';
        $this->_extra_view = array('finance' => 'ar/item.html');

    }


    /**
     * 查找er
     * @return mixed 返回结果
     */
    public function finder(){
        $this->export_href = 'index.php?app=finance&ctl=ar_item&act=index&action=export';
        $params =  array(
            'model' => 'finance_mdl_ar_statistics',
            'params' => array(
                'actions'=>array(
                     array(
                        'label' => '导出',
                        'href' => $this->export_href,
                        'target' => '{width:600,height:300,title:\'导出\'}',
                        //'id'=>'export_id',
                        'class'=>'export',
                     ),
                ),
                'title'=>'销售到账明细<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=finance&ctl=ar_item&act=index&action=export\');}</script>',
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
                'use_buildin_filter'=>false,
                'finder_aliasname'=>'item_ar',
                'finder_cols'=>'ar_bn,trade_time,member,type,order_bn,channel_name,column_items_nums,money,column_fee_money,column_qcys,cloumn_bqys,cloumn_bqss,cloumn_qmys',
            ),
        );
        #增加财务导出权限
        $is_export = kernel::single('desktop_user')->has_permission('finance_export');
        if(!$is_export){
            unset($params['params']['actions']);
        }
        return $params;
    }

     function export_href()
    {
        $base_href = $this->export_href;
        $str = <<<EOF
        <script>
        $('export_id').href="{$base_href}";
        $('export_id').addEvent('click',function(){var finder_id = $('workground').getElement('input[name^=_finder[finder_id]');
        var filter_input = $('finder-filter-'+finder_id.value);
        if(filter_input){
            $('export_id').href="{$base_href}"+"&"+filter_input.value+"&export_href=true";
        }
        });
        </script>
EOF;
        return $str;
    }


    //统计期初应收金额
    public function statistics_qcys($filter = array()){
        $where = ' 1 ';
        if(isset($filter['shop_id']) && $filter['shop_id']!='0'){
            $where .= " AND channel_id = '".$filter['shop_id']."'";
            unset($filter['shop_id']);
        }
        if(isset($filter['time_from']) && $filter['time_from']!=''){
            $where .= " AND trade_time < ".strtotime($filter['time_from']);
            unset($filter['time_from']);
        }
        $statistics_qcys_sql = 'SELECT SUM(money) as money FROM sdb_finance_ar WHERE '.$where;
        $statistics_qcys_verification_sql = 'SELECT SUM(money) as money FROM sdb_finance_verification_items WHERE type=0 AND  bill_id in(SELECT ar_id as bill_id FROM sdb_finance_ar WHERE '.$where.')';
        $statistics_qcys = kernel::database()->select($statistics_qcys_sql);
        $statistics_qcys_verification = kernel::database()->select($statistics_qcys_verification_sql);
        $qcys = sprintf("%01.2f",$statistics_qcys[0]['money'] - $statistics_qcys_verification[0]['money']);
        return $qcys;
    }

    //统计本期应收金额
    public function statistics_bqys($filter = array()){
        $ar_statistics_mdl = app::get('finance')->model('ar_statistics');
        $statistics_bqys_sql = 'SELECT SUM(money) as money FROM sdb_finance_ar WHERE '.$ar_statistics_mdl->statistics_filter($filter );
        $statistics_bqys = kernel::database()->select($statistics_bqys_sql);
        $bqys = sprintf("%01.2f",$statistics_bqys[0]['money']);
        return $bqys;
    }

    //统计本期实收金额
    public function statistics_bqss($filter = array()){
        $ar_statistics_mdl = app::get('finance')->model('ar_statistics');
        $sql = 'SELECT ar_id as bill_id FROM sdb_finance_ar WHERE '.$ar_statistics_mdl->statistics_filter($filter );
        $statistics_bqss_verification_sql = 'SELECT SUM(money) as money FROM sdb_finance_verification_items WHERE type=0 AND  bill_id in('.$sql.')';
        $statistics_bqss_verification = kernel::database()->select($statistics_bqss_verification_sql);
        $bqss = sprintf("%01.2f",$statistics_bqss_verification[0]['money']);
        return $bqss;
    }

}