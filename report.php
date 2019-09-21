<?php
$guest_account=true;
chdir('../../');
include_once('./include/global.php');
include_once('./include/auth.php');
include_once($config['base_path'] . '/lib/rrd.php');
include_once($config['base_path'] . '/plugins/report/phpexcel/PHPExcel.php');//PHPExcel函数文件
include_once($config['base_path'] . '/plugins/report/report_functions.php');//报表管理公共函数文件
include_once($config['base_path'] . '/plugins/report/idc_statistic.php');//IDC统计
include_once($config['base_path'] . '/plugins/report/traffic_settlement.php');//流量结算统计
switch(get_request_var('action')) {
	case 'ajax_tree'://ajax得到tree请求数据
        ajax_tree();
        break;
	case 'traffic_settlement'://流量结算统计
		general_header();
		report_tabs('traffic_settlement');
		traffic_settlement();
		bottom_footer();
		break;
	case 'traffic_settlement_edit'://流量结算统计编辑页面
		general_header();
		traffic_settlement_edit();
		bottom_footer();
		break;
	case 'traffic_settlement_save'://流量结算统计保存
		traffic_settlement_save();
		break;
	case 'idc_statistic'://IDC统计
		general_header();
		report_tabs('idc_statistic');
		idc_statistic();
		bottom_footer();
		break;
	case 'idc_statistic_edit'://IDC统计编辑页面
		general_header();
		idc_statistic_edit();
		bottom_footer();
		break;
	case 'idc_statistic_save'://IDC统计保存
		idc_statistic_save();
		break;
	case 'idc_statistic_import'://IDC统计导出
		general_header();
		idc_statistic_import();
		bottom_footer();
		break;
	case 'do_idc_statistic_import'://执行IDC统计导出
		do_idc_statistic_import();
		break;
	case 'traffic_settlement_import'://流量结算统计导出
		general_header();
		traffic_settlement_import();
		bottom_footer();
		break;
	case 'do_traffic_settlement_import'://执行流量结算统计导出
		do_traffic_settlement_import();
		break;
	case 'actions':
		form_actions();
		break;
	default:
        general_header();
        report_tabs('traffic_settlement');
        traffic_settlement();
        bottom_footer();
		break;
}
/**
 * ajax得到tree请求数据
 */
function ajax_tree(){
	$graph_tree_id=get_filter_request_var('graph_tree_id');
    $data=get_tree_data($graph_tree_id,0);
    print json_encode($data);
}

/**
 * form_actions
 */
