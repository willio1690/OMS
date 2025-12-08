<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_ctl_admin_preprocess_tbgift extends desktop_controller{
    var $name = "淘宝促销赠品管理";
    var $workground = "setting_tools";

    function index(){
       $this->finder('ome_mdl_tbgift_goods',array(
            'title' => '淘宝赠品管理',
            'actions'=>array(
                array('label'=>'赠品','href'=>'index.php?app=ome&ctl=admin_preprocess_tbgift&act=showAdd','target'=>'_blank'),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
       ));
    }

    function showAdd(){
		$this->pagedata['title'] = '添加赠品';
        $this->singlepage('admin/preprocess/tbgift/addGift.html');
    }

    function edit($gift_id,$type){
        $this->begin('index.php?app=ome&ctl=admin_preprocess_tbgift&act=index');
        if (empty($gift_id)){
            $this->end(false,'操作出错，请重新操作');
        }
        $giftObj = $this->app->model('tbgift_goods');
        $data['gift'] = $giftObj->getGiftById($gift_id);

		$this->pagedata['gift'] = $data['gift'][0];
		$this->pagedata['title'] = '编辑赠品';
		$this->pagedata['goods_type'] = $type;
        $this->singlepage("admin/preprocess/tbgift/editGift.html");
    }

    function getEditProducts($gift_id){
        if ($gift_id == ''){
            $gift_id = $_POST['p[0]'];
        }

        $giftProductObj = $this->app->model('tbgift_product');
        $rows = array();
        $items = $giftProductObj->getList('product_id,bn,name',array('goods_id'=>$gift_id),0,-1);
        foreach($items as $k => $item){
            $list[$k]['sm_id'] = $item['product_id'];
            $list[$k]['sales_material_bn'] = $item['bn'];
            $list[$k]['sales_material_name'] = $item['name'];
        }

        echo json_encode($list);
    }

    function save(){
        $this->begin('index.php?app=ome&ctl=admin_preprocess_tbgift&act=index');

        if(empty($_POST['pid'])){
            $this->end(false, '赠品对应的实际销售物料不能为空。');
        }

        if(empty($_POST['name']) || empty($_POST['gift_bn'])){
            $this->end(false,'赠品基本信息必须填写。');
        }

        $giftObj = $this->app->model('tbgift_goods');
        $giftProductObj = $this->app->model('tbgift_product');
        $goods_id = $giftObj->checkGiftByBn($_POST['gift_bn']);
        $salesMLib = kernel::single('material_sales_material');

        if(!empty($goods_id)){
            $this->end(false,'赠品编码已经存在，请重新填写。');
        }

        $data['name'] = $_POST['name'];
        $data['gift_bn'] = $_POST['gift_bn'];
        $data['goods_type'] = $_POST['goods_type'];
        $data['status'] = $_POST['status'];
        $giftObj->save($data);

        if($data['goods_id']){
            foreach($_POST['pid'] as $k=>$v){
                $salesMInfo = $salesMLib->getSalesMById('_ALL_', $v);
                if($salesMInfo['is_bind'] == 1){
                    $tmp['goods_id'] = $data['goods_id'];
                    $tmp['bn'] = $salesMInfo['sales_material_bn'];
                    $tmp['product_id'] = $v;
                    $tmp['name'] = $salesMInfo['sales_material_name'];
                    $giftProductObj->insert($tmp);
                    $tmp = null;
                }
            }

            $this->end(true,'添加成功。');
        }else{
            $this->end(false,'添加失败。');
        }

    }

    function updateGift(){
        $this->begin('index.php?app=ome&ctl=admin_preprocess_tbgift&act=index');
        if(empty($_POST['goods_id'])){
            $this->end(false,'操作出错，请重新操作');
        }
        $giftObj = $this->app->model('tbgift_goods');
        $giftProductObj = $this->app->model('tbgift_product');
        $salesMLib = kernel::single('material_sales_material');

        if(empty($_POST['pid'])){
            $this->end(false, '捆绑商品不能为空');
        }

        if(empty($_POST['name'])){
            $this->end(false,'赠品基本信息必须填写');
        }

        $data['name'] = $_POST['name'];
        $data['status'] = $_POST['status'];
        $giftObj->update($data,array('goods_id'=>$_POST['goods_id']));
        $giftProductObj->delete(array('goods_id'=>$_POST['goods_id']));

        foreach($_POST['pid'] as $k=>$v){
            $salesMInfo = $salesMLib->getSalesMById('_ALL_', $v);
            if($salesMInfo['is_bind'] == 1){
                $tmp['goods_id'] = $_POST['goods_id'];
                $tmp['bn'] = $salesMInfo['sales_material_bn'];
                $tmp['product_id'] = $v;
                $tmp['name'] = $salesMInfo['sales_material_name'];
                $giftProductObj->insert($tmp);
                $tmp = null;
            }
        }

        $this->end(true,'修改成功。');

    }

    function setStatus($gid, $status) {
        if ($status == 'true') {
            $status = 1;
        } else {
            $status = 2;
        }

        kernel::database()->query("update  sdb_ome_tbgift_goods set status='{$status}' where goods_id={$gid}");
        echo "<script>parent.MessageBox.success('设置已成功！');parent.finderGroup['{$_GET[finder_id]}'].refresh();</script>";
        exit;
    }
}

?>
