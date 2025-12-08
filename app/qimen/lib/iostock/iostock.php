<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 创建出入库单Lib类
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version 1.0
 */
class qimen_iostock_iostock
{
    /**
     * 入库单仓库列表
     */
    public function iostockBranch($storeCode)
    {
        $branchList    = array('007802'=>'GXKIDCP01', '007540'=>'GXZP01');
        
        //直接返回仓库编码
        if($storeCode){
            return $branchList[$storeCode];
        }
        
        return $branchList;
    }
    
    /**
     * 验证参数
     */
    public function checkParams($data, &$error_msg)
    {
        $branchObj = app::get('ome')->model('branch');
        $notifyObj = app::get('qimen')->model('iostock_notify');
        
        if(empty($data['trfoutno'])){
            $error_msg = '入库单名称不能为空';
            return false;
        }
        
        //转仓单据号是否已存在
        $notifyInfo = $notifyObj->dump(array('trfoutno'=>$data['trfoutno']), 'iso_bn');
        if($notifyInfo){
            $error_msg = 'SAP单号：'. $data['trfoutno'] .' 已存在!';
            return false;
        }
        
        if(empty($data['branch_bn'])){
            $error_msg = '没有找到可用的仓库';
            return false;
        }
        
        //仓库是否存在
        $branchInfo = $branchObj->dump(array('branch_bn'=>$data['branch_bn']), 'branch_id');
        if(empty($branchInfo)){
            $error_msg = '仓库编码：'. $data['branch_bn'] .' 对应仓库不存在!';
            return false;
        }
        
        return true;
    }
    
    /**
     * 创建入库单
     */
    public function add($sdf)
    {
        $basicMaterialObj = app::get('material')->model('basic_material');
        $notifyObj = app::get('qimen')->model('iostock_notify');
        $itemsyObj = app::get('qimen')->model('iostock_items');
        
        //组织数据
        $data = array();
        $data['trfoutno'] = $sdf['trfoutno'];//入库单名称
        $data['name'] = 'SAP:'. $sdf['trfoutno'];//入库单名称
        $data['vendor'] = '';//供应商
        $data['type'] = 'E';//'E'=>'70'直接入库
        $data['delivery_cost'] = '20.00';//入库费用
        $data['operator'] = 'system';
        $data['memo'] = $sdf['trfoutremark'];//备注
        $data['confirm'] = 'N';//确认状态
        $data['is_ttpos'] = 1;//转仓单标识
        
        //入库单仓库
        $data['branch_bn'] = $this->iostockBranch($sdf['trfinstore']);
        
        //参数检查
        $res    = $this->checkParams($data, $error_msg);
        if(!$res){
            return array('rsp'=>'fail', 'sub_code'=>'e0053', 'sub_message'=>$error_msg);
        }
        
        //items
        $data['items'] = unserialize($sdf['items']);
        if(empty($data['items'])){
            $error_msg = '没有入库单商品明细';
            return array('rsp'=>'fail', 'sub_code'=>'e0053', 'sub_message'=>$error_msg);
        }
        
        $itemList    = array();
        foreach ($data['items'] as $key => $val)
        {
            $val['outqty'] = intval($val['outqty']);
            
            $item = array(
                    'bn'=>$val['plu'],
                    'invtype'=>$val['invtype'],
                    'barcode'=>$val['itemlotnum'],
                    'nums'=>$val['outqty'],
                    'price'=>0.00,
            );
            
            if(empty($item['bn'])){
                $error_msg = '入库商品货号为空';
                return array('rsp'=>'fail', 'sub_code'=>'e0053', 'sub_message'=>$error_msg);
            }
            
            if($item['nums'] < 1){
                $error_msg = '入库商品数量不正确';
                return array('rsp'=>'fail', 'sub_code'=>'e0053', 'sub_message'=>$error_msg);
            }
            
            //商品名称
            $productInfo = $basicMaterialObj->dump(array('material_bn'=>$item['bn']), 'material_name');
            $item['name'] = $productInfo['material_name'];
            
            //check
            if(empty($productInfo)){
                $error_msg = '系统中未找到货号:'. $item['bn'];
                return array('rsp'=>'fail', 'sub_code'=>'e0053', 'sub_message'=>$error_msg);
            }
            
            $itemList[] = $item;
        }
        
        //items
        $data['items'] = $itemList;
        
        //开启事务
        $notifyObj->db->beginTransaction();
        
        //创建入库单
        $res = kernel::single('openapi_data_original_transfer')->add($data);
        if($res['rsp'] == 'fail'){
            //回滚事务

            $notifyObj->db->rollBack();
            
            $error_msg = $res['msg'];
            return array('rsp'=>'fail', 'sub_code'=>'e0053', 'sub_message'=>$error_msg);
        }
        
        //入库单号
        $iso_bn = $res['data'];
        
        //存储TTPOS打过来的数据
        $items = unserialize($sdf['items']);
        
        //unset
        unset($sdf['items']);
        
        //保存数据
        $sdf['createtime']    = time();
        $sdf['last_modified'] = time();
        $sdf['iso_bn'] = $iso_bn;
        
        //save
        $res = $notifyObj->save($sdf);
        $cin_id = $sdf['cin_id'];
        if(!$res){
            //回滚事务
            $notifyObj->db->rollBack();
            
            $error_msg = '保存TTPOS的入库单数据失败';
            return array('rsp'=>'fail', 'sub_code'=>'e0053', 'sub_message'=>$error_msg);
        }
        
        foreach ($items as $key => $val)
        {
            $val['cin_id'] = $cin_id;
            $val['invtype'] = ($val['invtype'] ? $val['invtype'] : 0);
            
            $res = $itemsyObj->save($val);
            if(!$res){
                //回滚事务
                $notifyObj->db->rollBack();
                
                $error_msg = '保存TTPOS的入库单明细失败';
                return array('rsp'=>'fail', 'sub_code'=>'e0053', 'sub_message'=>$error_msg);
            }
        }
        
        //事务提交
        $notifyObj->db->commit();
        
        return array('rsp'=>'succ', 'res' => '创建入库单成功,出入库单号：'. $iso_bn, 'data'=>'', 'iso_bn'=>$iso_bn);
    }
    
