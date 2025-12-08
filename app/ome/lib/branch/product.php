<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 物料仓库处理Lib
 * 
 * @version 1.0
 */
class ome_branch_product extends ome_redis
{
    var $export_name = '仓库库存';
    private $db;
    function __construct()
    {
        $this->db    = kernel::database();
    }

    // redis需要回滚的《仓冻结》
    static private $_redisFreezeRollbackList = [];
    // redis需要回滚的《仓库存》
    static private $_redisStoreRollbackList = [];
    
    // redis的《冻结/库存流水》
    static private $_redisFlow = [];

    // 初始化redis需要回滚的《仓冻结》和《仓库存》
    static public function initRedisBranch()
    {
        self::_initRedisFreezeRollbackList();
        self::_initRedisStoreRollbackList();
    }

    // 初始化redis需要回滚的《仓冻结》
    static private function _initRedisFreezeRollbackList()
    {
        self::$_redisFreezeRollbackList = [];
        return true;
    }

    // 初始化redis需要回滚的《仓库存》
    static private function _initRedisStoreRollbackList()
    {
        self::$_redisStoreRollbackList = [];
        return true;
    }

    // 追加redis需要回滚的《仓冻结》
    static private function _setRedisFreezeRollbackList($freezeRollbackReq=[])
    {
        if ($freezeRollbackReq) {
            self::$_redisFreezeRollbackList[] = $freezeRollbackReq;
        }
        return true;
    }

    // 追加redis需要回滚的《仓库存》
    static private function _setRedisStoreRollbackList($storeRollbackReq=[])
    {
        if ($storeRollbackReq) {
            self::$_redisStoreRollbackList[] = $storeRollbackReq;
        }
        return true;
    }

    // 获取redis需要回滚的《仓冻结》
    static private function _getRedisFreezeRollbackList()
    {
        return self::$_redisFreezeRollbackList;
    }

    // 获取redis需要回滚的《仓库存》
    static private function _getRedisStoreRollbackList()
    {
        return self::$_redisStoreRollbackList;
    }

    // 删除《冻结/库存流水》并且 回滚redis的《仓冻结》和《仓库存》
    static public function rollbackRedisBranch()
    {
        self::delRedisBranchFlow();
        self::freezeRollbackInRedis();
        self::storeRollbackInRedis();
    }

    // 获取redis《冻结/库存流水》的hash表名
    public function getFlowHash($type = '')
    {
        if ($type == 'freeze') {
            return sprintf('%s#branchFreezeFlow', base_shopnode::node_id('ome'));

        } elseif ($type == 'store') {
            return sprintf('%s#branchStoreFlow', base_shopnode::node_id('ome'));

        } else {
            return '';
        }
    }

    // 临时保存redis《冻结流水》或者《库存流水》
    static private function _setRedisFlow($_key, $_field, $_value)
    {
        return true;
        return true;
        return true;
        $isRedis = parent::_connectRedis();
        if (!$isRedis) {
            return true;
        }
        // 只有在事务里才去保存临时流水，否则静态变量里的数据不会删除
        if (!kernel::database()->isInTransaction()) {
            return true;
        }
        $info = parent::$stockRedis->hget($_key, $_field);

        if ($info) {
            $_value = $info . ';' . $_value;
        } else {   
            $key = $_key . '-' . $_field;
            self::$_redisFlow[$key] = [
                'flowHash'  =>  $_key,
                'flowKey'   =>  $_field,
            ];
        }
        parent::$stockRedis->hset($_key, $_field, $_value);
    }

    // 删除redis《冻结流水》和redis《库存流水》
    static public function delRedisBranchFlow()
    {
        $isRedis = parent::_connectRedis();
        if (!$isRedis) {
            return [true, 'isRedis is false'];
        }

        if (!self::$_redisFlow) {
            return [true, 'branchProduct redisFlow is []'];
        }

        foreach (self::$_redisFlow as $ro_k => $ro_v) {
            if ($ro_v['flowHash'] && $ro_v['flowKey']) {
                parent::$stockRedis->hdel($ro_v['flowHash'], $ro_v['flowKey']);
                unset(self::$_redisFlow[$ro_k]);
            }
        }
        return [true, 'succ'];
    }
     
    /*
     * 增加冻结库存
     */
    function freez($branch_id,$product_id,$nums)
    {
        //暂时没有在branch_product上使用冻结库存
        $this->chg_product_store_freeze($branch_id, $product_id, $nums, "+");
        return true;
    }
    
    /*
     * 释放冻结库存
    */
    function unfreez($branch_id,$product_id,$nums)
    {
        //暂时没有在branch_product上使用冻结库存
        return $this->chg_product_store_freeze($branch_id, $product_id, $nums, "-");
    }
     
    /*
     * 修改冻结库存
     */
    function chg_product_store_freeze($branch_id, $product_id, $num, $operator='=', $log_type='delivery')
    {
        // 改用freezeInRedis方法
        return false;
        return false;
        return false;

        $branchObj = app::get('ome')->model('branch_product');
        if ($log_type == 'negative_stock' && !$branchObj->count(array('branch_id' => $branch_id, 'product_id' => $product_id))) {
            $branch_arr = array();
            $branchLib  = kernel::single('ome_store_manage_branch');
        
            $stores                   = $branchLib->getStoreByBranchId($branch_id);
            $branch_arr['branch_id']  = $branch_id;
            $branch_arr['product_id'] = $product_id;
            $branch_arr['store_id']   = $stores['store_id'];
            $branch_arr['store_bn']   = $stores['store_bn'];
        
            $branch_arr['store']         = 0;
            $branch_arr['last_modified'] = time();
            $branchObj->insert($branch_arr);
        }

        $filter = array("branch_id=$branch_id","product_id=$product_id");

        $UpdateValues = array('last_modified='.time());

        switch($operator)
        {
            case "+":
                $UpdateValues[] = "store_freeze=IFNULL(store_freeze,0)+$num";
                if ($log_type != 'negative_stock') {
                    $filter[] = "store>=store_freeze+$num";
                }
                
                break;
            case "-":
                $UpdateValues[] = " store_freeze=IF((CAST(store_freeze AS SIGNED)-$num)>0,store_freeze-$num,0)";
                break;
            case "=":
            default:
                $UpdateValues[] = "store_freeze=$num";
                break;
        }

        $sql = 'UPDATE `' . DB_PREFIX . 'ome_branch_product` SET '.implode(',',$UpdateValues).' WHERE '.implode(' AND ', $filter);

        if($this->db->exec($sql)){
            $affect_row = $this->db->affect_row();

            // 先针对冻结库存，处理影响行数。发货时有库存减，再减冻结可能时间一样，影响行数为0，发货不做影响行数限制
            if ($operator == '+') {
                return $affect_row === 1 ? true : false;
            }

            return true;
        }else{
            return false;
        }
    }
    
