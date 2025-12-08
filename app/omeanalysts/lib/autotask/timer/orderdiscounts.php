<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   XueDing
 * @Version:  1.0
 * @DateTime: 2021/4/25 16:21:45
 * @describe: 类
 * ============================
 */
class omeanalysts_autotask_timer_orderdiscounts
{
    
    /**
     * 处理
     * @param mixed $params 参数
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */

    public function process($params, &$error_msg = '')
    {
        set_time_limit(0);
        ignore_user_abort(1);
        @ini_set('memory_limit', '512M');
        $key   = 'omeanalysts_autotask_timer_ome_orderDiscounts';
        $isRun = cachecore::fetch($key);
        
        if ($isRun) {
            $error_msg = 'is running';
            if (!isset($params['execute'])) {
                return false;
            }
        }
        cachecore::store($key, 'running', 1795);
        $offset = 0;
        $count  = ceil($this->count_orders($params) / 500);
        while ($offset<=$count) {
            $this->insert_data($offset, $params, $error_msg);
            echo '页码：' . $offset. "\n\r";
            $offset++;
        }
        base_kvstore::instance('omeanalysts_ome_orderDiscounts')->store('order_discounts_last_exec_time', time());
    }
    
    /**
     * insert_data
     * @param mixed $offset offset
     * @param mixed $params 参数
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */
    public function insert_data($offset, $params, &$error_msg = '')
    {
        $limit = 500;
        $start = $offset * $limit;
        
        $db = kernel::database();
        
        base_kvstore::instance('omeanalysts_ome_orderDiscounts')->fetch('order_discounts_last_exec_time',
            $last_exec_time);
        
        if ($last_exec_time) {
            $time = $last_exec_time;
        } else {
            $time = time() - 1800;
        }
        
        // 如果传参使用传参时间
        if (isset($params['start_time'])) {
            $time = $params['start_time'];
        }
        $ed_time = '';
        if (isset($params['end_time'])) {
            $ed_time = '  AND paytime < "' . $params['end_time'] . '"';
        }
        
        //主订单数据查询出来所有符合条件的订单id
        $sql = 'SELECT
                    order_id,order_bn,shop_id,shop_type,cost_item,total_amount,payed,createtime,paytime,pay_status,org_id
                FROM
                    sdb_ome_orders
                WHERE
                    paytime > "' . $time . '"' . $ed_time . ' AND
                    pay_status = "1"
                ORDER BY paytime
                LIMIT ' . $start . ',' . $limit;
        $order_list = $db->select($sql);
        
        if (empty($order_list)) {
            $error_msg = '没有符合条件插入数据';
            return false;
        }
        $order_list = array_column($order_list, null, 'order_id');
        //查询主表已有数据与字表已有数据排除
        $order_id_arr  = array_column($order_list, 'order_id');
        $sql           = 'SELECT order_id FROM sdb_omeanalysts_ome_orderDiscounts WHERE order_id IN ( ' . implode(',',
                $order_id_arr) . ' )';
        $discount_list = $db->select($sql);
        $discount_list = array_column($discount_list, null, 'order_id');
        if (!empty($discount_list)) {
            foreach ($order_list as $k => $v) {
                if (isset($discount_list[$v['order_id']])) {
                    unset($order_list[$v['order_id']]);
                }
            }
            $order_id_arr = array_column($order_list, 'order_id');
        }
        
        if (empty($order_id_arr)) {
            $error_msg = '该时间段内数据已插入';
            return false;
        }
        
        //根据剔除已插入的数据order查询优惠明细表
        $sql = 'SELECT order_id,pmt_amount,pmt_describe FROM sdb_ome_order_pmt WHERE order_id IN ( ' . implode(',',
                $order_id_arr) . ' )';
        $order_pmt_list = $db->select($sql);
        
        if (!empty($order_pmt_list)) {
            $order_pmt_list = $this->filter_by_value($order_pmt_list, 'order_id');
        }
        
        //组装插入数据
        $inset_data_str = '';
        $insert_data    = array();
        foreach ($order_list as $k => $v) {
            $insert_data['order_id']         = $v['order_id'];
            $insert_data['order_bn']         = $v['order_bn'];
            $insert_data['shop_id']          = $v['shop_id'];
            $insert_data['shop_type']        = $v['shop_type'];
            $insert_data['original_money']   = $v['cost_item'];
            $insert_data['sale_money']       = $v['total_amount'];
            $insert_data['pay_money']        = !empty($v['payed']) ? $v['payed']: 0;
            $insert_data['order_createtime'] = !empty($v['createtime']) ? $v['createtime'] : time();
            $insert_data['paytime']          = $v['paytime'];
            $insert_data['pay_status']       = $v['pay_status'];
            $insert_data['createtime']       = time();
            if (isset($order_pmt_list[$v['order_id']])) {
                $insert_datas = $insert_data;
                foreach ($order_pmt_list[$v['order_id']] as $key => $value) {
                    $insert_datas['discount_name']  = $value['pmt_describe'];
                    $insert_datas['discount_money'] = $value['pmt_amount'];
                    $insert_datas['org_id']         = $v['org_id'];
                    $inset_data_str .= '("' . implode('","', $insert_datas) . '"),';
                }
                unset($insert_data);
                continue;
            } else {
                $insert_data['discount_name']  = '';
                $insert_data['discount_money'] = 0;
                $insert_data['org_id']           = (int) $v['org_id'];
            }
            $inset_data_str .= '("' . implode('","', $insert_data) . '"),';
            unset($insert_data);
        }
        
        $field = [
            'order_id',
            'order_bn',
            'shop_id',
            'shop_type',
            'original_money',
            'sale_money',
            'pay_money',
            'order_createtime',
            'paytime',
            'pay_status',
            'createtime',
            'discount_name',
            'discount_money',
            'org_id',
        ];
        if (!empty($inset_data_str)) {
            $sql = 'INSERT INTO sdb_omeanalysts_ome_orderDiscounts (' . implode(',',$field) . ') VALUES' . substr($inset_data_str,0, -1);
            $db->exec($sql);
            unset($inset_data_str, $order_pmt_list, $order_id_arr, $discount_list, $order_list);
            $error_msg = 'succ';
            return true;
        } else {
            unset($inset_data_str, $order_pmt_list, $order_id_arr, $discount_list, $order_list);
            $error_msg = 'error';
            return true;
        }
    }
    