    /**
     * 入库单推送通知TTPOS
     * 
     * @param string $iso_bn
     * @param int $branch_id
     * @return bool
     */
    public function responeIostockNotify($iso_info)
    {
        $itemsObj = app::get('taoguaniostockorder')->model('iso_items');
        $notifyObj = app::get('qimen')->model('iostock_notify');
        $itemsyObj = app::get('qimen')->model('iostock_items');
        $branchLib = kernel::single('ome_branch');
        
        $iso_id    = $iso_info['iso_id'];
        $iso_bn    = $iso_info['iso_bn'];
        $branch_id = $iso_info['branch_id'];
        
        //国家编码、品牌编码
        $country_code  = app::get('ome')->getConf('custom.order.country_code');
        $brand_code    = app::get('ome')->getConf('custom.order.brand_code');
        
        //仓库类型
        $wms_id = $branchLib->getWmsIdById($branch_id);
        
        //入库单明细
        $iostock_items = array();
        $itemList    = $itemsObj->getList('iso_items_id,product_id,bn,nums,normal_num,defective_num', array('iso_id'=>$iso_id));
        foreach ($itemList as $key => $val)
        {
            $item_bn = $val['bn'];
            $iostock_items[$item_bn] = intval($val['normal_num']) + intval($val['defective_num']);
        }
        
        //转仓单信息
        $data    = $notifyObj->dump(array('iso_bn'=>$iso_bn), '*');
        if(empty($data)){
            return false;
        }
        
        $data['country']    = $country_code;
        $data['brand']      = $brand_code;
        
        //转仓单明细
        $items    = array();
        $itemList = $itemsyObj->getList('*', array('cin_id'=>$data['cin_id']));
        foreach ($itemList as $key => $val)
        {
            $item_bn = $val['plu'];
            $outqty  = $iostock_items[$item_bn];
            
            $items[] = array(
                    'invttype'=>$val['invttype'],
                    'cartoncount'=>$val['cartoncount'],
                    'plu'=>$val['plu'],
                    'itemlotnum'=>$val['itemlotnum'],
                    'outqty'=>(string)$outqty,
                    'cartonnum'=>$val['cartonnum'],
            );
        }
        
        //json_encode
        $data['items'] = json_encode($items);
        
        //推送数据
        $data['iso_bn'] = $iso_bn;
        
        //转出时间使用入库时间(2018.04.12客户发邮件要求修改)
        //$data['trfoutdate'] = date('Ymd', time());
        
        //入库单推送通知TTPOS
        $res = kernel::single('ome_event_trigger_ttpos')->iostock_notify($wms_id, $data);
        
        return $res;
    }
    
    /**
     * 日志
     *
     * @return void
     * @author 
     **/
    function _write_log($title, $original_bn, $status='succ', $params=array(), $msg='')
    {
        // 写日志
        $apilogModel = app::get('ome')->model('api_log');
        $log_id = $apilogModel->gen_id();
        
        //if ($params['task'] && $result['rsp']=='succ') $apilogModel->set_repeat($params['task'],$log_id);
        
        //kafka
        list($usec, $sec) = explode(" ", microtime());
        $end_time = $usec + $sec;
        
        $logsdf = array(
                'log_id'        => $log_id,
                'task_name'     => $title,
                'status'        => $status,
                'worker'        => '',
                'params'        => serialize($params),
                'msg'           => $msg,
                'log_type'      => '',
                'api_type'      => 'response',
                'memo'          => '',
                'original_bn'   => $original_bn,
                'createtime'    => time(),
                'last_modified' => time(),
                'msg_id'      => (string) $params['msg_id'],
                'method'      => (string) $params[0],
                'spendtime'   => 1,
        );
        
        $apilogModel->insert($logsdf);
    }
}
