<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_task{
    function post_install($params){
        //初始化bill_fee_type数据
		$init_sdf_type = app::get('finance')->app_dir . '/initial/finance.bill_fee_type.sdf';
		kernel::single('base_initial','finance')->init_sdf('finance','bill_fee_type',$init_sdf_type);
        app::get('finance')->model('bill_fee_type')->update(array('createtime' => time()));

        //初始化bill_fee_type数据
		$init_sdf_item = app::get('finance')->app_dir . '/initial/finance.bill_fee_item.sdf';
		kernel::single('base_initial','finance')->init_sdf('finance','bill_fee_item',$init_sdf_item);
        app::get('finance')->model('bill_fee_item')->update(array('createtime' => time()));

        //初始化KV
        $fee_item_kv_tmp = app::get('finance')->model('bill_fee_item')->getList('fee_item_id,fee_type_id,fee_item,inlay');
        $fee_item_kv = array();
        foreach($fee_item_kv_tmp as $v){
            $fee_type = app::get('finance')->model('bill_fee_type')->getList('fee_type',array('fee_type_id'=>$v['fee_type_id']));
            $fee_item_kv[$v['fee_type_id']]['name'] = $fee_type[0]['fee_type'];
            $fee_item_kv[$v['fee_type_id']]['item'][$v['fee_item_id']]['inlay'] = $v['inlay'];
            $fee_item_kv[$v['fee_type_id']]['item'][$v['fee_item_id']]['name']= $v['fee_item'];
        }
        app::get('finance')->setConf('fee_item',$fee_item_kv);

        #卸载对账APP
        $shell = new base_shell_loader;
        $script_dri = ROOT_DIR."/app/finance/script/update/";
        include_once($script_dri.'1.0.1.php');

        $this->get_tmall_account();
    }

    function post_update($params){
        $shell = new base_shell_loader;

        $app = app::get('finance')->define();
        // 升级
        if ($app['version'] == '1.0.2') {
            $init_sdf_type = app::get('finance')->app_dir . '/initial/finance.bill_fee_type.sdf';
            kernel::single('base_initial','finance')->init_sdf('finance','bill_fee_type',$init_sdf_type);
        
            $init_sdf_item = app::get('finance')->app_dir . '/initial/finance.bill_fee_item.sdf';
            kernel::single('base_initial','finance')->init_sdf('finance','bill_fee_item',$init_sdf_item);

            $this->get_tmall_account();
        }

    }

    function post_uninstall($params){
        #清空KV
        app::get('finance')->setConf('fee_item','');
        app::get('finance')->setConf('monthly_report_money','');
        app::get('finance')->setConf('finance_setting_init_time',array('flag'=>'false'));
        $financeObj = base_kvstore::instance('setting/finance');
        $financeObj->delete('sales_read_time');
        $financeObj->delete("bills_get");

        $funcObj = kernel::single('finance_func');
        $shop_list = $funcObj->taobao_shop_list();
        if ($shop_list){
                foreach ($shop_list as $shop){
                    $financeObj->delete("shop_trade_search_".$shop['node_id']);
                    $financeObj->delete("shop_bills_get_".$shop['node_id']);
                }
        }
    }

    /**
     * 安装/升级时，获取天猫的科目
     *
     * @return void
     * @author 
     **/
    private function get_tmall_account()
    {
        $shop_id = null;

        $shops = app::get('ome')->model('shop')->getList('shop_id,node_id',array('node_type' => 'taobao'));

        foreach ($shops as $shop) {
            if ($shop['node_id']) {
                $shop_id = $shop['shop_id'];
                break;
            }
        }

        if ($shop_id) {
            kernel::single('erpapi_router_request')->set('shop', $shop_id)->finance_bill_account_get();
        }
    }
}