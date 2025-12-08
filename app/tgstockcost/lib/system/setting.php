<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class tgstockcost_system_setting
{

    private $tgstockcost_cost = array(
        '1' => '不计成本',
        '2' => '固定成本法',
        '3' => '平均成本法',
        '4' => '先进先出法',
    );

    private $tgstockcost_get_value_type = array(
        '1' => '取货品的固定成本',
        '2' => '取货品的单位平均成本',
        '3' => '取货品的最近一次出入库成本',
        '4' => '取0',
    );

    /**
     * 退货成本可取值
     * 
     * @var string
     * */
    private $_returnCostOptions = [
        '1' => '取销售出库成本',
        '2' => '取单位平均成本',
    ];
    
    private $branch_unit_cost = array(
        '1'=>'单仓库成本计算',
        '2'=>'组合仓库成本计算',
    );

        /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 获取_tgstockcost_cost
     * @return mixed 返回结果
     */
    public function get_tgstockcost_cost()
    {
        return $this->tgstockcost_cost;
    }

    /**
     * 获取_tgstockcost_get_value_type
     * @return mixed 返回结果
     */
    public function get_tgstockcost_get_value_type()
    {
        return $this->tgstockcost_get_value_type;
    }

    /**
     * 获取_setting_value
     * @param mixed $key key
     * @return mixed 返回结果
     */
    public function get_setting_value($key = '')
    {
        $setting = array();

        foreach ($this->all_settings() as $k => $v) {
            $setting[$v] = app::get('ome')->getConf($v);
        }

        return $key ? $setting[$key] : $setting;
    }

    /**
     * 库存成本配置
     * 
     * @return void
     * @author
     * */
    public function get_setting_tab()
    {
        $settingTabs = array(
            array(
                'name'                  => '成本设置',
                'file_name'             => 'admin/system/setting/tab_stockcost.html',
                'app'                   => 'tgstockcost',
                // 'url'       => 'index.php?app=tgstockcost&ctl=setting&act=settingpage'
                'hidden_default_button' => true,
                'order'                 => 50,
            ),
        );

        return $settingTabs;
    }

    /**
     * 获取成本配置项
     * 
     * @return void
     * @author
     * */
    public function getCostSetting()
    {
        $costSet = [
            'options' => $this->tgstockcost_cost,
            'value'   => $this->get_setting_value('tgstockcost.cost'),
        ];

        $getValueTypeSet = [
            'options' => $this->tgstockcost_get_value_type,
            'value'   => $this->get_setting_value('tgstockcost.get_value_type'),
        ];

        $returnCostSet = [
            'options' => $this->_returnCostOptions,
            'value'   => $this->get_setting_value('tgstockcost.return_cost'),
        ];
        
        $branchCostSet = [
            'options' => $this->branch_unit_cost,
            'value'   => $this->get_setting_value('tgstockcost.branch_cost'),
        ];

        return ['cost' => $costSet, 'get_value_type' => $getValueTypeSet, 'return_cost' => $returnCostSet, 'branch_cost'=>$branchCostSet];
    }

        /**
     * 获取_pagedata
     * @param mixed $controller controller
     * @return mixed 返回结果
     */
    public function get_pagedata(&$controller)
    {
        $costSetting = $this->getCostSetting();

        $controller->pagedata['tgstockcost']['setting'] = $costSetting;

        $controller->pagedata['tgstockcost']['install_time'] = app::get('ome')->getConf('tgstockcost_install_time');

        $oplogModel    = app::get('tgstockcost')->model('operation');
        $operationList = $oplogModel->getList('*', array(), 0, 10, 'operation_id desc');

        if ($operationList) {
            foreach ((array) $operationList as $key => $value) {

                $operationList[$key]['tgstockcost_cost']           = $costSetting['cost']['options'][$value['tgstockcost_cost']];
                $operationList[$key]['tgstockcost_get_value_type'] = $costSetting['get_value_type']['options'][$value['tgstockcost_get_value_type']];
                $operationList[$key]['tgstockcost_return_cost']    = $costSetting['return_cost']['options'][$value['tgstockcost_return_cost']];
                $operationList[$key]['tgstockcost_branch_cost']    = $costSetting['branch_cost']['options'][$value['tgstockcost_branch_cost']];

                $operationList[$key]['type'] = $value['type'] == '2' ? '成本设置期初' : '成本设置变更';
            }

            $controller->pagedata['tgstockcost']['operations'] = $operationList;
        }
    }

    /**
     * view
     * @return mixed 返回值
     */
    public function view()
    {
        $render                                       = $this->app->render();
        $render->pagedata['stockcost_cost']           = app::get("ome")->getConf("tgstockcost.cost");
        $render->pagedata['stockcost_get_value_type'] = app::get("ome")->getConf("tgstockcost.get_value_type");
        return $render->fetch("admin/system/system_setting.html");
    }

    /**
     * all_settings
     * @return mixed 返回值
     */
    public function all_settings()
    {

        $all_settings = array(
            'tgstockcost.cost',
            'tgstockcost.get_value_type',
            'tgstockcost.installed',
            'tgstockcost_install_time',
            'tgstockcost.return_cost',
            'tgstockcost.branch_cost',
        );
        return $all_settings;
    }

    /**
     * undocumented function
     * 
     * @return void
     * @author
     * */
    public function get_setting_data()
    {
        $setData = array();

        $all_settings = $this->all_settings();

        foreach ($all_settings as $set) {
            $key           = str_replace('.', '_', $set);
            $setData[$key] = app::get('ome')->getConf($set);
        }

        return $setData;
    }

    public function setting_save($setting, &$msg = "")
    {
        $costSetting = $this->getCostSetting();

        if ($setting['tgstockcost_cost'] == $costSetting['cost']['value']
            && $setting['tgstockcost_get_value_type'] == $costSetting['get_value_type']['value']
            && $setting['tgstockcost_return_cost'] == $costSetting['return_cost']['value']
            && $setting['tgstockcost_branch_cost'] == $costSetting['branch_cost']['value']
        ) {
            $msg = '成本设置无变化，不需要修改';
            return false;
        }

        if (!$this->checkCost()) {
            $msg = '商品成本价未设置,请先去商品管理中设置成本价';
            return false;
        }

        if ($setting['tgstockcost_cost'] == '1' && $setting['tgstockcost_get_value_type']) {
            $msg = '不计成本发不能设置盘点/调账成本取值';
            return false;
        } elseif ($setting['tgstockcost_cost'] != '1' && !$setting['tgstockcost_get_value_type']) {
            $msg = '请设置盘点/调账成本取值';
            return false;
        }

        $oplogModel = app::get('tgstockcost')->model('operation');
        $lastoplog  = $oplogModel->getList('install_time', array(), 0, 1, 'install_time desc');
        if ($lastoplog) {
            if (time() - 86400 < $lastoplog[0]['install_time']) {
                $msg = '不允许修改：这一之内不允许重复修改';
                return false;
            }
        }

        // 保存设置
        app::get("ome")->setConf("tgstockcost.cost", $setting['tgstockcost_cost']);
        app::get("ome")->setConf("tgstockcost.get_value_type", $setting['tgstockcost_get_value_type']);
        app::get("ome")->setConf("tgstockcost.return_cost", $setting['tgstockcost_return_cost']);
        app::get("ome")->setConf("tgstockcost.branch_cost", $setting['tgstockcost_branch_cost']);

        if (!app::get('ome')->getConf('tgstockcost_install_time')) {
            app::get('ome')->setConf('tgstockcost_install_time', time());
        }

        // 刷新数据
        kernel::single("tgstockcost_instance_router")->create_queue();

        // 库存成本切换日志
        $now = time();
        $oplogModel->update(array('status' => '0', 'end_time' => $now), array('status' => '1'));

        $_tgcost                 = $setting;
        $_tgcost['install_time'] = $now;
        $_tgcost['op_id']        = kernel::single('desktop_user')->get_id();
        $_tgcost['op_name']      = kernel::single('desktop_user')->get_name();
        $_tgcost['operate_time'] = $now;
        $_tgcost['status']       = '1'; //当前成本法
        $_tgcost['type']         = '1';
        $oplogModel->save($_tgcost);

        return true;
    }

        /**
     * 检查Cost
     * @return mixed 返回验证结果
     */
    public function checkCost()
    {
        $sql = "SELECT SUM(cost) AS sum_cost FROM sdb_material_basic_material AS a
                   LEFT JOIN sdb_material_basic_material_ext AS b ON a.bm_id=b.bm_id
                   WHERE a.visibled=1";
        $rows = kernel::database()->selectrow($sql);

        if (intval($rows['sum_cost']) == 0) {
            return false;
        } else {
            return true;
        }

    }

}
