<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
*
*/
class inventorydepth_shop_skus
{
    function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 将字符串做crc32
     *
     * @return void
     * @author
     **/
    public function crc32($val)
    {
        return sprintf('%u',crc32($val));
    }

    //获取库存管理的views
    public function get_shop_adjustment_views_arr(){
        return array(
            0 => array('label'=>$this->app->_('全部'),'addon'=>'','href'=>'','filter'=>[]),
            1 => array('label'=>$this->app->_('已关联'),'addon'=>'','href'=>'','filter'=>array('mapping'=>1)),
            2 => array('label'=>$this->app->_('未关联'),'addon'=>'','href'=>'','filter'=>array('mapping'=>0,'filter_sql'=>'{table}shop_product_bn is not null AND {table}shop_product_bn != ""','shop_product_bn'=>'exceptrepeat')),
            3 => array('label'=>$this->app->_('货号为空'),'addon'=>'','href'=>'','filter'=>array('filter_sql'=>'({table}shop_product_bn is null OR {table}shop_product_bn="")')),
            4 => array('label'=>$this->app->_('货号重复'),'addon'=>'','href'=>'','filter'=>array('shop_product_bn'=>'repeat')),
        );
    }

    //根据type获取导出标题
    public function get_title_arr($type){
        switch($type){
            case "release_stock":
                $arr = array('*:店铺编码','*:货品编号','*:货品名称','*:发布库存');
                break;
        }
        foreach ($arr as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }

    //根据type获取导出字段 修改字段后get_release_stock_export_content方法和get_shop_product_bn_export_content方法都要做相应的修改
    public function get_field_arr($type){
        switch($type){
            case "release_stock":
                $arr = array('shop_bn','shop_product_bn','shop_title','release_stock');
                break;
        }
        return $arr;
    }

    //根据条件获取导出发布库存模板数据
    public function get_release_stock_export_content($filter){
        $return_arr = array();
        $limit = 100;
        $mdl_in_shop_shop_adjustment = app::get('inventorydepth')->model('shop_adjustment');
        $lib_charset = kernel::single('base_charset');
        if(isset($filter["shop_id"])){ //全选过来的
            $offset = 0;
            do{
                $start_pos = $offset*$limit;
                $rs_data = $mdl_in_shop_shop_adjustment->getlist("*",$filter,$start_pos,$limit);
                if(empty($rs_data)){
                    break;
                }
                foreach ($rs_data as $var_rd){
                    $return_arr[] = array(
                            "shop_bn" => $lib_charset->utf2local($var_rd["shop_bn"]),
                            "shop_product_bn" => $lib_charset->utf2local($var_rd["shop_product_bn"]),
                            "shop_title" => $lib_charset->utf2local($var_rd["shop_title"]),
                            "release_stock" => $lib_charset->utf2local($var_rd["release_stock"]),
                    );
                }
                $offset++;
            }while(!empty($rs_data));
        }elseif(isset($filter["id"])){ //勾选过来的
            $arr_filter = array_chunk($filter["id"],$limit);
            foreach($arr_filter as $var_af){
                $rs_data = $mdl_in_shop_shop_adjustment->getlist("*",array("id"=>$var_af));
                foreach ($rs_data as $var_rd){
                    $return_arr[] = array(
                            "shop_bn" => $lib_charset->utf2local($var_rd["shop_bn"]),
                            "shop_product_bn" => $lib_charset->utf2local($var_rd["shop_product_bn"]),
                            "shop_title" => $lib_charset->utf2local($var_rd["shop_title"]),
                            "release_stock" => $lib_charset->utf2local($var_rd["release_stock"]),
                    );
                }
            }
        }
        return $return_arr;
    }

}
