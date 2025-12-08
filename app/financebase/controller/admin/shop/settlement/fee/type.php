<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 费用项控制层
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_ctl_admin_shop_settlement_fee_type extends desktop_controller
{

	// 队列列表
    /**
     * index
     * @return mixed 返回值
     */

    public function index()
	{

        $use_buildin_export    = false;
        $use_buildin_import    = false;
    

        $actions = array(
                    array('label'=>'新增','href'=>'index.php?app=financebase&ctl=admin_shop_settlement_fee_type&act=add&singlepage=false&finder_id='.$_GET['finder_id'],'target'=>'dialog::{width:600,height:300,title:\'新增收支分类\'}'),
                );
        

        $params = array(
            'title'=>'费用项设置',
            'actions' => $actions,
            // 'base_filter' => $base_filter,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_filter'=>true,
            'use_bulidin_view'=>true,
            'use_buildin_export'=> $use_buildin_export,
            'use_buildin_import'=> $use_buildin_import,
        );

        $this->finder('financebase_mdl_bill_fee_type',$params);
    }


    // 设置
    /**
     * 添加
     * @return mixed 返回值
     */
    public function add()
    {
        
        $list = array();
        $fee_item = financebase_func::getFeeItem();
        foreach ($fee_item as $v) $list[$v['platform_type']][] = $v['fee_type'];
        $shop_list = financebase_func::getShopList(financebase_func::getShopType());
        $this->pagedata['list'] = $list;
        $this->pagedata['shop_list'] = $shop_list;
        $this->pagedata['json'] = json_encode($list,JSON_UNESCAPED_UNICODE);
        $this->display("admin/bill/fee_type.html");
    }

    /**
     * edit
     * @param mixed $fee_type_id ID
     * @return mixed 返回值
     */
    public function edit($fee_type_id)
    {
        
        $list = array();
        $fee_item = financebase_func::getFeeItem();
        foreach ($fee_item as $v) $list[$v['platform_type']][] = $v['fee_type'];
        $shop_list = financebase_func::getShopList(financebase_func::getShopType());
        $this->pagedata['list'] = $list;
        $this->pagedata['shop_list'] = $shop_list;
        $this->pagedata['json'] = json_encode($list,JSON_UNESCAPED_UNICODE);

        $this->pagedata['fee_type'] = app::get('financebase')->model('bill_fee_type')->dump($fee_type_id);

        $this->singlepage("admin/bill/fee_type.html");
    }

    /**
     * 保存
     * @return mixed 返回操作结果
     */
    public function save()
    {
        $this->begin('index.php?app=financebase&ctl=admin_shop_settlement_fee_type&act=index');
        $oFeeType = app::get('financebase')->model("bill_fee_type");
        $data = array();

        $shop_array = explode('_', $_POST['shop_id']);
        $shop = app::get('ome')->model('shop')->db_dump(['shop_id'=>$shop_array[0]], 'node_type');
        $data['shop_id'] = $shop_array[0];
        $data['fee_type'] = $_POST['fee_type'];
        $data['rule_id'] = $_POST['rule_id'] ? : null;
        $data['platform_type'] = ome_shop_type::get_shop_type()[$shop['node_type']];
        $data['bill_type'] = intval($_POST['bill_type']);
        $data['createtime'] = time();
        $filter = array('shop_id'=>$data['shop_id'],'rule_id'=>$data['rule_id']);
        if(empty($data['rule_id'])) {
            $this->end(false, "具体类别需填写");
        }
        // 判断具体类别是否唯一
        if($oFeeType->getList('fee_type_id',$filter))
        {
            $this->end(false, "费用项已存在");
        }


        if($oFeeType->save($data))
        {
            $this->end(true, app::get('base')->_('保存成功'));
        }
        else
        {
            $this->end(false, app::get('base')->_('保存失败'));
        }
    }


    // 设置白名单
    /**
     * 设置WhiteList
     * @param mixed $fee_type_id ID
     * @return mixed 返回操作结果
     */
    public function setWhiteList($fee_type_id=0)
    {
        $oFeeType = app::get('financebase')->model("bill_fee_type");
        $info = $oFeeType->getList('fee_type_id,whitelist',array('fee_type_id'=>$fee_type_id),0,1);
        $this->pagedata['info'] = $info[0];
        $this->singlepage("admin/bill/fee_type_whitelist.html");
    }

    // 保存白名单
    /**
     * 保存WhiteList
     * @return mixed 返回操作结果
     */
    public function saveWhiteList()
    {
        $this->begin('index.php?app=financebase&ctl=admin_shop_settlement_fee_type&act=index');
        $oFeeType = app::get('financebase')->model("bill_fee_type");
 

        if($oFeeType->update(array('whitelist'=>$_POST['whitelist']),array('fee_type_id'=>$_POST['fee_type_id'])))
        {
            $this->end(true, app::get('base')->_('保存成功'));
        }
        else
        {
            $this->end(false, app::get('base')->_('保存失败'));
        }
    }




}
