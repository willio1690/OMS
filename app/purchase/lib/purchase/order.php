<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 唯品会JIT采购单Lib类
 * 
 * @author wangbiao@shopex.cn
 * @version 0.1
 */
class purchase_purchase_order
{
    function __construct()
    {
        $this->_purchaseObj    = app::get('purchase')->model('order');
        $this->_logObj         = app::get('ome')->model('operation_log');
    }
    
    /**
     * 创建采购单
     */
    function create_purchase($sdf)
    {
        //开启事务
        $this->_purchaseObj->db->beginTransaction();
        
        $sdf['create_time']    = time();
        $sdf['last_modified']  = time();
        
        if(!$this->_purchaseObj->save($sdf))
        {
            //事务回滚
            $this->_purchaseObj->db->rollBack();
            return false;
        }
        else
        {
            //事务确认
            $this->_purchaseObj->db->commit();
        }
        
        //增加采购单创建日志
        $this->_logObj->write_log('create_vopurchase@ome', $sdf['po_id'], '采购单创建成功');
        
        return true;
    }
    
    /**
     * 更新采购单
     */
    function update_purchase($sdf)
    {
        //开启事务
        $this->_purchaseObj->db->beginTransaction();
        
        $sdf['last_modified']  = time();
        
        if(!$this->_purchaseObj->save($sdf))
        {
            //事务回滚
            $this->_purchaseObj->db->rollBack();
            return false;
        }
        else
        {
            //事务确认
            $this->_purchaseObj->db->commit();
        }
        
        //增加订单创建日志
        $log_str    = '销售数量：'. $sdf['old_sales_num'] .'->'. $sdf['sales_num'] .';未拣货数量：'. $sdf['old_unpick_num'] .'->'. $sdf['unpick_num'];
        $this->_logObj->write_log('update_vopurchase@ome', $sdf['po_id'], $log_str);
        
        return true;
    }
    
    /**
     * 初始化唯品会仓库列表
     */
    function initWarehouse($branch_bn=null)
    {
        $branch_list    = array(
                            1=>array('branch_bn'=>'VIP_NH', 'branch_id'=>1, 'branch_name'=>'华南：南海仓'),
                            array('branch_bn'=>'VIP_SH', 'branch_id'=>2, 'branch_name'=>'华东：上海仓'),
                            array('branch_bn'=>'VIP_CD', 'branch_id'=>3, 'branch_name'=>'西南：成都仓'),
                            array('branch_bn'=>'VIP_BJ', 'branch_id'=>4, 'branch_name'=>'北京仓'),
                            array('branch_bn'=>'VIP_HZ', 'branch_id'=>5, 'branch_name'=>'华中：鄂州仓'),
                            array('branch_bn'=>'VIP_HH', 'branch_id'=>7, 'branch_name'=>'花海仓'),
                            array('branch_bn'=>'VIP_ZZ', 'branch_id'=>8, 'branch_name'=>'郑州仓'),
                            array('branch_bn'=>'VIP_SE', 'branch_id'=>9, 'branch_name'=>'首尔仓'),
                            array('branch_bn'=>'VIP_JC', 'branch_id'=>10, 'branch_name'=>'白云仓'),
                            array('branch_bn'=>'VIP_DA', 'branch_id'=>11, 'branch_name'=>'唯品团仓'),
                            array('branch_bn'=>'VIP_MRC', 'branch_id'=>12, 'branch_name'=>'唯品卡仓'),
                            array('branch_bn'=>'VIP_ZZKG', 'branch_id'=>13, 'branch_name'=>'郑州空港仓'),
                            array('branch_bn'=>'VIP_GZNS', 'branch_id'=>14, 'branch_name'=>'广州南沙仓'),
                            array('branch_bn'=>'VIP_CQKG', 'branch_id'=>15, 'branch_name'=>'重庆空港仓'),
                            array('branch_bn'=>'VIP_SZGY', 'branch_id'=>16, 'branch_name'=>'苏州工业仓'),
                            array('branch_bn'=>'VIP_FZPT', 'branch_id'=>17, 'branch_name'=>'福州平潭仓'),
                            array('branch_bn'=>'VIP_QDHD', 'branch_id'=>18, 'branch_name'=>'青岛黄岛仓'),
                            array('branch_bn'=>'HT_GZZY', 'branch_id'=>19, 'branch_name'=>'广州中远仓'),
                            array('branch_bn'=>'HT_GZFLXY', 'branch_id'=>20, 'branch_name'=>'富力心怡仓'),
                            array('branch_bn'=>'VIP_NBJCBS', 'branch_id'=>21, 'branch_name'=>'机场保税仓'),
                            array('branch_bn'=>'HT_NBYC', 'branch_id'=>22, 'branch_name'=>'云仓代运营'),
                            array('branch_bn'=>'HT_HZHD', 'branch_id'=>23, 'branch_name'=>'杭州航都仓'),
                            array('branch_bn'=>'HT_JPRT', 'branch_id'=>24, 'branch_name'=>'日本日通仓'),
                            array('branch_bn'=>'HT_AUXNXY', 'branch_id'=>25, 'branch_name'=>'悉尼心怡仓'),
                            array('branch_bn'=>'HT_USALATM', 'branch_id'=>26, 'branch_name'=>'洛杉矶天马仓'),
                            array('branch_bn'=>'HT_USANYTM', 'branch_id'=>27, 'branch_name'=>'纽约天马仓'),
                            array('branch_bn'=>'HT_SZQHBH', 'branch_id'=>28, 'branch_name'=>'前海保宏仓'),
                            array('branch_bn'=>'FJFZ', 'branch_id'=>29, 'branch_name'=>'福建福州仓'),
                            array('branch_bn'=>'PJ_ZJHZ', 'branch_id'=>30, 'branch_name'=>'杭州仓'),
                            array('branch_bn'=>'HNZZ', 'branch_id'=>31, 'branch_name'=>'郑州小仓'),
                            array('branch_bn'=>'SXXA', 'branch_id'=>32, 'branch_name'=>'西安小仓'),
                            array('branch_bn'=>'LNSY', 'branch_id'=>33, 'branch_name'=>'沈阳小仓'),
                            array('branch_bn'=>'YNKM', 'branch_id'=>34, 'branch_name'=>'昆明小仓'),
                            array('branch_bn'=>'VFN1700001', 'branch_id'=>35, 'branch_name'=>'广州集配仓'),
                            array('branch_bn'=>'VFN1700002', 'branch_id'=>36, 'branch_name'=>'上海集配仓'),
                            array('branch_bn'=>'VFN1700003', 'branch_id'=>37, 'branch_name'=>'北京集配仓'),
                            array('branch_bn'=>'VFN1700004', 'branch_id'=>38, 'branch_name'=>'沈阳集配仓'),
                            array('branch_bn'=>'VFN1700005', 'branch_id'=>39, 'branch_name'=>'武汉集配仓'),
                            array('branch_bn'=>'VFN1700006', 'branch_id'=>40, 'branch_name'=>'成都集配仓'),
                            array('branch_bn'=>'VFN1700007', 'branch_id'=>41, 'branch_name'=>'西安集配仓'),
                            array('branch_bn'=>'VFN1700008', 'branch_id'=>42, 'branch_name'=>'深圳集配仓'),
                            array('branch_bn'=>'VFN1700009', 'branch_id'=>43, 'branch_name'=>'杭州集配仓'),
                            array('branch_bn'=>'VFN1700010', 'branch_id'=>44, 'branch_name'=>'天津集配仓'),
                            array('branch_bn'=>'VFN1700011', 'branch_id'=>45, 'branch_name'=>'泉州集配仓'),
                            array('branch_bn'=>'VFN1700012', 'branch_id'=>46, 'branch_name'=>'宁波集配仓'),
                            array('branch_bn'=>'VFN1700013', 'branch_id'=>47, 'branch_name'=>'温州集配仓'),
                            array('branch_bn'=>'VFN1700014', 'branch_id'=>48, 'branch_name'=>'南京集配仓'),
        );
        
        if($branch_bn)
        {
            foreach ($branch_list as $key => $val)
            {
                if($val['branch_bn'] == $branch_bn)
                {
                    return $val;
                }
            }
        }
        
        return $branch_list;
    }
    
