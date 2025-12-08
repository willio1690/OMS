<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_ome_salestatistics extends eccommon_analysis_abstract implements eccommon_analysis_interface
{
    
    public $report_type = 'true';
    public $type_options = array(
        'display' => 'true',
    );
    public $detail_options = array(
        'hidden' => false,
    );
    public $graph_options = array(
        'hidden' => true,
    );
    public $logs_options = array(
        
        '1'  => array(
            'name'  => '下单量总量',
            'value' => 0,
            'memo'  => '下单量总量',
            'col'   => '1',
            'icon'  => 'money.gif',
        ),
        '2'  => array(
            'name'  => '下单总金额',
            'value' => 0,
            'memo'  => '下单总金额',
            'col'   => '2',
            'icon'  => 'coins.gif',
        ),
        '3'  => array(
            'name'  => '发货总量',
            'value' => 0,
            'memo'  => '发货总量',
            'col'   => '3',
            'icon'  => 'money.gif',
        ),
        '4'  => array(
            'name'  => '发货总金额',
            'value' => 0,
            'memo'  => '发货总金额',
            'col'   => '4',
            'icon'  => 'coins.gif',
        ),
        '5'  => array(
            'name'  => '发货退货总量',
            'value' => 0,
            'memo'  => '发货退货总量',
            'col'   => '5',
            'icon'  => 'money.gif',
        ),
        '6'  => array(
            'name'  => '发货退货总金额',
            'value' => 0,
            'memo'  => '发货退货总金额',
            'col'   => '7',
            'icon'  => 'coins.gif',
        ),
        '7'  => array(
            'name'  => '退货量总量',
            'value' => 0,
            'memo'  => '退货量总量',
            'col'   => '6',
            'icon'  => 'money.gif',
        ),
        '8'  => array(
            'name'  => '退货总金额',
            'value' => 0,
            'memo'  => '退货总金额',
            'col'   => '8',
            'icon'  => 'coins.gif',
        ),
        '9'  => array(
            'name'  => '当日数量退货率',
            'value' => 0,
            'memo'  => '当日数量退货率',
            'col'   => '6',
            'icon'  => 'money.gif',
        ),
        '10' => array(
            'name'  => '当日金额退货率',
            'value' => 0,
            'memo'  => '当日金额退货率',
            'col'   => '8',
            'icon'  => 'coins.gif',
        ),
    );
    
    function __construct(&$app)
    {
        parent::__construct($app);
    }
    
    /**
     * 获取_type2
     * @return mixed 返回结果
     */
    public function get_type2()
    {
        $lab     = '店铺';
        $typeObj = $this->app->model('ome_type');
        $data    = $typeObj->get_shop();
        $return  = array(
            'lab'  => $lab,
            'data' => $data,
        );
        return $return;
    }
    
    /**
     * 获取_type
     * @return mixed 返回结果
     */
    public function get_type()
    {
        $typeObj   = $this->app->model('ome_type');
        $shop_data = $typeObj->get_shop();
        
        $return = array(
            'shop_id[]' => array(
                'lab'  => '店铺',
                'data' => $shop_data,
                'id' => 'shop_type_id',
                'multiple' => 'true',
                'type' => 'select',
            ),
        );
        
        return $return;
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
                'shop_id[]' => $this->_params['shop_id'],
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
        
        $_extra_view = array(
            'omeanalysts' => 'ome/extra_view.html',
        );
        
        $this->set_extra_view($_extra_view);
        
        $params = array(
            'model'  => 'omeanalysts_mdl_ome_salestatistics',
            'params' => array(
                'actions'               => array(
                    array(
                        'class'  => 'export',
                        'label'  => '生成报表',
                        'href'   => 'index.php?app=omeanalysts&ctl=ome_salestatistics&act=index&action=export',
                        'target' => '{width:400,height:170,title:\'生成报表\'}'
                    ),
                ),
                'title'                 => app::get('omeanalysts')->_('退货率统计<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=omeanalysts&ctl=ome_salestatistics&act=index&action=export\');}</script>'),
                'use_buildin_recycle'   => false,
                'use_buildin_selectrow' => false,
                'use_buildin_filter'    => false,
                //'use_buildin_selectrow'=>false,
                //'use_buildin_filter'=>true,
            ),
        );
        
        return $params;
    }
    
    /**
     * ext_detail
     * @param mixed $detail detail
     * @return mixed 返回值
     */
    public function ext_detail(&$detail)
    {
        
        $filter                     = $this->_params;
        $ome_aftersale_Mdl          = $this->app->model('ome_salestatistics');
        $data                       = $ome_aftersale_Mdl->get_count($filter);
        $detail['下单量总量']['value']   = $data['total_order_num'] ? $data['total_order_num'] : 0;
        $detail['下单总金额']['value']   = "￥" . number_format($data['total_order_amount'], 2, ".", " ");
        $detail['发货总量']['value']    = $data['total_delivery_num'] ? $data['total_delivery_num'] : 0;
        $detail['发货总金额']['value']   = "￥" . number_format($data['total_delivery_amount'], 2, ".", " ");
        $detail['发货退货总量']['value']  = $data['total_delivery_return_num'] ? $data['total_delivery_return_num'] : 0;
        $detail['发货退货总金额']['value'] = "￥" . number_format($data['total_delivery_return_amount'], 2, ".", " ");
        $detail['退货量总量']['value']   = $data['total_return_num'] ? $data['total_return_num'] : 0;
        $detail['退货总金额']['value']   = "￥" . number_format($data['total_return_amount'], 2, ".", " ");
        $detail['当日数量退货率']['value'] = $data['total_return_num_rate'] ? $data['total_return_num_rate'] : '0.00%';
        $detail['当日金额退货率']['value'] = $data['total_return_amount_rate'] ? $data['total_return_amount_rate'] : '0.00%';
    }
    
    /**
     * detail
     * @return mixed 返回值
     */
    public function detail()
    {
        if ($this->detail_options['hidden'] == true) {
            $this->_render->pagedata['detail_hidden'] = 1;
            return false;
        }
        $detail = array();
        
        foreach ($this->logs_options as $target => $option) {
            $detail[$option['name']]['value'] = ($tmp[$target]) ? $tmp[$target] : 0;
            $detail[$option['name']]['memo']  = $this->logs_options[$target]['memo'];
            $detail[$option['name']]['icon']  = $this->logs_options[$target]['icon'];
            $detail[$option['name']]['col']   = $target;
        }
        
        if (method_exists($this, 'ext_detail')) {
            $this->ext_detail($detail);
        }
        foreach ($detail as $key => $val) {
            $name                 = $this->app->_($key);
            $data[$name]['value'] = $val['value'];
            $data[$name]['memo']  = $this->app->_($val['memo']);
            $data[$name]['icon']  = $val['icon'];
            $data[$name]['col']   = $val['col'];
        }
        $this->_render->pagedata['detail'] = $data;
        return true;
    }//End Function
    
    
}