    /*
     * 更新仓库库存
     * redis高并发以后已弃用，改用storeInRedis方法
    */
    function change_store($branch_id, $product_id, $num, $operator='=', $update_material=true,$negative_stock=false)
    {
        return false;
        return false;
        return false;
        
        $now = time();
        $store = '';
        $where = '';
        switch($operator){
            case "+":
                $store = "store=IFNULL(store,0)+".$num;
                break;
            case "-":
                if ($negative_stock === true) {
                    $store = "store=store-$num ";
                } else {
                    $store = " store=IF((CAST(store AS SIGNED)-$num)>0,store-$num,0) ";
                    $where .= ' AND store>=' . $num;
                }
                
                break;
            case "=":
            default:
                $store = "store=".$num;
                break;
        }
    
        $sql    = "UPDATE ".DB_PREFIX."ome_branch_product SET ".$store.",last_modified=".$now." 
                   WHERE product_id=".$product_id." AND branch_id=".$branch_id.$where;
    
        $result    = $this->db->exec($sql);
        
        if($result)
        {
            $rs = $this->db->affect_row();
            if(is_numeric($rs) && $rs > 0){
                if($update_material == false)
                {
                    return true;#不统计基础物料库存
                }
                
                return $this->count_store($product_id);
            }
        }
        return false;
    }
    
    /*
     * 统计所有此基础物料库存
     * redis库存高可用，废弃掉直接修改db库存、冻结的方法
    */
    function count_store($product_id, $branch_id=0)
    {
        return false;
        return false;
        return false;

        $mStock    = app::get('material')->model('basic_material_stock');
        
        $time    = time();
        
        $sql     = "SELECT product_id, SUM(store) AS 'store' FROM ".DB_PREFIX."ome_branch_product WHERE product_id='".$product_id."' GROUP BY product_id";
        $row     = $this->db->selectrow($sql);
        if(!$row)
        {
            $data = array(
                    'bm_id' => $product_id,
                    'store' => 0,
            );
        }
        else
        {
            $data = array(
                    'bm_id' => $row['product_id'],
                    'store' => $row['store'],
            );
        }
        $data['last_modified']            = $time;
        $data['real_store_lastmodify']    = $time;
        $data['max_store_lastmodify']     = $time;
        
        # [更新]基础物料库存
        $mStock->save($data);
        
        return true;
    }
    
    #获取基础物料对应仓库库存
    function getStoreByBranch($product_id, $branch_id)
    {
        $sql    = 'select store from '. DB_PREFIX .'ome_branch_product where product_id='.$product_id.' and branch_id='.$branch_id;
        $row    = $this->db->selectRow($sql);
        
        if($row)
        {
            return $row['store'];
        }
        else 
        {
            return false;
        }
    }
    
    #获取单仓库-多个基础物料仓库
    function getStoreListByBranch($branch_id, $product_ids)
    {
        if(empty($product_ids) || empty($branch_id) || !is_array($product_ids)) {
            return [];
        }
        $sql    = 'select product_id,store from '. DB_PREFIX .'ome_branch_product where product_id in('.implode(',', $product_ids).') and branch_id='.$branch_id;
        $rows   = $this->db->select($sql);
        
        if($rows)
        {
            $products = array();
            foreach($rows as $row)
            {
                $products[$row['product_id']] = $row['store'];
            }
            
            return $products;
        }
        else 
        {
            return [];
        }
    }
    
    #获取单仓库-多个基础物料的可用库存
    function getAvailableStore($branch_id, $product_ids)
    {
        $basicMStockFreezeLib  = kernel::single('material_basic_material_stock_freeze');
        
        $sql    = 'select product_id, branch_id, store,store_freeze from '. DB_PREFIX .'ome_branch_product where product_id in('.implode(',', $product_ids).') and branch_id='.$branch_id;
        $rows   = $this->db->select($sql);
        
        if($rows)
        {
            $products = array();
            foreach($rows as $row)
            {
                //根据仓库ID、基础物料ID获取该物料仓库级的预占
                $row['store_freeze']    = $basicMStockFreezeLib->getBranchFreeze($row['product_id'], $row['branch_id']);
                
                $products[$row['product_id']] = $row['store'] - $row['store_freeze'];
            }
    
            return $products;
        }
        else 
        {
            return false;
        }
    }
    
    #获取单仓库-单个基础物料中的可用库存
    function get_available_store($branch_id, $product_id)
    {
        $basicMStockFreezeLib  = kernel::single('material_basic_material_stock_freeze');
        $bpModel   = app::get('ome')->model('branch_product');
        
        $branch    = $bpModel->getList('store, store_freeze',array('product_id'=>$product_id,'branch_id'=>$branch_id), 0, 1);
        
        //根据仓库ID、基础物料ID获取该物料仓库级的预占
        $branch[0]['store_freeze']    = $basicMStockFreezeLib->getBranchFreeze($product_id, $branch_id);
        
        return $branch[0]['store'] - $branch[0]['store_freeze'];
    }
    
    #查询对应库存总数
    function countBranchProduct($product_id, $column='safe_store')
    {
        $sql    = "SELECT SUM($column) AS 'total' FROM ". DB_PREFIX ."ome_branch_product WHERE product_id = $product_id ";
        $count  = $this->db->selectrow($sql);
        
        return $count['total'];
    }
    
    /**
     * 初始化库存数量为NULL的货品
     */
    public function initNullStore($product_id, $branch_id)
    {
        if($product_id)
        {
            if($branch_id) {
                $sql = "UPDATE ". DB_PREFIX ."ome_branch_product SET store=0 WHERE branch_id='" . $branch_id . "' AND product_id='" . $product_id ."' AND ISNULL(store) LIMIT 1";
            }else{
                $sql = "UPDATE ". DB_PREFIX ."ome_branch_product SET store=0 WHERE product_id='" . $product_id ."' AND ISNULL(store) LIMIT 1";
            }
            
            return $this->db->exec($sql);
        }
        else
        {
            return false;
        }
    }
    
    /*------------------------------------------------------ */
    //-- 以下是不常用方法
    /*------------------------------------------------------ */
    /* 减仓库表库存
     * 备注：货位库存相关方法，可以删除此方法
    * \app\purchase\model\returned\purchase.php 中使用
    */
    function Cut_store($adata)
    {
        $libBranchProductPos    = kernel::single('ome_branch_product_pos');
        
        foreach($adata['items'] as $k=>$v)
        {
            $libBranchProductPos->change_store($adata['branch_id'],$v['product_id'],$v['pos_id'],$v['num'],'-');
        }
    }
    
