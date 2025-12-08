<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_bill_item extends eccommon_analysis_abstract implements eccommon_analysis_interface{

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
        #费用类/费用项
        $feekv = app::get('finance')->getConf('fee_item');
        if($_POST['fee_item_id']){
            $this->_render->pagedata['item_id']= $_POST['fee_item_id'];
            $item_id = $_POST['fee_item_id'];
        }else{
            $item_id = '';
        }
        if($_POST['fee_type_id']){
            $this->_render->pagedata['type_id']= $_POST['fee_type_id'];
            $type_id = $_POST['fee_type_id'];
        }else{
            $type_id = '';
        }
        #各费用类的总额

        $feedata = kernel::single('finance_bill')->get_fee_type_money_by_fee_item_id($type_id,$item_id,$_POST['time_from'],$_POST['time_to']);

        $this->_render->pagedata['feedata']= $feedata;
        $this->_render->pagedata['feekv']= $feekv;
        $this->_extra_view = array('finance' => 'bill/item.html');
    }

    /**
     * 查找er
     * @return mixed 返回结果
     */
    public function finder(){
        
        return array(
            'model' => 'finance_mdl_bill',
            'params' => array(
                'actions'=>array(
                    array(
                        'label' => '导出普通模板',
                        'href' => 'index.php?app=omecsv&ctl=admin_export&act=main&add=finance&ctler=finance_mdl_bill&filter[template]=1',
                        'target' => "dialog::{width:400,height:170,title:'导出普通模板'}",
                    ),
                    array(
                        'label' => '导入',
                        'href' => 'index.php?app=omecsv&ctl=admin_import&act=main&add=finance&ctler=finance_mdl_bill',
                        'target' => "dialog::{width:400,height:180,title:'导入'}",
                    ),
                ),
                'title'=>'明细账单',
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
                'use_buildin_filter'=>false,
                'finder_aliasname'=>'item_bill',
                'finder_cols'=>'column_edit,order_bn,trade_time,fee_type,fee_item,fee_obj,column_moneyin,column_moneyout,credential_number',
            ),
        );
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
}