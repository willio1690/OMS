<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 *
 */
class inventorydepth_mdl_shop_mapping extends dbeav_model
{
	public $filter_use_like = false;
	
	public $defaultOrder = 'download_time';
	
	var $has_export_cnf = true;
	var $export_name = '平台商品列表';
	
	function __construct($app)
	{
		parent::__construct($app);
		
		$this->app = $app;
	}
	
	public function table_name($real = false)
	{
		$table_name = 'shop_skus';
		if ($real) {
			return kernel::database()->prefix . $this->app->app_id . '_' . $table_name;
		} else {
			return $table_name;
		}
	}
	
	public function get_schema()
	{
		$schema = array(
			'columns'         => array(
				'shop_iid'        =>
					array(
						'type'  => 'table:shop@ome',
						'label' => '平台商品ID',
						'width' => 150,
						'order' => 1,
					),
				'shop_sku_id'     =>
					array(
						'type'  => 'table:shop@ome',
						'label' => '平台sku编码',
						'width' => 120,
						'order' => 1,
					),
				'shop_title'      =>
					array(
						'type'  => 'table:orders@ome',
						'label' => '平台商品名称',
						'width' => 120,
						'order' => 2,
					),
				'code'            =>
					array(
						'type'  => 'table:reship@ome',
						'label' => '平台商家编码',
						'width' => 120,
						'order' => 3,
					),
				'shop_product_bn' =>
					array(
						'type'  => 'table:reship@ome',
						'label' => '本地商品ID',
						'width' => 120,
						'order' => 3,
					),
				'material_name'   =>
					array(
						'label' => '本地商品名称',
						'order' => 4,
						'width' => 130,
					),
				'shop_name'       =>
					array(
						'label' => '店铺名称',
						'order' => 4,
						'width' => 130,
					),
				'channel_id'      =>
					array(
						'label' => '渠道ID',
						'order' => 4,
						'width' => 230,
						'orderby'=>false,
					),
				'update_time'     =>
					array(
						'type'  => 'time',
						'label' => '最后修改时间',
						'order' => 4,
						'width' => 130,
					),
				'op_name'         =>
					array(
						'label' => '操作人',
						'order' => 4,
						'width' => 130,
					),
				'mapping'         =>
					array(
						'label' => '映射关系',
						'width' => 95,
						'order' => 5,
					)
			),
			'idColumn'        => 'id',
			'in_list'         => array(
				0  => 'shop_iid',
				1  => 'shop_title',
				2  => 'shop_product_bn',
				3  => 'material_name',
				4  => 'shop_name',
				5  => 'channel_id',
				6  => 'update_time',
				7  => 'op_name',
				8  => 'mapping',
				9  => 'code',
				10 => 'shop_sku_id',
			),
			'default_in_list' => array(
				0  => 'shop_iid',
				1  => 'shop_title',
				2  => 'shop_product_bn',
				3  => 'material_name',
				4  => 'shop_name',
				5  => 'channel_id',
				6  => 'update_time',
				7  => 'op_name',
				8  => 'mapping',
				9  => 'code',
				10 => 'shop_sku_id',
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
		if ($logParams['app'] == 'inventorydepth' && $logParams['ctl'] == 'shop_mapping') {
			$type .= '_inventorydepth_shop_mapping';
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
		if ($logParams['app'] == 'inventorydepth' && $logParams['ctl'] == 'shop_mapping') {
			$type .= '_inventorydepth_shop_mapping';
		}
		$type .= '_import';
		return $type;
	}
	
	public function exportName(&$data)
	{
		$data['name'] = '平台商品列表';
	}
	
	public function io_title($filter = null, $ioType = 'csv')
	{
		switch ($ioType) {
			case 'csv':
			default:
				$this->oSchema['csv']['main'] = array(
					'*:店铺货品ID'     => 'shop_iid',
					'*:店铺货品sku_id' => 'shop_sku_id',
					'*:店铺货品名称'     => 'shop_title',
					'*:货号'         => 'shop_product_bn',
					'*:商家编码'       => 'code',
					'*:货品名称'       => 'material_name',
					'*:店铺名称'       => 'shop_name',
					'*:渠道ID'       => 'channel_id',
					'*:最后更新时间'     => 'update_time',
					'*:已对映上本地货品'   => 'mapping',
				);
		}
		$this->ioTitle[$ioType][$filter] = array_keys($this->oSchema[$ioType]['main']);
		return $this->ioTitle[$ioType][$filter];
	}
	
	function export_csv($data, $exportType = 1)
	{
		$output   = array();
		$output[] = $data['title']['mapping'] . "\n" . implode("\n", (array)$data['content']['mapping']);
		echo implode("\n", $output);
	}
	
	public function fgetlist_csv(&$data, $filter, $offset, $exportType = 1)
	{
		@ini_set('memory_limit', '1024M');
		if (!$data['title']) {
			$title = array();
			foreach ($this->io_title() as $k => $v) {
				$title[] = $v;
			}
			$data['title']['mapping'] = mb_convert_encoding('"' . implode('","', $title) . '"', 'GBK', 'UTF-8');
		}
		
		$oShop = app::get('ome')->model('shop');
		$rs    = $oShop->getList('shop_id,name');
		foreach ($rs as $v) {
			$shops[$v['shop_id']] = $v;
		}
		
		$limit = 100;
		if (!$mapping = $this->getList('*', $filter, $offset * $limit, $limit)) {
			return false;
		}
		
		foreach ($mapping as $aFilter) {
			$mapRow['*:店铺货品']     = $aFilter['shop_iid'] . "\t";
			$mapRow['*:店铺货品名称']   = $aFilter['shop_title'];
			$mapRow['*:货号']       = $aFilter['shop_product_bn'] . "\t";
			$mapRow['*:商家编码']     = $aFilter['code'] . "\t";
			$mapRow['*:货品名称']     = $aFilter['material_name'];
			$mapRow['*:店铺名称']     = $aFilter['shop_name'];
			$mapRow['*:渠道ID']     = $aFilter['channel_id'];
			$mapRow['*:已对映上本地货品'] = $aFilter['mapping'];
			$mapRow['*:最后更新时间']   = date('Y-m-d H:i:s', $aFilter['update_time']);
			
			$data['content']['mapping'][] = mb_convert_encoding('"' . implode('","', $mapRow) . '"', 'GBK',
				'UTF-8');
		}
		
		return true;
	}
	
	//根据查询条件获取导出数据
	public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end, $op_id)
	{
		//根据选择的字段定义导出的第一行标题
		if ($curr_sheet == 1) {
			$data['content']['main'][] = $this->getExportTitle($fields);
		}
		
		$this->io_title();
		
		if (!$mapping = $this->getList('*', $filter, $start, $end)) {
			return false;
		}
		
		foreach ($mapping as $aFilter) {
			$mapRow['shop_iid']        = $aFilter['shop_iid'] . "\t";
			$mapRow['shop_sku_id']     = $aFilter['shop_sku_id'] . "\t";
			$mapRow['shop_title']      = $aFilter['shop_title'];
			$mapRow['shop_product_bn'] = $aFilter['shop_product_bn'] . "\t";
			$mapRow['code']            = $aFilter['code'] . "\t";
			$mapRow['material_name']   = $aFilter['material_name'];
			$mapRow['shop_name']       = $aFilter['shop_name'];
			$mapRow['channel_id']      = $aFilter['channel_id'];
			$mapRow['op_name']         = $aFilter['op_name'];
			$mapRow['mapping']         = $aFilter['mapping'] == 1 ? 'SKU已匹配' : 'SKU未匹配';
			$mapRow['update_time']     = date('Y-m-d H:i:s', $aFilter['update_time']);
			
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
	
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author
	 **/
	public function _filter($filter, $tableAlias = null, $baseWhere = null)
	{
		$where = 1;
		
		$where .= " AND shop_type = 'luban'";
		
		if (isset($filter['system_name']) && !empty($filter['system_name'])) {
			$where .= " AND shop_title like '%" . $filter['system_name'] . "%'";
		}
		if (isset($filter['shop_iid']) && !empty($filter['shop_iid'])) {
			$where .= " AND shop_iid = '" . $filter['shop_iid'] . "'";
		}
		if (isset($filter['shop_product_bn']) && !empty($filter['shop_product_bn'])) {
			$where .= " AND shop_product_bn = '" . $filter['shop_product_bn'] . "'";
		}
		
		if (isset($filter['id']) && !empty($filter['id'])) {
			$where .= ' AND id in ( "' . implode('","', $filter['id']) . '" )';
		}
		
		return $where;
	}
	
	/**
	 * @description 获取重复货号
	 */
	public function get_repeat_product_bn($filter)
	{
		$sql  = 'SELECT id,shop_product_bn,shop_id FROM ' . $this->table_name(true) . ' WHERE shop_id="' . $filter['shop_id'] . '" AND shop_product_bn!="" AND shop_product_bn is not null GROUP BY shop_product_bn,shop_id  Having count(1)>1 ';
		$list = $this->db->select($sql);
		$pbn  = array();
		if ($list) {
			foreach ($list as $key => $value) {
				$pbn[] = $value['shop_product_bn'];
			}
		}
		return $pbn;
	}
	
	/**
	 * @description
	 * @access public
	 * @param void
	 * @return void
	 */
	public function modifier_mapping($row)
	{
		if ($row == '1') {
			$row = '<div style="color:green;">SKU已匹配</div>';
		} else {
			$row = '<div style="color:red;">SKU未匹配</div>';
		}
		return $row;
	}
	
	public function getList($cols = '*', $filter = array(), $offset = 0, $limit = -1, $orderType = null)
	{
		if ($orderType) {
			$orderby = explode(' ', $orderType);
			if (reset($orderby) == 'code') {
				$orderType = 'shop_product_bn ' . $orderby[1];
			}
			if (reset($orderby) == 'material_name') {
				$orderType = 'shop_title ' . $orderby[1];
			}
			
		}
		$list              = parent::getList('*', $filter, $offset, $limit, $orderType);
		$bn_arr            = array_column($list, 'shop_product_bn');
		$channelMappingObj = app::get('material')->model('basic_material_channel');
		$channel_list      = $channelMappingObj->getList('material_bn,channel_id', ['material_bn|in' => $bn_arr]);
		$basicMappingObj   = app::get('material')->model('basic_material');
		$basic_detail      = $basicMappingObj->getList('material_bn,material_name', ['material_bn|in' => $bn_arr]);
		$basic_detail_arr  = array();
		if ($basic_detail) {
			$basic_detail_arr = array_column($basic_detail, null, 'material_bn');
		}
		$channel_list_arr = array();
		if ($channel_list) {
			$channel_list_arr = $this->filter_by_value($channel_list, 'material_bn');
		}
		
		foreach ($list as $key => $value) {
			$list[$key]['channel_id']      = isset($channel_list_arr[$value['shop_product_bn']]) ? implode('、',
				array_column($channel_list_arr[$value['shop_product_bn']], 'channel_id')) : '暂无渠道关联';
			$list[$key]['material_name']   = isset($basic_detail_arr[$value['shop_product_bn']]) ? $basic_detail_arr[$value['shop_product_bn']]['material_name'] : '';
			$list[$key]['code']            = $list[$key]['shop_product_bn'];
			$list[$key]['shop_product_bn'] = $list[$key]['mapping'] != 1 ? '' : $list[$key]['shop_product_bn'];
			$list[$key]['mapping']         = $list[$key]['mapping'] != 1 ? false : true;
		}
		return $list;
	}
	
	/*
	* 根据二维数组某个字段查找数组
	*/
	function filter_by_value($array, $index)
	{
		if (is_array($array) && count($array) > 0) {
			foreach (array_keys($array) as $key) {
				$temp[$key][$index] = $array[$key][$index];
				if ($temp[$key][$index] == $array[$key][$index]) {
					$newarray[$array[$key][$index]][] = $array[$key];
				}
			}
		}
		return $newarray;
	}
	
}