    /*
     * 备注：货位库存相关方法，可以删除此方法
    * \app\purchase\model\appropriation.php 中使用
    */
    function operate_store($adata,$operate)
    {
        $libBranchProductPos    = kernel::single('ome_branch_product_pos');
    
        if($operate=='add')
        {
            $libBranchProductPos->change_store($adata['branch_id'],$adata['product_id'],$adata['pos_id'],$adata['num'],'+');
        }
        else if($operate=='lower')
        {
            $libBranchProductPos->change_store($adata['branch_id'],$adata['product_id'],$adata['pos_id'],$adata['num'],'-');
        }
    
    }
    
    /*
     * 更改在途库存
    */
    function change_arrive_store($branch_id, $product_id, $num, $type='+')
    {
        $now = time();
        $store = "";
        switch($type)
        {
            case "+":
                $store = "arrive_store=IFNULL(arrive_store,0)+".$num;
                break;
            case "-":
                $store = " arrive_store=IF((CAST(arrive_store AS SIGNED)-$num)>0,arrive_store-$num,0) ";
                break;
            case "=":
            default:
                $store = "arrive_store=".$num;
                break;
        }
        $branch_pro = $this->db->selectrow("SELECT arrive_store FROM sdb_ome_branch_product WHERE product_id=".$product_id." AND branch_id=".$branch_id."");
        if($branch_pro){
            $sql   = "UPDATE ".DB_PREFIX."ome_branch_product SET ".$store." WHERE product_id=".$product_id." AND branch_id=".$branch_id;
            $rs    = $this->db->exec($sql);
        }else{
            if($type == '-' || $num < 0) {
                return false;
            }

            $branchLib = kernel::single('ome_store_manage_branch');

            $stores = $branchLib->getStoreByBranchId($branch_id);
            $bp = array(
                'branch_id' => $branch_id,
                'product_id' => $product_id,
                'store' => 0,
                'store_freeze' => 0,
                'last_modified' => time(),
                'arrive_store' => $num,
                'safe_store' => 0,
                'store_id'  =>  $stores['store_id'],
                'store_bn'  =>  $stores['store_bn'],
            );
            $obranch_product = app::get('ome')->model('branch_product');
            $rs = $obranch_product->insert($bp);
        }
    
        return $rs;
    }
    
    /*
     * 备注：货位相关方法，可以删除此方法
    * \app\purchase\controller\admin\stock.php
    */
    function Get_pos_id($branch_id,$store_position)
    {
        $obranch_pos = app::get('ome')->model('branch_pos');
    
        $pos    = $obranch_pos->dump(array('branch_id'=>$branch_id,'store_position'=>$store_position),'pos_id');
    
        return $pos['pos_id'];
    }
    
    /*
     * 获取货品在对应仓库中的库存
    *
    * @param int $product_id 货品id
    *
    * @return array
    */
    function get_branch_store($product_id)
    {
        $basicMStockFreezeLib  = kernel::single('material_basic_material_stock_freeze');
        
        $ret = array();
        $branch_product = $this->db->select("SELECT * FROM sdb_ome_branch_product WHERE product_id=".intval($product_id));
        
        if($branch_product)
        {
            foreach($branch_product as $v){
                
                //根据仓库ID、基础物料ID获取该物料仓库级的预占
                $v['store_freeze']    = $basicMStockFreezeLib->getBranchFreeze($v['product_id'], $v['branch_id']);
                
                //将订单确认拆分的仓库货品数量由store改为store_freeze //因冻结存在负数情况，会出现a−(−b)=a+b的情况
                $store = max(0,$v['store']-abs($v['store_freeze']));
                $ret[$v['branch_id']] = $store;
            }
        }
        return $ret;
    }
    
    /**
     * 分仓查询可售库存
     * @Author: xueding
     * @Vsersion: 2022/7/11 下午9:16
     * @param $product_ids
     * @param $branch_ids
     * @return array
     */
    function getBranchStoreList($product_ids,$branch_ids)
    {
        $ret = array();
        $branch_product = app::get('ome')->model('branch_product')->getList('*',array('product_id'=>$product_ids,'branch_id'=>$branch_ids));
    
        $sql = "SELECT sum(num) as total,branch_id,bm_id FROM sdb_material_basic_material_stock_freeze WHERE bm_id IN (".implode(',',$product_ids).") AND obj_type='1' AND branch_id IN (".implode(',',$branch_ids).") GROUP BY branch_id,bm_id";
        $branchStoreFree = app::get('material')->model('basic_material_stock_freeze')->db->select($sql);
        $branchStoreFreeS = ome_func::filter_by_value($branchStoreFree,'bm_id');

        if($branch_product)
        {
            foreach($branch_product as $v){
                
                //根据仓库ID、基础物料ID获取该物料仓库级的预占
                $branchStoreFreeNum = array_column($branchStoreFreeS[$v['product_id']],null,'branch_id');
                $v['store_freeze'] = $branchStoreFreeNum[$v['branch_id']]['total'];
                
                //将订单确认拆分的仓库货品数量由store改为store_freeze
                $store = max(0,$v['store']-$v['store_freeze']);
                $ret[$v['product_id']][$v['branch_id']] = $store;
            }
        }
        return $ret;
    }
    
    /*
    * 根据仓库ID和货品ID 获取相应的库存数量
    *
    * @param int $product_id 货品id
    *
    * @return array
    */
    function get_product_store($branch_id, $product_id)
    {
        $basicMStockFreezeLib  = kernel::single('material_basic_material_stock_freeze');
        
        $sql    = "SELECT * FROM sdb_ome_branch_product WHERE product_id=".intval($product_id)." AND branch_id=".intval($branch_id);
        $branch_product = $this->db->selectrow($sql);
        
        //根据仓库ID、基础物料ID获取该物料仓库级的预占
        $branch_product['store_freeze']    = $basicMStockFreezeLib->getBranchFreeze($product_id, $branch_id);
        
        $sale_store     = $branch_product['store'] - $branch_product['store_freeze'];
        
        return $sale_store;
    }
    
