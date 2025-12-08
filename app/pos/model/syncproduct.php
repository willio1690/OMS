<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_mdl_syncproduct extends dbeav_model {

    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter,$tableAlias=null,$baseWhere=null)
    {
        // 多货号查询
        if($filter['material_bn'] && is_string($filter['material_bn']) && strpos($filter['material_bn'], "\n") !== false){
            $filter['material_bn'] = array_unique(array_map('trim', array_filter(explode("\n", $filter['material_bn']))));
        }

        return parent::_filter($filter, $tableAlias, $baseWhere);
    }

    /**
     * replaceinsert
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function replaceinsert($data)
    {
        $columns = $this->schema['columns'];

        $strFields=$strValue=array();
        foreach ($data as $d) {
            $insertValues = array();
            foreach ($d as $c => $v) {
                if (!isset($columns[$c])) continue;

                $insertValues[$c] = $this->db->quote($v);

            }

            if (!$insertValues) continue;

            $strValue[] = "(".implode(',',$insertValues).")";
        }

        $strFields = array_keys($insertValues);

        if (!$strFields || !$strValue) return ;

        $strFields = implode('`,`', $strFields);$strValue = implode(',', $strValue);

        $sql = 'REPLACE INTO `'.$this->table_name(true).'` ( `'.$strFields.'` ) VALUES '.$strValue;

        $this->db->exec($sql);
    }
   
}