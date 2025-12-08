<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omevirtualwms_finder_goods{

    /* 详情
     *
     */
    function detail_log($log_id){
        
        $render = app::get('omeapilog')->render();
        $oApilog = app::get('omeapilog')->model("api_log");
        $shopObj = app::get('ome')->model('shop');
        $apilog = $oApilog->dump($log_id);

        // 批量同步成功的msg_id
        $apilog_sdf = unserialize($apilog['params']);
        if (isset($apilog_sdf[1]['msg_id'])){
            $render->pagedata['batch_msg_id'] = implode(',', $apilog_sdf[1]['msg_id']);
        }

        $apilog['params'] = unserialize($apilog['params']);
        $apilog['send_api_name'] = $apilog['params'][0];// API名称

        if (is_array($apilog['params'][1])){
            foreach ($apilog['params'][1] as $key=>$val){
                if ($key && $key == 'all_list_quantity'){
                    $apilog['all_list_quantity'] = $val;
                    continue;//排除显示所有库存Bn，单独放在外面显示
                }
                if ($key && $key == 'list_quantity'){
                    $params .= $key."(待更新库存):".$val."<br/>";
                }else{
                    if (is_array($val)) $params .= $key.'='.serialize($val);
                    else $params .= $key."=".$val."<br/>";
                }
            }
        }else{
            foreach ($apilog['params'] as $key=>$val){
                $params .= $key."=".$val."<br/>";
            }
        }
        $apilog['send_api_params'] = $params;

        $apilog_msg = @json_decode($apilog['msg'],true);
        $api_arr = false;
        $msg = '';
        if (is_array($apilog_msg)){
            ob_start();
            echo "<pre>";
            var_export($apilog_msg);
            $msg = ob_get_contents();
            ob_clean();
        }else{
            $msg = htmlspecialchars($apilog['msg']);
            $shop_detail = $shopObj->dump($apilog['shop_id'], 'node_type');
            $code_msg = omeapilog_func::code2msg($msg, $shop_detail['node_type']);
            if (!empty($code_msg)){
                $msg = $code_msg;
            }
        }
        $apilog['msg'] = $msg;
        $apilog['api_arr'] = $api_arr;
        $render->pagedata['apilog'] = $apilog;
        return $render->fetch("admin/api/detail.html");
    }

    var $column_back='操作';
    var $column_back_width = 70;
    var $column_back_order = 1;
    var $addon_cols = "status,error_lv";
    function column_back($row){
        $log_id = $row['log_id'];
        $finder_id = $_GET['_finder']['finder_id'];
        $button = "<a href='index.php?app=omevirtualwms&ctl=admin_wms&act=callback_goods_html&p[0]={$log_id}&p[1]={$finder_id}'>开始模拟</a>";
        return $button;
    }

}
?>