<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_sales_delivery_order_item extends eccommon_analysis_abstract implements eccommon_analysis_interface{
    public $detail_options = array(
        'hidden' => false,
    );

    public $graph_options = array(
        'hidden' => true,
    );

	public $type_options = array(
        'display' => 'true',
    );

    public $analysts_options = array(
        'display' => false,
        'description' => '毛利 = 销售金额 - 商品成本 - 物流成本 &nbsp;&nbsp;
                          毛利率 = 毛利 / 销售金额',
    );

    public $logs_options = array(
        '1' => array(
            'name' => '订单总数',
            'flag' => array(),
            'memo' => '订单总数',
            'icon' => 'money.gif',
		    'col'  => '1',
        ),
        '2' => array(
            'name' => '货品总数',
            'flag' => array(),
            'memo' => '货品总数',
            'icon' => 'money.gif',
		    'col'  => '2',
        ),
        // '3' => array(
        //     'name' => '商品总额',
        //     'flag' => array(),
        //     'memo' => '商品总额',
        //     'icon' => 'coins.gif',
		//     'col'  => '3',
        // ),
        // '4' => array(
        //     'name' => '优惠总额',
        //     'flag' => array(),
        //     'memo' => '优惠总额',
        //     'icon' => 'coins.gif',
		//     'col'  => '4',
        // ),
        '5' => array(
            'name' => '销售总金额',
            'flag' => array(),
            'memo' => '销售总金额',
            'icon' => 'coins.gif',
		    'col'  => '5',
        ),
        // '6' => array(
        //     'name' => '商品总成本',
        //     'flag' => array(),
        //     'memo' => '商品总成本',
        //     'icon' => 'coins.gif',
		//     'col'  => '6',
        // ),
    );

    /**
     * 获取_type
     * @return mixed 返回结果
     */
    public function get_type(){
        $typeObj = $this->app->model('ome_type');
        $shop_data = $typeObj->get_shop();
        $branch_data = $typeObj->get_branch();

        $shop_types = $typeObj->getShopType();

        array_unshift($branch_data, array('type_id'=>'','name'=>'全部'));
        $return = array(
          'shop_id[]'=>array(
            'lab' => '店铺',
            'data' => $shop_data,
            'id' => 'shop_type_id',
            'multiple' => 'true',
          ),
          'shop_type'=>array(
            'lab'=>'平台类型',
            'data'=>$shop_types,
            // 'type' => 'select',
          ),
          'branch_id'=>array(
            'lab' => '仓库',
            'data' => $branch_data,
            'id' => 'shop_branch_id',
            'multiple' => 'true',
          ),
        );

        return $return;
    }

    /**
     * headers
     * @return mixed 返回值
     */
    public function headers(){
        parent::headers();

        if($this->type_options['display'] == 'true'){
            $this->_render->pagedata['type_display'] = 'true';
            $this->_render->pagedata['typeData'] = $this->get_type();
            $this->_render->pagedata['timeExplain'] = '(发货时间)';

          
            $type_selected = array(
                                'shop_id[]'=>$this->_params['shop_id'],
                                'branch_id'=>$this->_params['branch_id'],
                                'shop_type'=>$this->_params['shop_type'],
                            );
            $this->_render->pagedata['type_selected'] = $type_selected;
        }

        $this->_render->pagedata['analysts_options'] = $this->analysts_options;

    }

    /**
     * 查找er
     * @return mixed 返回结果
     */
    public function finder(){

        $_extra_view = array(
            'omeanalysts' => 'ome/extra_view.html',
        );

        $this->set_extra_view($_extra_view);

        if(!$_GET['action']){//保存筛选器的信息,用于做导出条件
            kernel::single('omeanalysts_func')->save_search_filter($this->_params);
        }

        $params = array(
            'actions'=>array(
                array(
                    'label'=>app::get('omeanalysts')->_('生成报表'),
                    'class'=>'export',
                    'icon'=>'add.gif',
                    'href'=>'index.php?app=omeanalysts&ctl=ome_analysis&act=salesDeliveryOrdeItem&action=export',
                    'target'=>'{width:600,height:300,title:\'生成报表\'}'),
            ),
            'title'=>app::get('omeanalysts')->_('发货销售统计<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=omeanalysts&ctl=ome_analysis&act=salesDeliveryOrdeItem&action=export\');}</script>'),
            'use_buildin_recycle'=>false,
            'use_buildin_selectrow'=>false,
            'use_buildin_filter'=>true,
        );
        #增加报表导出权限
        $is_export = kernel::single('desktop_user')->has_permission('analysis_export');
        if(!$is_export){
            unset($params['actions']);
        }

        return array(
            'model' => 'omeanalysts_mdl_sales_delivery_order_item',
            'params' => $params,
        );
    }

    /**
     * ext_detail
     * @param mixed $detail detail
     * @return mixed 返回值
     */
    public function ext_detail(&$detail){
        $filter = $this->_params;

        $Osales = $this->app->model('sales_delivery_order_item');
        $sales = $Osales->get_sales($filter);

        $cost_amounts = $sales['cost_amounts']?$sales['cost_amounts']:0;
        $sale_amounts = $sales['sale_amounts']?$sales['sale_amounts']:0;

        $detail['订单总数']['value'] = $sales['order_counts']?$sales['order_counts']:0;
        $detail['货品总数']['value'] = $sales['product_nums']?$sales['product_nums']:0;
        // $detail['商品总额']['value'] = "￥".($sales['total_amounts']?$sales['total_amounts']:0);
        // $detail['优惠总额']['value'] = "￥".($sales['discounts']?$sales['discounts']:0);
        $detail['销售总金额']['value'] = "￥".$sale_amounts;
        // $detail['商品总成本']['value'] = "￥".$cost_amounts;
    }

    /**
     * detail
     * @return mixed 返回值
     */
    public function detail()
    {
        if($this->detail_options['hidden'] == true){
            $this->_render->pagedata['detail_hidden'] = 1;
            return false;
        }
        $detail = array();

		foreach($this->logs_options AS $target=>$option){
			$detail[$option['name']]['value'] = 0;
			$detail[$option['name']]['memo'] = $this->logs_options[$target]['memo'];
			$detail[$option['name']]['icon'] = $this->logs_options[$target]['icon'];
			$detail[$option['name']]['col'] = $target;
		}

        if(method_exists($this, 'ext_detail')){
            $this->ext_detail($detail);
        }
        foreach($detail AS $key=>$val){
            $name = $this->app->_($key);
            $data[$name]['value'] = $val['value'];
            $data[$name]['memo'] = $this->app->_($val['memo']);
            $data[$name]['icon'] = $val['icon'];
            $data[$name]['col'] = $val['col'];
        }
        $this->_render->pagedata['detail'] = $data;
        return true;
    }//End Function

}