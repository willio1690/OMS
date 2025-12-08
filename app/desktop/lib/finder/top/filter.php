<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class desktop_finder_top_filter extends desktop_finder_builder_prototype{
    
    function main($full_object_name,$panel_id){
        $view = $_GET['view'];
        $view_filter = $this->get_views();
        $__filter = $view_filter[$view];
        if( $__filter['filter'] ) $filter = $__filter['filter'];
        
        $this->object_name = $full_object_name;
    
        if ($p = strpos($full_object_name, '_mdl_')) {
            $object_app  = substr($full_object_name, 0, $p);
            $object_name = substr($full_object_name, $p + 5);
        } else {
            trigger_error('finder only accept full model name: ' . $full_object_name, E_USER_ERROR);
        }
        $app_id      = substr($full_object_name, 0, strpos($full_object_name, '_'));
        $app         = app::get($app_id);
        $this->app = $app;
        $this->object   = app::get($object_app)->model($object_name);

        $o = new desktop_finder_builder_filter_top();

		if (method_exists($this->object, 'object_name')){
			$object_name = $this->object->object_name();
		}else{
			$object_name = $this->object->table_name();
		}
		$finder_id = $this->name;
        return $o->main($object_name,$this->app,$filter,$this->controller,false,$panel_id,$finder_id);
    }
}
