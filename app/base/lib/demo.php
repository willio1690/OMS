<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class base_demo
{

    /**
     * 初始化
     * @return mixed 返回值
     */
    public function init() 
    {
        $demo_dir = ROOT_DIR . '/demo';
        if(is_dir($demo_dir)){
            $handle = opendir($demo_dir);
            while($file = readdir($handle)){
                $realfile = $demo_dir . '/' . $file;
                if(is_file($realfile)){
                    list($app_id, $model, $ext) = explode('.', $file);
                    if($ext == 'sdf'){
                        $this->init_sdf($app_id, $model, $realfile);
                    }elseif($ext=='php' && $model=='setting'){
                        $setting = include($realfile);
                        $this->init_setting($app_id, $setting);
                    }
                }
            }
            closedir($handle);
        }
    }//End Function

    /**
     * 初始化_setting
     * @param mixed $app_id ID
     * @param mixed $setting setting
     * @return mixed 返回值
     */
    public function init_setting($app_id, $setting) 
    {
        $app = app::get($app_id);
        if(is_array($setting)){
            foreach($setting AS $key=>$value){
                $app->setConf($key, $value);
            }
        }
    }//End Function

    /**
     * 初始化_sdf
     * @param mixed $app_id ID
     * @param mixed $model model
     * @param mixed $file file
     * @return mixed 返回值
     */
    public function init_sdf($app_id, $model, $file) 
    {
        $handle = fopen($file, 'r');
        if($handle){
            while(!feof($handle)){
                $buffer .= fgets($handle);
                if(!($sdf = unserialize($buffer))){
                    continue;
                }
                app::get($app_id)->model($model)->db_save($sdf);
                $buffer = '';
            }
            fclose($handle);
        }
    }//End Function
}//End Class
