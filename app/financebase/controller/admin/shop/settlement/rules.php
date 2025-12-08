<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 对账规则控制层
 *
 * @author 334395174@qq.com
 * @version 0.1
 */
class financebase_ctl_admin_shop_settlement_rules extends desktop_controller
{

	// 违禁词列表
    /**
     * index
     * @return mixed 返回值
     */

    public function index()
	{

        $use_buildin_export    = false;
        $use_buildin_import    = false;
        

        
        $base_filter = array();
        $actions = array(
                    array('label'=>'新增','href'=>'index.php?app=financebase&ctl=admin_shop_settlement_rules&act=setCategory&p[0]=0&singlepage=false&finder_id='.$_GET['finder_id'],'target'=>'dialog::{width:550,height:400,resizeable:false,title:\'新增收支分类\'}'),
                );



        $params = array(
            'title'=>'收支分类设置',
            'actions' => $actions,
            'base_filter' => $base_filter,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_filter'=>false,
            'use_buildin_export'=> $use_buildin_export,
            'use_buildin_import'=> $use_buildin_import,
            'orderBy'=>'ordernum',
        );

        $this->finder('financebase_mdl_bill_category_rules',$params);
    }


    // 设置具体类别名称
    /**
     * 设置Category
     * @param mixed $rule_id ID
     * @return mixed 返回操作结果
     */
    public function setCategory($rule_id=0)
    {
        $rule_info = array('rule_id'=>$rule_id);
        $oRules = app::get('financebase')->model("bill_category_rules");
        if($rule_id)
        {
            $rule_info=$oRules->getRow('rule_id,bill_category,ordernum',array('rule_id'=>$rule_id));
        }else{
            $row = $oRules->getRow('max(ordernum) as max',array());
            $rule_info['ordernum'] = $row ? $row['max'] + 1 : 1;
        }

        $this->pagedata['rule_info'] = $rule_info;
        $this->display("admin/bill/category.html");
    }

    // 保存具体类别名称
    /**
     * 保存Category
     * @return mixed 返回操作结果
     */
    public function saveCategory()
    {
        $this->begin('index.php?app=financebase&ctl=admin_shop_settlement_rules&act=index');
        $oRules = app::get('financebase')->model("bill_category_rules");
        $data = array();

        $data['rule_id'] = intval($_POST['rule_id']);
        if(!$data['rule_id'])
        {
            $data['create_time'] = time();
        }

        $data['ordernum'] = intval($_POST['ordernum']);
        $data['bill_category'] = htmlspecialchars($_POST['bill_category']);

        // 判断具体类别是否唯一
        if($oRules->isExist(array('bill_category'=>$data['bill_category']),$data['rule_id']))
        {
            $this->end(false, "具体类别有重名");
        }

        // 判断优先级是否唯一
        if($oRules->isExist(array('ordernum'=>$data['ordernum']),$data['rule_id']))
        {
            $this->end(false, app::get('base')->_('优先级已存在'));
        }

        if($oRules->save($data))
        {
            $this->end(true, app::get('base')->_('保存成功'));
        }
        else
        {
            $this->end(false, app::get('base')->_('保存失败'));
        }
    }


    /**
     * 添加 OR 编辑 对账规则页面
     * @param  integer    $rule_id       规则ID
     * @param  string     $platform_type 类型
     * 如果rule_id为0时显示添加页面，否则显示编辑页面
     */
    public function setRule($rule_id=0,$platform_type='alipay')
    {
        // $rule_info = array('rule_id'=>$rule_id);
        $oRules = app::get('financebase')->model("bill_category_rules");
        $rule_info=$oRules->getRow('rule_id,business_type,bill_category,rule_content',array('rule_id'=>$rule_id));

        $rule_content = json_decode($rule_info['rule_content'],1);


        $rule_info['rule_content'] = isset($rule_content[$platform_type]) ? $rule_content[$platform_type] : array();
        // if($rule_id)
        // {
        //     $rule_info=$oRules->getRow('rule_id,bill_category,rule_content,ordernum',array('rule_id'=>$rule_id));

        //     $rule_info['rule_content'] = json_decode($rule_info['rule_content'],1);
        // }else{
        //     $row = $oRules->getRow('max(ordernum) as max',array());
        //     $rule_info['ordernum'] = $row ? $row['max'] + 1 : 1;
        //     $rule_info['rule_content'] = array( array( array('rule_type'=>'trade_type','rule_op'=>'and','rule_filter'=>'contain','rule_value'=>'') ) );
        // }

        $oFunc = kernel::single('financebase_func');
        $platform = $oFunc->getShopPlatform();

        $this->pagedata['title'] = $platform[$platform_type]."规则设置";
        $this->pagedata['platform_type'] = $platform_type;
        $this->pagedata['rule_num'] = count($rule_info['rule_content']);
        $this->pagedata['rule_info'] = $rule_info;
        $this->singlepage("admin/bill/rules.html");
    }

    // 保存规则
    /**
     * 保存Rule
     * @return mixed 返回操作结果
     */
    public function saveRule()
    {

        $this->begin('index.php?app=financebase&ctl=admin_shop_settlement_rules&act=index');
        $oRules = app::get('financebase')->model("bill_category_rules");

        $rule_content = array();

        if(!$_POST['rule_id'])
        {
            $this->end(false, app::get('base')->_('参数错误'));
        }

        $i = 0;
        foreach ($_POST['rule_op'] as $k => $v) {
            foreach ($v as $k2 => $v2) {
                $rule_content[$i][$k2] = ['rule_type'=>$_POST['rule_type'][$k][$k2],'rule_value'=>$_POST['rule_value'][$k][$k2],'rule_op'=>$v2,'rule_filter'=>$_POST['rule_filter'][$k][$k2]];
            }
            $i++;
        }

        if(!$rule_content)
        {
            $this->end(false, app::get('base')->_('规则不能为空'));
        }

        $rule_info=$oRules->getRow('rule_id,rule_content',array('rule_id'=>$_POST['rule_id']));

        $rule_info['rule_content'] = json_decode($rule_info['rule_content'],1);

        $rule_info['rule_content'][$_POST['platform_type']] =  $rule_content;

        $rule_info['rule_content'] = json_encode($rule_info['rule_content'],JSON_UNESCAPED_UNICODE);

        $rule_info['business_type'] = (string) $_POST['business_type'];
        // // 判断具体类别是否唯一
        // if($oRules->isExist(array('bill_category'=>$data['bill_category']),$data['rule_id']))
        // {
        //     $this->end(false, json_encode($filter));
        // }

        // // 判断优先级是否唯一
        // if($oRules->isExist(array('ordernum'=>$data['ordernum']),$data['rule_id']))
        // {
        //     $this->end(false, app::get('base')->_('优先级已存在'));
        // }

        if($oRules->save($rule_info))
        {
            kernel::single('financebase_data_bill_category_rules')->setRules();
            $this->end(true, app::get('base')->_('保存成功'));
        }
        else
        {
            $this->end(false, app::get('base')->_('保存失败'));
        }
    }


}