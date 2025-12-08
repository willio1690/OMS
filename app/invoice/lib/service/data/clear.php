<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class invoice_service_data_clear{
    // 清除invoice的相关表数据
    public function data_clear(){
        $app_dbschema_path = APP_DIR . '/invoice/dbschema';
        $dbschame_dir = opendir($app_dbschema_path);
        while( $file = readdir($dbschame_dir) ){
            $ext = substr($file, strpos($file,'.php'));
            if ($file != '..' && $file != '.' && $ext == '.php'){
                $file = substr($file, 0, strpos($file,'.php'));
                $table_name = 'sdb_invoice_' . $file;
                if(in_array($table_name,array('sdb_invoice_channel','sdb_invoice_content','sdb_invoice_goods_items','sdb_invoice_order_setting'))){
                    continue;
                }
                $sql = "truncate table `" . $table_name . "`;";
                kernel::database()->exec($sql);
            }
        }
    }
}