<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2022/6/23 15:50:46
 * @describe: 京东平台拆
 * ============================
 */
class ome_ctl_admin_order_platformsplit extends desktop_controller {

    /**
     * index
     * @return mixed 返回值
     */

    public function index() {}

    /**
     * do_confirm
     * @param mixed $order_id ID
     * @return mixed 返回值
     */
    public function do_confirm($order_id) {
        $order = app::get('ome')->model('orders')->db_dump(['order_id'=>$order_id], 'order_bn, is_modify');
        if($order['is_modify'] == 'true') {
            header("content-type:text/html; charset=utf-8");
            echo "<script>alert('订单号：".$order['order_bn']." ');window.close();</script>";
            exit;
        }
        $orderObjects = app::get('ome')->model('order_objects')->getList('*', ['order_id'=>$order_id, 'delete'=>'false']);
        $psRows = app::get('ome')->model('order_platformsplit')->getList('obj_id,split_oid', ['order_id'=>$order_id]);
        $psRows = array_column($psRows, null, 'obj_id');
        foreach ($orderObjects as $key => $value) {
            if(empty($value['oid'])) {
                header("content-type:text/html; charset=utf-8");
                echo "<script>alert('订单号：".$order['order_bn']." 有赠品或新增的商品，不能拆子单');window.close();</script>";
                exit;
            }
            $orderObjects[$key]['obj_type'] = $value['obj_type'] == 'pkg' ? '组合商品' : ($value['obj_type'] == 'pko' ? '多选一商品' : '普通');
            if($psRows[$value['obj_id']]) {
                $orderObjects[$key]['split_oid'] = $psRows[$value['obj_id']]['split_oid'];
            }
        }
        $this->pagedata['order_id'] = $order_id;
        $this->pagedata['order_objects'] = $orderObjects;
        $this->singlepage('admin/order/platform/split.html');
    }

    /**
     * 保存
     * @return mixed 返回操作结果
     */
    public function save() {
        $split_oid = $_POST['split_oid'];
        if(empty($split_oid)) {
            $this->splash('error', $this->url, '无效数据');
        }
        $order_id = $_POST['order_id'];
        $emptyArr = [];
        $oidArr = [];
        $numArr = [];
        foreach ($split_oid as $obj_id => $val) {
            if($val) {
                if($_POST['split_type'] == 'oid'){
                    $oidArr[$val][] = $obj_id;
                } else {
                    $numArr[$obj_id] = $val;
                }
            } else {
                $emptyArr[] = $obj_id;
            }
        }
        $order = ['order_id'=>$order_id];
        $orderObj = app::get('ome')->model('order_objects');
        foreach ($oidArr as $key => $value) {
            $order['objects'] = $orderObj->getList('*', ['obj_id'=>$value, 'order_id'=>$order_id, 'delete'=>'false']);
            if($order['objects']) {
                list($rs, $msg) = kernel::single('ome_order_platform_split')->dealOrderObjects($order, $split_status, $key);
                if(!$rs) {
                    $this->splash('error', $this->url, $msg);
                }
            }
        }
        foreach ($numArr as $key => $value) {
            $order['objects'] = $orderObj->getList('*', ['obj_id'=>$key, 'order_id'=>$order_id, 'delete'=>'false']);
            if($order['objects']) {
                list($rs, $msg) = kernel::single('ome_order_platform_split')->dealOrderObjects($order, $split_status, '', $value);
                if(!$rs) {
                    $this->splash('error', $this->url, $msg);
                }
            }
        }
        if($emptyArr) {
            $order['objects'] = $orderObj->getList('*', ['obj_id'=>$emptyArr, 'order_id'=>$order_id, 'delete'=>'false']);
            if($order['objects']) {
                list($rs, $msg) = kernel::single('ome_order_platform_split')->dealOrderObjects($order, $split_status);
                if(!$rs) {
                    $this->splash('error', $this->url, $msg);
                }
            }
        }
        $sdf = array(
            'order_id'       => $order['order_id'],
            'process_status' => $split_status,
            'confirm'        => 'Y',
            'dispatch_time'  => time(),
        );
        app::get('ome')->model('orders')->save($sdf);
        $log_msg = $split_status == 'splited' ? '订单确认' : '订单部分确认';
        app::get('ome')->model('operation_log')->write_log('order_confirm@ome', $order_id, $log_msg);
        $this->splash('success', $this->url, '操作成功');
    }

    /**
     * doBack
     * @return mixed 返回值
     */
    public function doBack() {
        $order_id = (int) $_GET['order_id'];
        app::get('ome')->model('order_platformsplit')->delete(['order_id'=>$order_id]);
        app::get('ome')->model('orders')->update(['process_status'=>'confirmed'], ['order_id'=>$order_id]);
        $updateSql = 'update sdb_ome_order_items set split_num=0 where order_id="'.$order_id.'"';
        kernel::database()->exec($updateSql);
        echo json_encode(['rsp'=>'succ']);
    }

    /**
     * sync
     * @return mixed 返回值
     */
    public function sync() {
        $order_id = (int) $_GET['order_id'];
        list($rs, $data) = kernel::single('ome_event_trigger_shop_order')->split_oid_sync($order_id);
        echo json_encode(['rsp'=>$rs?'succ':'fail', 'msg'=>$data['msg']]);
    }
}