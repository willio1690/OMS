<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-22
 * @describe 快递单打印模板
 */
class logisticsmanager_print_express{
    public $smarty = null;
    public $printTpl; //打印模板
    public $printField; //打印需要的数据字段
    public $msg;
    static $singleton = null;
    static $logi_list = array('amazon','dangdang');//需要根据物流公司自定义快递单输入信息列表

    static function instance($logi,$controller){
        $logi = strtolower($logi);
        if(self::$singleton[$logi] === null){
            if(in_array($logi,self::$logi_list)){
                self::$singleton[$logi] = kernel::single('logisticsmanager_print_express_'.$logi,$controller);
            }else{
                self::$singleton[$logi] = kernel::single('logisticsmanager_print_express',$controller);
            }
        }
        return self::$singleton[$logi];
    }

    /**
     * __construct
     * @param mixed $controller controller
     * @return mixed 返回值
     */

    public function __construct($controller){
        $this->smarty = $controller;
    }
    public function setParams( $params = array() ){
        return $this;
    }

    /**
     * 获取ExpressTpl
     * @param mixed $prtTmplId ID
     * @return mixed 返回结果
     */
    public function getExpressTpl($prtTmplId) {
        $templateObj = app::get("logisticsmanager")->model('express_template');
        $printTmpl = $templateObj->dump($prtTmplId);
        if(empty($printTmpl)) {
            $this->msg = '没有设定快递单模板';
            return false;
        }
        if(empty($printTmpl['template_select'])){
            $printTmpl['template_select'] = json_encode(array());
        }else{
            $printTmpl['template_select'] = json_encode(unserialize($printTmpl['template_select']));
        }
        $this->printTpl = $printTmpl;
        $this->_dataToField($printTmpl['template_data']);
        if(empty($this->printField)) {
            $this->printField = array(//菜鸟打印固定部分
                'seller_id','ship_mobile','ship_tel','cp_code','print_config','mailno_position','package_wdjc','logi_no','ship_name','ship_detailaddr','dly_name','dly_mobile','dly_detailaddr','dly_area_1'
            );
            $this->printField = array_merge(json_decode($printTmpl['template_select'], true), $this->printField);
        }
        return true;
    }

    //获取快递单打印模板中需要的字段
    private function _dataToField($data) {
        $arrData = explode(';', $data);
        $field = array();
        foreach($arrData as $val) {
            if(strpos($val, 'report_field:') !== false || strpos($val, 'report_barcode:') !== false) {
                $tmpData = explode(',', $val);
                $field[] = $tmpData[5];
            }
        }
        $this->printField = $field;
    }

    /**
     * 获取Tmpl
     * @return mixed 返回结果
     */
    public function getTmpl(){
        $this->smarty->pagedata['userAgent'] = $this->getUserAgent();
        if($this->smarty->pagedata['printTmpl']['template_type']=='normal') {
            $this->smarty->singlepage("admin/print/express_normal.html", 'logisticsmanager');
        } elseif($this->smarty->pagedata['printTmpl']['template_type']=='cainiao'){
            $this->smarty->singlepage("admin/print/express_cainiao.html", 'logisticsmanager');
        }else {
            $this->smarty->singlepage("admin/print/express_electron.html", 'logisticsmanager');
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