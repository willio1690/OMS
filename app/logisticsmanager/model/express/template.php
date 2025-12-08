<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_mdl_express_template extends dbeav_model{    
    function getElements(){
        $elements = array();
        //获取快递单打印项的配置列表
        foreach(kernel::servicelist('ome.service.template') as $object=>$instance){
            if (method_exists($instance, 'getElements')){
                $tmp = $instance->getElements();
            }
            $elements = array_merge($elements, $tmp);
        }
        return $elements;
    }

    /**
     * 获得单据项
     * @param String $type 类型
     */
    public function getElementsItem($type) {
        $elements = array();
        $class = 'ome_service_template_' . $type;
        $instance = kernel::single($class);
        $elements = $instance->getElements();
        return $elements;
    }

    public function modifier_source($col, $list) {
        if($col == 'local' || !$col) {
            return '本地';
        }
        static $shopName = array();
        if($shopName[$col]) {
            return $shopName[$col];
        }
        $shopId = array();
        foreach($list as $val) {
            if($val['source'] && $val['source'] != 'local') {
                $shopId[$val['source']] = $val['source'];
            }
        }
        $shop = app::get('ome')->model('shop')->getList('shop_id, name', array('shop_id'=>$shopId));
        foreach($shop as $val) {
            $shopName[$val['shop_id']] = $val['name'];
        }
        return $shopName[$col];
    }

    /**
     * 获取打印模板列表
     * default模板置顶
     * 
     * @param string $type: ['normal','electron','cainiao','cainiao_standard','cainiao_user','delivery','stock']
     * @param string $control: ['default','lodop']
     * @return void
     * @author 
     */
    public function getPrintTemplates($type, $control = 'default')
    {
        $filter = array(
            'template_type' => $type, 
            'status'        => 'true', 
            'control_type'  => $control
        );

        $list = $this->getList('*',$filter,0,-1,'is_default DESC');

        $templates = array();
        foreach ($list as $value) {
            // 如果是lodop需要特殊转
            if ($value['control_type'] == 'lodop') {
                $value = $this->lodopTemplate($value);
            }

            $templates[$value['template_id']] = $value;
        }

        return $templates;
    }

    /**
     * summary
     * 
     * @return void
     * @author 
     */
    public function lodopTemplate($template)
    {
        $template['title']   = sprintf('打印%s', $template['template_name']);
        $template['plugins'] = array_filter(unserialize($template['template_data'])); unset($template['template_data']);
        $template['page'] = array(
            'width'      => $template['template_width'],
            'height'     => $template['template_height'],
            'type'       => $template['page_type'] ? $template['page_type'] : 0,//默认为0
            'file_id'    => $template['file_id'],
            'background' =>''
        );

        return $template;
    }

    public function saveTemplate($info)
    {
        $filter = [
            'template_id' => $info['template_id']
        ];
        $template = $this->db_dump($filter);
        if (in_array($template['control_type'], ['youzan'])) {
            $template_select = @unserialize($template['template_select']) ?: [];
            $template_select['user_url'] = trim($info['customer_template_url']);            

            $upData = [
                'template_select' => serialize($template_select),
            ];

        } else {
            $template['template_data'] = @json_decode($template['template_data'] ,1);
            $template['template_data']['customerTemplateUrl'] = trim($info['customer_template_url']);
    
            $upData = [
                'template_data' => json_encode($template['template_data']),
            ];
        }

        $this->update($upData, $filter);
        return true;
    }
}