    /**
     * count_orders
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function count_orders($params)
    {
        $db = kernel::database();
        
        base_kvstore::instance('omeanalysts_ome_orderDiscounts')->fetch('order_discounts_last_exec_time',
            $last_exec_time);
        
        if ($last_exec_time) {
            $time = $last_exec_time;
        } else {
            $time = time() - 1800;
        }
        
        // 如果传参使用传参时间
        if (isset($params['start_time'])) {
            $time = $params['start_time'];
        }
        $ed_time = '';
        if (isset($params['end_time'])) {
            $ed_time = '  AND paytime < "' . $params['end_time'] . '"';
        }
        
        //主订单数据查询出来所有符合条件的订单id
        $sql = 'SELECT
                    count(*) as order_count
                FROM
                    sdb_ome_orders
                WHERE
                    paytime > "' . $time . '"' . $ed_time . ' AND
                    pay_status = "1"
                ORDER BY paytime';
        return $db->select($sql)[0]['order_count'];
    }
    
    
    /*
    * 根据二维数组某个字段查找数组
    */
    function filter_by_value($array, $index)
    {
        if (is_array($array) && count($array) > 0) {
            foreach (array_keys($array) as $key) {
                $temp[$key][$index] = $array[$key][$index];
                if ($temp[$key][$index] == $array[$key][$index]) {
                    $newarray[$array[$key][$index]][] = $array[$key];
                }
            }
        }
        return $newarray;
    }
    
}
