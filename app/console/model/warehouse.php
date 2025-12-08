<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 唯品会仓库mdl类
 *
 * @access public
 * @author wangbiao<wangbiao@shopex.cn>
 */
class console_mdl_warehouse extends dbeav_model
{

    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real = false)
    {
        if ($real) {
            $table_name = 'sdb_purchase_warehouse';
        } else {
            $table_name = 'warehouse';
        }

        return $table_name;
    }

    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema()
    {
        return app::get('purchase')->model('warehouse')->get_schema();
    }

    /**
     * 保存Warehouses
     * @param mixed $data 数据
     * @return mixed 返回操作结果
     */
    public function saveWarehouses($data = [])
    {
        $warehouseList = array_column($data, 'warehouse_code');
        $omsWarehouse  = $this->getList('branch_id,branch_bn', ['branch_bn|in' => $warehouseList]);
        $omsWarehouse  = array_column($omsWarehouse, 'branch_id', 'branch_bn');

        $regionLib = kernel::single('eccommon_regions');
        foreach ($data as $k => $v) {

            //收货人/发货人地区转换
            $area = $v['province_ame'] . "/" . $v['city_name'] . "/" . $v['region_name'] . "/" . $v['town_name'];
            $regionLib->region_validate($area);

            $param = [
                'branch_bn'      => $v['warehouse_code'],
                'branch_name'    => $v['warehouse_name'],
                // 'uname'          => '',
                // 'mobile'         => '',
                // 'phone'          => '',
                // 'email'          => '',
                // 'zip'            => '',
                'area'           => $area,
                'address'        => $v['warehouse_address'],
                'warehouse_type' => $v['warehouse_type'],
                'status'         => $v['is_active'],
                // 'wcontent'       => json_encode($v),
            ];
            if ($omsWarehouse[$param['branch_bn']]) {
                $param['branch_id'] = $omsWarehouse[$param['branch_bn']];
            }
            $this->save($param);
        }
    }

    /**
     * 保存Cooperation
     * @param mixed $data 数据
     * @return mixed 返回操作结果
     */
    public function saveCooperation($data = [])
    {
        foreach ($data as $k => $v) {
            // JITX子类型，0-仅门店；1-仅省仓；
            if ($v['jitx_subtype'] == '1') { 
                $param = [
                    'cooperation_no' => $v['cooperation_no'],
                    'ccontent'       => json_encode($v),
                ];
                $this->update($param, ['warehouse_type' => '2']);
            }
        }
    }
}
