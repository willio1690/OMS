<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单明细
 *
 * @access public
 * @author xueding<xueding@shopex.cn>
 * @date  2021-05-22
 */
class erpapi_shop_response_plugins_order_settle extends erpapi_shop_response_plugins_order_abstract
{
	public function convert(erpapi_shop_response_abstract $platform)
	{
		$settleObj = app::get('ome')->model('order_settle');
		//获取订单明细根据obj层插入数据
		$order_obj = $platform->_ordersdf['order_objects'];
		$settle    = array();
		if (empty($order_obj)) {
			return false;
		}
		foreach ($order_obj as $key => $value) {
			$result = kernel::single('erpapi_router_request')->set('shop',
				$platform->__channelObj->channel['shop_id'])->product_itemsOrderGet([$value['oid']],$platform->_ordersdf['order_bn']);
			
			if ($result['rsp'] != 'succ' || empty($result['data'])) {
				continue;
			}
			
			$data     = reset($result['data']);
			$settle[] = array(
				'type'                => 'commission',
				'material_bn'         => $value['bn'],
				'oid'                 => $value['oid'],
				'real_comission'      => $data['real_comission'],
				'estimated_comission' => $data['estimated_comission'],
				'commission_rate'     => $data['commission_rate'],
				'extend'              => $data,
			);
		}
		// 更新的时候
		if ($platform->_tgOrder) {
			$tgSettle     = $settleObj->getList('order_id,oid,material_bn',
				array('order_id' => $platform->_tgOrder['order_id']));
			$tgSettleData = array_column($tgSettle, null, 'oid');
			foreach ($settle as $key => $value) {
				if (isset($tgSettleData[$value['oid']])) {
					$oldData = $tgSettleData[$value['oid']];
					if ($oldData['order_id'] == $platform->_tgOrder['order_id'] && $oldData['oid'] == $value['oid'] && $oldData['material_bn'] == $value['material_bn']) {
						unset($settle[$key]);
						continue;
					}
				}
			}
		}
		
		return $settle;
	}
	
	/**
	 * 订单完成后处理
	 *
	 * @return void
	 * @author
	 **/
	public function postCreate($order_id, $settle)
	{
		$settleObj = app::get('ome')->model('order_settle');
		foreach ($settle as $key => $value) {
			$settle[$key]['order_id'] = $order_id;
		}
		
		$sql = ome_func::get_insert_sql($settleObj, $settle);
		
		kernel::database()->exec($sql);
	}
}