    /**
     * [人工预占库存]订单创建
     * @todo场景：平台推送的订单明细中指定仓库编码进行发货,需要提前进行仓库级库存冻结
     * 
     * @param array $orderItems
     * @param int $branch_id
     * @param string $error_msg
     * @return bool
     */
    public function order_artificial_freeze($orderItems, $branch_id, &$error_msg='')
    {
        $artFreezeObj = app::get('material')->model('basic_material_stock_artificial_freeze');
        $freeGroupObj = app::get('material')->model('basic_material_stock_artificial_freeze_group');
        $operLogObj = app::get('ome')->model('operation_log');
        
        $storeManageLib = kernel::single('ome_store_manage');
        
        //check
        if(empty($orderItems) || empty($branch_id)){
            $error_msg = '没有可操作的数据';
            return false;
        }
        
        //system账号信息
        $opInfo = kernel::single('ome_func')->get_system();
        
        //人工库存预占组
        $group_name = '指定仓发货';
        $groupInfo = $freeGroupObj->dump(array('group_name'=>$group_name), 'group_id');
        $group_id = intval($groupInfo['group_id']);
        if(empty($group_id)){
            $sdf = array(
                    'group_name' => '订单创建',
            );
            $freeGroupObj->insert($sdf);
            $group_id = $freeGroupObj->db->lastInsertId();
        }
        
        //params
        $params = array();
        $params['node_type'] = 'artificialFreeze';
        $storeManageLib->loadBranch(array('branch_id'=>$branch_id));
        
        //order_items
        foreach ($orderItems as $key => $val)
        {
            $sdf = array(
                    'branch_id' => $branch_id,
                    'bm_id' => $val['product_id'],
                    'freeze_num' => $val['nums'],
                    'freeze_reason' => '订单指定仓库发货',
                    'freeze_time' => time(),
                    'update_modified' => time(),
                    'op_id' => $opInfo['op_id'],
                    'original_bn' => $val['order_bn'],
                    'original_type' => 'create_order',
                    'group_id' => $group_id,
                    'shop_id' => $val['shop_id'], //货品预占流水记录上的shop_id
                    'bn' => $val['bn'],
            );
            $result = $artFreezeObj->insert($sdf);
            if(!$result){
                $error_msg = sprintf('插入人工预占流水失败,货号：%s', $val['bn']);
                return false;
            }
            $bmsaf_id = $sdf['bmsaf_id'];
            
            //log
            $operLogObj->write_log('add_artificial_freeze@ome', $bmsaf_id, '订单指定仓库发货,新增人工库存预占记录');
            
            //货品预占信息
            $sdf['obj_id'] = $bmsaf_id;
            $params['params'][] = $sdf;
        }
        
        //人工库存预占
        $result = $storeManageLib->processBranchStore($params, $error_msg);
        if(!$result){
            $error_msg = '人工库存预占失败';
            return false;
        }
        
        return true;
    }
    
