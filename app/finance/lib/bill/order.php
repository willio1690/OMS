<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_bill_order extends eccommon_analysis_abstract implements eccommon_analysis_interface{

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
        #店铺
        $shopObj = &app::get('ome')->model('shop');
        $shopdata = $shopObj->getList('name,shop_id');
        $this->_render->pagedata['shopdata']= $shopdata;
        $this->_render->pagedata['shop_id']= $_POST['shop_id'] ? $_POST['shop_id'] : '0';
        #各费用类的总额
        $feedata = kernel::single('finance_bill')->get_fee_type_money_by_shop_id($_POST['time_from'],$_POST['time_to'],$this->_render->pagedata['shop_id']);
        $this->_render->pagedata['feedata']= $feedata;
        $this->_extra_view = array('finance' => 'bill/order.html');
    }

    /**
     * 查找er
     * @return mixed 返回结果
     */
    public function finder(){
       	//$base_query_string = 'time_from='.$_POST['time_from'].'&time_to='.$_POST['time_to'];
		$this->export_href = 'index.php?app=finance&ctl=bill_order&act=index&action=export';
        $params =  array(
            'model' => 'finance_mdl_bill_order',
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
                'title'=>'订单账单<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=finance&ctl=bill_order&act=index&action=export\');}</script>',
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
                'use_buildin_filter'=>false,
                'finder_aliasname'=>'order_bill',
                'object_method'=>array('count'=>'count_order_bill','getlist'=>'getlist_order_bill'),
                'finder_cols'=>'order_bn,channel_name,column_trade,column_plat,column_branch,column_delivery,column_other,column_total',
                //'base_query_string'=>$base_query_string,
                //'base_filter'=>array('time_from'=>$this->_params['time_from'],'time_to'=>$this->_params['time_to'],'shop_id'=>$this->_params['shop_id']),
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
}