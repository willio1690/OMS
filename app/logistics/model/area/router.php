<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: yaokangming
 * Date: 2018/11/20
 * Time: 12:00
 */
class logistics_mdl_area_router extends dbeav_model
{
    protected $nums;
    protected $weight;
    protected $allAreaId = array();
    protected $import_data = array();

    /**
     * 获取TemplateColumn
     * @return mixed 返回结果
     */

    public function getTemplateColumn() {
        static $return = array();
        if($return) {
            return $return;
        }
        $templateColumn = app::get('eccommon')->model('regions')->getList('region_id, local_name', array('filter_sql'=>'p_region_id is null'));
        foreach ($templateColumn as $value) {
            $return[$value['local_name']] = $value['region_id'];
        }
        //$return['大仓(非门店)优先'] = 'first_dc';
        return $return;
    }

    /**
     * prepared_import_csv
     * @return mixed 返回值
     */
    public function prepared_import_csv(){
        $this->ioObj->cacheTime = time();
    }

    /**
     * prepared_import_csv_row
     * @param mixed $row row
     * @param mixed $title title
     * @param mixed $tmpl tmpl
     * @param mixed $mark mark
     * @param mixed $newObjFlag newObjFlag
     * @param mixed $msg msg
     * @return mixed 返回值
     */
    public function prepared_import_csv_row($row,&$title,&$tmpl,&$mark,&$newObjFlag,&$msg)
    {
        if(empty($row)) return true;
        $templateColumn = $this->getTemplateColumn();
        if($row[0] == '' || $row[0] == '优先级'){
            $this->nums = 1;
            $this->allAreaId = array();
            $this->import_data = array();
            if($row[0] == '优先级') {
                $this->weight = 999;
            } else {
                $this->weight = 0;
            }
            $title = array_flip($row);
            foreach($title as $k => $val) {
                if($val == 0) {
                    continue;
                }
                if(!isset($templateColumn[$k])) {
                    $msg['error'] = '请使用正确的模板';
                    return false;
                }
            }
            $row = app::get('logistics')->model('area_router')->getList('area_id', array(), 0, 1);
            if($row) {
                $msg['error'] = '已经存在物流就近规则，不能初始化';
            }
            return $title;
        }
        if (empty($title)) {
            $msg['error'] = "请使用正确的模板格式！";
            return false;
        }
        $arrData = array();
        $firstDc = 'true';
        foreach($templateColumn as $k => $val) {
            if($val == 'first_dc') {
                if(trim($row[$title[$k]]) == '否') {
                    $firstDc = 'false';
                }
                continue;
            }
            $weight = $val == isset($row[$title[$k]])
                    ? intval($this->weight ? $this->weight - trim($row[$title[$k]]) : trim($row[$title[$k]]))
                    : 0;
            $arrData[$val] = array(
                'weight' => $weight,
                'name' => $k
            );
        }
        $areaId = $templateColumn[$row[0]];
        if(isset($this->nums)){
            $this->nums++;
            if($this->nums > 5000){
                $msg['error'] = "导入的数据量过大，请减少到5000条以下！";
                return false;
            }
        }
        if (empty($areaId)) {
            $msg['warning'][] = 'Line '.$this->nums.'：'.$row[0].' 找不到对应的省份！';
            return false;
        }
        if (in_array($areaId, $this->allAreaId)) {
            $msg['warning'][] = 'Line '.$this->nums.'：'.$row[0].' 已经存在！';
            return false;
        }
        $this->allAreaId[] = $areaId;
        $sdf = array (
            'area_id' => $areaId,
            'area_name' => $row[0],
            'first_dc' => $firstDc,
            'router_area' => $arrData
        );
        $this->import_data[] = $sdf;
        $mark = 'contents';
        return true;
    }

    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){
        return null;
    }

    /**
     * finish_import_csv
     * @return mixed 返回值
     */
    public function finish_import_csv(){
        $oQueue = app::get('base')->model('queue');
        $queueData = array(
            'queue_title'=>'门店拆单路由规则初始化导入',
            'start_time'=>time(),
            'params'=>array(
                'sdfdata'=>$this->import_data,
            ),
            'worker'=>'logistics_mdl_area_router.import_run',
        );
        $oQueue->save($queueData);

        $oQueue->flush();
    }

    /**
     * import_run
     * @param mixed $cursor_id ID
     * @param mixed $params 参数
     * @param mixed $errormsg errormsg
     * @return mixed 返回值
     */
    public function import_run($cursor_id,$params,$errormsg)
    {
        $imData = $params['sdfdata'];
        foreach($imData as $data) {
            $this->saveRouterArea($data);
        }
        return false;
    }

    /**
     * cmp_router_weight
     * @param mixed $a a
     * @param mixed $b b
     * @return mixed 返回值
     */
    public function cmp_router_weight($a, $b) {
        if($a['weight'] === $b['weight']) {
            return 0;
        }
        return $a['weight'] > $b['weight'] ? -1 : 1;
    }

    /**
     * 保存RouterArea
     * @param mixed $data 数据
     * @return mixed 返回操作结果
     */
    public function saveRouterArea($data) {
        uasort($data['router_area'], array($this, 'cmp_router_weight'));
        $data['router_area'] = serialize($data['router_area']);
        return $this->db_save($data);
    }

    /**
     * modifier_router_area
     * @param mixed $col col
     * @return mixed 返回值
     */
    public function modifier_router_area($col) {
        $tmp = unserialize($col);
        $str = '';
        foreach ($tmp as $v) {
            $str .= $v['name'] . ',';
        }
        return $str;
    }
}