    /**
     * [释放人工预占库存]订单取消
     *
     * @param array $orderItems
     * @param int $branch_id
     * @param string $error_msg
     * @return bool
     */
    public function order_artificial_unfreeze($orderInfo, $branch_id, &$error_msg='')
    {
        $artFreezeObj = app::get('material')->model('basic_material_stock_artificial_freeze');
        $operLogObj = app::get('ome')->model('operation_log');
        $storeManageLib = kernel::single('ome_store_manage');
        
        $original_bn = $orderInfo['order_bn'];
        
        //check
        if(empty($original_bn)){
            $error_msg = '没有可释放的数据';
            return false;
        }
        
        if(empty($branch_id)){
            $error_msg = '没有指定仓库';
            return false;
        }
        
        //system账号信息
        $opInfo = kernel::single('ome_func')->get_system();
        
        //filter
        $filter = array (
                'original_bn' => $original_bn, //订单号
                'original_type' => 'create_order', //预占类型
        );
        $artFreezeList = $artFreezeObj->getList('*', $filter);
        if(empty($artFreezeList)){
            $error_msg = '没有可释放的预占记录';
            return false;
        }
        
        //params
        $params = array();
        $params['node_type'] = 'artificialUnfreeze';
        $storeManageLib->loadBranch(array('branch_id'=>$branch_id));

        $trans = kernel::database()->beginTransaction();
        
        //unfreeze
        foreach ($artFreezeList as $key => $val)
        {
            $bmsaf_id = $val['bmsaf_id'];
            
            //check
            if($branch_id && $val['branch_id'] != $branch_id){
                continue; //指定仓库释放
            }
            
            if($val['status'] != '1'){
                continue; //已释放的则跳过
            }
            
            //update
            $updateData = array('status'=>'2', 'op_id'=>$opInfo['op_id'], 'update_modified'=>time());
            $affect_rows = $artFreezeObj->update($updateData, array('bmsaf_id'=>$bmsaf_id));
            if ($affect_rows !== 1) {
                $error_msg = '货号ID：'. $val['bm_id'] .' 释放预占失败';
                kernel::database()->rollBack();
                return false;
            }
            
            //log
            $operLogObj->write_log('add_artificial_freeze@ome', $bmsaf_id, '取消订单,释放人工库存预占记录');
            
            //货品预占信息
            $val['obj_id'] = $bmsaf_id;
            
            //不用释放冻结库存,否则会返回“货品冻结释放失败!”
            $val['not_unfreeze'] = true;
            
            $params['params'][] = $val;
        }
        
        //释放人工库存预占
        $result = $storeManageLib->processBranchStore($params, $error_msg);
        if(!$result){
            $error_msg = '释放人工库存预占失败';
            kernel::database()->rollBack();
            return false;
        }
        kernel::database()->commit($trans);
        
        return true;
    }

    
    /**
     * 扣减冻结
     *
     * @params array $items,
     *         示例：[['branch_id'=>1,'product_id'=>1,'quantity'=>1,'bn'=>'test001','obj_type'=>'对象类型', 'bill_type'=>'业务类型','obj_id'=>'对象ID','obj_bn'=>'业务单号','log_type'=>'允许冻结大于库存传negative_stock'],['branch_id'=>1,'product_id'=>2,'quantity'=>1,'bn'=>'test002','obj_type'=>'对象类型', 'bill_type'=>'业务类型','obj_id'=>'对象ID','obj_bn'=>'业务单号','log_type'=>'']]
     * @params string $opt，可选值: +、-
     * @return array
     */
    static public function freezeInRedis($items, $opt, $source='')
    {
        $node_id = base_shopnode::node_id('ome');
     
        if (!in_array($opt, ['+', '-'])){
            return [false, '仓冻结操作符错误：'.$opt];
        }
       
        if ($opt == '+') {
            $ratio = 1;
            $lua = file_get_contents(app::get('ome')->app_dir.'/lua/branch_store_freeze_incr.lua');
            $title = '增加仓冻结';
        } else {
            $ratio = -1;
            $lua = file_get_contents(app::get('ome')->app_dir.'/lua/branch_store_freeze_decr.lua');
            $title = '扣减仓冻结';
        }

        
        $flowHash = kernel::single('ome_branch_product')->getFlowHash('freeze');

        // todo 需要加参数验证
        $fItems = [];
        foreach ($items as $item) {
            if (!$item['branch_id']) {
                return [false, '参数错误-branch_id空：'.json_encode($item)];
            } elseif (!$item['product_id']) {
                return [false, '参数错误-bm_id空：'.json_encode($item)];
            } elseif (!$item['quantity']) {
                $bm_bn = self::getbmBn($item['product_id']);
                return [false, '参数错误-quantity空[bm_bn=>'.$bm_bn.']：'.json_encode($item)];
            }
            
            $key = sprintf('%s#stock:%s:%s', $node_id, $item['branch_id'], $item['product_id']);
            
            // 扣减冻结为负数
            $item['quantity'] = abs($item['quantity']) * $ratio;
            
            $flowKey = sprintf('%s#%s#%s#%s#%s', $item['obj_type'], $item['bill_type'], $item['obj_id'], $opt, time());

            if (isset($fItems[$key])) {
                $fItems[$key]['quantity'] += $item['quantity'];
                if (isset($item['log_type']) && $item['log_type'] == 'negative_stock') {
                    $fItems[$key]['log_type'] = 'negative_stock';
                }
            } else {
                if (!isset($item['log_type'])) {
                    $item['log_type'] = '';
                }
                $fItems[$key] = $item;
            }

            // 查库存数，用于 增加仓冻结 的时候冻结数如果超过库存数，redis返回报错
            $fItems[$key]['store_quantity'] = 0;
            if ($opt == '+') {                
                $branchProductStore = kernel::database()->selectrow("SELECT store FROM sdb_ome_branch_product WHERE branch_id = " . $item['branch_id'] . " AND product_id = ".$item['product_id']);
                $fItems[$key]['store_quantity'] = $branchProductStore ? $branchProductStore['store'] : 0;
            }
        }

        if (!$fItems) {
            return [true, '数据为空'];
        }

        // 根据product_id对数组进行升序排序
        uasort($fItems, function($a, $b) { 
            return $a['product_id'] - $b['product_id'];
        });
        
        $flowValue = [];
        foreach ($fItems as $_k => $_v) {
            $tmp = [
                0  =>  $_v['branch_id'],
                1  =>  $_v['product_id'],
                2  =>  abs($_v['quantity']),
                // 3  =>  time(),
            ];
            $flowValue[] = implode(':', $tmp);
        }
        $flowValue = implode(';', $flowValue);

        $isRedis = parent::_connectRedis();
        $code = null;

        // 1. 更新Redis
        if ($isRedis) {

            $args = array_merge(array_keys($fItems), array_column($fItems, 'quantity'), array_column($fItems, 'store_quantity'), array_column($fItems, 'log_type'));

            list($code, $msg, $sku_id) = $rr = parent::$stockRedis->eval($lua, $args, count($fItems));

            if ($code === false) {
                return [false, $title.'失败：脚本异常'];
            }
            
            if ($code > 100) {
                $bm_bn = $fItems[$sku_id]['bn'] ? $fItems[$sku_id]['bn'] : self::getbmBn($fItems[$sku_id]['product_id']);
                return [false, '【'.$bm_bn.'】'.$title.'失败：'.$msg];
            }

            // 存redis流水
            self::_setRedisFlow($flowHash, $flowKey, $flowValue);
        }
        
        // 2. 如果Redis不存在，更新数据库
        if (!$isRedis || $code == 100){
            $db = kernel::database();
            foreach ($fItems as $key => $item) {
                /*
                $sql = "UPDATE `" . DB_PREFIX . "ome_branch_product`
                        SET `last_modified`=".time().",`store_freeze`=IF((CAST(`store_freeze` AS SIGNED)+{$item['quantity']})>0,`store_freeze`+{$item['quantity']},0)
                        WHERE `branch_id`={$item['branch_id']} AND `product_id`={$item['product_id']}";
                */
    
                $branchObj = app::get('ome')->model('branch_product');
                if ($item['log_type'] == 'negative_stock' && !$branchObj->count(array('branch_id' => $item['branch_id'], 'product_id' => $item['product_id']))) {
                    $branch_arr = array();
                    $branchLib  = kernel::single('ome_store_manage_branch');
                    $stores                   = $branchLib->getStoreByBranchId($item['branch_id']);
                    $branch_arr['branch_id']  = $item['branch_id'];
                    $branch_arr['product_id'] = $item['product_id'];
                    $branch_arr['store_id']   = $stores['store_id'];
                    $branch_arr['store_bn']   = $stores['store_bn'];
                    $branch_arr['store']         = 0;
                    $branch_arr['last_modified'] = time();
                    $branchObj->insert($branch_arr);
                }

                // 修改成允许store_freeze减成负数
                $sql = "UPDATE `" . DB_PREFIX . "ome_branch_product`
                        SET `last_modified`=".time().",`store_freeze`=`store_freeze`+{$item['quantity']}
                        WHERE `branch_id`={$item['branch_id']} AND `product_id`={$item['product_id']}";

                // 如果是增加冻结需要判断库存是否足够
                if ($item['quantity'] > 0 && $item['log_type'] != 'negative_stock'){
                   $sql .= " AND `store` >= `store_freeze` + {$item['quantity']}" ;
                }

                if (!$db->exec($sql)){
                    $bm_bn = $item['bn'] ? $item['bn'] : self::getbmBn($item['product_id']);
                    return [false, '【'.$bm_bn.'】'.$title.'失败：'.$db->errorinfo()];
                }

                if (1 !== $db->affect_row()){
                    $bm_bn = $item['bn'] ? $item['bn'] : self::getbmBn($item['product_id']);
                    return [false, '【'.$bm_bn.'】'.$title.'失败：仓冻结不足'];
                }
            }
        }

        // 3. 数据库更新成功，做SET
        if ($isRedis && $code == 100) {
            $args = [];

            foreach ($fItems as $key => $item) {
                $row = $db->selectrow("SELECT `store`,`store_freeze` FROM `" . DB_PREFIX . "ome_branch_product` WHERE `branch_id`={$item['branch_id']} AND `product_id`={$item['product_id']}");
                if (!$row) {
                    continue;
                }
                $args[] = $row['store'].','.$row['store_freeze'];
            }

            if ($args) {
                $lua = file_get_contents(app::get('ome')->app_dir.'/lua/branch_store_set.lua');

                $args = array_merge(array_keys($fItems), $args);

                parent::$stockRedis->eval($lua, $args, count($fItems));
            }
        }

        // 4. 如果走队列并没有更新数据库生成冻结流水，方便后台任务读取并更新
        if ($isRedis && $code != 100){
            $freezeQueueMdl = app::get('ome')->model('branch_freeze_queue');
            foreach ($fItems as $key => $item) {
                $queue = [
                    'branch_id'  => $item['branch_id'],
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'obj_type'   => $item['obj_type'],
                    'bill_type'  => $item['bill_type'],
                    'obj_id'     => $item['obj_id'],
                    'obj_bn'     => $item['obj_bn'],
                    'source'     => $source,
                    // 'obj_item_id'=> $item['obj_item_id'],
                ];
                
                $freezeQueueMdl->insert($queue);
            }
        }

        // 业务roback以后，需要对redis回滚，freezeRollbackReq是需要回滚的参数。
        if ($isRedis) {
            $freezeRollbackReq = [
                'items'     =>  $items,
                'opt'       =>  $opt == '+' ? '-' : '+', // 回滚需要逆向
                'source'    =>  $source, // __CLASS__.'::'.__FUNCTION__
            ];
            self::_setRedisFreezeRollbackList($freezeRollbackReq);
        }

        return [true, $title.'成功'];
    }
    

