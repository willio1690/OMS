<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_ome_refundNoreturn extends eccommon_analysis_abstract implements eccommon_analysis_interface
{
    public $graph_options = array(
        'hidden' => true,
    );
    public $type_options = array(
        'display' => 'true',
    );
    
    public $detail_options = array(
        'hidden'    => true,
        'force_ext' => true,
    );
    
    public $report_type = true;
    
    function __construct(&$app)
    {
        parent::__construct($app);
    }
    
    /**
     * 获取_type
     * @return mixed 返回结果
     */
    public function get_type()
    {
        $refund_status = [
            ['type_id'=>'99','name'=>'未审核','relate_id'=>'0'],
            ['type_id'=>'1','name'=>'审核中','relate_id'=>'0'],
            ['type_id'=>'2','name'=>'已接受申请','relate_id'=>'0'],
            ['type_id'=>'3','name'=>'已拒绝','relate_id'=>'0'],
            ['type_id'=>'4','name'=>'已退款','relate_id'=>'0'],
            ['type_id'=>'5','name'=>'退款中','relate_id'=>'0'],
            ['type_id'=>'6','name'=>'退款失败','relate_id'=>'0'],
            ['type_id'=>'10','name'=>'卖家拒绝退款','relate_id'=>'0'],
        ];
    
        $return_status = [
            ['type_id'=>'99','name'=>'未入库','relate_id'=>'0'],
            ['type_id'=>'1','name'=>'待入库','relate_id'=>'0'],
            ['type_id'=>'2','name'=>'已入库','relate_id'=>'0'],
        ];
        
        $order_status = [
            ['type_id'=>'99','name'=>'未发货','relate_id'=>'0'],
            ['type_id'=>'1','name'=>'已发货','relate_id'=>'0'],
            ['type_id'=>'2','name'=>'部分发货','relate_id'=>'0'],
            ['type_id'=>'3','name'=>'部分退货','relate_id'=>'0'],
            ['type_id'=>'4','name'=>'已退货','relate_id'=>'0'],
        ];
       
        $return     = array(
            'order_bn' => array(
                'lab'  => '订单号',
                'type' => 'text',
            ),
            'refund_apply_bn' => array(
                'lab'  => '退款单号',
                'type' => 'text',
            ),
            'return_bn'=>array(
                'lab' => '退货单号',
                'type' => 'text',
            ),
            'order_status'         => array(
                'lab'  => '订单状态',
                'data' => $order_status,
                'type' => 'select',
            ),
            'refund_status'         => array(
                'lab'  => '退款状态',
                'data' => $refund_status,
                'type' => 'select',
            ),
            'return_status'       => array(
                'lab'  => '退货状态',
                'data' => $return_status,
                'type' => 'select',
            ),
        );
        
        return $return;
    }
    
    /**
     * 设置_params
     * @param mixed $params 参数
     * @return mixed 返回操作结果
     */
    public function set_params($params)
    {
        $this->_params = $params;
        
        if(isset($this->analysis_config)){
            $time_from = date("Y-m-d", time()-(date('w')?date('w')-$this->analysis_config['setting']['week']:7-$this->analysis_config['setting']['week'])*86400);
        }else{
            $time_from = date("Y-m-d", time()-(date('w')?date('w')-1:6)*86400);
        }
        $time_to = date("Y-m-d", strtotime($time_from)+86400*7-1);
        
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
                'shop_id'         => $this->_params['shop_id'],
                'order_bn'         => $this->_params['order_bn'],
                'refund_apply_bn'       => $this->_params['refund_apply_bn'],
                'return_bn'       => $this->_params['return_bn'],
                'order_status'  => $this->_params['order_status'],
                'refund_status' => $this->_params['refund_status'],
                'return_status' => $this->_params['return_status'],
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
            'model'  => 'omeanalysts_mdl_ome_refundNoreturn',
            'params' => array(
                'actions'               => array(
                    array(
                        'class'  => 'export',
                        'label'  => '生成报表',
                        'href'   => 'index.php?app=omeanalysts&ctl=ome_analysis&act=refundNoreturn&action=export',
                        'target' => '{width:600,height:300,title:\'生成报表\'}'
                    ),
                ),
                'title'                 => app::get('omeanalysts')->_('退款未退货统计【每日零点自动删除半年前已入库报表数据】'),
                'use_buildin_recycle'   => false,
                'use_buildin_selectrow' => false,
                'use_buildin_filter'    => false,
                'orderBy'               => ' return_status ASC,at_time DESC',
            ),
        );
        #增加报表导出权限
        $is_export = kernel::single('desktop_user')->has_permission('analysis_export');
        if (!$is_export) {
            unset($params['params']['actions']);
        }
    
        return $params;
    }
    
    /**
     * ext_detail
     * @param mixed $detail detail
     * @return mixed 返回值
     */
    public function ext_detail(&$detail)
    {
        $filter = $this->_params;
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
    }
    
}