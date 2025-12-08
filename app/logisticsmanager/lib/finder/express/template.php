<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_finder_express_template{
    var $addon_cols = "template_id,out_template_id,is_default,control_type,template_select,template_data";
    var $column_confirm = "操作";
    var $column_confirm_width = "150";
    var $column_confirm_order = COLUMN_IN_HEAD;
    function column_confirm($row){
        $id = $row[$this->col_prefix.'template_id'];
        if($row[$this->col_prefix . 'out_template_id']) {
            if (in_array($row['control_type'], ['xhs', 'youzan'])) {
                return  <<<EOF
            <span class="lnk" onclick="new Dialog('index.php?app=logisticsmanager&ctl=admin_express_template&act=editField&p[0]=$id&finder_id=$finder_id',{title:'快递面单编辑',width:500,height:140})">编辑</span>
EOF;
            }
            return '';
        }

        $finder_id = $_GET['_finder']['finder_id'];
        $type = $row['template_type'];

        if ($row[$this->col_prefix.'control_type'] == 'lodop') {
            $act = 'editLodopTemplate';
        } elseif ($type == 'delivery') {
            $act = 'editDeliveryTmpl';
            $act1 = 'copyDeliveryTmpl';
        } elseif ($type == 'stock') {
            $act = 'editStockTmpl';
            $act1 = 'copyStockTmpl';
        } else {
            $act  = 'editTmpl';
            $act1 = 'copyTmpl';
        }

        $button = '';
        if (in_array($type, array('delivery','stock'))) {
            if ($row[$this->col_prefix.'is_default'] == 'true') {
                $button = sprintf('<a href="index.php?app=logisticsmanager&ctl=admin_express_template&act=unsetDefault&p[0]=%s&finder_id=%s"><font class="c-red">取消默认</font></a>',$row['template_id'],$finder_id);
            } else {
                $button = sprintf('<a href="index.php?app=logisticsmanager&ctl=admin_express_template&act=setDefault&p[0]=%s&finder_id=%s">设置默认</a>',$row['template_id'],$finder_id);
            }
        }


        $button .= <<<EOF
        <a href="index.php?app=logisticsmanager&ctl=admin_express_template&act=$act&p[0]=$id&finder_id=$finder_id" class="lnk" target="_blank">编辑</a>
EOF;

        if ($row[$this->col_prefix.'control_type'] != 'lodop') {
            $button.= <<<EOF
                        <a href="index.php?app=logisticsmanager&ctl=admin_express_template&act=$act1&p[0]=$id&finder_id=$finder_id" class="lnk" target="_blank">复制</a>
EOF;
        }

        if (!in_array($type,array('delivery','stock'))) {


        $button.= <<<EOF
        <span onclick="window.open('index.php?app=logisticsmanager&ctl=admin_express_template&act=downloadTmpl&p[0]=$id&finder_id=$finder_id')" class="lnk">下载</span>
EOF;
        }

        return $button;
    }
    
    var $column_isdefault = "是否默认";
    var $column_isdefault_width = "60";
    var $column_isdefault_order = '32';
    var $column_isdefault_order_field = 'is_default';
    /**
     * column_isdefault
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_isdefault($row) {
        $is_default = $row[$this->col_prefix.'is_default'];
        $title = '';
        if ($is_default == 'false') {
            $title = '否';
        }
        else {
            $title = '是';
        }
        return $title;
    }


    public $column_custom_template_url = "自定义区";
    public $column_custom_template_url_width = "260";
    public $column_custom_template_url_order = "31";
    /**
     * column_custom_template_url
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_custom_template_url($row)
    {
        $control_type = $row[$this->col_prefix . 'control_type'];
        if(in_array($control_type, ['douyin','kuaishou'])) {
            $template_data = @json_decode($row[$this->col_prefix . 'template_data'], 1);
            return $template_data['custom_template_url'];
        }
        if(in_array($control_type, ['xhs'])) {
            $template_data = @json_decode($row[$this->col_prefix . 'template_data'], 1);
            return $template_data['customerTemplateUrl'];
        }

        // if (in_array($control_type, ['youzan'])) {
        //     return $row[$this->col_prefix . 'template_select'];
        // }

        if (!in_array($control_type, ['jd','cainiao','pdd'])) {
            return '-';
        }

        $template_select = (array)@unserialize($row[$this->col_prefix . 'template_select']);

        return $template_select['user_url'];
    }

    public $column_template_url = "标准模板";
    public $column_template_url_width = "260";
    public $column_template_url_order = "30";
    /**
     * column_template_url
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_template_url($row)
    {
        $control_type = $row[$this->col_prefix . 'control_type'];
        if(in_array($control_type, ['douyin','kuaishou'])) {
            $template_data = @json_decode($row[$this->col_prefix . 'template_data'], 1);
            return $template_data['template_url'];
        }
        if(in_array($control_type, ['xhs'])) {
            $template_data = @json_decode($row[$this->col_prefix . 'template_data'], 1);
            return $template_data['standardTemplateUrl'];
        }
        /*if (!in_array($control_type, ['jd','cainiao','pdd'])) {
            return '-';
        }*/

        return $row[$this->col_prefix . 'template_data'];
    }
}
?>