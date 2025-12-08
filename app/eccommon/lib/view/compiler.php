<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class eccommon_view_compiler{

    function compile_modifier_cur($attrs,&$compile) {
        if(!strpos($attrs,',') || false!==strpos($attrs,',')){
            return $attrs = 'app::get(\'eccommon\')->model(\'currency\')->changer('.$attrs.')';
        }
    }

    /**
     * compile_modifier_cur_name
     * @param mixed $attrs attrs
     * @param mixed $compile compile
     * @return mixed 返回值
     */
    public function compile_modifier_cur_name($attrs,&$compile) {
		 if(!strpos($attrs,',') || false!==strpos($attrs,',')){
            return $attrs = 'app::get(\'eccommon\')->model(\'currency\')->get_cur_name('.$attrs.')';
        }
	}

    /**
     * compile_modifier_pay_name
     * @param mixed $attrs attrs
     * @return mixed 返回值
     */
    public function compile_modifier_pay_name($attrs) {
        //todo 需要将货币汇率也缓存
        if(!strpos($attrs,',') || false!==strpos($attrs,',')){
            return $attrs = 'app::get(\'eccommon\')->model(\'payment_cfgs\')->get_app_display_name('.$attrs.')';
        }
    }

    /**
     * compile_modifier_operactor_name
     * @param mixed $attrs attrs
     * @return mixed 返回值
     */
    public function compile_modifier_operactor_name($attrs) {
		if (!strpos($attrs,',') || false!==strpos($attrs,',')){
			return $attrs = 'app::get(\'pam\')->model(\'account\')->get_operactor_name('.$attrs.')';
		}
	}
}