    /**
     * 扣减库存
     *
     * @params array $items,
     *         示例：[['branch_id'=>1,'product_id'=>1,'quantity'=>1,'bn'=>'test001','iostock_bn'=>'出入库单号','negative_stock'=>true/false],['branch_id'=>1,'product_id'=>2,'quantity'=>1,'bn'=>'test002','iostock_bn'=>'出入库单号']]
     *         negative_stock 是否允许负库存，true/false
     * @params string $opt，可选值: +、-
     * @return void
     */
    static public function storeInRedis($items, $opt, $source='')
    {
        // redis库存高可用，迭代掉本类的change_store方法
        $node_id = base_shopnode::node_id('ome');
     
        if (!in_array($opt, ['+', '-'])){
            return [false, '仓库存操作符错误：'.$opt];
        }
       
        if ($opt == '+') {
            $ratio = 1;
            $lua = file_get_contents(app::get('ome')->app_dir.'/lua/branch_store_incr.lua');
            $title = '增加仓库存';
        } else {
            $ratio = -1;
            $lua = file_get_contents(app::get('ome')->app_dir.'/lua/branch_store_decr.lua');
            $title = '扣减仓库存';
        }

        $flowHash = kernel::single('ome_branch_product')->getFlowHash('store');
        
        // todo 需要加参数验证
        $fItems = [];
        foreach ($items as $item) {
            if (!$item['branch_id']) {
                return [false, '参数错误-branch_id空：'.json_encode($item)];
            } elseif (!$item['product_id']) {
                return [false, '参数错误-bm_id空：'.json_encode($item)];
            } elseif (!$item['quantity']) {
                $bm_bn = self::getbmBn($item['product_id']);
                return [false, '参数错误-quantity空[bm_bn=>'.$bm_bn.']：'.json_encode($item)];
            }
            
            $key = sprintf('%s#stock:%s:%s', $node_id, $item['branch_id'], $item['product_id']);

            $flowKey = sprintf('%s#%s#%s', $item['iostock_bn'], $opt, time());
            
            // 扣减冻结为负数
            $item['quantity'] = abs($item['quantity']) * $ratio;
            
            if (isset($fItems[$key])) {
                $fItems[$key]['quantity'] += $item['quantity'];
                if ($item['negative_stock'] === true){
                    $fItems[$key]['negative_stock'] = 'true';
                }
            } else {
                $fItems[$key] = $item;
                $fItems[$key]['negative_stock'] = ($item['negative_stock'] && $item['negative_stock'] === true) ? 'true' : 'false';
            }
        }

        if (!$fItems) {
            return [true, '数据为空'];
        }

        // 根据product_id对数组进行升序排序
        uasort($fItems, function($a, $b) { 
            return $a['product_id'] - $b['product_id'];
        });

        $flowValue = [];
        foreach ($fItems as $_k => $_v) {
            $tmp = [
                0  =>  $_v['branch_id'],
                1  =>  $_v['product_id'],
                2  =>  abs($_v['quantity']),
                // 3  =>  time(),
            ];
            $flowValue[] = implode(':', $tmp);
        }
        $flowValue = implode(';', $flowValue);

        // 1. 更新数据库        
        $db = kernel::database();
        foreach ($fItems as $key => $item) {

            $where = "`branch_id`={$item['branch_id']} AND `product_id`={$item['product_id']}";
    
            if ($opt == '+') {
                $sql_set = "`store`=IFNULL(store,0)+{$item['quantity']}";
            } else {
                if ($item['negative_stock'] == 'true') {
                    $sql_set = "`store`=`store`+{$item['quantity']}";
                } else {
                    $sql_set = "`store`=IF((CAST(`store` AS SIGNED)+{$item['quantity']})>0,`store`+{$item['quantity']},0)";
                    $where   .= " AND `store` + {$item['quantity']} >=0";
                }
            }
            $sql_set .= ",`last_modified`=".time();

            /*
            // 扣减库存的时候需要把冻结一起扣减掉
            if ($item['quantity'] < 0){
                $sql_set .= ", `store_freeze`=IF((CAST(`store_freeze` AS SIGNED)+{$item['quantity']})>0,`store_freeze`+{$item['quantity']},0)";
            }
            */
            $sql = "UPDATE `" . DB_PREFIX . "ome_branch_product` SET " . $sql_set . " WHERE " . $where;

            if (!$db->exec($sql)){
                $bm_bn = $item['bn'] ? $item['bn'] : self::getbmBn($item['product_id']);
                return [false, '【'.$bm_bn.'】'.$title.'失败：'.$db->errorinfo()];
            }

            if (1 !== $db->affect_row()){
                $bm_bn = $item['bn'] ? $item['bn'] : self::getbmBn($item['product_id']);
                return [false, '【'.$bm_bn.'】'.$title.'失败：仓库存不足'];
            }
        }
        
        $isRedis = parent::_connectRedis();
        $code = null;

        // 1. 更新Redis
        if ($isRedis) {

            $args = array_merge(array_keys($fItems), array_column($fItems, 'quantity'), array_column($fItems, 'negative_stock'));

            list($code, $msg, $sku_id) = $rr = parent::$stockRedis->eval($lua, $args, count($fItems));

            if ($code === false) {
                return [false, $title.'失败：脚本异常'];
            }
            
            if ($code > 100) {
                $bm_bn = $fItems[$sku_id]['bn'] ? $fItems[$sku_id]['bn'] : self::getbmBn($fItems[$sku_id]['product_id']);
                return [false, '【'.$bm_bn.'】'.$title.'失败：'.$msg];
            }

            // 存redis流水
            self::_setRedisFlow($flowHash, $flowKey, $flowValue);
        }
        
        // // 2. 如果Redis不存在，更新数据库
        // if (!$isRedis || $code == 100){
        //     $db = kernel::database();
        //     foreach ($fItems as $key => $item) {

        //         $sql_set = "`last_modified`=".time().",`store`=IF((CAST(`store` AS SIGNED)+{$item['quantity']})>0,`store`+{$item['quantity']},0)";

        //         /*
        //         // 扣减库存的时候需要把冻结一起扣减掉
        //         if ($item['quantity'] < 0){
        //             $sql_set .= ", `store_freeze`=IF((CAST(`store_freeze` AS SIGNED)+{$item['quantity']})>0,`store_freeze`+{$item['quantity']},0)";
        //         }
        //         */
        //         $sql = "UPDATE `" . DB_PREFIX . "ome_branch_product`
        //                 SET " . $sql_set . "
        //                 WHERE `branch_id`={$item['branch_id']} AND `product_id`={$item['product_id']}";

        //         // 如果是扣减库存需要判断库存是否足够
        //         if ($item['quantity'] < 0){
        //            $sql .= " AND `store` + {$item['quantity']} >=0" ;
        //         }

        //         if (!$db->exec($sql)){
        //             return [false, '【'.$item['bn'].'】'.$title.'失败：'.$db->errorinfo()];
        //         }

        //         if (1 !== $db->affect_row()){
        //             return [false, '【'.$item['bn'].'】'.$title.'失败：仓库存不足'];
        //         }
        //     }
        // }

        // 3. Redis不存在，做SET
        if ($isRedis && $code == 100) {
            $args = [];
            foreach ($fItems as $key => $item) {
                $row = $db->selectrow("SELECT `store`,`store_freeze` FROM `" . DB_PREFIX . "ome_branch_product` WHERE `branch_id`={$item['branch_id']} AND `product_id`={$item['product_id']}");
                
                $args[] = $row['store'].','.$row['store_freeze'];
            }
            $lua = file_get_contents(app::get('ome')->app_dir.'/lua/branch_store_set.lua');

            $args = array_merge(array_keys($fItems), $args);

            parent::$stockRedis->eval($lua, $args, count($fItems));
        }

        // // 4. 如果走队列并没有更新数据库生成库存变更流水，方便后台任务读取并更新
        // if ($isRedis && $code != 100){
        //     $stockQueueMdl = app::get('ome')->model('branch_stock_queue');
        //     foreach ($fItems as $key => $item) {
        //         $queue = [
        //             'branch_id'  => $item['branch_id'],
        //             'product_id' => $item['product_id'],
        //             'quantity'   => $item['quantity'],
        //             'iostock_bn' => $item['iostock_bn'],
        //         ];
                
        //         $stockQueueMdl->insert($queue);
        //     }
        // }

        // 业务rollback以后，需要对redis回滚，storeRollbackReq是需要回滚的参数。
        if ($isRedis) {
            $storeRollbackReq = [
                'items'     =>  $items,
                'opt'       =>  $opt == '+' ? '-' : '+', // 回滚需要逆向
                'source'    =>  $source, // __CLASS__.'::'.__FUNCTION__
            ];
            self::_setRedisStoreRollbackList($storeRollbackReq);
        }

        return [true, $title.'成功'];
    }


