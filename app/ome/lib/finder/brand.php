<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_finder_brand{

    var $column_edit = '品牌操作';
    /**
     * column_edit
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_edit($row){

        $finder_id = $_GET['_finder']['finder_id'];
        return '<a href="index.php?app=ome&ctl=admin_brand&act=edit&brand_id='.$row ['brand_id'].'&finder_id='.$finder_id.'" target="dialog::{width:600,height:300,title:\'编辑物料品牌\'}">编辑</a>';
    }


    var $detail_info = '品牌信息';
    function detail_info($brand_id){

        $render =  app::get('ome')->render();
        $render->path[] = array('text'=>app::get('base')->_('商品品牌编辑'));
        $objBrand = &$render->app->model('brand');
        $render->pagedata['brandInfo'] = $objBrand->dump($brand_id);

        if(empty($render->pagedata['brandInfo']['brand_url'])) $render->pagedata['brandInfo']['brand_url'] = 'http://';

        $brand_type_id = $objBrand->getBrandTypes($brand_id);
        foreach($brand_type_id as $row){
            $aType[$row['type_id']] = $row;
        }

        $render->pagedata['seo']=$seo_info;
        $render->pagedata['brandInfo']['type'] = $aType;
        $render->pagedata['type'] = $objBrand->getDefinedType();//所有的商品类型
        $objGtype = &$render->app->model('goods_type');
        $render->pagedata['gtype']['status'] = $objGtype->checkDefined();

        return $render->fetch('admin/goods/brand/detail.html');
    }

}
