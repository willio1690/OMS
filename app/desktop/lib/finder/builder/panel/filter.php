<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class desktop_finder_builder_panel_filter extends desktop_finder_builder_prototype{
    
    private $panelId = '';
    private $file = array();

    function main(){
        $view = $_GET['view'];
        $view_filter = $this->get_views('panel');
        $__filter = $view_filter[$view];
        if( $__filter['filter'] ) $filter = $__filter['filter'];
        
        $o = new desktop_finder_builder_panel_render($this->finder_aliasname);
        $o->setFinder($this);
 
        $html = $o->main($this->object->table_name(), $this->app, $filter, $this->controller);
        
        $this->controller->pagedata['panel_html'] = $html;
    }

    /**
     * 设置Id
     * @param mixed $id ID
     * @return mixed 返回操作结果
     */
    public function setId($id) {
        $this->panelId = $id;
    }
    
    /**
     * 获取Id
     * @return mixed 返回结果
     */
    public function getId() {
        return $this->panelId;
    }
    
    /**
     * 设置File
     * @param mixed $file file
     * @return mixed 返回操作结果
     */
    public function setFile($file) {
        $this->file = $file;
    }
    
    /**
     * 获取File
     * @return mixed 返回结果
     */
    public function getFile() {
        return $this->file;
    }
}