    /**
     * redis冻结回滚
     */
    static public function freezeRollbackInRedis()
    {
        if (!self::_getRedisFreezeRollbackList()) {
            return [true, 'branchProduct redisFreezeRollbackList is []'];
        }
        foreach (self::_getRedisFreezeRollbackList() as $ro_value) {
            $items  = $ro_value['items'];
            $opt    = $ro_value['opt'];
            self::_freezeRollbackInRedis($items, $opt);
        }

        self::_initRedisFreezeRollbackList();
        return [true, 'redis仓库存回滚成功'];
    }


    /**
     * redis冻结回滚
     *
     * @params array $items,
     *         示例：[['branch_id'=>1,'product_id'=>1,'quantity'=>1,'bn'=>'test001'],['branch_id'=>1,'product_id'=>2,'quantity'=>1,'bn'=>'test002']]
     * @params string $opt，可选值: +、-
     * @return void
     */
    static private function _freezeRollbackInRedis($items, $opt)
    {
        $node_id = base_shopnode::node_id('ome');

        if (!in_array($opt, ['+', '-'])){
            return [false, '(回滚)仓冻结操作符错误：'.$opt];
        }

        if ($opt == '+') {
            $ratio = 1;
            $lua = file_get_contents(app::get('ome')->app_dir.'/lua/branch_store_freeze_incr.lua');
            $title = '(回滚)增加仓冻结';
        } else {
            $ratio = -1;
            $lua = file_get_contents(app::get('ome')->app_dir.'/lua/branch_store_freeze_decr.lua');
            $title = '(回滚)扣减仓冻结';
        }

        // todo 需要加参数验证
        $fItems = [];
        foreach ($items as $item) {
            if (!$item['branch_id']) {
                return [false, '(回滚)参数错误-branch_id空：'.json_encode($item)];
            } elseif (!$item['product_id']) {
                return [false, '(回滚)参数错误-bm_id空：'.json_encode($item)];
            } elseif (!$item['quantity']) {
                $bm_bn = self::getbmBn($item['product_id']);
                return [false, '(回滚)参数错误-quantity空[bm_bn=>'.$bm_bn.']：'.json_encode($item)];
            }
            
            $key = sprintf('%s#stock:%s:%s', $node_id, $item['branch_id'], $item['product_id']);

            // 扣减冻结为负数
            $item['quantity'] = abs($item['quantity']) * $ratio;
            
            if (isset($fItems[$key])) {
                $fItems[$key]['quantity'] += $item['quantity'];
            } else {
                $fItems[$key] = $item;
            }
        }

        $isRedis = parent::_connectRedis();
        $code = null;

        if (!$isRedis) {
            return [true, '冻结回滚，连接redis失败'];
        }

        // 1. 更新Redis
        if ($isRedis) {
            $args = array_merge(array_keys($fItems), array_column($fItems, 'quantity'));

            list($code, $msg, $sku_id) = $rr = parent::$stockRedis->eval($lua, $args, count($fItems));

            if ($code === false) {
                return [false, $title.'失败：脚本异常'];
            }
            
            if ($code > 100) {
                $bm_bn = $fItems[$sku_id]['bn'] ? $fItems[$sku_id]['bn'] : self::getbmBn($fItems[$sku_id]['product_id']);
                return [false, '(回滚)'.$bm_bn.'】'.$title.'失败：'.$msg];
            }
        }

        /* // 不能做set处理，因为调用redis的rollback的地方有的在db->rollback之前有的在之后
        // 3. 数据库更新成功，做SET
        if ($isRedis && $code == 100) {
            $args = [];
            foreach ($fItems as $key => $item) {
                $row = $db->selectrow("SELECT `store`,`store_freeze` FROM `" . DB_PREFIX . "ome_branch_product` WHERE `branch_id`={$item['branch_id']} AND `product_id`={$item['product_id']}");
                
                $args[] = $row['store'].','.$row['store_freeze'];
            }
            $lua = file_get_contents(app::get('ome')->app_dir.'/lua/branch_store_set.lua');

            $args = array_merge(array_keys($fItems), $args);

            parent::$stockRedis->eval($lua, $args, count($fItems));
        }
        */

        return [true, $title.'成功'];
    }