function form_actions() {
	global $config;
    global $traffic_settlement_actions,$idc_statistic_actions;
    /* ================= input validation ================= */
    get_filter_request_var('drp_action', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^([a-zA-Z0-9_]+)$/')));
	/**********************流量结算管理操作begin***********************/
	if(get_nfilter_request_var('drp_action') == '11'||get_nfilter_request_var('drp_action') == '12'){
		//流量结算删除操作begin
		if (isset_request_var('selected_items')) {
			$selected_items = sanitize_unserialize_selected_items(get_nfilter_request_var('selected_items'));
			if ($selected_items != false) {
				if (get_nfilter_request_var('drp_action') == '11') { /* delete */
					//cacti_log('DELETE FROM plugin_report_traffic_settlement WHERE ' . array_to_sql_or($selected_items, 'id'));
					//cacti_log('DELETE FROM plugin_report_traffic_settlement_detail WHERE ' . array_to_sql_or($selected_items, 'report_traffic_settlement_id'));
					//cacti_log('DELETE FROM plugin_report_traffic_settlement_excel WHERE ' . array_to_sql_or($selected_items, 'report_traffic_settlement_id'));
					db_execute('DELETE FROM plugin_report_traffic_settlement WHERE ' . array_to_sql_or($selected_items, 'id'));
					db_execute('DELETE FROM plugin_report_traffic_settlement_detail WHERE ' . array_to_sql_or($selected_items, 'report_traffic_settlement_id'));
					db_execute('DELETE FROM plugin_report_traffic_settlement_excel WHERE ' . array_to_sql_or($selected_items, 'report_traffic_settlement_id'));
				}
			}
			header('Location: report.php?action=traffic_settlement&header=false');
			exit;
		}
		//流量结算删除操作end
		$report_traffic_settlement_html = ''; $i = 0; $report_traffic_settlement_id_list='';
		foreach ($_POST as $var => $val) {
			if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
				/* ================= input validation ================= */
				input_validate_input_number($matches[1]);
				$report_traffic_settlement_name=html_escape(db_fetch_cell_prepared('SELECT name FROM plugin_report_traffic_settlement WHERE id = ?', array($matches[1])));
				/* ==================================================== */
				if(get_nfilter_request_var('drp_action') == '11'){//删除
					$report_traffic_settlement_html .= '<li>' . $report_traffic_settlement_name  . '</li>';
				}
				if(get_nfilter_request_var('drp_action') == '12'){//下载
					$report_traffic_settlement_html .= '<h3>' . $report_traffic_settlement_name  . '</h3>';
					$report_traffic_settlement_html .='<ul>';
					//获取excel集合
					$report_traffic_settlement_excel_array_1= db_fetch_assoc("SELECT * FROM plugin_report_traffic_settlement_excel WHERE  excel_type='实时统计' and report_traffic_settlement_id = ". $matches[1] . " order by last_modified desc limit 1");
					//$report_traffic_settlement_excel_array_2= db_fetch_assoc("SELECT * FROM plugin_report_traffic_settlement_excel WHERE  excel_type!='实时统计' and report_traffic_settlement_id = ". $matches[1] . " order by last_modified desc");
					$report_traffic_settlement_excel_array_2= db_fetch_assoc("SELECT * FROM plugin_report_traffic_settlement_excel WHERE ( excel_type='周统计' or excel_type='月统计')  and report_traffic_settlement_id = ". $matches[1] . " order by last_modified desc");

					$report_traffic_settlement_excel_array=array_merge($report_traffic_settlement_excel_array_1,$report_traffic_settlement_excel_array_2);
					if(cacti_count($report_traffic_settlement_excel_array)==0){
						$report_traffic_settlement_html .= '<li>暂无数据</li>';
					}else{
						foreach ($report_traffic_settlement_excel_array as $report_traffic_settlement_excel){
							//$report_traffic_settlement_html .= '<li><a target="_blank" href="' . $config['url_path'] . $report_traffic_settlement_excel['excel_path'] . '" download="' . $report_traffic_settlement_excel['excel_name'] . '">' . html_escape($report_traffic_settlement_excel['excel_name']) . '</a>----'.$report_traffic_settlement_excel['excel_type'] . '</li>';
							$report_traffic_settlement_html .= '<li><a target="_blank" href="' . $config['url_path'] . $report_traffic_settlement_excel['excel_path']  . '" download="' . $report_traffic_settlement_excel['excel_name'] . '">' . html_escape($report_traffic_settlement_excel['excel_name']) . '</a></li>';
						}
					}
					//获取excel集合
					$report_traffic_settlement_html .='</ul>';
				}
				$report_traffic_settlement_id_list[$i] = $matches[1];
				$i++;
			}
		}
		top_header();
		form_start('report.php');
		html_start_box($traffic_settlement_actions[get_nfilter_request_var('drp_action')], '60%', '', '3', 'center', '');
		if (isset($report_traffic_settlement_id_list) && cacti_sizeof($report_traffic_settlement_id_list)) {
			if (get_nfilter_request_var('drp_action') == '11') { /* delete */
				print "<tr>
					<td class='textArea' class='odd'>
						<p>点击'继续'删除以下流量结算</p>
						<div class='itemlist'><ul>$report_traffic_settlement_html</ul></div>
					</td>
				</tr>\n";
				$save_html = "<input type='button' class='ui-button ui-corner-all ui-widget' value='" . __esc('Cancel') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' class='ui-button ui-corner-all ui-widget' value='" . __esc('Continue') . "' title='删除流量结算'>";
			}
			if (get_nfilter_request_var('drp_action') == '12') { /* download */
				print "<tr>
					<td class='textArea' class='odd'>
						<p>点击文件名称下载报表</p>
						<div class='itemlist'>$report_traffic_settlement_html</div>
					</td>
				</tr>\n";
				$save_html = "<input type='button' class='ui-button ui-corner-all ui-widget' value='" . __esc('Cancel') . "' onClick='cactiReturnTo()'>";
			}
		} else {
			raise_message(40);
			header('Location: report.php?action=traffic_settlement&header=false');
			exit;
		}
        print "<tr>
					<td class='saveRow'>
						<input type='hidden' name='action' value='actions'>
						<input type='hidden' name='selected_items' value='" . (isset($report_traffic_settlement_id_list) ? serialize($report_traffic_settlement_id_list) : '') . "'>
						<input type='hidden' name='drp_action' value='" . html_escape(get_nfilter_request_var('drp_action')) . "'>
						$save_html
					</td>
			   </tr>\n";
		html_end_box();
		form_end();
		bottom_footer();
	}
	/**********************流量结算管理操作end***********************/
	
	/**********************IDC统计管理操作begin***********************/
	if(get_nfilter_request_var('drp_action') == '21'||get_nfilter_request_var('drp_action') == '22'){
		//IDC统计删除操作begin
		if (isset_request_var('selected_items')) {
			$selected_items = sanitize_unserialize_selected_items(get_nfilter_request_var('selected_items'));
			if ($selected_items != false) {
				if (get_nfilter_request_var('drp_action') == '21') { /* delete */
					//cacti_log('DELETE FROM plugin_report_idc_statistic WHERE ' . array_to_sql_or($selected_items, 'id'));
					//cacti_log('DELETE FROM plugin_report_idc_statistic_detail WHERE ' . array_to_sql_or($selected_items, 'report_idc_statistic_id'));
					//cacti_log('DELETE FROM plugin_report_idc_statistic_excel WHERE ' . array_to_sql_or($selected_items, 'report_idc_statistic_id'));
					db_execute('DELETE FROM plugin_report_idc_statistic WHERE ' . array_to_sql_or($selected_items, 'id'));
					db_execute('DELETE FROM plugin_report_idc_statistic_detail WHERE ' . array_to_sql_or($selected_items, 'report_idc_statistic_id'));
					db_execute('DELETE FROM plugin_report_idc_statistic_excel WHERE ' . array_to_sql_or($selected_items, 'report_idc_statistic_id'));
				}
			}
			header('Location: report.php?action=idc_statistic&header=false');
			exit;
		}
		//IDC统计删除操作end
		$report_idc_statistic_html = ''; $i = 0; $report_idc_statistic_id_list='';
		foreach ($_POST as $var => $val) {
			if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
				/* ================= input validation ================= */
				input_validate_input_number($matches[1]);
				$report_idc_statistic_name=html_escape(db_fetch_cell_prepared('SELECT name FROM plugin_report_idc_statistic WHERE id = ?', array($matches[1])));
				/* ==================================================== */
				if(get_nfilter_request_var('drp_action') == '21'){//删除
					$report_idc_statistic_html .= '<li>' . $report_idc_statistic_name  . '</li>';
				}
				if(get_nfilter_request_var('drp_action') == '22'){//下载
					$report_idc_statistic_html .= '<h3>' . $report_idc_statistic_name  . '</h3>';
					$report_idc_statistic_html .='<ul>';
					//获取excel集合
					$report_idc_statistic_excel_array_1= db_fetch_assoc("SELECT * FROM plugin_report_idc_statistic_excel WHERE  excel_type='实时统计' and report_idc_statistic_id = ". $matches[1] . " order by last_modified desc limit 1");
					//$report_idc_statistic_excel_array_2= db_fetch_assoc("SELECT * FROM plugin_report_idc_statistic_excel WHERE  excel_type!='实时统计' and report_idc_statistic_id = ". $matches[1] . " order by last_modified desc");
					$report_idc_statistic_excel_array_2= db_fetch_assoc("SELECT * FROM plugin_report_idc_statistic_excel WHERE  (excel_type='周统计' or excel_type='月统计') and report_idc_statistic_id = ". $matches[1] . " order by last_modified desc");
					$report_idc_statistic_excel_array=array_merge($report_idc_statistic_excel_array_1,$report_idc_statistic_excel_array_2);
					if(cacti_count($report_idc_statistic_excel_array)==0){
						$report_idc_statistic_html .= '<li>暂无数据</li>';
					}else{
						foreach ($report_idc_statistic_excel_array as $report_idc_statistic_excel){
							//$report_idc_statistic_html .= '<li><a target="_blank" href="' . $config['url_path'] . $report_idc_statistic_excel['excel_path'] . '" download="' . $report_idc_statistic_excel['excel_name'] . '">' . html_escape($report_idc_statistic_excel['excel_name']) . '</a>----'.$report_idc_statistic_excel['excel_type'] . '</li>';
							$report_idc_statistic_html .= '<li><a target="_blank" href="' . $config['url_path'] . $report_idc_statistic_excel['excel_path']  . '" download="' . $report_idc_statistic_excel['excel_name'] . '">'  . html_escape($report_idc_statistic_excel['excel_name']) . '</a></li>';
						}
					}
					//获取excel集合
					$report_idc_statistic_html .='</ul>';
				}
				$report_idc_statistic_id_list[$i] = $matches[1];
				$i++;
			}
		}
		top_header();
		form_start('report.php');
		html_start_box($idc_statistic_actions[get_nfilter_request_var('drp_action')], '60%', '', '3', 'center', '');
		if (isset($report_idc_statistic_id_list) && cacti_sizeof($report_idc_statistic_id_list)) {
			if (get_nfilter_request_var('drp_action') == '21') { /* delete */
				print "<tr>
					<td class='textArea' class='odd'>
						<p>点击'继续'删除以下IDC统计</p>
						<div class='itemlist'><ul>$report_idc_statistic_html</ul></div>
					</td>
				</tr>\n";
				$save_html = "<input type='button' class='ui-button ui-corner-all ui-widget' value='" . __esc('Cancel') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' class='ui-button ui-corner-all ui-widget' value='" . __esc('Continue') . "' title='删除IDC统计'>";
			}
			if (get_nfilter_request_var('drp_action') == '22') { /* download */
				print "<tr>
					<td class='textArea' class='odd'>
						<p>点击文件名称下载报表</p>
						<div class='itemlist'>$report_idc_statistic_html</div>
					</td>
				</tr>\n";
				$save_html = "<input type='button' class='ui-button ui-corner-all ui-widget' value='" . __esc('Cancel') . "' onClick='cactiReturnTo()'>";
			}
		} else {
			raise_message(40);
			header('Location: report.php?action=idc_statistic&header=false');
			exit;
		}
        print "<tr>
					<td class='saveRow'>
						<input type='hidden' name='action' value='actions'>
						<input type='hidden' name='selected_items' value='" . (isset($report_idc_statistic_id_list) ? serialize($report_idc_statistic_id_list) : '') . "'>
						<input type='hidden' name='drp_action' value='" . html_escape(get_nfilter_request_var('drp_action')) . "'>
						$save_html
					</td>
			   </tr>\n";
		html_end_box();
		form_end();
		bottom_footer();
	}
	/**********************IDC统计管理操作end***********************/

	/***************************************IDC导出操作begin*************************************** */
	if(get_nfilter_request_var('drp_action') == '23'){
		$report_idc_statistic_id_list = ''; $i = 0;
		foreach ($_POST as $var => $val) {
			if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
				input_validate_input_number($matches[1]);
				$report_idc_statistic_id_list[$i] = $matches[1];
				$i++;
			}
		}
		if (isset($report_idc_statistic_id_list) && cacti_sizeof($report_idc_statistic_id_list)) {
			if (get_nfilter_request_var('drp_action') == '23') {//记录操作
				if(cacti_sizeof($report_idc_statistic_id_list)>1){
					raise_message(2,'只能选择一条数据操作',MESSAGE_LEVEL_ERROR);
					header('Location: report.php?action=idc_statistic&header=false');
					exit;
				}else{
					header('Location: report.php?action=idc_statistic_import&idc_statistic_id=' . $report_idc_statistic_id_list[0]);
					exit;
				}
			}
		} else {
			raise_message(40);
			header('Location: report.php?action=idc_statistic&header=false');
			exit;
		}
	}
	/***********************IDC导出操作end ************************/

	/***************************************流量结算统计导出操作begin*************************************** */
	if(get_nfilter_request_var('drp_action') == '13'){
		$report_traffic_settlement_id_list = ''; $i = 0;
		foreach ($_POST as $var => $val) {
			if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
				input_validate_input_number($matches[1]);
				$report_traffic_settlement_id_list[$i] = $matches[1];
				$i++;
			}
		}
		if (isset($report_traffic_settlement_id_list) && cacti_sizeof($report_traffic_settlement_id_list)) {
			if (get_nfilter_request_var('drp_action') == '13') {//记录操作
				if(cacti_sizeof($report_traffic_settlement_id_list)>1){
					raise_message(2,'只能选择一条数据操作',MESSAGE_LEVEL_ERROR);
					header('Location: report.php?action=traffic_settlement&header=false');
					exit;
				}else{
					header('Location: report.php?action=traffic_settlement_import&traffic_settlement_id=' . $report_traffic_settlement_id_list[0]);
					exit;
				}
			}
		} else {
			raise_message(40);
			header('Location: report.php?action=traffic_settlement&header=false');
			exit;
		}
	}
	/***********************流量结算统计导出操作end ************************/

}
?>