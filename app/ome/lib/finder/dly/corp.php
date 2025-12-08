<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_finder_dly_corp {

    var $detail_basic = "物流公司详情";

    function detail_basic($corp_id) {
        $render = app::get('ome')->render();
        $oCorp = app::get('ome')->model("dly_corp");
        $dly_info = $oCorp->dump($corp_id);
        $dly_info['area_fee_conf'] = unserialize($dly_info['area_fee_conf']);
        $dly_info['protect_rate'] = $dly_info['protect_rate'] * 100;
        $render->pagedata['dt_info'] = $dly_info;

        return $render->fetch("admin/system/dly_corp_detail.html");
    }

    public $detail_channel = '电子面单来源';
    public $detail_channel_order = '100';
    /**
     * detail_channel
     * @param mixed $corpId ID
     * @return mixed 返回值
     */
    public function detail_channel($corpId) {
        $render   = app::get('ome')->render();
        $oCorp    = app::get('ome')->model("dly_corp");
        $oCorpChannel    = app::get('ome')->model("dly_corp_channel");
        $oChannel = app::get('logisticsmanager')->model('channel');
        $oTemplate = app::get('logisticsmanager')->model('express_template');
        if($_POST) {
            $updateId = array();
            foreach($_POST['shop_type'] as $k => $shopType) {
                $channelId = $_POST['channel_id'][$k];
                $pti = $_POST['prt_tmpl_id'][$k];
                $row = $oCorpChannel->db_dump(array('corp_id'=>$corpId,'shop_type'=>$shopType,'channel_id'=>$channelId), 'id,prt_tmpl_id');
                if($row) {
                    if($row['prt_tmpl_id'] != $pti) {
                        $oCorpChannel->update(array('prt_tmpl_id'=>$pti), array('id'=>$row['id']));
                    }
                    $updateId[] = $row['id'];
                } else {
                    $iData = array(
                        'corp_id'=>$corpId,'shop_type'=>$shopType,'channel_id'=>$channelId,'prt_tmpl_id'=>$pti
                    );
                    $oCorpChannel->insert($iData);
                    $updateId[] = $iData['id'];
                }
            }
            $oCorpChannel->delete(array('id|notin'=>$updateId, 'corp_id'=>$corpId));
        }
        $dly_info = $oCorp->db_dump($corpId);
        if($dly_info['tmpl_type'] != 'electron') {
            return '<div class="division">非电子面单无来源</div>';
        }
        $dly_info['template'] = $this->column_tmpl($dly_info);
        $row = $oChannel->db_dump($dly_info['channel_id'], 'name,logistics_code');
        $dly_info['channel_name'] = $row['name'];
        $render->pagedata['channel'] = $oChannel->getList('channel_id,name,channel_type', array('status'=>'true','channel_id|noequal'=>$dly_info['channel_id']));
        $templates = $oTemplate->getList('template_id,template_name,template_type', array('status'=>'true'));
        $cainiaoTmpl = $pddTmpl = $electronTmpl = array();
        foreach($templates as $val){
            if(in_array($val['template_type'], array('cainiao_standard', 'cainiao_user'))) {
                $cainiaoTmpl[$val['template_id']] = $val['template_name'];
            }elseif(in_array($val['template_type'], array('pdd_standard', 'pdd_user'))) {
                $pddTmpl[$val['template_id']] = $val['template_name'];
            }elseif(in_array($val['template_type'], array('jd_standard', 'jd_user'))) {
                $jdTmpl[$val['template_id']] = $val['template_name'];
            }elseif(in_array($val['template_type'], array('douyin_standard', 'douyin_user'))) {
                $dyTmpl[$val['template_id']] = $val['template_name'];
            }elseif(in_array($val['template_type'], array('kuaishou_standard', 'kuaishou_user'))) {
                $ksTmpl[$val['template_id']] = $val['template_name'];
            }else{
                $electronTmpl[$val['template_id']] = $val['template_name'];
            }
        }
        $render->pagedata['cainiaoTmpl']  = $cainiaoTmpl;
        $render->pagedata['pddTmpl']  = $pddTmpl;
        $render->pagedata['jdTmpl']  = $jdTmpl;
        $render->pagedata['dyTmpl']  = $dyTmpl;
        $render->pagedata['ksTmpl']  = $ksTmpl;
        $render->pagedata['electronTmpl'] = $electronTmpl;
        $render->pagedata['shop_type'] = ome_shop_type::get_shop_type();
        $render->pagedata['dly_info'] = $dly_info;
        $render->pagedata['corp_channel'] = $oCorpChannel->getList('*', array('corp_id'=>$corpId));
        return $render->fetch("admin/system/dly_corp_channel.html");

    }

    var $addon_cols = "corp_id,disabled,prt_tmpl_id,corp_type";
    var $column_edit = "操作";
    var $column_edit_width = "70";

    function column_edit($row) {
        $finder_id = $_GET['_finder']['finder_id'];
        return '<a href="index.php?app=ome&ctl=admin_dly_corp&act=editdly_corp&p[0]=' . $row[$this->col_prefix . 'corp_id'] . '&finder_id=' . $finder_id . '" target="_blank">编辑</a>';
    }

    var $column_used = "当前状态";
    var $column_used_width = "70";
    function column_used($row) {

        if ($row[$this->col_prefix . 'disabled'] == 'false') {

            $ret = '<span style="color:green;">已启用</span>';
        } else {

            $ret = '<span style="color:red;">已停用</span>';
        }

        return $ret;
    }

    var $column_tmpl = "使用模板";
    var $column_tmpl_width = "70";
    function column_tmpl($row) {

        $tpl_id = $row[$this->col_prefix . 'prt_tmpl_id'];
        if (app::get('logisticsmanager')->is_installed()) {
            //新版控件打印
            $tmpl = app::get('logisticsmanager')->model('express_template')->dump($tpl_id);
            $editStr = is_array($tmpl) ? sprintf('<a class="lnk" target="_blank" href="index.php?app=logisticsmanager&ctl=admin_express_template&act=editTmpl&p[0]=%s&finder_id=%s">%s</a>',$tpl_id,$_GET['_finder']['finder_id'],$tmpl['template_name']) : '';
        } else {
            //老版falsh打印
            $tmpl = app::get('wms')->model('print_tmpl')->dump($tpl_id);
            $ret = sprintf('<a class="lnk" target="_blank" href="index.php?app=wms&ctl=admin_delivery_print&act=editTmpl&p[0]=%s&finder_id=%s">%s</a>',$tpl_id,$_GET['_finder']['finder_id'],$tmpl['prt_tmpl_title']);
        }

        if (is_array($tmpl)) {
            $ret = $editStr;
        } else {
            $ret = '<span style="color:red;">未设置</span>';
        }

        return $ret;
    }

    var $column_corp_type = "物流类型";
    var $column_corp_type_width = "70";
    /**
     * column_corp_type
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_corp_type($row){
        return $row[$this->col_prefix.'corp_type']==0?'国内':'境外';
    }
}

?>