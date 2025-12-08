<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_task{

    function post_install(){
    
    	// 接口下载地址
        if(!is_dir(DATA_DIR.'/financebase/settlement')){
            utils::mkdir_p(DATA_DIR.'/financebase/settlement');
        }

        //财务本地缓存目录
        if(!is_dir(DATA_DIR.'/financebase/tmp_local')){
            utils::mkdir_p(DATA_DIR.'/financebase/tmp_local');
        }


        kernel::single('base_initial', 'financebase')->init();

   
  
    }


    function install_options(){
        return array(
                
            );
    }
}
