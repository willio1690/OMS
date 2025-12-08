<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 *
 * @author ykm 2015-12-23
 * @describe 仓库相关数据
 */
class logisticsmanager_print_data_branch {
    private $mField = array(
        'area',
    );

    /**
     * branch
     * @param mixed $oriData ID
     * @param mixed $corp corp
     * @param mixed $field field
     * @param mixed $type type
     * @return mixed 返回值
     */

    public function branch(&$oriData, $corp, $field, $type) {
        $pre = __FUNCTION__ . '.';

        $branch_id = array('0');
        foreach($oriData as $k => $val) {
            $branch_id[$val['branch_id']] = $val['branch_id'];
        }

        $branchList = array();
        $branchMdl = app::get('ome')->model('branch');
        foreach ($branchMdl->getList('branch_id,area,uname,address,phone,mobile',array('branch_id'=>$branch_id,'skip_permission'=>true)) as $value) {
            $branchList[$value['branch_id']] = $value;
        }

        foreach ($oriData as $key => $value) {
            $branch = $branchList[$value['branch_id']];

            foreach ($field as $f) {
                if (isset($branch[$f])) {
                    $oriData[$key][$pre . $f] = (string)$branch[$f];
                } elseif (method_exists($this, $f)) {
                    $oriData[$key][$pre . $f] = (string)$this->$f($branch);
                } else {
                    $oriData[$key][$pre . $f] = '';
                }
            }
        }
    }

    private function area_0($row) {
        $area = $this->getArea($row);
        return $area[0];
    }

    private function area_1($row) {
        $area = $this->getArea($row);
        return $area[1];
    }

    private function area_2($row) {
        $area = $this->getArea($row);
        return $area[2];
    }

    private function detailaddr($row)
    {
        list(,$area) = explode(':', $row['area']);

        return str_replace('/','',$area).$row['address'];
    }

    private function getArea($row) {
        static $area = array();

        if(!$area[$row['area']]) {
            list(,$a) = explode(':', $row['area']);
            $area[$row['area']] = explode('/',$a);
        }

        return $area[$row['area']];
    }
}