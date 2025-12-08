<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_refuse_reason_statistics extends eccommon_analysis_abstract implements eccommon_analysis_interface{

    public $report_type ='false';
    protected $_title = '门店拒单原因分析';
    public $detail_options = array(
      'hidden' => true,
      'force_ext' => false,
    );

    public $graph_options = array(
        'hidden' => true,
    );

    public $type_options = array(
        'display' => 'true',
    );

    /**
     * 获取_type
     * @return mixed 返回结果
     */
    public function get_type(){
        $lab = '门店';
        $data    = array();
        
        /***
        $storeObj = app::get('o2o')->model('store');
        $tmp = $storeObj->getList("store_bn,name", array(), 0, -1);

        foreach($tmp as $k=>$val){
            $data[$k]['type_id'] = $val['store_bn'];
            $data[$k]['name'] = $val['name'];
        }
        ***/

        $return = array(
            'lab' => $lab,
            'data' => $data,
        );
        return $return;
    }

    function __construct(&$app)
    {
        parent::__construct($app);
        $this->_render = kernel::single('desktop_controller');

        for($i=0;$i<=5;$i++){
            if ($i == 1) continue;
            $val = $i+1;
            $this->_render->pagedata['time_shortcut'][$i] = $val;
        }
    }

    /**
     * 重写设置报表统计的参数
     * @param array 需要设置的参数
     * @return object 本类对象
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
     * display
     * @param mixed $fetch fetch
     * @return mixed 返回值
     */
    public function display($fetch=false)
    {
        parent::headers();
        parent::detail();
        parent::graph();
        $this->rank();
        $content = $this->_render->page('admin/analysis/graphic_extra_view.html', 'o2o');
        if($fetch){
            return $content;
        }else{
            echo $content;
        }

    }
    /**
     * rank
     * @return mixed 返回值
     */
    public function rank() {
        $filter = $this->_params;
        $filter['time_from'] = isset($filter['time_from'])?$filter['time_from']:'';
        $filter['time_to'] = isset($filter['time_to'])?$filter['time_to']:'';

        $render = kernel::single('base_render');

        $render->pagedata['timefrom'] = $filter['time_from'];
        $render->pagedata['timeto'] = $filter['time_to'];
        $render->pagedata['type_id'] = $filter['type_id'];
        $html = $render->fetch('admin/analysis/refusereason.html','o2o');

        $this->_render->pagedata['rank_html'] = $html;
    }
}
