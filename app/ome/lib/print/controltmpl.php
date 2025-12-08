<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 打印模板
 *
 */
class ome_print_controltmpl
{
    static $singleton = null;
    public $smarty = null;
    public $model = null;
    public $tmplData = null;

    public static function instance($model, $controller) {
        if (self::$singleton[$model] == null) {
            self::$singleton[$model] = kernel::single('ome_print_controltmpl',array('controller' => $controller, 'model' => $model));
        }
        return self::$singleton[$model];
    }

    /**
     * __construct
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function __construct($params) {
        $this->smarty = $params['controller'];
        $this->model = $params['model'];
    }

    /**
     * printOTmpl
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function printOTmpl($id) {
        if (empty($this->model)) {
            $this->_message('请先选择打印模板类型!');
            return;
        }
        $this->tmplData = $this->_getAllTmpl();
        $tmplSelect = $this->_getTmplSelect();
        #如果没有找到对应的模板使用默认模板
        if (!$id || !isset($tmplSelect[$id])) {
            $id = $this->_getDefaultTemplateId();
            //如果没有设置默认模板，选择第一个模板为默认模版
            if (empty($id)) {
                reset($tmplSelect);
                $id = key($tmplSelect);
            }
        }
        #设置当前模板ID
        $this->smarty->pagedata['current_otmpl_id'] = $id;
        #打印模板
        $printTmpl = array();
        foreach ($this->tmplData as $v) {
            if ($v['template_id'] == $id) {
                $printTmpl = $v;
                break;
            }
        }
        #页面模板select
        $otmplList = array();
        foreach ($this->tmplData as $v) {
            $otmplList[] = array('id' => $v['template_id'], 'title' => $v['template_name'],'page_type'=>$v['page_type']);
        }

        $this->smarty->pagedata['otmplList'] = $otmplList;

        #发货单背景图
        if ($printTmpl['file_id']) {
            $this->smarty->pagedata['tmpl_bg'] = 'index.php?app=ome&ctl=admin_delivery_print&act=showPicture&p[0]=' . $printTmpl['file_id'];
        }

        $this->smarty->pagedata['printTmpl'] = $printTmpl;

        parse_str($_SERVER['QUERY_STRING'],$output); unset($output['otmplId']);
        $request_uri = 'index.php?' . http_build_query($output);
        $this->smarty->pagedata['request_uri'] = $request_uri;

        $post = kernel::single('base_component_request')->get_post();

        if ($post) {
            self::array_to_flat($post,$ret);

            $this->smarty->pagedata['postData'] = $ret;
        }

        $this->smarty->pagedata['userAgent'] = $this->getUserAgent();

        $this->_displayTmpl($printTmpl);
    }

    /**
     * 错误信息显示
     * @param String $msg
     */
    protected function _message($msg) {
        $this->smarty->pagedata['err'] = 'true';
        $this->smarty->pagedata['base_dir'] = kernel::base_url();
        $this->smarty->pagedata['time'] = date("Y-m-d H:i:s");
        $this->smarty->pagedata['msg'] = $msg;
        $this->smarty->singlepage('admin/delivery/message.html','ome');
        $this->smarty->display('admin/delivery/print.html','ome');
    }

    /**
     * 获得所有模板类型
     */
    protected function _getAllTmpl() {
        $model = app::get('logisticsmanager')->model('express_template');
        $filter = array('template_type' => $this->model);
        $result = $model->getList('*', $filter, 0, -1);

        foreach ($result as $key=>$value) {
            if ($value['control_type'] == 'lodop') {
                $result[$key] = $model->lodopTemplate($value);
            }
        }

        return $result;
    }

    /**
     * 获得模板选择器
     */
    protected function _getTmplSelect() {
        $select = array();
        foreach ($this->tmplData as $v) {
            $select[$v['template_id']] = $v['template_name'];
        }
        return $select;
    }

    
    protected function _getDefaultTemplateId() {
        $defaultTemplateId = '';
        foreach ($this->tmplData as $v) {
            if ($v['is_default'] == 'true') {
                $defaultTemplateId = $v['template_id'];
                break;
            }
        }
        return $defaultTemplateId;
    }

    /**
     * 页面显示
     */
    protected function _displayTmpl($curTmpl) {
        if ($curTmpl['control_type'] == 'lodop') {
            $this->smarty->pagedata['printTmpl']['template_data'] = kernel::base_url(1) . '/index.php/openapi/template/getToCNTpl?tpl_id=' . $this->smarty->pagedata['current_otmpl_id'];


            $this->smarty->singlepage('admin/delivery/express_lodop.html','wms');
        } else {
            $templateFile = 'admin/delivery/controllertmpl/' . $this->model . '.html';
            $this->smarty->singlepage($templateFile, 'wms');
        }
    }

    //多维数组转成一维数组，
    static function array_to_flat($array,&$ret,$p_key=null){
        foreach($array as $key=>$item){
            if($p_key != null){
                $key = $p_key."[".$key."]";
            }
           if(is_array($item)){
               self::array_to_flat($item,$ret,$key);
           }else{
               $ret[$key] = $item;
           }
        }
    }

    /**
     * 获得浏览器版本
     * Enter description here ...
     */
    public function getUserAgent() {
        $agent = $_SERVER["HTTP_USER_AGENT"];
        $brower = array('brower' => 'Other', 'ver' => '0', 'type' => 2);

        if (strpos($agent, "MSIE 10.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '10.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 9.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '9.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 8.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '8.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 7.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '7.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 6.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '6.0', 'type' => 1);
        }
        elseif (strpos($agent, "Trident")) {
            //IE11以后的版本
            $str = substr($agent, strpos($agent, 'rv:') + strlen('rv:'));
            $ver = substr($str, 0, strpos($str, ')'));
            $brower = array('brower' => 'Ie', 'ver' => $ver, 'type' => 1);
        }
        elseif (strpos($agent, "Chrome")) {
            $str = substr($agent, strpos($agent, 'Chrome/') + strlen('Chrome/'));
            $verInfo = explode(" ", $str);
            $brower = array('brower' => 'Chrome', 'ver' => $verInfo[0], 'type' => 2);
        }
        elseif (strpos($agent, "Firefox")) {
            $str = substr($agent, strpos($agent, 'Firefox/') + strlen('Firefox/'));
            $brower = array('brower' => 'Firefox', 'ver' => $str, 'type' => 2);
        }
        return $brower;
    }
}
