<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
class desktop_application_menu extends desktop_application_prototype_xml
{

    public $xml  = 'desktop.xml';
    public $xsd  = 'desktop_content';
    public $path = 'workground';

    /**
     * current
     * @return mixed 返回值
     */
    public function current()
    {
        $this->current           = $this->iterator()->current();
        $this->current['action'] = $this->current['action'] ? $this->current['action'] : 'index';
        $this->key               = $this->target_app->app_id . '_ctl_' . $this->current['controller'];
        return $this;
    }

    /**
     * row
     * @param mixed $fag fag
     * @param mixed $key key
     * @return mixed 返回值
     */
    public function row($fag, $key)
    {
        if ($this->current['menugroup'][$fag]['menu'][$key]['params']) {
            $a_tmp = explode('|', $this->current['menugroup'][$fag]['menu'][$key]['params']);
            foreach ((array) $a_tmp as $k => $v) {
                $a = explode(':', $v);
                if (strpos($a[0], 'p[') !== false) {
                    $a = explode(':', $v);
                    eval('$url_params' . str_replace('p', '["p"]', $a[0]) . ' = $a[1];');
                } else {
                    $url_params[$a[0]] = $a[1];
                }
            }
            $addon['url_params'] = $url_params;
            unset($url_params);
        }
        $row = array(
            'menu_type'    => $this->content_typename(),
            'app_id'       => $this->target_app->app_id,
            'workground'   => $this->current['id'],
            'content_name' => $this->key(),
            'menu_group'   => $this->current['menugroup'][$fag]['name'],
            'menu_order'   => $this->current['menugroup'][$fag]['menu'][$key]['order'],
            'addon'        => serialize($addon),
            'target'       => $this->current['menugroup'][$fag]['menu'][$key]['target'] ? $this->current['menugroup'][$fag]['menu'][$key]['target'] : '',
            'en'           => $this->current['menugroup'][$fag]['en'],
        );

        $this->current['menugroup'][$fag]['menu'][$key]['action'] = $this->current['menugroup'][$fag]['menu'][$key]['action'] ? $this->current['menugroup'][$fag]['menu'][$key]['action'] : 'index';
        $row['menu_path']                                         = "app={$this->target_app->app_id}&ctl={$this->current['menugroup'][$fag]['menu'][$key]['controller']}&act={$this->current['menugroup'][$fag]['menu'][$key]['action']}";
        $row['menu_title']                                        = $this->current['menugroup'][$fag]['menu'][$key]['value'];
        $row['permission']                                        = $this->current['menugroup'][$fag]['menu'][$key]['permission'];
        $row['display']                                           = $this->current['menugroup'][$fag]['menu'][$key]['display'] ? $this->current['menugroup'][$fag]['menu'][$key]['display'] : "true";
        return $row;
    }
    /**
     * menu_row
     * @param mixed $fag fag
     * @return mixed 返回值
     */
    public function menu_row($fag)
    {
        if ($this->current['menu'][$fag]['params']) {
            $a_tmp = explode('|', $this->current['menu'][$fag]['params']);
            foreach ((array) $a_tmp as $k => $v) {
                $a                 = explode(':', $v);
                $url_params[$a[0]] = $a[1];
            }
            $addon['url_params'] = $url_params;
            unset($url_params);
        }
        $row = array(
            'menu_type'    => $this->content_typename(),
            'app_id'       => $this->target_app->app_id,
            'workground'   => $this->current['id'],
            'content_name' => $this->key(),
            'menu_group'   => '',
            'menu_order'   => $this->current['menu'][$fag]['order'],
            'addon'        => serialize($addon),
            'target'       => $this->current['menu'][$fag]['target'] ? $this->current['menu'][$fag]['target'] : '',
        );
        # $row = parent::row();

        $this->current['menu'][$fag]['action'] = $this->current['menu'][$fag]['action'] ? $this->current['menu'][$fag]['action'] : 'index';
        $row['menu_path']                      = "app={$this->target_app->app_id}&ctl={$this->current['menu'][$fag]['controller']}&act={$this->current['menu'][$fag]['action']}";
        $row['menu_title']                     = $this->current['menu'][$fag]['value'];
        $row['permission']                     = $this->current['menu'][$fag]['permission'];
        $row['display']                        = $this->current['menu'][$fag]['display'];
        return $row;
    }

    /**
     * install
     * @return mixed 返回值
     */
    public function install()
    {
        foreach ((array)$this->current['menugroup'] as $fag => $val) {
            foreach ((array)$val['menu'] as $key => $data) {
                kernel::log('Installing ' . $this->content_typename() . ' ' . $this->key() . $data['controller'] . '::' . $data['action']);

                $menus_row = $this->row($fag, $key);
                app::get('desktop')->model('menus')->insert($menus_row);
            }
        }
        if ($this->current['menu']) {
            foreach ($this->current['menu'] as $fag => $val) {

                kernel::log('Installing ' . $this->content_typename() . ' ' . $this->key() . $data['controller'] . '::' . $data['action']);

                $menu_row = $this->menu_row($fag);
                app::get('desktop')->model('menus')->insert($menu_row);

            }
        }

        if ($menus_row) {
            base_kvstore::instance('menudefine')->store('version', date('YmdHis'));
        }
    }

    /**
     * 清除_by_app
     * @param mixed $app_id ID
     * @return mixed 返回值
     */
    public function clear_by_app($app_id)
    {
        if (!$app_id) {
            return false;
        }
        app::get('desktop')->model('menus')->delete(array(
            'app_id' => $app_id, 'menu_type' => $this->content_typename()));
    }

}
