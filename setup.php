<?php

/**
 * 安装时的方法
 */
function plugin_report_install() {
    /* core plugin functionality */
    api_plugin_register_hook('report', 'top_header_tabs', 'report_show_tab', 'setup.php');
    api_plugin_register_hook('report', 'top_graph_header_tabs', 'report_show_tab', 'setup.php');
    // Breadcrums
    api_plugin_register_hook("report", 'draw_navigation_text', 'report_draw_navigation_text', 'setup.php');
    api_plugin_register_hook('report', 'page_head', 'plugin_report_page_head', 'setup.php');
    api_plugin_register_realm('report', 'report.php', '报表管理', 1);
    report_setup_table();
}

/**
 * 卸载时候的方法
 */
function plugin_report_uninstall() {

}

/**
 * 用于检查插件的版本，并提供更多信息
 * @return mixed
 */
function plugin_report_version() {
    global $config;
    $info = parse_ini_file($config['base_path'] . '/plugins/report/INFO', true);
    return $info['info'];
}

/**
 * 用于确定您的插件是否已准备好在安装后启用
 */
function plugin_report_check_config() {
    return true;
}

/**
 * 显示顶部选项卡
 */
function report_show_tab() {
    global $config;
    print '<a href="' . $config['url_path'] . 'plugins/report/report.php"><img src="' . $config['url_path'] . 'plugins/monitor/images/tab_monitor.gif" alt="报表管理"></a>';
}

/**
 * 面包屑
 */
function report_draw_navigation_text ($nav) {
    $nav['report:'] = array('title' => "报表管理", 'mapping' => '', 'url' => 'report.php', 'level' => '0');
    $nav['report.php:'] = array('title' => "流量结算统计", 'mapping' => 'report:', 'url' => 'report.php', 'level' => '1');
    
    $nav['report.php:traffic_settlement'] = array('title' => "流量结算统计", 'mapping' => 'report:', 'url' => 'report.php?action=traffic_settlement', 'level' => '1');
    $nav['report.php:traffic_settlement_edit'] = array('title' => "流量结算统计编辑", 'mapping' => 'report:,report.php:traffic_settlement', 'url' => 'report.php?action=traffic_settlement_edit', 'level' => '2');
    $nav['report.php:traffic_settlement_import'] = array('title' => "流量结算统计导出", 'mapping' => 'report:,report.php:traffic_settlement', 'url' => 'report.php?action=traffic_settlement_import', 'level' => '2');

    $nav['report.php:idc_statistic'] = array('title' => "IDC统计", 'mapping' => 'report:', 'url' => 'report.php?action=idc_statistic', 'level' => '1');
    $nav['report.php:idc_statistic_edit'] = array('title' => "IDC统计编辑", 'mapping' => 'report:,report.php:idc_statistic', 'url' => 'report.php?action=idc_statistic_edit', 'level' => '2');
    $nav['report.php:idc_statistic_import'] = array('title' => "IDC统计导出", 'mapping' => 'report:,report.php:idc_statistic', 'url' => 'report.php?action=idc_statistic_import', 'level' => '2');

    return $nav;
}

/**
 * 自定义js
 */
function plugin_report_page_head() {
     print get_md5_include_css('plugins/report/include/css/report.css') . PHP_EOL;
}

function report_setup_table() {
    
}