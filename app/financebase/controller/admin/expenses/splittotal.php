<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/11/27 10:07:11
 * @describe: 控制器
 * ============================
 */
class financebase_ctl_admin_expenses_splittotal extends desktop_controller {

    /**
     * index
     * @return mixed 返回值
     */

    public function index() {
        $timeFrom = date('Y-m-01', strtotime(date("Y-m-d")));
        $this->pagedata['time_from'] = $timeFrom;
        $this->pagedata['time_to'] = date('Y-m-d', strtotime("{$timeFrom} +1 month -1 day"));
        $this->pagedata['billCategory']= app::get('financebase')->model('expenses_rule')->getBillCategory();
        $shopdata = financebase_func::getShopList(financebase_func::getShopType());
        $this->pagedata['shopdata']= $shopdata;
        $this->page('admin/expenses/split_total.html');
    }

    /**
     * export
     * @return mixed 返回值
     */
    public function export() {
        switch ($_POST['url_type']) {
            case 'trade':
                $url = "index.php?app=financebase&ctl=admin_expenses_splittotal&act=tradeExport";
                break;
            default:
                $url = "index.php?app=financebase&ctl=admin_expenses_splittotal&act=splitExport";
                break;
        }
        $ioType = array('csv');
        $this->pagedata['ioType'] = $ioType;
        $this->pagedata['thisUrl'] = $url;
        echo $this->fetch('common/export.html',app::get('desktop')->app_id);
    }

    /**
     * tradeSearch
     * @return mixed 返回值
     */
    public function tradeSearch() {
        foreach ($_POST as $k => $v) {
            if (!is_array($v) && $v !== false)
                $_POST[$k] = trim($v);
            if ($_POST[$k] === '') {
                unset($_POST[$k]);
            }
        }
        $list = app::get('financebase')->model('bill')->getBillCategorySplitCount($_POST);
        $total = array();
        foreach ($list as $v) {
            $total['total_money'] += $v['total_money'];
            $total['split_money'] += $v['split_money'];
            $total['unsplit_money'] += $v['unsplit_money'];
        }
        echo json_encode(array('total'=>$total, 'items'=>$list));
    }

    /**
     * tradeExport
     * @return mixed 返回值
     */
    public function tradeExport() {
        foreach ($_POST as $k => $v) {
            if (!is_array($v) && $v !== false)
                $_POST[$k] = trim($v);
            if ($_POST[$k] === '') {
                unset($_POST[$k]);
            }
        }
        $exportName = '账期'.date('Ymd').".csv";
        header("Content-Type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=".$exportName);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo "\xEF\xBB\xBF";
        $list = app::get('financebase')->model('bill')->getBillCategorySplitCount($_POST);
        if(empty($list)) {
            echo '没有数据';
            exit();
        }
        $opInfo = kernel::single('ome_func')->getDesktopUser();
        $inLogData = array(
            'export_type' => 'main',
            'filter' => json_encode($_POST, JSON_UNESCAPED_UNICODE),
            'export_time' => time(),
            'op_id' => $opInfo['op_id'],
        );
        app::get('financebase')->model('expenses_export_log')->insert($inLogData);
        $title = array(
            "具体类别","总费用","已拆分费用","未拆分费用"
        );
        echo implode(",", $title);
        foreach ($list as $v) {
            echo "\n";
            echo '"'.implode('","', $v).'"';
        }
    }

    /**
     * splitSearch
     * @return mixed 返回值
     */
    public function splitSearch() {
        foreach ($_POST as $k => $v) {
            if (!is_array($v) && $v !== false)
                $_POST[$k] = trim($v);
            if ($_POST[$k] === '') {
                unset($_POST[$k]);
            }
        }
        $data = $_POST;
        $data['split_status'] = array('2','3');
        $list = app::get('financebase')->model('bill')->getBillCategorySplitCount($data);
        $total = array();
        foreach ($list as $k => $v) {
            $list[$k]['total_money'] = round($v['total_money'],2);
            $total['total_money'] += $v['total_money'];
        }
        $total['total_money'] = round($total['total_money'], 2);
        echo json_encode(array('total'=>$total, 'items'=>$list));
    }

    /**
     * splitExport
     * @return mixed 返回值
     */
    public function splitExport() {
        foreach ($_POST as $k => $v) {
            if (!is_array($v) && $v !== false)
                $_POST[$k] = trim($v);
            if ($_POST[$k] === '') {
                unset($_POST[$k]);
            }
        }
        $exportName = '拆分'.date('Ymd').".csv";
        header("Content-Type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=".$exportName);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo "\xEF\xBB\xBF";
        $data = $_POST;
        $data['split_status'] = array('2','3');
        $list = app::get('financebase')->model('bill')->getBillCategorySplitCount($data);
        if(empty($list)) {
            echo '没有数据';
            exit();
        }
        $opInfo = kernel::single('ome_func')->getDesktopUser();
        $inLogData = array(
            'export_type' => 'main',
            'filter' => json_encode($_POST, JSON_UNESCAPED_UNICODE),
            'export_time' => time(),
            'op_id' => $opInfo['op_id'],
        );
        app::get('financebase')->model('expenses_export_log')->insert($inLogData);
        $title = array(
            "具体类别","拆分费用"
        );
        echo implode(",", $title);
        foreach ($list as $v) {
            echo "\n";
            $data = array($v['bill_category'],$v['total_money']);
            echo '"'.implode('","', $data).'"';
        }
    }
}