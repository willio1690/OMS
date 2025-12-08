<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class base_application_lang extends base_application_prototype_filepath 
{
    var $path = 'lang';

    /**
     * install
     * @return mixed 返回值
     */
    public function install() 
    {
        $dir = $this->getPathname();
        $dir = str_replace('\\', '/', $dir);
        $app_lang_dir = str_replace('\\', '/', $this->target_app->lang_dir);
        $lang_name = basename($dir);
        foreach(utils::tree($dir) AS $k=>$v){
            if(!is_file($v))  continue;
            $tree[$lang_name][] = str_replace($app_lang_dir.'/'.$lang_name.'/', '', $v);
        }
        kernel::log($this->target_app->app_id . ' "' . $lang_name . '" language resource stored');
        lang::set_res($this->target_app->app_id, $tree);
    }//End Function
    
    /**
     * 清除_by_app
     * @param mixed $app_id ID
     * @return mixed 返回值
     */
    public function clear_by_app($app_id){
        if(!$app_id){
            return false;
        }
        lang::del_res($app_id);
    }
    
}//End Class
