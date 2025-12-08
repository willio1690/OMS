<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/11/23 17:17:48
 * @describe: 控制器
 * ============================
 */
class financebase_ctl_admin_expenses_rule extends desktop_controller {

    /**
     * index
     * @return mixed 返回值
     */

    public function index() {
        $actions = array();
        $params = array(
                'title'=>'费用拆分规则',
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>false,
                'use_buildin_selectrow'=>false,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_recycle'=>false,
                'finder_cols'=>'column_edit,bill_category,split_type,split_rule,split_last_modify',
                'actions'=>$actions,
                'object_method' => array(
                    'count'=>'count',   //获取数量的方法名
                    'getlist'=>'getExpensesList',   //获取列表的方法名
                )
        );
        
        $this->finder('financebase_mdl_expenses_rule', $params);
    }

    /**
     * 设置Rule
     * @param mixed $ruleId ID
     * @return mixed 返回操作结果
     */
    public function setRule($ruleId) {
        if($ruleId == 'undefined') {
            $row = app::get('financebase')->getConf('expenses.rule.undefined');
        } else {
            $row = app::get('financebase')->model('expenses_rule')->db_dump(array('rule_id'=>$ruleId));
        }
        $this->pagedata['rule'] = $row;
        $this->pagedata['split_info'] = app::get('financebase')->model('expenses_rule')->getSplitInfo();
        $this->display('admin/expenses/rule.html');
    }

    /**
     * 保存
     * @return mixed 返回操作结果
     */
    public function save() {
        $ruleId = $_POST['rule_id'];
        $upData = array(
            'split_type' => $_POST['split_type'],
            'split_rule' => $_POST['split_rule'],
            'split_last_modify' => time(),
        );
        if($ruleId == 'undefined') {
            $row = app::get('financebase')->getConf('expenses.rule.undefined');
            $row = array_merge((array) $row, $upData);
            app::get('financebase')->setConf('expenses.rule.undefined', $row);
        } else {
            app::get('financebase')->model('expenses_rule')->update($upData, array('rule_id'=>$ruleId));
        }
        $url = "index.php?app=financebase&ctl=admin_expenses_rule&act=index";
        $this->splash('success', $url);
    }
}