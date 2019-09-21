<?php
//动态tab选显卡操作
function report_tabs($current_tab='traffic') {
	global $config;
	/* present a tabbed interface */
	$tabs = array(
		'traffic_settlement'    => __('流量结算统计', 'report'),
		'idc_statistic'=> __('IDC统计', 'report')
	);
	$tabs = api_plugin_hook_function('report_tabs', $tabs);//资产管理table
	get_filter_request_var('tab', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^([a-zA-Z]+)$/')));
	load_current_session_value('tab', 'sess_report_tab', 'general');
	//$current_tab = get_request_var('action');//得到当前选项卡
	print "<div class='tabs'><nav><ul>\n";
	if (cacti_sizeof($tabs)) {//得到选项卡
		foreach (array_keys($tabs) as $tab_short_name) {
			print "<li><a class='tab" . (($tab_short_name == $current_tab) ? " selected'" : "'") .
				" href='" . html_escape($config['url_path'] .
				'plugins/report/report.php?' .
				'action=' . $tab_short_name) .
				"'>" . $tabs[$tab_short_name] . "</a></li>\n";
		}
	}
	print "</ul></nav></div>\n";
}
/**
 * 根据图形ID得到数据信息
 * 返回 local_data_id与upper_limit
 */
function get_local_data($local_graph_id){
    $graph_local = db_fetch_row_prepared('select dl.id as local_data_id ,gtg.upper_limit
                                        from data_local dl
                                        left join graph_local gl
                                        on dl.host_id = gl.host_id and dl.snmp_query_id = gl.snmp_query_id 
                                        and dl.snmp_index = gl.snmp_index
                                        left join graph_templates_graph gtg on gl.id = gtg.local_graph_id
                                        where gl.id =? limit 1',array($local_graph_id));
    return $graph_local;
}
/**
 * 根据图形ID与开始结束时间得到当前图形最大的出口与入口流量
 * 返回的格式： 单位是bit
 * array(
        "traffic_in" => value1,
        "traffic_out" => value2
    );
 * @return number[]
 */
function get_traffic_max_value($local_data_id, $start_time, $end_time){
    $result = rrdtool_function_fetch($local_data_id, $start_time, $end_time, 0,false,null,'MAX','');
    $array_in = array_search("traffic_in", $result['data_source_names']);//入口流量集合
    $array_out = array_search("traffic_out", $result['data_source_names']);//出口流量集合
    if (!isset($result['values'][$array_in]) || count($result['values'][$array_in]) == 0) {
        $traffic_in = 0;
    }else {
        $traffic_in = max($result['values'][$array_in]);
    }
    if (!isset($result['values'][$array_out]) || count($result['values'][$array_out]) == 0) {
        $traffic_out = 0;
    }else{
        $traffic_out = max($result['values'][$array_out]);
    }
    return array(
        "traffic_in" => getUnitVal($traffic_in * 8),
        "traffic_out" => getUnitVal($traffic_out * 8)
    );
}
/**
 * 得到IDC峰值
 * 返回的格式： 单位是bit
 * array(
        "traffic_in" => [6,5,4,3,2,1],
        "traffic_out" => [6,5,4,3,2,1]
    );
 * 
 */
function get_idc_peak_value($local_data_id, $start_time, $end_time){
    $result = rrdtool_function_fetch($local_data_id, $start_time, $end_time, 0,false,null,'MAX','');
    $array_in = array_search("traffic_in", $result['data_source_names']);
    $array_out = array_search("traffic_out", $result['data_source_names']);
    if (!isset($result['values'][$array_in]) || count($result['values'][$array_in]) == 0) {
        $traffic_in = array(0,0,0,0,0,0);
    }else {
        $traffic_in=array();
        foreach (array($result['values'][$array_in]) as $data => $timestamp_data) {
            foreach ($timestamp_data as $key=>$value) {
                $traffic_in[]=getUnitVal($value * 8);
            }
        }
        rsort($traffic_in);//降序操作
    }
    if (!isset($result['values'][$array_out]) || count($result['values'][$array_out]) == 0) {
        $traffic_out = array(0,0,0,0,0,0);
    }else{
        $traffic_out=array();
        foreach (array($result['values'][$array_out]) as $data => $timestamp_data) {
            foreach ($timestamp_data as $key=>$value) {
                $traffic_out[]=getUnitVal($value * 8);
            }
        }
        rsort($traffic_out);//降序操作
    }
    return array(
        "traffic_in" => $traffic_in,
        "traffic_out" => $traffic_out
    );
}
/**
 * 返回的格式： 单位是G
 */
function getUnitVal($val){
    $val = round($val / 1000000000,10);
    return $val;
}
/**
 * 得到图形层级树data
 * $tree_id 树ID
 * $parent=0 父级菜单
 */
function get_tree_data($tree_id, $parent = 0) {
    $tree_array = array();
	$heirarchy = get_allowed_tree_level($tree_id, $parent, false, 0 );//$heirarchy等级秩序
	if (cacti_sizeof($heirarchy)) {
		foreach ($heirarchy as $leaf) {//$leaf叶子节点
            $node['id']= $leaf['id'];
			if ($leaf['host_id'] > 0) {
                $node['type']='device';
                $node['host_id']=$leaf['host_id'];
                $node['text']=html_escape($leaf['hostname']);
			} elseif ($leaf['site_id'] > 0) {
                $node['type']='site';
                $node['site_id']=$leaf['site_id'];
                $node['text']=html_escape($leaf['sitename']);
			} elseif ($leaf['local_graph_id'] > 0) {
                $node['type']='graph';
                $node['local_graph_id']=$leaf['local_graph_id'];
                $node['text']=html_escape(get_graph_title($leaf['local_graph_id']));
			} else {
                $node['type']='';
                $node['text']=html_escape($leaf['title']);
            }
            if($leaf['local_graph_id'] == 0){//表示非图形数据
                $node['children']=get_tree_data($tree_id,$leaf['id']);
            }
            $tree_array[]=$node;
		}
    }
    return $tree_array;
}
/**
 * 获取指定日期段内每一天的日期
 * @param  Date  $begin_date 开始日期
 * @param  Date  $end_date   结束日期
 * @return Array
 */
function getDateFromRange($begin_date, $end_date){
    $begin_timestamp = strtotime($begin_date);
    $end_timestamp = strtotime($end_date);
    // 计算日期段内有多少天
    $days = ($end_timestamp-$begin_timestamp)/86400+1;
    // 保存每天日期
    $date = array();
    for($i=0; $i<$days; $i++){
        $date[] = date('Y-m-d', $begin_timestamp+(86400*$i));
    }
    return $date;
}
/**
 * 获取单元格的AA等格式
 */
function getCellIndex($index){
    $cellIndex=chr($index+65);//类似A B AA等的格式
    $result = ($index / 26);//26个英文字母
    if ($result >= 1){
        $result = intval($result);//取整数
        $cellIndex=chr($result+64) . chr($index-$result*26 + 65);//拼装AA格式
    }
    return $cellIndex;
}

//遍历集合end
function idc_statistic_excel($report_idc_statistic,$idc_statistic_type,$data_begin_date,$data_end_date){
    $excel_name=$report_idc_statistic['name'] . '-';
    $excel_name=$excel_name . ('IDC统计报表(' . $data_begin_date . '至' . $data_end_date . ')');
    $excel_type=$idc_statistic_type;//excel类型
    $report_idc_statistic_excel= db_fetch_row_prepared("SELECT * FROM plugin_report_idc_statistic_excel WHERE excel_name = '" . $excel_name . "' and excel_type = '" . $excel_type . "'");
    if(isset($report_idc_statistic_excel['id'])&&$report_idc_statistic_excel['excel_type']!='手工统计'){//数据库中已经存在记录
        return;
     }
    $extension=json_decode($report_idc_statistic['extension'],true);//将json字符串转为对象
    $datas_checked=$extension['datas_checked'];//报表配置data
    $peak_count=0;
    if($report_idc_statistic['is_first_max']=='on'){
        $peak_count++;
    }
    if($report_idc_statistic['is_second_max']=='on'){
        $peak_count++;
    }
    if($report_idc_statistic['is_three_max']=='on'){
        $peak_count++;
    }
    if($report_idc_statistic['is_fourth_max']=='on'){
        $peak_count++;
    }
    $style_array = array(
        'borders' => array(
            'allborders' => array(
                'style' =>PHPExcel_Style_Border::BORDER_THIN
            )
        ),
        'font'=> array (  
            'bold'=> true  
        ),
        'alignment' => array (  
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical'=> PHPExcel_Style_Alignment::VERTICAL_CENTER
        )
    );
    //第一行的样式
    $style_array_1 = array(
        'fill' => array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array('rgb' => 'CCFFCC')
        ),
        'borders' => array(
            'allborders' => array(
                'style' =>PHPExcel_Style_Border::BORDER_THIN
            )
        ),
        'font'=> array (  
            'bold'=> true  
        ),
        'alignment' => array (  
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical'=> PHPExcel_Style_Alignment::VERTICAL_CENTER
        )
    );
    $objReader = PHPExcel_IOFactory::createReader('Excel2007');//创建一个读Excel模版的对象
    $objPHPExcel = $objReader->load (dirname(__FILE__) . "/templates/idc_statistic.xlsx" );//获取模版
    // 操作第一个工作表
    $objActSheet = $objPHPExcel->getActiveSheet();//获取当前活动的表
    $objActSheet->setTitle('IDC统计报表('. $data_begin_date . '至' . $data_end_date . ')');
    /************************************第一行操作开始*************************************/
    $objActSheet->setCellValue('A1', '');
    $objActSheet->getStyle('A1')->applyFromArray($style_array);//设置边框样式
    $index=1;//单元格操作从1开始
    $local_graph_array_checked=array();//选中的图形集合
    //第一层数据遍历begin
    foreach ($datas_checked as $firstData){//第一层地区数据
        if(isset($firstData['checked'])&&$firstData['checked']){//区县是否选中状态
            $region_id=$firstData['id'];//地区ID
            $region_name=$firstData['text'];//地区名称
            if(isset($firstData['children'])){
                //第二层数据遍历begin
                foreach ($firstData['children'] as $secondtData){//遍历图形data
                    if(isset($secondtData['local_graph_id'])&&isset($secondtData['checked'])&&$secondtData['checked']){
                        $local_graph_array_checked[]=$secondtData;//追加到选中的图形中
                        $city_id=$secondtData['id'];//城市ID
                        $city_name=$secondtData['text'];//城市名称
                        $cellBegin=getCellIndex($index);//将数字转化为AA形式的
                        $index+=($peak_count-1);//?很重要
                        $cellEnd=getCellIndex($index);
                        $objActSheet->mergeCells($cellBegin . '1:' . $cellEnd . "1");// 合并单元格
                        $objActSheet->setCellValue($cellBegin . '1', $secondtData['text']);//设置地区名
                        $objActSheet->getStyle($cellBegin . '1:' . $cellEnd . "1")->applyFromArray($style_array_1);//设置边框样式
                        $index++;//操作完成后+1
                    }
                }
                //第二层数据遍历end
            }
        }
    }
    //第一层数据遍历end
    /************************************第一行操作完成*************************************/

    /************************************第二行操作开始*************************************/
    $x=0;
    foreach ($local_graph_array_checked as $local_graph_checked){//遍历选中的图形
        $cellIndex=getCellIndex($x);
        if($x==0){
            $objActSheet->setCellValue($cellIndex . '2', '日期');
            $objActSheet->getStyle($cellIndex . '2')->applyFromArray($style_array);//设置边框样式
        }
        if($report_idc_statistic['is_first_max']=='on'){
            $x++;
            $cellIndex=getCellIndex($x);
            $objActSheet->setCellValue($cellIndex . '2', '第一峰值(G)');
            $objActSheet->getStyle($cellIndex . '2')->applyFromArray($style_array);//设置边框样式
            
        }
        if($report_idc_statistic['is_second_max']=='on'){
            $x++;
            $cellIndex=getCellIndex($x);
            $objActSheet->setCellValue($cellIndex . '2', '第二峰值(G)');
            $objActSheet->getStyle($cellIndex . '2')->applyFromArray($style_array);//设置边框样式
        }
        if($report_idc_statistic['is_three_max']=='on'){
            $x++;
            $cellIndex=getCellIndex($x);
            $objActSheet->setCellValue($cellIndex . '2', '第三峰值(G)');
            $objActSheet->getStyle($cellIndex . '2')->applyFromArray($style_array);//设置边框样式
        }
        if($report_idc_statistic['is_fourth_max']=='on'){
            $x++;
            $cellIndex=getCellIndex($x);
            $objActSheet->setCellValue($cellIndex . '2', '第四峰值(G)');
            $objActSheet->getStyle($cellIndex . '2')->applyFromArray($style_array);//设置边框样式
        }
    }
    /************************************第二行操作完成*************************************/

    /************************************数据行操作开始*************************************/
    for($cell_date=strtotime($data_begin_date),$y=3;$cell_date<=strtotime($data_end_date); $cell_date+= 86400,$y++) {
        $data_date=date('Y-m-d',$cell_date);
        $sql="select * from plugin_report_idc_statistic_detail where report_idc_statistic_id=" . $report_idc_statistic['id'] . " and data_date='".$data_date."'";
        $report_idc_statistic_detail_array = db_fetch_assoc($sql);
        $x=0;
        foreach ($local_graph_array_checked as $local_graph_checked){//遍历选中的图形
            $cellIndex=getCellIndex($x);
            if($x==0){
                $objActSheet->setCellValue($cellIndex . $y, date('Y-m-d',$cell_date));//日期
                $objActSheet->getStyle($cellIndex . $y)->applyFromArray($style_array);//设置边框样式
            }
            $cellData=array();
            foreach($report_idc_statistic_detail_array as $report_idc_statistic_detail) {
                if($report_idc_statistic_detail['city_id']==$local_graph_checked['id']){
                    $cellData=$report_idc_statistic_detail;
                    break;
                }
            }
            if($report_idc_statistic['is_first_max']=='on'){
                $x++;
                $cellIndex=getCellIndex($x);
                if(isset($cellData['first_data_max_out'])){
                    $objActSheet->setCellValue($cellIndex . $y, $cellData['first_data_max_out']);
                }else{
                    $objActSheet->setCellValue($cellIndex . $y, '-');
                }
                $objActSheet->getStyle($cellIndex . $y)->applyFromArray($style_array);//设置边框样式
            }
            if($report_idc_statistic['is_second_max']=='on'){
                $x++;
                $cellIndex=getCellIndex($x);
                if(isset($cellData['second_data_max_out'])){
                    $objActSheet->setCellValue($cellIndex . $y, $cellData['second_data_max_out']);
                }else{
                    $objActSheet->setCellValue($cellIndex . $y, '-');
                }
                $objActSheet->getStyle($cellIndex . $y)->applyFromArray($style_array);//设置边框样式
            }
            if($report_idc_statistic['is_three_max']=='on'){
                $x++;
                $cellIndex=getCellIndex($x);
                if(isset($cellData['three_data_max_out'])){
                    $objActSheet->setCellValue($cellIndex . $y, $cellData['three_data_max_out']);
                }else{
                    $objActSheet->setCellValue($cellIndex . $y, '-');
                }
                $objActSheet->getStyle($cellIndex . $y)->applyFromArray($style_array);//设置边框样式
            }
            if($report_idc_statistic['is_fourth_max']=='on'){
                $x++;
                $cellIndex=getCellIndex($x);
                if(isset($cellData['fourth_data_max_out'])){
                    $objActSheet->setCellValue($cellIndex . $y, $cellData['fourth_data_max_out']);
                }else{
                    $objActSheet->setCellValue($cellIndex . $y, '-');
                }
                $objActSheet->getStyle($cellIndex . $y)->applyFromArray($style_array);//设置边框样式
            }
        }
        $objActSheet->getRowDimension($y)->setRowHeight(25);//行高
    }
    /************************************数据行操作结束*************************************/

    /************************************保存excel begin*************************************/
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $path='plugins/report/excel/idc_statistic/' . $excel_name . '.xlsx';
    $objWriter->save(dirname(__FILE__) . '/excel/idc_statistic/' . $excel_name . '.xlsx');
    /************************************保存excel end*************************************/

    /************************************发送邮件begin*************************************/
    $report_idc_statistic_excel['report_idc_statistic_id']=$report_idc_statistic['id'];
    $report_idc_statistic_excel['excel_name']=$excel_name;
    $report_idc_statistic_excel['excel_type']=$excel_type;
    $report_idc_statistic_excel['excel_path']=$path;
    if($report_idc_statistic_excel['excel_type']!='实时统计'&&$report_idc_statistic_excel['excel_type']!='手动统计'){//实时统计不需要发送邮件
        if($excel_type=='日统计'){
            $report_idc_statistic['emails']=$report_idc_statistic['emails_day'];
        }
        if($excel_type=='周统计'){
            $report_idc_statistic['emails']=$report_idc_statistic['emails_week'];
        }
        if($excel_type=='月统计'){
            $report_idc_statistic['emails']=$report_idc_statistic['emails_month'];
        }
        if(isset($report_idc_statistic['emails'])){
            $msg='<h3>' .$excel_name . '</h3>';
            //$msg=$msg .'<br>';
            //$msg=$msg . '<a>http://106.13.81.220/cacti/'. $path .'</a>';
            $report_idc_statistic_excel['subject']=$excel_name;
            $report_idc_statistic_excel['body']=$msg;
            $report_idc_statistic_excel['to_emails']=$report_idc_statistic['emails'];
            //附件begin
            $attachments=array();
            $attachment=array();
            $attachment['attachment']=$path;
            $attachment['filename']=$excel_name . '.xlsx';
            $attachment['mime_type']='xlsx';
            $attachments[$excel_name]= $attachment;
            //附近end
            $errors = send_mail($report_idc_statistic['emails'],"",$excel_name,$msg,$attachments,"",true);
            if($errors == ''){
                $report_idc_statistic_excel['status']='邮件发送成功';
            }else{
                $report_idc_statistic_excel['status']='邮件发送失败';
            }
        }
    }
    $report_idc_statistic_excel['description']=$excel_name;
    $report_idc_statistic_excel['last_modified'] = date('Y-m-d H:i:s', time());
    sql_save($report_idc_statistic_excel, 'plugin_report_idc_statistic_excel');
    /************************************发送邮件end*************************************/
}

//流量结算统计导出excel
function traffic_settlement_excel($report_traffic_settlement,$traffic_settlement_type,$data_begin_date,$data_end_date){
    $excel_name=$report_traffic_settlement['name'] . '-';
    $excel_name=$excel_name . ('流量结算统计报表(' . $data_begin_date . '至' . $data_end_date . ')');
    $excel_type=$traffic_settlement_type;//excel类型
    $report_traffic_settlement_excel= db_fetch_row_prepared("SELECT * FROM plugin_report_traffic_settlement_excel WHERE excel_name = '" . $excel_name . "' and excel_type = '" . $excel_type . "'");
    if(isset($report_traffic_settlement_excel['id'])&&$report_traffic_settlement_excel['excel_type']!='手动统计'){//数据库中已经存在记录
       return;
    }
    $extension=json_decode($report_traffic_settlement['extension'],true);//将json字符串转为对象
    $datas_checked=$extension['datas_checked'];//报表配置data
    $style_array = array(
        'borders' => array(
            'allborders' => array(
                'style' =>PHPExcel_Style_Border::BORDER_THIN
            )
        ),
        'font'=> array (  
            'bold'=> true  
        ),
        'alignment' => array (  
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical'=> PHPExcel_Style_Alignment::VERTICAL_CENTER
        )
    );
    $objReader = PHPExcel_IOFactory::createReader('Excel2007');//创建一个读Excel模版的对象
    $objPHPExcel = $objReader->load (dirname(__FILE__) . "/templates/traffic_settlement.xlsx" );//获取流量结算模版
    // 操作第一个工作表
    $objActSheet = $objPHPExcel->getActiveSheet();//获取当前活动的表
    $objActSheet->setTitle('流量结算统计报表('. $data_begin_date . '至' . $data_end_date . ')');
    /************************************第一行操作开始*************************************/
    $objActSheet->setCellValue('A1', $report_traffic_settlement['name'] . '('. $data_begin_date . '至' . $data_end_date . ')');
    /************************************第一行操作完成*************************************/

    /************************************第二行操作开始*************************************/
    
    /************************************第二行操作完成*************************************/

    /************************************第三行操作开始*************************************/
    $style_array_3 = array(
        'fill' => array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array('rgb' => 'CCFFCC')
        ),
        'borders' => array(
            'allborders' => array(
                'style' =>PHPExcel_Style_Border::BORDER_THIN
            )
        ),
        'font'=> array (  
            'bold'=> true  
        ),
        'alignment' => array (  
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical'=> PHPExcel_Style_Alignment::VERTICAL_CENTER
        )
    );
    $x=4;//横坐标
    for($cell_date=strtotime($data_begin_date);$cell_date<=strtotime($data_end_date); $cell_date+= 86400) {
        $data_date=date('m/d',$cell_date);//日期
        $cellIndex=getCellIndex($x);
        $objActSheet->setCellValue($cellIndex . '3', $data_date .'(G)');
        $objActSheet->getStyle($cellIndex . '3')->applyFromArray($style_array_3);//设置边框样式
        $objActSheet->getColumnDimension($cellIndex)->setWidth(16);//宽
        $x++;
    }
    //开通宽带单元格
    $cellIndex=getCellIndex($x);
    $objActSheet->setCellValue($cellIndex . '3', '开通宽带(单位：G)');
    $objActSheet->getStyle($cellIndex . '3')->applyFromArray($style_array_3);//设置边框样式
    $objActSheet->getColumnDimension($cellIndex)->setWidth(20);//宽
    $x++;
    //备注单元格
    $cellIndex=getCellIndex($x);
    $objActSheet->setCellValue($cellIndex . '3', '备  注');
    $objActSheet->getStyle($cellIndex . '3')->applyFromArray($style_array_3);//设置边框样式
    $objActSheet->getColumnDimension($cellIndex)->setWidth(20);//宽
    //合并第一行单元格
    $objActSheet->mergeCells('A1:' . $cellIndex . '1');
    //合并第二行单元格
    $objActSheet->mergeCells('A2:' . $cellIndex . '2');
    /************************************第三行操作结束*************************************/

    /************************************数据行操作开始*************************************/
    $number=1;//序号
    $y=4;//纵坐标从第四行开始
    //第一层循环遍历begin
    foreach ($datas_checked as $firstData){//第一层地区数据
        if(isset($firstData['checked'])&&$firstData['checked']){//区县是否选中状态
            if(isset($firstData['children'])){
                $count=0;//纵向合并单元格的个数
                //第二次遍历begin
                foreach ($firstData['children'] as $secondtData){//遍历图形data
                    if(isset($secondtData['local_graph_id'])&&isset($secondtData['checked'])&&$secondtData['checked']){
                        $city_id=$secondtData['id'];//城市ID
                        $objActSheet->getRowDimension($y)->setRowHeight(30);//行高
                        $objActSheet->setCellValue('B' .$y, $number);//填写序号
                        $objActSheet->getStyle('B' .$y)->applyFromArray($style_array);//设置边框样式
                        $objActSheet->setCellValue('C' .$y, $secondtData['text']);//图形名称也叫地市名称
                        $objActSheet->getStyle('C' .$y)->applyFromArray($style_array);//设置边框样式
                        //根据流量结算ID与城市ID得到流量结算详细信息
                        $sql="select * from plugin_report_traffic_settlement_detail where report_traffic_settlement_id=" . $report_traffic_settlement['id'] . " and city_id='".$city_id."'";
                        $report_traffic_settlement_detail_array = db_fetch_assoc($sql);
                        //遍历开始结束日期找到对应的流量结算详情
                        $x=4;
                        for($cell_date=strtotime($data_begin_date);$cell_date<=strtotime($data_end_date); $cell_date+= 86400) {
                            $data_date=date('Y-m-d',$cell_date);//数据日期
                            $cellData=array();
                            foreach($report_traffic_settlement_detail_array as $report_traffic_settlement_detail) {
                                if($report_traffic_settlement_detail['data_date']==$data_date&&$report_traffic_settlement_detail['city_id']==$city_id){
                                    $cellData=$report_traffic_settlement_detail;
                                    break;
                                }
                            }
                            //通道容量开始begin
                            //因为有些日期找不到详情信息所有判断一下 
                            if(isset($cellData['upper_limit'])&&$cellData['upper_limit']!=''){
                                $objActSheet->setCellValue('D'.$y, $cellData['upper_limit']);//通道容量
                            }else{
                                $upper_limit=$objActSheet->getCell('D'.$y)->getValue();
                                if($upper_limit==''){
                                    $objActSheet->setCellValue('D'.$y, '-');//通道容量
                                }
                            }
                            $objActSheet->getStyle('D'.$y)->applyFromArray($style_array);//设置边框样式
                            //通道容量开始end
                            //数据开始begin
                            $cellIndex=getCellIndex($x);
                            if(isset($cellData['data_max_out'])){
                                $objActSheet->setCellValue($cellIndex.$y, $cellData['data_max_out']);
                            }else{
                                $objActSheet->setCellValue($cellIndex.$y, '-');
                            }
                            $objActSheet->getStyle($cellIndex.$y)->applyFromArray($style_array);//设置边框样式
                            //数据开始end
                            $x++;
                        }
                        //空单元格begin
                        $cellIndex=getCellIndex($x);
                        $objActSheet->setCellValue($cellIndex.$y, '');
                        $objActSheet->getStyle($cellIndex.$y)->applyFromArray($style_array);//设置边框样式
                        $x++;
                        $cellIndex=getCellIndex($x);
                        $objActSheet->setCellValue($cellIndex.$y, '');
                        $objActSheet->getStyle($cellIndex.$y)->applyFromArray($style_array);//设置边框样式
                        //空单元格end
                        $number++;//序号加加
                        $y++;//纵坐标加加
                        $count++;//纵向合并单元格的个数加加
                    }
                }
                //第二次遍历end
                //等于0表示城市不存在
                if($count!=0){
                    $objActSheet->mergeCells('A' .($y-$count) .':' . 'A' . ($y-1));// 合并单元格
                    $objActSheet->setCellValue('A' .($y-$count), $firstData['text']);//图形名称
                    $objActSheet->getStyle('A' .($y-$count))->applyFromArray($style_array);//设置边框样式
                }
            }
        }
    }
    //第一层循环遍历end
    /************************************数据行操作结束*************************************/

    /************************************合计行操作开始*************************************/
    $style_array_total = array(
        'borders' => array(
            'allborders' => array(
                'style' => \PHPExcel_Style_Border::BORDER_THIN
            )
        ),
        'font'  => array(
            'bold'  => true,
            'color' => array('rgb' => 'FF0000')
        ),
        'alignment' => array (  
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical'=> PHPExcel_Style_Alignment::VERTICAL_CENTER
        )
    );
    $objActSheet->getRowDimension($y)->setRowHeight(30);//行高
    $objActSheet->setCellValue('A' .$y, '合计');
    $objActSheet->getStyle('A' .$y)->applyFromArray($style_array_total);
    $objActSheet->setCellValue('B' .$y, $number);
    $objActSheet->getStyle('B' .$y)->applyFromArray($style_array_total);
    $objActSheet->setCellValue('C' .$y, '');
    $objActSheet->getStyle('C' .$y)->applyFromArray($style_array_total);
    $objActSheet->setCellValue( 'D'.$y, '=SUM(D4:D'.($y-1).')' );//通道容量总计
    $objActSheet->getStyle('D' .$y)->applyFromArray($style_array_total);
    //数据统计开始
    $x=4;
    for($cell_date=strtotime($data_begin_date);$cell_date<=strtotime($data_end_date); $cell_date+= 86400) {
        $cellIndex=getCellIndex($x);
        $objActSheet->setCellValue($cellIndex . $y, '=SUM('.$cellIndex.'4:'. $cellIndex.($y-1).')');
        $objActSheet->getStyle($cellIndex.$y)->applyFromArray($style_array_total);
        $x++;
    }
    //空单元格begin
    $cellIndex=getCellIndex($x);
    $objActSheet->setCellValue($cellIndex.$y, '');
    $objActSheet->getStyle($cellIndex.$y)->applyFromArray($style_array);//设置边框样式
    $x++;
    $cellIndex=getCellIndex($x);
    $objActSheet->setCellValue($cellIndex.$y, '');
    $objActSheet->getStyle($cellIndex.$y)->applyFromArray($style_array);//设置边框样式
    //空单元格end
    //数据统计结束
    $y++;
    /************************************合计行操作结束*************************************/
    /************************************制作表人行操作开始*************************************/
    $objActSheet->getRowDimension($y)->setRowHeight(20);//行高
    $objActSheet->mergeCells('A' . $y . ':' . $cellIndex . $y);
     // 设置靠左显示
     $objActSheet->getStyle('A' .$y)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_JUSTIFY);
     $objActSheet->setCellValue('A' .$y, "     制表人：                                 审核人：                                                   日期：" . $data_begin_date . '至' . $data_end_date );
    /************************************制作表人行操作结束*************************************/

    /************************************保存excel begin*************************************/
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $path='plugins/report/excel/traffic_statistic/' . $excel_name . '.xlsx';
    $objWriter->save(dirname(__FILE__) . '/excel/traffic_statistic/' . $excel_name . '.xlsx');
    /************************************保存excel end*************************************/
    /************************************发送邮件begin*************************************/
    $report_traffic_settlement_excel['report_traffic_settlement_id']=$report_traffic_settlement['id'];
    $report_traffic_settlement_excel['excel_name']=$excel_name;
    $report_traffic_settlement_excel['excel_type']=$excel_type;
    $report_traffic_settlement_excel['excel_path']=$path;
    if($report_traffic_settlement_excel['excel_type']!='实时统计'&&$report_traffic_settlement_excel['excel_type']!='手动统计'){//实时统计不需要发送邮件
        if($excel_type=='日统计'){
            $report_traffic_settlement['emails']=$report_traffic_settlement['emails_day'];
        }
        if($excel_type=='周统计'){
            $report_traffic_settlement['emails']=$report_traffic_settlement['emails_week'];
        }
        if($excel_type=='月统计'){
            $report_traffic_settlement['emails']=$report_traffic_settlement['emails_month'];
        }
        if(isset($report_traffic_settlement['emails'])){
            $msg='<h3>' .$excel_name . '</h3>';
            //$msg=$msg .'<br>';
            //$msg=$msg . '<a>http://106.13.81.220/cacti/'. $path .'</a>';
            $report_traffic_settlement_excel['subject']=$excel_name;
            $report_traffic_settlement_excel['body']=$msg;
            $report_traffic_settlement_excel['to_emails']=$report_traffic_settlement['emails'];
            //附件begin
            $attachments=array();
            $attachment=array();
            $attachment['attachment']=$path;
            $attachment['filename']=$excel_name . '.xlsx';
            $attachment['mime_type']='xlsx';
            $attachments[$excel_name]= $attachment;
            //附近end
            $errors = send_mail($report_traffic_settlement['emails'],"",$excel_name,$msg,$attachments,"",true);
            if($errors == ''){
                $report_traffic_settlement_excel['status']='邮件发送成功';
            }else{
                $report_traffic_settlement_excel['status']='邮件发送失败';
            }
        }
    }
    $report_traffic_settlement_excel['description']=$excel_name;
    $report_traffic_settlement_excel['last_modified'] = date('Y-m-d H:i:s', time());
    sql_save($report_traffic_settlement_excel, 'plugin_report_traffic_settlement_excel');
    /************************************发送邮件end*************************************/
}