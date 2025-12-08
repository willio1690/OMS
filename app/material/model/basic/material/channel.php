<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 *
 */
class material_mdl_basic_material_channel extends dbeav_model
{
	public $filter_use_like = false;
	
	public $defaultOrder = 'create_time';
	
	var $has_export_cnf = true;
	var $export_name = '渠道商品列表';
	
	function __construct($app)
	{
		parent::__construct($app);
		
		$this->app = $app;
	}
	
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real = false)
	{
		$table_name = 'basic_material_channel';
		if ($real) {
			return kernel::database()->prefix . $this->app->app_id . '_' . $table_name;
		} else {
			return $table_name;
		}
	}
	
    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema()
	{
		$schema = array(
			'columns'         => array(
				'material_bn'        =>
					array(
						'label' => 'OMS商品编码',
						'width' => 120,
						'order' => 1,
					),
				'material_name'      =>
					array(
						'label' => 'OMS商品名称',
						'width' => 170,
						'order' => 2,
						'orderby'=>false,
					),
				'outer_product_id'   =>
					array(
						'label' => '云交易商品编码',
						'width' => 120,
						'order' => 3,
					),
				'outer_product_name' =>
					array(
						'label' => '云交易商品名称',
						'order' => 4,
						'width' => 170,
						'orderby'=>false,
					),
				'channel_id'         =>
					array(
						'label' => '云交易渠道ID',
						'order' => 4,
						'width' => 130,
					),
				'channel_name'       =>
					array(
						'label' => '云交易渠道名称',
						'order' => 4,
						'width' => 230,
					),
				'approve_status'     =>
					array(
						'label' => '云交易渠道上下架状态',
						'order' => 4,
						'width' => 50,
					),
				'last_modify'        =>
					array(
						'type'  => 'time',
						'label' => '最后更新时间',
						'order' => 4,
						'width' => 130,
					),
				'op_name'            =>
					array(
						'label' => '操作人',
						'order' => 4,
						'width' => 130,
					),
                'price' =>
                    array(
                        'label' => '售价',
                        'width' => 75,
                    ),
				'is_error' =>
                    array(
                        'label' => '是否异常',
                        'width' => 75,
                    ),
			),
			'idColumn'        => 'id',
			'in_list'         => array(
				0 => 'material_bn',
				1 => 'material_name',
				2 => 'outer_product_id',
				3 => 'outer_product_name',
				4 => 'channel_id',
				5 => 'channel_name',
				6 => 'approve_status',
				7 => 'op_name',
				8 => 'last_modify',
			),
			'default_in_list' => array(
				0 => 'material_bn',
				1 => 'material_name',
				2 => 'outer_product_id',
				3 => 'outer_product_name',
				4 => 'channel_id',
				5 => 'channel_name',
				6 => 'approve_status',
				7 => 'op_name',
				8 => 'last_modify',
			),
		);
		return $schema;
	}
	
	/**
	 * 获得日志类型(non-PHPdoc)
	 * @see dbeav_model::getLogType()
	 */
	public function getLogType($logParams)
	{
		$type    = $logParams['type'];
		$logType = 'none';
		if ($type == 'export') {
			$logType = $this->exportLogType($logParams);
		} elseif ($type == 'import') {
			$logType = $this->importLogType($logParams);
		}
		return $logType;
	}
	
	/**
	 * 导出日志类型
	 * @param Array $logParams 日志参数
	 */
	public function exportLogType($logParams)
	{
		$params = $logParams['params'];
		$type   = 'report';
		if ($logParams['app'] == 'material' && $logParams['ctl'] == 'material_basic_channel') {
			$type .= '_material_basic_material_channel';
		}
		$type .= '_export';
		return $type;
	}
	
	/**
	 * 导入操作日志类型
	 * @param Array $logParams 日志参数
	 */
	public function importLogType($logParams)
	{
		$params = $logParams['params'];
		$type   = 'report';
		if ($logParams['app'] == 'material' && $logParams['ctl'] == 'material_basic_channel') {
			$type .= '_material_basic_material_channel';
		}
		$type .= '_import';
		return $type;
	}
	
	public function exportName(&$data)
	{
		$data['name'] = '渠道商品列表';
	}
	
	public function io_title($filter = null, $ioType = 'csv')
	{
		switch ($ioType) {
			case 'csv':
			default:
				$this->oSchema['csv']['main'] = array(
					'*:OMS商品编码'    => 'material_bn',
					'*:OMS商品名称'    => 'material_name',
					'*:云交易商品编码'    => 'outer_product_id',
					'*:云交易商品名称'    => 'outer_product_name',
					'*:云交易渠道ID'    => 'channel_id',
					'*:云交易渠道名称'    => 'channel_name',
					'*:云交易渠道上下架状态' => 'approve_status',
					'*:最后更新时间'     => 'last_modify',
					'*:操作人'        => 'op_name',
				);
		}
		$this->ioTitle[$ioType][$filter] = array_keys($this->oSchema[$ioType]['main']);
		return $this->ioTitle[$ioType][$filter];
	}
	
	function export_csv($data, $exportType = 1)
	{
		$output   = array();
		$output[] = $data['title']['channel'] . "\n" . implode("\n", (array)$data['content']['channel']);
		echo implode("\n", $output);
	}
	
    /**
     * fgetlist_csv
     * @param mixed $data 数据
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $exportType exportType
     * @return mixed 返回值
     */
    public function fgetlist_csv(&$data, $filter, $offset, $exportType = 1)
	{
		@ini_set('memory_limit', '1024M');
		if (!$data['title']) {
			$title = array();
			foreach ($this->io_title() as $k => $v) {
				$title[] = $v;
			}
			$data['title']['channel'] = mb_convert_encoding('"' . implode('","', $title) . '"', 'GBK', 'UTF-8');
		}
		
		$limit = 100;
		if (!$channel = $this->getList('*', $filter, $offset * $limit, $limit)) {
			return false;
		}
		
		foreach ($channel as $aFilter) {
			$mapRow['*:OMS商品编码']    = $aFilter['material_bn'] . "\t";
			$mapRow['*:OMS商品名称']    = $aFilter['material_name'];
			$mapRow['*:云交易商品编码']    = $aFilter['outer_product_id'] . "\t";
			$mapRow['*:云交易商品名称']    = $aFilter['outer_product_name'];
			$mapRow['*:云交易渠道ID']    = $aFilter['channel_id'];
			$mapRow['*:云交易渠道名称']    = $aFilter['channel_name'];
			$mapRow['*:云交易渠道上下架状态'] = $aFilter['approve_status'];
			$mapRow['*:最后更新时间']     = date('Y-m-d H:i:s', $aFilter['last_modify']);
			$mapRow['*:操作人']        = $aFilter['op_name'];
			
			$data['content']['channel'][] = mb_convert_encoding('"' . implode('","', $mapRow) . '"', 'GBK',
				'UTF-8');
		}
		
		return true;
	}
	
	//根据查询条件获取导出数据
    /**
     * 获取ExportDataByCustom
     * @param mixed $fields fields
     * @param mixed $filter filter
     * @param mixed $has_detail has_detail
     * @param mixed $curr_sheet curr_sheet
     * @param mixed $start start
     * @param mixed $end end
     * @param mixed $op_id ID
     * @return mixed 返回结果
     */
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end, $op_id)
	{
		//根据选择的字段定义导出的第一行标题
		if ($curr_sheet == 1) {
			$data['content']['main'][] = $this->getExportTitle($fields);
		}
		
		$this->io_title();
		
		if (!$channel = $this->getList('*', $filter, $start, $end)) {
			return false;
		}
		
		foreach ($channel as $aFilter) {
			$mapRow['material_bn']     = $aFilter['material_bn'] . "\t";
			$mapRow['material_name']      = $aFilter['material_name'];
			$mapRow['outer_product_id'] = $aFilter['outer_product_id'] . "\t";
			$mapRow['outer_product_name']   = $aFilter['outer_product_name'];
			$mapRow['channel_id']       = $aFilter['channel_id'];
			$mapRow['channel_name']      = $aFilter['channel_name'];
			$mapRow['approve_status']         = $aFilter['approve_status'] == 1 ? '上架' : '下架';
			$mapRow['last_modify']     = date('Y-m-d H:i:s', $aFilter['last_modify']);
			$mapRow['op_name']     = $aFilter['op_name'];
			
			$exptmp_data = array();
			foreach (explode(',', $fields) as $key => $col) {
				if (isset($mapRow[$col])) {
					$mapRow[$col]  = mb_convert_encoding($mapRow[$col], 'GBK', 'UTF-8');
					$exptmp_data[] = $mapRow[$col];
				} else {
					$exptmp_data[] = '';
				}
			}
			
			$data['content']['main'][] = implode(',', $exptmp_data);
		}
		
		return $data;
	}
    function _filter($filter,$tableAlias=null,$baseWhere=array ())
    {
        if ($filter['is_error'] == '1') {
            $baseWhere[] = '  is_error="1" ';
        }
        if ($filter['is_error'] == '2') {
            $baseWhere[] = '  is_error!="1" ';
        }
	    unset($filter['is_error']);
        if ($filter['filter']['is_error'] == '1') {
            $baseWhere[] = '  is_error="1" ';
        }
        if ($filter['filter']['is_error'] == '2') {
            $baseWhere[] = '  is_error!="1" ';
        }
	    unset($filter['filter']['is_error']);
        
        if(is_array($filter['outer_product_id'])){
            $baseWhere[] = " outer_product_id IN('". implode("','", $filter['outer_product_id']) ."') ";
            unset($filter['outer_product_id']);
        }elseif ($filter['outer_product_id']) {
            $baseWhere[] = '  outer_product_id="'.$filter['outer_product_id'].'" ';
            unset($filter['outer_product_id']);
        }
        
        if ($filter['filter']['outer_product_id']) {
            $baseWhere[] = '  outer_product_id="'.$filter['filter']['outer_product_id'].'" ';
        }
        if ($filter['bm_id']) {
            $baseWhere[] = '  bm_id="'.$filter['bm_id'].'" ';
            unset($filter['bm_id']);
        }
        if ($filter['filter']['bm_id']) {
            $baseWhere[] = '  bm_id="'.$filter['filter']['bm_id'].'" ';
        }

        return parent::_filter($filter, $tableAlias, $baseWhere);
    }
	
	public function getList($cols = '*', $filter = array(), $offset = 0, $limit = -1, $orderType = null)
	{
	 
		$list             = parent::getList('*', $filter, $offset, $limit, $orderType);
		$bn_arr           = array_column($list, 'material_bn');
		$basicMappingObj  = app::get('material')->model('basic_material');
		$basic_detail     = $basicMappingObj->getList('material_bn,material_name', ['material_bn|in' => $bn_arr]);
		$basic_detail_arr = array();
		if ($basic_detail) {
			$basic_detail_arr = array_column($basic_detail, null, 'material_bn');
		}
		
		foreach ($list as $key => $value) {
			$list[$key]['material_name']      = isset($basic_detail_arr[$value['material_bn']]) ? $basic_detail_arr[$value['material_bn']]['material_name'] : '';
			$list[$key]['outer_product_name'] = isset($basic_detail_arr[$value['material_bn']]) ? $basic_detail_arr[$value['material_bn']]['material_name'] : '';
			$list[$key]['approve_status']     = $list[$key]['approve_status'] == 1 ? '上架' : '下架';
		}
		return $list;
	}
}