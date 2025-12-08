<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 清空拣货数据
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class tgkpi_service_data_clear{

    function data_clear(){
        $app_dbschema_path = APP_DIR.'/tgkpi/dbschema';
        $dbschame_dir = opendir($app_dbschema_path);
        while( $file = readdir($dbschame_dir) ){
            $ext = substr($file, strpos($file,'.php'));
            if ($file != '..' && $file != '.' && $ext == '.php'){
                $file = substr($file, 0, strpos($file,'.php'));

                $table_name = 'sdb_tgkpi_'.$file;

                $sql = "truncate table `".$table_name."`;";

                kernel::database()->exec($sql);
            }
        }
    }
}