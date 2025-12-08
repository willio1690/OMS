<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class base_initial
{

    /**
     * __construct
     * @param mixed $app_id ID
     * @return mixed 返回值
     */
    public function __construct($app_id)
    {
        $this->app = app::get($app_id);
    } //End Function

    /**
     * 初始化
     * @return mixed 返回值
     */
    public function init()
    {
        if (defined('CUSTOM_CORE_DIR') && file_exists(CUSTOM_CORE_DIR . '/' . $this->app->app_id . '/initial')) {
            $demo_dir = CUSTOM_CORE_DIR . '/' . $this->app->app_id . '/initial';
        } else {
            $demo_dir = $this->app->app_dir . '/initial';
        }
        if (is_dir($demo_dir)) {
            $handle = opendir($demo_dir);
            while ($file = readdir($handle)) {
                $realfile = $demo_dir . '/' . $file;
                if (is_file($realfile)) {
                    list($app_id, $model, $ext) = explode('.', $file);
                    if ($ext == 'sdf') {
                        $this->init_sdf($app_id, $model, $realfile);
                    } elseif ($ext == 'php' && $model == 'setting') {
                        $setting = include $realfile;
                        $this->init_setting($app_id, $setting);
                    } elseif ($ext == 'sql') {
                        $this->init_sql($app_id, $model, $realfile);
                    }
                }
            }
            closedir($handle);
        }
    } //End Function

    /**
     * 初始化_setting
     * @param mixed $app_id ID
     * @param mixed $setting setting
     * @return mixed 返回值
     */
    public function init_setting($app_id, $setting)
    {
        $app = app::get($app_id);
        if (is_array($setting)) {
            foreach ($setting as $key => $value) {
                $app->setConf($key, $value);
            }
        }
    } //End Function

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
        if ($handle) {
            while (!feof($handle)) {
                $buffer .= fgets($handle);
                if (!($sdf = unserialize($buffer))) {
                    continue;
                }
                app::get($app_id)->model($model)->db_save($sdf);
                $buffer = '';
            }
            fclose($handle);
        }
    } //End Function

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    public function init_sql($app_id, $model, $file)
    {
        if ($sqls = file_get_contents($file)) {
            foreach (array_filter(preg_split("/;[\r\n]/", $sqls)) as $sql) {
                $sql = trim($sql);
                if (!$sql) {
                    continue;
                }
                kernel::database()->exec($sql);
            }
        }
    }

} //End Class
