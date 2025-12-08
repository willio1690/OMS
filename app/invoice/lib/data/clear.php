<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * Class invoice_data_clear
 * 发票相关业务库表数据初始化
 */
class invoice_data_clear{

    function data_clear(){
        // 初始化数据标识 true初始化成功 false失败
        $res = true;
        $app_dbschema_path = APP_DIR . '/invoice/dbschema';
        $dbschame_dir = opendir($app_dbschema_path);
        while( $file = readdir($dbschame_dir) ){
            $ext = substr($file, strpos($file,'.php'));
            if ($file != '..' && $file != '.' && $ext == '.php'){
                $file = substr($file, 0, strpos($file,'.php'));
                $table_name = 'sdb_invoice_' . $file;
                $sql = "truncate table `" . $table_name . "`;";
                $res = kernel::database()->exec($sql);
                if(!$res){
                    $res = false;
                }
            }
        }
        return $res;
    }
}