<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 盘点
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_response_process_inventory
{
    /**
     * 盘点
     *  
     * @param Array $params=array(
     *                  'inventory_bn'=>@盘点单号@
     *                  'operate_time'=>@操作时间@
     *                  'memo'=>@备注@
     *                  'wms_id'=>@仓储id@
     *                  'io_source'=>selfwms
     *                  'branch_bn'=>@库存编号@
     *                  'inventory_type'=>@盘点类型@
     *                  'items'=>array(
     *                      'bn'=>@货号@ 
     *                      'num'=>@库存@
     *                      'normal_num'=>@良品@
     *                      'defective_num'=>@不良品@
     *                  )
     *              )
     *
     * @return void
     * @author 
     **/
    public function add($data)
    {
        $oBranchProduct = app::get('ome')->model("branch_product");
        $oInventory     = app::get('console')->model("inventory_apply");
        $oInventoryItem = app::get('console')->model("inventory_apply_items");
        
        $bmIds = array_column($data['items'], 'bm_id');
        $bpStore = [];
        if($data['negative_branch_id']) {
            $data['branch_id'] = current($data['negative_branch_id']);
            $bpRows = $oBranchProduct->getList('branch_id, product_id, store', ['product_id'=>$bmIds, 'branch_id'=>$data['negative_branch_id']]);
            foreach($bpRows as $v) {
                $bpStore[$v['product_id']]['zp'] += $v['store'];
            }
        }
        if($data['negative_cc_branch_id']) {
            if(empty($data['branch_id'])) {
                $data['branch_id'] = current($data['negative_cc_branch_id']);
            }
            $bpRows = $oBranchProduct->getList('branch_id, product_id, store', ['product_id'=>$bmIds, 'branch_id'=>$data['negative_cc_branch_id']]);
            foreach($bpRows as $v) {
                $bpStore[$v['product_id']]['cc'] += $v['store'];
            }
        }
        //盘点单详情
        $items = array();
        foreach ($data['items'] as $item)
        {
            if($item['zp']) {
                $oms_stores = $bpStore[$item['bm_id']]['zp'];
                $diff_stores = ($data['mode'] == '2' ? $item['zp']['diff_stores'] : ($item['zp']['wms_stores'] - $oms_stores));
                $items[] = array(
                    'bm_id'         => $item['bm_id'],
                    'material_bn'   => $item['material_bn'],
                    'm_type'        => 'zp',
                    'wms_stores'    => $item['zp']['wms_stores'],
                    'oms_stores'    => $oms_stores,
                    'diff_stores'   => $diff_stores,
                    'is_confirm'    => $diff_stores == 0 ? '1' : '0',
                    'batch'        => $item['batch'] ? json_encode($item['batch'])   : '',  
                );
            }
            if($item['cc']) {
                $oms_stores = $bpStore[$item['bm_id']]['cc'];
                $diff_stores = ($data['mode'] == '2' ? $item['cc']['diff_stores'] : ($item['cc']['wms_stores'] - $oms_stores));
                $items[] = array(
                    'bm_id'         => $item['bm_id'],
                    'material_bn'   => $item['material_bn'],
                    'm_type'        => 'cc',
                    'wms_stores'    => $item['cc']['wms_stores'],
                    'oms_stores'    => $oms_stores,
                    'diff_stores'   => $diff_stores,
                    'is_confirm'    => $diff_stores == 0 ? '1' : '0',
                    'batch'        => $item['batch'] ? json_encode($item['batch'])   : '',  
                );
            }
        }
        
        if (count($items)<=0){
           
           $msg = '没有明细';
           return ['rsp'=>'fail', 'msg'=>$msg];
        }
        if ($data['operate_time'] && (strtotime($data['operate_time']) && strtotime($data['operate_time']) != -1))//-1兼容5.1
            $date = strtotime($data['operate_time']);
        else
            $date = time();

        $memo           = $data['memo'];
        $wms_id         = $data['wms_id'];
        $warehouse      = $data['branch_bn'];
        $inventory_bn   = $data['inventory_bn'];
        $_inventory = $oInventory->getlist('inventory_apply_id,inventory_apply_bn,status',array('inventory_apply_bn'=>$data['inventory_bn']),0,1);
        $_inventory = $_inventory[0];
        if (empty($_inventory['inventory_apply_bn'])){

            //创建申请 
            $inventory = array(
                'inventory_apply_bn'    => $data['inventory_bn'],
                'branch_id'             => $data['branch_id'],
                'negative_branch_id'    => $data['negative_branch_id'] ? json_encode($data['negative_branch_id']) : '',
                'negative_cc_branch_id' => $data['negative_cc_branch_id'] ? json_encode($data['negative_cc_branch_id']) : '',
                'out_id'                => $warehouse,
                'wms_id'                => $wms_id,
                'inventory_date'        => $date,
                'memo'                  => $data['memo'],
                'inventory_apply_items' => $items,
            );
            

            kernel::database()->beginTransaction();
            $rs = $oInventory->save($inventory);
            if (!$rs){
                kernel::database()->rollBack();
                $msg = '盘点单保存失败';
                return ['rsp'=>'fail', 'msg'=>$msg];
            }
            $this->updateHangTotal($inventory['inventory_apply_id']);
            app::get('ome')->model('operation_log')->write_log('inventory_apply@console',$inventory['inventory_apply_id'],"新建成功");
            kernel::database()->commit();
            $inventory_apply_id = $inventory['inventory_apply_id'];
        }else {
            //增加Items 
            if($_inventory['status'] != 'unconfirmed') {
                return ['rsp'=>'fail', 'msg'=>'盘点状态不对'];
            }
            kernel::database()->beginTransaction();
            foreach ($items as $item){
                $invi = $oInventory->db->selectrow('SELECT item_id FROM sdb_console_inventory_apply_items WHERE inventory_apply_id='.$_inventory['inventory_apply_id'].' AND bm_id=\''.$item['bm_id'].'\' AND m_type="'.$item['m_type'].'"');
                if(empty($invi)){
                    $item['inventory_apply_id'] = $_inventory['inventory_apply_id'];
                    $oInventoryItem->insert($item);
                }else{
                    $oInventoryItem->update($item,array('item_id'=>$invi['item_id']));
                }
            }
            $rs = $this->updateHangTotal($_inventory['inventory_apply_id']);
            if(!$rs) {
                kernel::database()->rollBack();
                return ['rsp'=>'succ', 'msg'=>'操作失败，盘点已被确认或取消或无变更'];
            }
            app::get('ome')->model('operation_log')->write_log('inventory_apply@console',$_inventory['inventory_apply_id'],"明细追加成功");
            kernel::database()->commit();
            $inventory_apply_id = $_inventory['inventory_apply_id'];
        }
        if($data['autoconfirm'] == 'Y') {
            $this->inventory_queue($inventory_apply_id);
        }
        return ['rsp'=>'succ', 'msg'=>'操作成功'];
    }

    public function updateHangTotal($inventory_apply_id) {
        $oInventoryItem = app::get('console')->model("inventory_apply_items");
        $item = $oInventoryItem->db_dump(['inventory_apply_id'=>$inventory_apply_id], 'sum(wms_stores) as sku_total, count(item_id) as sku_hang');
        $rs = app::get('console')->model("inventory_apply")->update(['sku_total'=>$item['sku_total'], 'sku_hang'=>$item['sku_hang']],array('inventory_apply_id'=>$inventory_apply_id, 'status'=>'unconfirmed'));
        return is_bool($rs) ? false : true;
    }

    public function inventory_queue($inventory_id)
    {
        $inventory_ids = array();
        $inventory_ids[0] = $inventory_id;

        //获取system账号信息
        $opinfo = kernel::single('ome_func')->get_system();

        //自动审单_批量日志
        $blObj  = app::get('ome')->model('batch_log');

        $batch_number = count($inventory_ids);
        $bldata = array(
                'op_id' => $opinfo['op_id'],
                'op_name' => $opinfo['op_name'],
                'createtime' => time(),
                'batch_number' => $batch_number,
                'log_type'=> 'confirm_inventory',
                'log_text'=> serialize($inventory_ids),
        );
        $result = $blObj->save($bldata);

        //自动审批任务队列(改成多队列多进程)
        if (defined('SAAS_COMBINE_MQ') && SAAS_COMBINE_MQ == 'true') {
            $data = array();
            $data['spider_data']['url'] = kernel::openapi_url('openapi.autotask','service');

            $push_params = array(
                    'log_text'  => $bldata['log_text'],
                    'log_id'    => $bldata['log_id'],
                    'task_type' => 'confirminventory',
            );
            $push_params['taskmgr_sign'] = taskmgr_rpc_sign::gen_sign($push_params);
            foreach ($push_params as $key => $val) {
                $postAttr[] = $key . '=' . urlencode($val);
            }

            $data['spider_data']['params']    = empty($postAttr) ? '' : join('&', $postAttr);
            $data['relation']['to_node_id']   = base_shopnode::node_id('ome');
            $data['relation']['from_node_id'] = '0';
            $data['relation']['tid']          = $bldata['log_id'];
            $data['relation']['to_url']       = $data['spider_data']['url'];
            $data['relation']['time']         = time();

            $routerKey = 'tg.order.inventory.'. $data['relation']['from_node_id'];

            $message = json_encode($data);
            $mq = kernel::single('base_queue_mq');
            $mq->connect($GLOBALS['_MQ_COMBINE_CONFIG'], 'TG_COMBINE_EXCHANGE', 'TG_COMBINE_QUEUE');
            $mq->publish($message, $routerKey);
            $mq->disConnect();
        } else {
            $push_params = array(
                    'data' => array(
                            'log_text'  => $bldata['log_text'],
                            'log_id'    => $bldata['log_id'],
                            'task_type' => 'confirminventory',
                    ),
                    'url' => kernel::openapi_url('openapi.autotask','service'),
            );

            kernel::single('taskmgr_interface_connecter')->push($push_params);
        }

        return true;
    }
}
