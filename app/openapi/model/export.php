<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2021/6/11 14:24:46
 * @describe: model层
 * ============================
 */
class openapi_mdl_export extends dbeav_model {

    /**
     * gen_id
     * @return mixed 返回值
     */

    public function gen_id() {
        $prefix = date("ymd");
        $sign   = kernel::single('eccommon_guid')->incId('openapiexport', $prefix, 4);
        return $sign;
    }

    /**
     * modifier_download_url
     * @param mixed $col col
     * @param mixed $list list
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_download_url($col,$list,$row) {
        $finder_id      = $_GET['_finder']['finder_id'];
        $ret = '';
        if($col) {
            $ret = '<a target="_blank" href="index.php?app=openapi&ctl=admin_export&act=download&p[0]='.$row['id'].'&finder_id=' . $finder_id . '">点击下载</a>';
        }
        return $ret;
    }

    /**
     * modifier_bill_time
     * @param mixed $col col
     * @param mixed $list list
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_bill_time($col,$list,$row) {
        return $col ? date('Y-m-d', $col) : '';
    }

    /**
     * 设置FTP config
     * @param array $config array(
                                'HOST'    => '127.0.0.1',
                                'PORT'    => '21',
                                'USER'    => 'test',
                                'PASSWD'  => 'test',
                                'TIMEOUT' => '30',
                                'SSL'    => true,
                                'PASV'    => true,
                            )
     */
    public function setFtpConfig($config) {
        app::get('openapi')->setConf('export.ftp.config',$config);
    }

    public function getDownloadFile($url,$local_file){
        $config = app::get('openapi')->getConf('export.ftp.config');
        if (empty($config)) {
            return [false, 'ftp 未配置'];
        }

        if($config['SSL'] == true) {
            $ftpRs = ftp_ssl_connect($config['HOST'], $config['PORT'], $config['TIMEOUT']);
        } else {
            $ftpRs = ftp_connect($config['HOST'], $config['PORT'], $config['TIMEOUT']);
        }
        if (!$ftpRs) {
            return [false, 'ftp 连接失败'];
        }

        $login_result = ftp_login($ftpRs, $config['USER'], $config['PASSWD']);
        if (!$login_result) {
            return [false, 'ftp 登录失败'];
        }
        #忽略FTP服务器返回的IP地址而直接使用ftp_connect里面写的IP地址
        ftp_set_option($ftpRs, FTP_USEPASVADDRESS, false);

        if ($config['PASV'] == true) {
            ftp_pasv($ftpRs, true);
        }

        $download_result = ftp_get($ftpRs, $local_file, $url, FTP_BINARY);

        //ftp链接退出
        ftp_close($ftpRs);
        unset($ftpRs);

        if ($download_result) {
            return [true];
        }
        return [false, 'ftp 下载失败'];
    }
}