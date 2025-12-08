<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class organization_mdl_organization extends dbeav_model
{

//     var $export_name = '企业组织架构';
    public $org_status = array(
        1 => "启用",
        2 => "停用",
    );

    //企业组织管理页面显示状态
    public function modifier_status($row)
    {
        return $this->org_status[$row];
    }

    public function modifier_org_type($row)
    {
        if ($row == 2) {
            return '门店';
        } else {
            return '-';
        }
    }

    //上级组织编码
    public function modifier_parent_no($row)
    {
        if (!$row) {
            return '-';
        } else {
            return $row;
        }
    }

    //下级组织编码
    public function modifier_child_nos($row)
    {
        if (!$row) {
            return '-';
        } else {
            return $row;
        }
    }

    //下级组织名称
    public function modifier_child_names($row)
    {
        if (!$row) {
            return '-';
        } else {
            return $row;
        }
    }

    //导出
    //     public function exportName(&$data){
    //         $data['name'] = $this->export_name.date("Y-m-d H:i:s");
    //     }

    //输出头标题和数据
    //     function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
    //         if( !$data['title']){
    //             $title = array();
    //             foreach($this->io_title('organization') as $k => $v ){
    //                 $title[] = $this->charset->utf2local($v);
    //             }
    //             $data['title']['organization'] = '"'.implode('","',$title).'"';
    //         }
    //         if( !$list=$this->getlist('*',$filter,0,-1) )return false;
    //         foreach( $list as $aFilter ){
    //             $pRow = array();
    //             $detail['org_id'] = $this->charset->utf2local($aFilter['org_id']);
    //             $detail['org_name'] = $this->charset->utf2local($aFilter['org_name']);
    //             $detail['org_level_num'] = $this->charset->utf2local($aFilter['org_level_num']);
    //             $detail['higher_level_id'] = $this->charset->utf2local($aFilter['higher_level_id']);
    //             $detail['lower_level_ids'] = $this->charset->utf2local($aFilter['lower_level_ids']); //需要replace ,
    //             $detail['lower_level_names'] = $this->charset->utf2local($aFilter['lower_level_names']); //需要replace ,
    //             $detail['status'] = $this->charset->utf2local($this->org_status[$aFilter['status']]);
    //             foreach( $this->oSchema['csv']['organization'] as $k => $v ){
    //                 $pRow[$k] =  utils::apath( $detail,explode('/',$v) );
    //             }
    //             $data['contents']['organization'][] = implode(',',$pRow);
    //         }
    //         return false;
    //     }

//     function export_csv($data,$exportType = 1 ){
    //         $output = array();
    //         $output[] = $data['title']['organization']."\n".implode("\n",(array)$data['contents']['organization']);
    //         echo implode("\n",$output);
    //     }

    //头标题
    //     function io_title( $filter, $ioType='csv' ){
    //         switch( $filter ){
    //             case 'organization':
    //                 $this->oSchema['csv'][$filter] = array(
    //                     '*:组织ID' => 'org_id',
    //                     '*:组织名称' => 'org_name',
    //                     '*:组织层级' => 'org_level_num',
    //                     '*:上级ID' => 'higher_level_id',
    //                     '*:下级ID' => 'lower_level_ids',
    //                     '*:下级ID名称' => 'lower_level_names',
    //                     '*:状态' => 'status',
    //                 );
    //             break;
    //         }
    //         $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
    //         return $this->ioTitle[$ioType][$filter];
    //     }

    /**
     * 导入门店模板的标题
     *
     * @param Null
     * @return Array
     */
    public function exportTemplate()
    {
        $tmpList = $this->io_title();
        foreach ($tmpList as $v) {
            $title[] = kernel::single('base_charset')->utf2local($v);
        }

        return $title;
    }

    /**
     * 导入导出的标题
     *
     * @param $ioType  导入文件格式
     * @return Array
     */
    public function io_title($ioType = 'csv')
    {
        $this->oSchema['csv'] = array(
            '*:组织编码'          => 'org_no',
            '*:组织名称'          => 'org_name',
            '*:所属上级(为空视为最高级)' => 'org_parent_name',
            '*:状态'            => 'status',
        );

        $this->ioTitle[$ioType] = array_keys($this->oSchema[$ioType]);

        return $this->ioTitle[$ioType];
    }

    /**
     * 准备导入的参数定义
     *
     * @param Null
     * @return Null
     */
    public function prepared_import_csv()
    {
        $this->ioObj->cacheTime = time();
    }

    /**
     * 准备导入的数据主体内容部分检查和处理
     *
     * @param Array $data
     * @param Boolean $mark
     * @param String $tmpl
     * @param String $msg
     * @return Null
     */
    public function prepared_import_csv_obj($data, $mark, $tmpl, &$msg = '')
    {
        return null;
    }

    /**
     * 准备导入的数据明细内容部分检查和处理
     *
     * @param Array $row
     * @param String $title
     * @param String $tmpl
     * @param Boolean $mark
     * @param Boolean $newObjFlag
     * @param array $msg
     * @return Null
     */
    public function prepared_import_csv_row($row, $title, &$tmpl, &$mark, &$newObjFlag, &$msg)
    {
        $organizationObj = app::get('organization')->model('organization');
        $orgCheckLib     = kernel::single('organization_organizations_check');
        $error_msg       = '';

        if (empty($row)) {
            return true;
        }

        $mark = false;

        if (substr($row[0], 0, 1) == '*') {
            $titleRs = array_flip($row);
            $mark    = 'title';

            # [防止重复]记录组织编码
            $this->org_no_list   = array();
            $this->org_name_list = array();
            $this->basicm_nums   = 1;

            return $titleRs;
        } else {
            $re = base_kvstore::instance('organization')->fetch('organization-' . $this->ioObj->cacheTime, $fileData);

            if (!$re) {
                $fileData = array();
            }

            //判断导入的数量
            if (isset($this->basicm_nums)) {
                $this->basicm_nums++;
                if ($this->basicm_nums > 5000) {
                    $msg['error'] = "导入的数量量过大，请减少到5000个以下！";
                    return false;
                }
            }

            //导入数据检查
            if (!$row[0]) {
                $msg['error'] = "组织编码必须填写,组织名称：" . $row[1];
                return false;
            }
            if (strlen($row[0]) > 15) {
                $msg['error'] = "组织编码长度必须在15个字符以内,组织编码：" . $row[0];
                return false;
            }

            if (!$row[1]) {
                $msg['error'] = "组织名称必须填写,组织编码：" . $row[0];
                return false;
            }

            if (!$row[3]) {
                $msg['error'] = "状态必须填写,组织编码：" . $row[0];
                return false;
            }

            //组织编码是否已经存在
            $org_row = $orgCheckLib->check_org_no_exist($row[0], $error_msg);
            if (!$org_row) {
                $msg['error'] = $error_msg . ",组织编码：" . $row[0];
                return false;
            }

            //组织名称是否已经存在
            $org_row = $orgCheckLib->check_org_name_exist($row[1], $error_msg);
            if (!$org_row) {
                $msg['error'] = $error_msg . ",组织编码：" . $row[0];
                return false;
            }
            $row[3] = trim($row[3]);
            //状态值
            if ($row[3] == "启用") {
                $row[3] = 1;
            } elseif ($row[3] == "停用") {
                $row[3] = 2;
            } else {
                $msg['error'] = "组织状态填写错误：" . $row[0] . ",请填写启用、停用";
                return false;
            }

            //所属上级(为空视为最高级)
            $org_level_num         = 1; //组织层级
            $parent_id             = 0;
            $parent_no             = '';
            $org_parents_structure = ''; //组织架构结构

            if (empty($row[2])) {
                $row[4] = 1; #组织层级org_level_num
            } else {
                $tempData = explode('-', $row[2]);
                foreach ($tempData as $key => $val) {
                    if (empty($val)) {
                        $msg['error'] = "所属上级填写错误,组织编码：" . $row[0];
                        return false;
                    }

                    #判断上下级是否关联
                    $org_filter = array('org_name' => $val, 'org_type' => 1);
                    if ($parent_id) {
                        $org_filter['parent_id'] = $parent_id;
                    }

                    #Check
                    $org_row = $organizationObj->dump($org_filter, 'org_id, org_no, org_name');
                    if (empty($org_row)) {
                        $msg['error'] = "所属组织[" . $val . "]与[" . $tempData[$key - 1] . "]没有关联,组织编码：" . $row[0];
                        return false;
                    }

                    $parent_id = $org_row['org_id'];
                    $parent_no = $org_row['org_no'];

                    $org_parents_structure .= ('/' . $org_row['org_name']);

                    $org_level_num++;
                }

                $org_parents_structure = substr($org_parents_structure, 1);
                $org_parents_structure = 'mainOrganization:' . $org_parents_structure . ':' . $parent_id;
            }

            #扩展信息
            $row[4] = $org_level_num;
            $row[5] = $parent_id;
            $row[6] = $parent_no;
            $row[7] = $org_parents_structure;

            #组织类型(默认为：1)
            $row[8] = 1;

            #时间
            $dateline = time();
            $row[9]   = $dateline; //新建时间create_time
            if ($row[3] == 1) {
                $row[10] = $dateline; //最近启用时间recently_enabled_time
                $row[11] = $dateline; //首次启用时间first_enable_time
                $row[12] = 0; //最近停用时间recently_stopped_time
            } else {
                $row[10] = 0; //最近启用时间recently_enabled_time
                $row[11] = 0; //首次启用时间first_enable_time
                $row[12] = $dateline; //最近停用时间recently_stopped_time
            }

            #目前组织结构最大支持五层层级
            $chk_org_level = $orgCheckLib->check_org_level($org_level_num, $error_msg);
            if (!$chk_org_level) {
                $msg['error'] = $error_msg . ",组织编码：" . $row[0];
                return false;
            }

            # [防止重复]检查组织编码
            if (in_array($row[0], $this->org_no_list)) {
                $msg['error'] = 'Line ' . $this->basicm_nums . '：组织编码【' . $row[0] . '】重复！';
                return false;
            }
            $this->org_no_list[] = $row[0];

            # [防止重复]检查组织名称
            if (in_array($row[1], $this->org_name_list)) {
                $msg['error'] = 'Line ' . $this->basicm_nums . '：组织名称【' . $row[1] . '】重复！';
                return false;
            }
            $this->org_name_list[] = $row[1];

            $fileData['basicm']['contents'][] = $row;
            base_kvstore::instance('organization')->store('organization-' . $this->ioObj->cacheTime, $fileData);
        }

        return null;
    }

    /**
     * 完成导入
     *
     * @param Null
     * @return Null
     */
    public function finish_import_csv()
    {
        base_kvstore::instance('organization')->fetch('organization-' . $this->ioObj->cacheTime, $data);
        base_kvstore::instance('organization')->store('organization-' . $this->ioObj->cacheTime, '');

        $oQueue = app::get('base')->model('queue');
        $aP     = $data;
        $pSdf   = array();

        $count     = 0;
        $limit     = 50;
        $page      = 0;
        $orderSdfs = array();

        foreach ($aP['basicm']['contents'] as $k => $aPi) {
            if ($count < $limit) {
                $count++;
            } else {
                $count = 0;
                $page++;
            }
            $pSdf[$page][] = $aPi;
        }

        foreach ($pSdf as $v) {
            $queueData = array(
                'queue_title' => '企业组织结构导入',
                'start_time'  => time(),
                'params'      => array(
                    'sdfdata' => $v,
                    'app'     => 'organization',
                    'mdl'     => 'organization',
                ),
                'worker'      => 'organization_organizations_to_import.run',
            );
            $oQueue->save($queueData);
        }
        $oQueue->flush();

        //记录日志
        $operationLogObj = app::get('ome')->model('operation_log');
        $operationLogObj->write_log('organization_import@wms', 0, "批量导入企业组织结构,本次共导入" . count($aP['basicm']['contents']) . "条记录!");

        return null;
    }

    public function get_first_parent($org_no)
    {
        $parent = array();

        $orgMdl = app::get('organization')->model('organization');

        // 注意：这里需要根据业务逻辑判断是否添加前缀
        // 如果是查询经销商，需要添加BS_前缀
        // 如果是查询门店或公司，不需要添加前缀
        // 由于这个方法没有org_type参数，暂时保持原样，后续可能需要优化
        $org = $orgMdl->db_dump(array('org_no' => $org_no), 'org_no,org_name,org_type,parent_id');
        if (!$org['parent_id']) {
            return $org;
        }

        $parent_id = $org['parent_id'];

        do {
            $parent = $orgMdl->db_dump(array('org_id' => $parent_id), 'org_no,org_name,org_type,parent_id');

            if (!$parent['parent_id']) {
                break;
            }

            $parent_id = $parent['parent_id'];

        } while (true);

        return $parent;
    }

    public function get_all_children($org_no)
    {
        $children = array();

        $orgMdl = app::get('organization')->model('organization');

        // 注意：这里需要根据业务逻辑判断是否添加前缀
        // 如果是查询经销商，需要添加BS_前缀
        // 如果是查询门店或公司，不需要添加前缀
        // 由于这个方法没有org_type参数，暂时保持原样，后续可能需要优化
        $org = $orgMdl->db_dump(array('org_no' => $org_no), 'org_id,org_no,org_name,org_type,parent_id,haschild');
        if (!$org['haschild']) {
            if ($org['org_type'] == '2') {
                $children[] = $org;
            }

            return $children;
        }

        $parent_id = $org['org_id'];
        do {
            $org_list = $orgMdl->getList('org_id,org_no,org_name,org_type,parent_id,haschild', array('parent_id' => $parent_id));
            if (!$org_list) {
                break;
            }

            $parent_id = array(0);
            foreach ($org_list as $key => $value) {
                if (!$value['haschild']) {
                    if ($value['org_type'] == '2') {
                        $children[] = $value;
                    }
                    continue;
                }

                $parent_id[] = $value['org_id'];
            }

        } while (true);

        return $children;
    }
}
