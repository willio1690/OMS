<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class material_basic_material_cat
{
    /**
     * 得到地区信息 - parent region id， 层级，下级地区
     * @params string region id
     * @return array 指定信息的数组
     */
    public function getCatById($cat_id = '')
    {
        $mdl = app::get('material')->model('basic_material_cat');

        if ($cat_id) {
            $aTemp = $mdl->getList('cat_id,parent_id,cat_name,p_order,cat_path,child_count', array('parent_id' => $cat_id), 0, -1, 'p_order ASC,cat_id ASC');
        } else {
            $aTemp = $mdl->getList('cat_id,parent_id,cat_name,p_order,cat_path,child_count', array('parent_id' => '0'), 0, -1, 'p_order ASC,cat_id ASC');
        }

        if (is_array($aTemp) && count($aTemp) > 0) {
            foreach ($aTemp as $key => $val) {
                $aTemp[$key]['parent_id']   = intval($val['parent_id']);
                $aTemp[$key]['step']        = intval(substr_count($val['cat_path'], ',')) - 1;
                $aTemp[$key]['child_count'] = $val['child_count'];
            }
        }

        return $aTemp;
    }



    /**
     * 多级分类保存
     */
    public function multiLevelSave($categorys,$level = 1,$parentId = 0)
    {
        $isCreate = false;
        $rs = [
            'rsp' => 'fail'
        ];
        try {
            if(!$categorys){
                throw new \Exception("分类信息为空请勿传输该字段");
            }
            
            $categoryList = explode("/", $categorys);
            $currentCategory = array_shift($categoryList);
          
            list($catCode,$catName) = explode(":", $currentCategory);
            if(!$catName){

                $catName = $currentCategory;
            }
            /*if(!$catCode){
                $msg = sprintf("第%s级,分类编码不能为空", $level);
                throw new \Exception($msg);
            }*/

            if (!$catName) {
                $msg = sprintf("第%s级,分类名称不能为空", $level);
                throw new \Exception($msg);
            }

            $catMdl = app::get('material')->model('basic_material_cat');
            $filter = array('cat_code' => $catCode,'parent_id' => $parentId);
            $category = $catMdl->dump($filter);
           
            if(!$category){
                $category = array(
                    'cat_code' => $catCode,
                    'cat_name' => $catName,
                    'parent_id' => $parentId,
                    'p_order' => 0,
                    'min_price' => 0,
                );
                $catMdl->save($category);
                $isCreate = true;
            }
            
            // 没消化完,则继续调用
            if(!empty($categoryList)){
                $tmpCategorys = implode('/', $categoryList);
                $level++;
                $parentId = $category['cat_id'];
                $rs = $this->multiLevelSave($tmpCategorys, $level, $parentId);
            }else{
                if($isCreate){
                    // 强制刷新 因递归调用catMapTree 未注销
                    $catMdl->catMapTree = null;
                    $catMdl->cat2json();
                }
                $rs['rsp'] = 'succ';
                $rs['data']['cat_id'] = $category['cat_id'];
                $rs['data']['cat_path'] = $category['cat_path'];
            }
            
            return $rs;
        } catch (Exception $e) {
            $message = $e->getMessage();
            $rs['msg'] = $message;
            return $rs;
        }
    }
}