    /**
     * redis库存回滚
     */
    static public function storeRollbackInRedis()
    {
        if (!self::_getRedisStoreRollbackList()) {
            return [true, 'branchProduct redisStoreRollbackList is []'];
        }
        foreach (self::_getRedisStoreRollbackList() as $ro_value) {
            $items  = $ro_value['items'];
            $opt    = $ro_value['opt'];
            self::_storeRollbackInRedis($items, $opt);
        }

        self::_initRedisStoreRollbackList();
        return [true, 'redis仓冻结回滚成功'];
    }


    /**
     * redis库存回滚
     *
     * @params array $items,
     *         示例：[['branch_id'=>1,'product_id'=>1,'quantity'=>1,'bn'=>'test001'],['branch_id'=>1,'product_id'=>2,'quantity'=>1,'bn'=>'test002']]
     * @params string $opt，可选值: +、-
     * @return void
     */
    static private function _storeRollbackInRedis($items, $opt)
    {
        $node_id = base_shopnode::node_id('ome');
     
        if (!in_array($opt, ['+', '-'])){
            return [false, '(回滚)仓库存操作符错误：'.$opt];
        }
       
        if ($opt == '+') {
            $ratio = 1;
            $lua = file_get_contents(app::get('ome')->app_dir.'/lua/branch_store_incr.lua');
            $title = '(回滚)增加仓库存';
        } else {
            $ratio = -1;
            $lua = file_get_contents(app::get('ome')->app_dir.'/lua/branch_store_decr.lua');
            $title = '(回滚)扣减仓库存';
        }

        
        // todo 需要加参数验证
        $fItems = [];
        foreach ($items as $item) {
            if (!$item['branch_id']) {
                return [false, '(回滚)参数错误-branch_id空：'.json_encode($item)];
            } elseif (!$item['product_id']) {
                return [false, '(回滚)参数错误-bm_id空：'.json_encode($item)];
            } elseif (!$item['quantity']) {
                $bm_bn = self::getbmBn($item['product_id']);
                return [false, '(回滚)参数错误-quantity空[bm_bn=>'.$bm_bn.']：'.json_encode($item)];
            }

            $key = sprintf('%s#stock:%s:%s', $node_id, $item['branch_id'], $item['product_id']);

            // 扣减冻结为负数
            $item['quantity'] = abs($item['quantity']) * $ratio;

            if (isset($fItems[$key])) {
                $fItems[$key]['quantity'] += $item['quantity'];
            } else {
                $fItems[$key] = $item;
            }
        }
        
        $isRedis = parent::_connectRedis();
        $code = null;

        if (!$isRedis) {
            return [true, '库存回滚，连接redis失败'];
        }

        // 1. 更新Redis
        if ($isRedis) {
            $args = array_merge(array_keys($fItems), array_column($fItems, 'quantity'));

            list($code, $msg, $sku_id) = $rr = parent::$stockRedis->eval($lua, $args, count($fItems));

            if ($code === false) {
                return [false, $title.'失败：脚本异常'];
            }
            
            if ($code > 100) {
                $bm_bn = $fItems[$sku_id]['bn'] ? $fItems[$sku_id]['bn'] : self::getbmBn($fItems[$sku_id]['product_id']);
                return [false, '(回滚)【'.$bm_bn.'】'.$title.'失败：'.$msg];
            }
        }

        // 3. 数据库更新成功，做SET
        if ($isRedis && $code == 100) {
            $db   = kernel::database();
            $args = [];
            foreach ($fItems as $key => $item) {
                $row = $db->selectrow("SELECT `store`,`store_freeze` FROM `" . DB_PREFIX . "ome_branch_product` WHERE `branch_id`={$item['branch_id']} AND `product_id`={$item['product_id']}");
                
                $args[] = $row['store'].','.$row['store_freeze'];
            }
            $lua = file_get_contents(app::get('ome')->app_dir.'/lua/branch_store_set.lua');

            $args = array_merge(array_keys($fItems), $args);

            parent::$stockRedis->eval($lua, $args, count($fItems));
        }

        return [true, $title.'成功'];
    }


    /**
     * 在redis中获取冻库存和结数
     *
     * @params array $branch_product_info,
     *         示例：['branch_id' => 222, 'product_id' => 666]
     * 
     * @return void
     */
    static public function storeFromRedis($branch_product_info = [])
    {
        if (!$branch_product_info['branch_id'] || !$branch_product_info['product_id']) {
            return [false, 'branch_id or product_id is null', []];
        }

        $isRedis = parent::_connectRedis();
        $code = null;

        // 1. 从Redis获取
        if ($isRedis) {

            $node_id = base_shopnode::node_id('ome');
            $key     = sprintf('%s#stock:%s:%s', $node_id, $branch_product_info['branch_id'], $branch_product_info['product_id']);

            $redis_quantity = parent::$stockRedis->hgetall($key);

            if ($redis_quantity) {
                $return = [
                    'store'         =>  $redis_quantity['store'],
                    'store_freeze'  =>  $redis_quantity['store_freeze'],
                ];
                return [true, 'succ', $return];

            } else {

                $isRedis = false;
            }
        }

        // 2. 如果Redis不存在，或者redis中数据不存在，查mysql
        if (!$isRedis){
            $db  = kernel::database();
            $row = $db->selectrow("SELECT `store`,`store_freeze` FROM `" . DB_PREFIX . "ome_branch_product` WHERE `branch_id`={$branch_product_info['branch_id']} AND `product_id`={$branch_product_info['product_id']}");
            if ($row) {

                return [true, 'succ', $row];

            } else {

                return [false, 'get dbstore is fail', []];
            }
        }

        return [false, 'storeFromRedis unknown error', []];
    }

    static private function getbmBn($bm_id='')
    {
        if (!$bm_id) {
            return '未知商品';
        }
        $mdl  = app::get('material')->model('basic_material');
        $info = $mdl->db_dump(['bm_id'=>$bm_id], 'material_bn');

        !$info && $info = ['material_bn'=>$bm_id];
        return $info['material_bn'];
    }

}
