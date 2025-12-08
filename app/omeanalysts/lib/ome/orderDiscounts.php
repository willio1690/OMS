<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_ome_orderDiscounts extends eccommon_analysis_abstract implements eccommon_analysis_interface
{
    public $type_options = array(
        'display' => 'true',
    );
    public $logs_options = array(
        '1' => array(
            'name' => '订单量',
            'flag' => array(),
            'memo' => '订单量',
            'icon' => 'money.gif',
        ),
        '2' => array(
            'name' => '销售总金额',
            'flag' => array(),
            'memo' => '销售总金额',
            'icon' => 'money_delete.gif',
        ),
        '3' => array(
            'name' => '优惠总金额',
            'flag' => array(),
            'memo' => '优惠总金额',
            'icon' => 'coins.gif',
        ),
    );
    
    public $graph_options = array(
        'hidden' => true,
    );
    
    
    /**
     * 获取_type
     * @return mixed 返回结果
     */
    public function get_type()
    {
        $shoptype = ome_shop_type::get_shop_type();
        #店铺
        $shop_mdl   = app::get("ome")->model("shop");
        $shop_datas = $shop_mdl->getList("shop_id,name,shop_type");
        
        foreach ($shop_datas as $v) {
            $shop_data[$v['shop_id']] = $v['name'];
            if ($v['shop_type']) {
                $shop_types[$v['shop_type']] = $shoptype[$v['shop_type']];
            }
        }
        
        $return = array(
            'type_id[]'   => array(
                'lab'  => '店铺',
                'data' => $shop_data,
                'type' => 'select',
                'id' => 'shop_type_id',
                'multiple' => 'true',
            ),
            'shop_type' => array(
                'lab'  => '平台类型',
                'data' => $shop_types,
                'type' => 'select',
            ),
            'order_bn'=>array(
                'lab' => '订单号',
                'type' => 'text',
            )
        );
        return $return;
    }
    
    /**
     * ext_detail
     * @param mixed $detail detail
     * @return mixed 返回值
     */
    public function ext_detail(&$detail)
    {
        $filter = $this->_params;
        
        $orderDiscountsObj   = $this->app->model('ome_orderDiscounts');
        $order_count    = $orderDiscountsObj->get_order_count($filter);
        $saleMoney = $orderDiscountsObj->get_order_count($filter,1);
        $discountMoney = $orderDiscountsObj->get_orderDiscount($filter);
        
        $detail['订单量']['value'] = $order_count;
        $detail['销售总金额']['value'] = number_format($saleMoney, 2, ".", " ");
        $detail['优惠总金额']['value']  = number_format($discountMoney, 2, ".", " ");
    }
    /**
     * 设置_params
     * @param mixed $params 参数
     * @return mixed 返回操作结果
     */
    public function set_params($params)
    {
        $this->_params = $params;
        $time_from = date("Y-m-d", strtotime("-3 month"));
        $time_to = date("Y-m-d", time());
        
        $this->_params['time_from'] = ($this->_params['time_from']) ? $this->_params['time_from'] : $time_from;
        $this->_params['time_to'] = ($this->_params['time_to']) ? $this->_params['time_to'] : $time_to;
        return $this;
    }
    /**
     * headers
     * @return mixed 返回值
     */
    public function headers()
    {
        parent::headers();
        
        if ($this->type_options['display'] == 'true') {
            $this->_render->pagedata['type_display']  = 'true';
            $this->_render->pagedata['typeData']      = $this->get_type();
            $type_selected                            = array(
                'type_id[]'   => $this->_params['type_id'],
                'order_bn' => $this->_params['order_bn'],
                'shop_type' => $this->_params['shop_type'],
            );
            $this->_render->pagedata['type_selected'] = $type_selected;
        }
    }
    
    /**
     * 查找er
     * @return mixed 返回结果
     */
    public function finder()
    {
        $_GET['filter']['from'] = array(
            'type_id'   => $_POST['type_id'],
            'order_bn'  => $_POST['order_bn'],
            'shop_type' => $_POST['shop_type'],
        );
        $_extra_view = array(
            'omeanalysts' => 'ome/income/extra_view.html',
        );
        
        $this->set_extra_view($_extra_view);
        
        $params = array(
            'model'  => 'omeanalysts_mdl_ome_orderDiscounts',
            'params' => array(
                'actions' => array(
                    array(
                        'label'  => app::get('omeanalysts')->_('生成报表'),
                        'class'  => 'export',
                        'icon'   => 'add.gif',
                        'href'   => 'index.php?app=omeanalysts&ctl=ome_orderDiscounts&act=index&action=export',
                        'target' => '{width:600,height:300,title:\'生成报表\'}'
                    ),
                ),
                'title'                 => app::get('omeanalysts')->_('订单优惠明细统计<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=omeanalysts&ctl=ome_orderDiscounts&act=index&action=export\');}</script>'),
                'use_buildin_recycle'   => false,
                'use_buildin_selectrow' => false,
                'use_view_tab'          => true,
            ),
        );
        #增加报表导出权限
        $is_export = kernel::single('desktop_user')->has_permission('analysis_export');
        if (!$is_export) {
            unset($params['params']['actions']);
        }
        return $params;
    }
}