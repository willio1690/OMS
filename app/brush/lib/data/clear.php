<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class brush_data_clear{

    function data_clear(){
        $app_dbschema_path = APP_DIR.'/brush/dbschema';
        $dbschame_dir = opendir($app_dbschema_path);
        while( $file = readdir($dbschame_dir) ){
            $ext = substr($file, strpos($file,'.php'));
            if ($file != '..' && $file != '.' && $ext == '.php'){
                $file = substr($file, 0, strpos($file,'.php'));
                $table_name = 'sdb_brush_'.$file;
                $sql = "truncate table `".$table_name."`;";
                kernel::database()->exec($sql);
            }
        }
    }
}