<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_analysis_store_daliy{

    /**
     * generate
     * @return mixed 返回值
     */
    public function generate(){
        $nowDay = date("Y-m-d",strtotime("-1 day"));
        $begin_unixtime = strtotime($nowDay);
        $end_unixtime = strtotime($nowDay.' 23:59:59');

        //获取配送自提、配送的物流公司信息
        $dlyCorpObj = app::get('ome')->model('dly_corp');
        $dlyCorpArr = $dlyCorpObj->getList('corp_id,type', array('d_type'=>2), 0, -1);
        foreach($dlyCorpArr as $dlyCorp){
            if($dlyCorp['type'] == 'o2o_ship'){
                $o2o_ship_logi_id = $dlyCorp['corp_id'];
            }elseif($dlyCorp['type'] =='o2o_pickup'){
                $o2o_pickup_logi_id = $dlyCorp['corp_id'];
            }
        }
        unset($dlyCorpArr);

        //获取当前系统门店以及门店仓的相关信息
        $storeObj = app::get('o2o')->model('store');
        $storeArr = $storeObj->getList('store_bn,name,branch_id', array(), 0, -1);
        foreach($storeArr as $store){
            $branch_ids[] = $store['branch_id'];
            $storeInfo[$store['branch_id']] = array('store_bn'=>$store['store_bn'],'store_name'=>$store['name']);
        }
        unset($storeArr);

        $storeDaliyObj = app::get('o2o')->model('store_daliy');

        //按门店仓的维度统计相关信息
        foreach($branch_ids as $branch_id){
            $store_daliy = $storeInfo[$branch_id];
            $store_daliy['createtime'] = $begin_unixtime;

            //订单总数
            $sql = "SELECT count(delivery_id) AS order_sum FROM sdb_wap_delivery WHERE branch_id =".$branch_id." AND create_time >=".$begin_unixtime." AND create_time <=".$end_unixtime;
            $data = $storeObj->db->selectrow($sql);
            $store_daliy['order_sum'] = $data['order_sum'];

            //审核单量
            $sql = "SELECT count(delivery_id) AS confirm_num FROM sdb_wap_delivery WHERE branch_id =".$branch_id." AND last_modified >=".$begin_unixtime." AND last_modified <=".$end_unixtime." AND confirm=1";
            $data = $storeObj->db->selectrow($sql);
            $store_daliy['confirm_num'] = $data['confirm_num'];

            //拒绝单量
            $sql = "SELECT count(delivery_id) AS refuse_num FROM sdb_wap_delivery WHERE branch_id =".$branch_id." AND last_modified >=".$begin_unixtime." AND last_modified <=".$end_unixtime." AND confirm=2";
            $data = $storeObj->db->selectrow($sql);
            $store_daliy['refuse_num'] = $data['refuse_num'];

            //发货单量
            $sql = "SELECT count(delivery_id) AS send_num FROM sdb_wap_delivery WHERE branch_id =".$branch_id." AND delivery_time >=".$begin_unixtime." AND delivery_time <=".$end_unixtime." AND status=3";
            $data = $storeObj->db->selectrow($sql);
            $store_daliy['send_num'] = $data['send_num'];

            if($store_daliy['send_num'] > 0){
                //销售货品数
                $sql = "SELECT SUM(wdi.number) AS sale_sum FROM sdb_wap_delivery_items AS wdi LEFT JOIN sdb_wap_delivery AS wd on wdi.delivery_id = wd.delivery_id WHERE wd.branch_id =".$branch_id." AND wd.delivery_time >=".$begin_unixtime." AND wd.delivery_time <=".$end_unixtime." AND wd.status=3";
                $data = $storeObj->db->selectrow($sql);
                $store_daliy['sale_sum'] = $data['sale_sum'];

                //配送占比
                $sql = "SELECT count(delivery_id) AS distribution_num FROM sdb_wap_delivery WHERE branch_id =".$branch_id." AND delivery_time >=".$begin_unixtime." AND delivery_time <=".$end_unixtime." AND status=3 AND logi_id=".$o2o_ship_logi_id;
                $data = $storeObj->db->selectrow($sql);
                $store_daliy['distribution_rate'] = $data['distribution_num'] ? $data['distribution_num']/$store_daliy['send_num'] : 0.0000;

                //自提占比
                $sql = "SELECT count(delivery_id) AS self_pick_num FROM sdb_wap_delivery WHERE branch_id =".$branch_id." AND delivery_time >=".$begin_unixtime." AND delivery_time <=".$end_unixtime." AND status=3 AND logi_id=".$o2o_pickup_logi_id;
                $data = $storeObj->db->selectrow($sql);
                $store_daliy['self_pick_rate'] = $data['self_pick_num'] ? (1-$store_daliy['distribution_rate']) : 0.0000;
            }else{
                $store_daliy['sale_sum'] = 0;
                $store_daliy['distribution_rate'] = 0.0000;
                $store_daliy['self_pick_rate'] = 0.0000;
            }

            //核销签收单量
            $sql = "SELECT count(delivery_id) AS verified_num FROM sdb_wap_delivery WHERE branch_id =".$branch_id." AND last_modified >=".$begin_unixtime." AND last_modified <=".$end_unixtime." AND is_received=2";
            $data = $storeObj->db->selectrow($sql);
            $store_daliy['verified_num'] = $data['verified_num'];

            $storeDaliyObj->insert($store_daliy);
            unset($store_daliy);
        }
    }
    
    /**
     * 每日(03点)统计补货差异单数据
     */
    public function statisReplenish()
    {
        $storeObj = app::get('o2o')->model('store');
        $repDiffObj = app::get('console')->model('replenish_diff');
        
        //filter
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $beginTime = strtotime($yesterday.' 00:00:00');
        $endTime = strtotime($yesterday.' 23:59:59');
        
        //select
        $sql = "SELECT * FROM sdb_console_cpfr WHERE create_time >=".$beginTime." AND create_time <=".$endTime." ";
        $sql .= " AND adjust_type='replenish' AND num_total != actual_total ORDER BY cpfr_id ASC";
        $dataList = $storeObj->db->select($sql);
        if(empty($dataList)){
            return true;
        }
        
        $ids = array();
        $cpfrList = array();
        foreach ($dataList as $key => $val)
        {
            $cpfr_id = $val['cpfr_id'];
            
            $cpfrList[$cpfr_id] = $val;
            
            $ids[$cpfr_id] = $cpfr_id;
        }
        
        //获取补货明细
        $sql = "SELECT * FROM sdb_console_cpfr_items WHERE cpfr_id IN(". implode(',', $ids) .") AND original_num != num ORDER BY item_id ASC";
        $dataList = $storeObj->db->select($sql);
        if(empty($dataList)){
            return true;
        }
        
        foreach ($dataList as $key => $val)
        {
            $cpfr_id = $val['cpfr_id'];
            $not_nums = $val['original_num'] - $val['num'];
            
            //补货信息
            $cpfrInfo = $cpfrList[$cpfr_id];
            
            //sdf
            $sdf = array(
                    'task_bn' => $cpfrInfo['origin_bn'],
                    'cpfr_bn' => $cpfrInfo['cpfr_bn'],
                    'out_branch_id' => $cpfrInfo['branch_id'],
                    'store_bn' => $val['store_bn'],
                    'to_branch_id' => $val['to_branch_id'],
                    'product_id' => $val['product_id'],
                    'material_bn' => $val['bn'],
                    'apply_nums' => $val['original_num'],
                    'finish_nums' => $val['num'],
                    'not_nums' => $not_nums,
                    'create_time' => time(),
            );
            $repDiffObj->insert($sdf);
        }
        
        return true;
    }
}
