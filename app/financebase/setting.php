<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$setting = array(
	'bill.rematch_time'=>array('type'=>SET_T_INT,'default'=>0,'desc'=>app::get('base')->_('对账单重新匹配时间')),
	'bill.sync_download_time'=>array('type'=>SET_T_INT,'default'=>0,'desc'=>app::get('base')->_('同步下载对账单时间')),
	'bill.logs.is_open'=>array('type'=>SET_T_BOOL,'default'=>'false','desc'=>app::get('base')->_('对账单日志开启设置')),
);
?>