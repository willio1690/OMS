<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

//todo:检查是否支持php5及pathinfo

if (version_compare(phpversion(), '5', '<')) {
    Header('Location: view/notice_php.html');   //todo:不支持php5，警告页
    exit;
}

if (isset($_GET['check_path_info'])) {
    $path_info = get_path_info();
    if ($path_info) {
        echo 'SUPPORT_PATHINFO';
    } else {
        echo 'NO_SUPPORT_PATHINFO';
    }
    exit;
}

function get_path_info() {
    $path_info = '';
    if (isset($_SERVER['PATH_INFO'])) {
        $path_info = $_SERVER['PATH_INFO'];
    } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
        $path_info = $_SERVER['ORIG_PATH_INFO'];
        $script_name = get_script_name();
        if (substr($script_name, -1, 1) == '/') {
            $path_info = $path_info . '/';
        }
    } else {
        $script_name = get_script_name();
        $script_dir = preg_replace('/[^\/]+$/', '', $script_name);
        $request_uri = get_request_uri();
        $urlinfo = parse_url($request_uri);
        if (strpos($urlinfo['path'], $script_name) === 0) {
            $path_info = substr($urlinfo['path'], strlen($script_name));
        } elseif (strpos($urlinfo['path'], $script_dir) === 0) {
            $path_info = substr($urlinfo['path'], strlen($script_dir));
        }
    }
    if ($path_info) {
        $path_info = "/" . ltrim($path_info, "/");
    }

    return $path_info;
}

function get_script_name() {
    return isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : (isset($_SERVER['ORIG_SCRIPT_NAME']) ? $_SERVER['ORIG_SCRIPT_NAME'] : '');
}

function get_request_uri() {
    if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
        return $_SERVER['HTTP_X_REWRITE_URL'];
    } elseif (isset($_SERVER['REQUEST_URI'])) {
        return $_SERVER['REQUEST_URI'];
    } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
        return $_SERVER['ORIG_PATH_INFO'] . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
    }
}

function get_host() {
    $host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
    //$host = $_SERVER['HTTP_HOST'];
    if (!empty($host)) {
        return $host;
    }

    $scheme = get_schema();
    $name = get_name();
    $port = get_port();

    if (($scheme == "HTTP" && $port == 80) || ($scheme == "HTTPS" && $port == 443)) {
        return $name;
    } else {
        return $name . ':' . $port;
    }
}

function get_name() {
    return isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['SERVER_NAME'];
}//End Function

function get_schema() {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? "HTTPS" : "HTTP";
}//End Function

function get_port() {
    return $_SERVER['SERVER_PORT'];
}//End Function

function check_pathinfo() {

    $host = get_host();
    $port = get_port();
    $uri = strtolower(get_schema()) . '://' . get_host() . get_request_uri() . '/pathinfotest?check_path_info=1';
    $schema = get_schema();

    if ($schema == 'HTTPS') {

        $host_addr_arr = array(
            'ssl://' . $host,
            'ssl://' . '127.0.0.1' . ':' . $port,
            'ssl://' . 'localhost' . ':' . $port,
        );
    } else {

        $host_addr_arr = array(
            'tcp://' . $host,
            'tcp://' . '127.0.0.1' . ':' . $port,
            'tcp://' . 'localhost' . ':' . $port,
        );
    }

    $stream_context = stream_context_create(array('ssl' => array(
        'verify_peer'       => false,
        'verify_peer_name'  => false,
        'allow_self_signed' => true)));


    foreach ($host_addr_arr AS $host_address) {
        $content = '';
        $fp = @stream_socket_client($host_address, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $stream_context);
        if ($fp) {
            $out = "GET " . $uri . " HTTP/1.1\r\n";
            $out .= "Host: {$host}\r\n";
            $out .= "Connection: close\r\n\r\n";
            fwrite($fp, $out);
            while (!feof($fp) && strlen((string)$content) < 512) {
                $content .= fgets($fp, 128);
            }
            fclose($fp);
        }
        $content = preg_split("\r?\n\r?\n", $content);
        
        //check
        if(isset($content[1])){
            if (strpos($content[1], 'SUPPORT_PATHINFO') !== false) {
                return true;
            }
        }
        
    }

    if (function_exists('curl_init')) {
        ob_start();
        $fp = curl_init();
        curl_setopt($fp, CURLOPT_URL, $uri);
        curl_setopt($fp, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($fp, CURLOPT_SSL_VERIFYHOST, FALSE);
        $content = curl_exec($fp);
        curl_close($fp);
        ob_end_clean();

        return ($content == 'SUPPORT_PATHINFO') ? true : false;
    } else {
        return false;
    }
}

if (check_pathinfo()) {
    $url = $_COOKIE['LOCAL_SETUP_URL'];
    setCookie('LOCAL_SETUP_URL', '', 0, '/');
    Header('Location: ' . $url);    //todo:进入安装流程
    exit;
} else {
    Header('Location: view/notice_pathinfo.html');   //todo:不支持pathinfo，警告页
    exit;
}