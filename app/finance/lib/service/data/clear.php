<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_service_data_clear{

    function data_clear(){
        $app_dbschema_path = APP_DIR.'/finance/dbschema';
        $dbschame_dir = opendir($app_dbschema_path);
        while( $file = readdir($dbschame_dir) ){
            $ext = substr($file, strpos($file,'.php'));
            if ($file != '..' && $file != '.' && $ext == '.php'){
                $file = substr($file, 0, strpos($file,'.php'));
                $table_name = 'sdb_finance_'.$file;
                if(in_array($table_name,array('sdb_finance_bill_fee_item','sdb_finance_bill_fee_type'))){
                    continue;
                }
                $sql = "truncate table `".$table_name."`;";
                
                kernel::database()->exec($sql);
            }
        }
        #清空KV
        app::get('finance')->setConf('fee_item','');
        app::get('finance')->setConf('monthly_report_money','');
        app::get('finance')->setConf('finance_setting_init_time',array('flag'=>'false'));
    }
}