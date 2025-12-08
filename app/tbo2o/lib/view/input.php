<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class tbo2o_view_input{

    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct(&$app){
        $this->app = $app;
    }
    
    /**
     * input_tbo2o_object
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function input_tbo2o_object($params) 
    {
        $return_url = $params['return_url']?$params['return_url']:'index.php?app=desktop&ctl=editor&act=object_rows'; 
        $callback = $params['callback']?$params['callback'] : ($params['data']['callback'] ? $params['data']['callback'] : '');
        $params['breakpoint'] = isset($params['breakpoint'])?$params['breakpoint']:20;

        $object = $params['object'];
        if(strpos($params['object'],'@')!==false){
            list($object,$app_id) = explode('@',$params['object']);
            $params['object'] = $object;
        }elseif($params['app']){
            $app_id = $params['app'];
        }else{
            $app_id = $this->app->app_id;
        }

        $app = app::get($app_id);
        $o = $app->model($object);
        $render = new base_render(app::get('desktop'));
        $ui = new base_component_ui($app);


        $dbschema = $o->get_schema();

        $params['app_id'] = $app_id;

        if(isset($params['filter'])){
            if(!is_array($params['filter'])){
                parse_str($params['filter'],$params['filter']);
            }
        }

        $params['domid'] = substr(md5(uniqid()),0,6);

        $key = $params['key']?$params['key']:$dbschema['idColumn'];
        $textcol = $params['textcol']?$params['textcol']:$dbschema['textColumn'];
        
        
        //显示列 可以多列显示 不完全修改 。。。。。。。 
        $textcol = explode(',',$textcol);
        $_textcol = $textcol;
        $textcol = $textcol[0];


        $tmp_filter = $params['filter']?$params['filter']:null;
        $count = $o->count($tmp_filter);
        if($count<=$params['breakpoint']&&!$params['multiple']&&$params['select']!='checkbox'){
            if(strpos($textcol,'@')===false){
                $list = $o->getList($key.','.$textcol,$tmp_filter);
                if(!$list[0]) $type=array();
                foreach($list as $row){
                    $label = $row[$textcol];
                    if(!$label&&method_exists($o,'title_modifier')){
                        $label = $o->title_modifier($row[$key]);
                    }
                    $type[$row[$key]] = $label;
                }
                
            }else{
                list($name,$table,$app_id) = explode('@',$textcol);
                $app = $app_id?app::get($app_id):$app;
                $mdl = $app->model($table);
                $list = $o->getList($key,$tmp_filter);
                foreach($list as $row){
                    $tmp_row = $mdl->getList($name,array($mdl->idColumn=>$row[$key]),0,1);
                    $label = $tmp_row[0][$name];
                    if(!$label&&method_exists($o,'title_modifier')){
                        $label = $o->title_modifier($row[$key]);
                    }
                    $type[$row[$key]] = $label;
                }

            }
            $tmp_params['name'] = $params['name'];
            $tmp_params['value'] = $params['value'];
            $tmp_params['type'] = $type;
            if($callback)
                $tmp_params['onchange'] = $callback.'(this)';
            $str_filter = $ui->input($tmp_params);
            unset($tmp_params);
            return $str_filter;

        }

        $params['idcol'] = $keycol['keycol'] = $key;
        $params['textcol'] = implode(',',$_textcol);
        
        $params['_textcol'] = $_textcol;
        if($params['value']){
            if(strpos($params['view'],':')!==false){
                list($view_app,$view) = explode(':',$params['view']);
                $params['view_app'] = $view_app;
                $params['view'] = $view;
            }
            if(is_string($params['value'])){
                $params['value'] = explode(',',$params['value']);
            }
            $params['items'] = &$o->getList('*',array($key=>$params['value']),0,-1);
            
            //过滤不存在的值
            //某些数据被添加后 可能原表数据已删除，但此处value中还存在。
            $_params_items_row_key = array();
            foreach( $params['items'] as $_params_items_row ) {
                $_params_items_row_key[] = $_params_items_row[$key];
            }
            $params['value'] = implode(',',$_params_items_row_key);
        }

        if(isset($params['multiple']) && $params['multiple']){
            $render->pagedata['_input'] = $params;
            return $render->fetch('finder/input.html','tbo2o');
        }else{
            if($params['value']){
                $string = $params['items'][0][$textcol];
            }else{
                $string = $params['emptytext']?$params['emptytext']:app::get('desktop')->_('请选择...');
            }

            unset($params['app']);

            if($params['data']){
                $_params = (array)$params['data'];
                unset($params['data']);
                $params = array_merge($params,$_params);
            }

            if($params['select']=='checkbox'){
                if($params['default_id'] ) $params['domid'] = $params['default_id'];
                $params['type'] = 'checkbox';
            }else{
                $id = "handle_".$params['domid'];
                $params['type'] = 'radio';
                $getdata = '&singleselect=radio';
            }
            if(is_array($params['items'])){
                foreach($params['items'] as $key=>$item){
                    $items[$key] = $item[$params['idcol']];
                }
            }
            $params['return_url'] = urlencode($params['return_url']);
            $vars = $params;
            $vars['items'] = $items;
            $vars['items'] = array();
            $vars['value'] = array();

            unset($vars['replacehtml']);
            $object = utils::http_build_query($vars);

            $url = 'index.php?app=desktop&act=alertpages&goto='.urlencode('index.php?app=tbo2o&ctl=admin_shop_products&act=finder_common&app_id='.$app_id.'&'.$object.$getdata);

            $render->pagedata['string'] = $string;
            $render->pagedata['url'] = $url;
            $render->pagedata['return_url'] = $return_url;
            $render->pagedata['id'] = $id;
            $render->pagedata['params'] = $params;
            $render->pagedata['object'] = $object;
            $render->pagedata['callback'] = $callback;
            return $render->fetch('finder/input_radio.html','tbo2o');
        }
    }
    
    function input_storecat($params){
        $objTbo2oCatSelect = kernel::single('tbo2o_cat_select');
        if(!$params['value']){
            return '<span package="'.$package.'" class="span _x_ipt"><input type="hidden" name="'.$params['name'].'" />'.$objTbo2oCatSelect->get_cat_select(null,$params).'</span>';
        }else{
            $mdlTbo2oStoreCat = app::get('tbo2o')->model('store_cat');
            $cat_id = $params['value'];
            //从右一个元素开始组下拉框
            $ret = "";
            //如果当前层有子类 则先加上请选择下拉框
            $rs_cur_p = $mdlTbo2oStoreCat->getList("*",array("p_stc_id"=>$cat_id),0,1);
            if(!empty($rs_cur_p)){
                $params['depth'] = $rs_cur_p[0]["cat_grade"];
                $ret = '<span class="x-region-child">&nbsp;-&nbsp'.$objTbo2oCatSelect->get_cat_select($cat_id,$params).$ret.'</span>';
            }
            while($cat_id && ($rs_store_cat = $mdlTbo2oStoreCat->dump(array("cat_id"=>$cat_id),'cat_id,cat_name,p_stc_id,cat_grade'))){
                $params['depth'] = $rs_store_cat["cat_grade"]--;
                if($cat_id = $rs_store_cat['p_stc_id']){
                    //先从右开始组select元素
                    $ret = '<span class="x-region-child">&nbsp;-&nbsp'.$objTbo2oCatSelect->get_cat_select($rs_store_cat['p_stc_id'],$params,$rs_store_cat['cat_id']).$ret.'</span>';
                }else{
                    //最高层级
                    $ret = '<span package="'.$package.'" class="span _x_ipt"><input type="hidden" value="'.$params["value"].'" name="'.$params['name'].'" />'.$objTbo2oCatSelect->get_cat_select(null,$params,$rs_store_cat['cat_id']).$ret.'</span>';
                }
            }
            if(!$ret){
                $ret = '<span package="'.$package.'" class="span _x_ipt"><input type="hidden" value="" name="'.$params['name'].'" />'.$objTbo2oCatSelect->get_cat_select(null,$params,$rs_store_cat['cat_id']).'</span>';
            }
            return $ret;
        }
    }
    
}