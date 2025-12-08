<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class desktop_panel {
    
    private $id;
    private $tmpl;
    private $controller;
    
    /**
     * __construct
     * @param mixed $controller controller
     * @return mixed 返回值
     */
    public function __construct(&$controller) {
        $this->controller = $controller;
    }
    
    /**
     * show
     * @param mixed $object_name object_name
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function show($object_name, $params) {
        $finder = new desktop_finder_builder_panel_filter($this->controller);

        foreach ($params as $k => $v) {
            $finder->$k = $v;
        }
        
        $app_id = substr($object_name, 0, strpos($object_name, '_'));
        $app = app::get($app_id);
        
        $finder->app = $app;
        $finder->setId($this->id);
        $finder->setFile($this->tmpl);
        
        $finder->work($object_name);
    }
    
    /**
     * 设置Id
     * @param mixed $id ID
     * @return mixed 返回操作结果
     */
    public function setId($id) {
        $this->id = $id;
    }
    
    /**
     * 设置Tmpl
     * @param mixed $tmpl tmpl
     * @return mixed 返回操作结果
     */
    public function setTmpl($tmpl) {
        $this->tmpl = $tmpl;
    }
    
}