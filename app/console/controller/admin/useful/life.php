<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: yaokangming
 * Date: 2019/5/6
 * Time: 14:13
 */
class console_ctl_admin_useful_life extends desktop_controller
{
    var $name = "仓库库存查看";
    var $workground = "storage_center";
    /**
     * index
     * @return mixed 返回值
     */

    public function index(){
        $params = array(
            'title'=>'有效期商品列表',
            'base_filter' => array(),
            'actions' => array(),
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>true,
            'use_buildin_filter'=>true,
            'use_buildin_selectrow'=>true,
            'use_view_tab' => false,
            'orderBy' => 'life_id desc'
        );
        $this->finder('console_mdl_useful_life', $params);
    }

    /**
     * 获取LifeItems
     * @return mixed 返回结果
     */
    public function getLifeItems() {
        $purchaseCode = trim($_POST['purchase_code']);
        $branchId = trim($_POST['branch_id']);
        $productId = trim($_POST['product_id']);
        $modelUsefulLife = app::get('ome')->model('useful_life');
        $rows = $modelUsefulLife->getList('*',
            array('purchase_code'=>$purchaseCode, 'branch_id'=>$branchId, 'product_id'=>$productId));
        if(empty($rows)) {
            echo json_encode(array('rsp'=>'fail', 'msg'=>'没有该有效期批次号'));
            exit();
        }
        foreach ($rows as $k => $val) {
            $val['product_time'] = date('Y-m-d', $val['product_time']);
            $val['expire_time'] = date('Y-m-d', $val['expire_time']);
            $rows[$k] = $val;
        }
        echo json_encode(array('rsp'=>'succ', 'data'=>$rows));
    }
}