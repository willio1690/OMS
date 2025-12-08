<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class financebase_mdl_bill_import extends dbeav_model
{
    const TRPE_ORDER = 'order';
    const TRPE_SKU = 'sku';
    const TRPE_SALE = 'sale';
    const TRPE_JZT = 'jzt';
    const TRPE_JDBILL = 'jdbill';

    public function getRow($cols='*',$filter=array())
    {
        $sql = "SELECT $cols FROM ".$this->table_name(true)." WHERE ".$this->filter($filter);
        return $this->db->selectrow($sql);
    }
    
    /**
     * 获取指定平台或指定类型
     * 
     * @param string $type
     * @param string $platform
     * @return array
     */
    public function getImportType($type=NULL, $platform=NULL)
    {
        $data = array(
            self::TRPE_ORDER => array('name' => '按单号','key' => self::TRPE_ORDER, 'platform'=>'cainiao'),
            self::TRPE_SKU => array('name' => '按SKU明细','key' => self::TRPE_SKU, 'platform'=>'cainiao'),
            self::TRPE_SALE => array('name' => '按销售周期','key' => self::TRPE_SALE, 'platform'=>'cainiao'),
            self::TRPE_JZT => array('name'=>'京准通', 'key'=>self::TRPE_JZT, 'platform'=>'jzt'),
            self::TRPE_JDBILL => array('name'=>'京东钱包流水', 'key'=>self::TRPE_JDBILL, 'platform'=>'jdbill'),
        );
        
        //指定类型
        if ($type) {
            return $data[$type];
        }
        
        //指定平台
        if($platform){
            $dataList = array();
            foreach ($data as $key => $val){
                if($val['platform'] == $platform){
                    $dataList[$key] = $val;
                }
            }
            
            return $dataList;
        }
        
        return $data;
    }
}