    /**
     * 唯品会仓库列表
     */
    function getWarehouse($branch_bn=null)
    {
        $branch_list    = array();
        $filter         = array();
        
        $warehouseObj    = app::get('purchase')->model('warehouse');
        $branch_list     = $warehouseObj->getList('branch_id, branch_bn, branch_name', $filter);
        
        if($branch_bn && $branch_list)
        {
            foreach ($branch_list as $key => $val)
            {
                if($val['branch_bn'] == $branch_bn)
                {
                    return $val;
                }
            }
        }
        
        return $branch_list;
    }
    
    /**
     * 获取OMS仓库(只支持自有仓储、伊藤忠仓储)
     */
    function get_branch_list()
    {
        $branchObj    = app::get('ome')->model('branch');
        $branch_list  = array();
        
        $sql    = "SELECT channel_id FROM sdb_channel_channel";
        $adapter_list   = $branchObj->db->select($sql);
        if(empty($adapter_list))
        {
            return array();
        }
        
        $wms_id_list    = array();
        foreach ($adapter_list as $key => $val)
        {
            $wms_id_list[]    = $val['channel_id'];
        }
        
        $branch_list  = $branchObj->getList('branch_id, branch_bn, name', array('type'=>array('main','damaged'), 'wms_id'=>$wms_id_list), 0, -1);
        
        //获取普通操作员管辖仓库
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super)
        {
            $oBops    = app::get('ome')->model('branch_ops');
            $opInfo   = kernel::single('ome_func')->getDesktopUser();
            $op_id    = $opInfo['op_id'];
            
            $bops_list = $oBops->getList('branch_id', array('op_id' => $op_id), 0, -1);
            if ($bops_list)
            {
                foreach ($bops_list as $k => $v) {
                    $bps[] = $v['branch_id'];
                }
                
                $branch_list = $branchObj->getList('branch_id,name,uname,phone,mobile', array('branch_id'=>$bps, 'wms_id'=>$wms_id_list), 0, -1);
                ksort($branch_list);
            }
        }
        
        return $branch_list;
    }
    
    /**
     * 获取唯品会JIT店铺
     * 
     * @param $shop_ids
     * @return Array
     */
    function get_vop_shop_list($shop_ids=null)
    {
        $shopObj   = app::get('ome')->model('shop');
        $filter    = array('node_type'=>'vop', 'node_id|noequal'=>'', 'tbbusiness_type'=>'jit');
        
        //指定店铺
        if($shop_ids)
        {
            $filter['shop_id']    = $shop_ids;
        }
        
        $shopList  = $shopObj->getList('shop_id, shop_bn, name, node_id, tbbusiness_type', $filter);
        
        return $shopList;
    }
}
?>