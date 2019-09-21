<?php
chdir(dirname(__FILE__));
chdir('../../');
include_once('./include/global.php');
include_once($config['base_path'] . '/lib/rrd.php');
include_once($config['base_path'] . '/plugins/report/report_functions.php');//报表管理公共函数文件
cacti_log("<<<<<<<<<<<<<<<<IDC统计详情时任务执行>>>>>>>>>>>>>>>> " . date('Y-m-d H:i:s', time()));
$report_idc_statistic_array = db_fetch_assoc("select * from plugin_report_idc_statistic where status_detail!='已执行'");
//遍历集合begin
foreach($report_idc_statistic_array as $report_idc_statistic) {
    $report_idc_statistic_id=$report_idc_statistic['id'];//IDC统计ID
    $begin_date=$report_idc_statistic['begin_date'];//开始日期
    $current_date=date('Y-m-d', time());//今天
    $extension=json_decode($report_idc_statistic['extension'],true);//将json字符串转为对象
    $datas_checked=$extension['datas_checked'];//报表配置data
    //第一层数据遍历begin
    foreach ($datas_checked as $firstData){//第一层地区数据
        if(isset($firstData['checked'])&&$firstData['checked']){//区县是否选中状态
            $region_id=$firstData['id'];//地区ID
            $region_name=$firstData['text'];//地区名称
            if(isset($firstData['children'])){
                //第二层数据遍历begin
                foreach ($firstData['children'] as $secondtData){//遍历图形data
                    if(isset($secondtData['local_graph_id'])&&isset($secondtData['checked'])&&$secondtData['checked']){
                        $city_id=$secondtData['id'];//城市ID
                        $city_name=$secondtData['text'];//城市名称
                        $date_array = array();
                        if(strtotime($current_date)>strtotime($begin_date)){//表示已经过期
                            $date_array = getDateFromRange($begin_date,date('Y-m-d',(strtotime($current_date)-86400)));//今天没有过完，只能统计前一天的数据
                        }
                        //日期集合遍历begin
                        foreach ($date_array as $data_date){
                            $report_idc_statistic_detail_id = db_fetch_cell_prepared("select id from plugin_report_idc_statistic_detail where report_idc_statistic_id=" . $report_idc_statistic_id . " and region_id=" . $region_id . " and city_id=" . $city_id . " and data_date='" . $data_date ."'");
                            if($report_idc_statistic_detail_id==''){//IDC详细记录为空
                                $local_graph_id=$secondtData['local_graph_id'];//图形ID
                                $local_data=get_local_data($secondtData['local_graph_id']);//根据图形ID查找数据源ID
                                $local_data_id = $local_data['local_data_id'];//根据图形ID查找数据源ID
                                $idc_peak_value=get_idc_peak_value($local_data_id, strtotime($data_date . " 00:00:00"), strtotime($data_date . " 23:59:59"));
                                //出口top6
                                $first_data_max_out=array_shift($idc_peak_value['traffic_out']);
                                $second_data_max_out=array_shift($idc_peak_value['traffic_out']);
                                $three_data_max_out=array_shift($idc_peak_value['traffic_out']);
                                $fourth_data_max_out=array_shift($idc_peak_value['traffic_out']);
                                $five_data_max_out=array_shift($idc_peak_value['traffic_out']);
                                $six_data_max_out=array_shift($idc_peak_value['traffic_out']);
                                //进口top6
                                $first_data_max_in=array_shift($idc_peak_value['traffic_in']);
                                $second_data_max_in=array_shift($idc_peak_value['traffic_in']);
                                $three_data_max_in=array_shift($idc_peak_value['traffic_in']);
                                $fourth_data_max_in=array_shift($idc_peak_value['traffic_in']);
                                $five_data_max_in=array_shift($idc_peak_value['traffic_in']);
                                $six_data_max_in=array_shift($idc_peak_value['traffic_in']);
                                //拼装保存数据
                                $report_idc_statistic_detail=array();//一定要空
                                $report_idc_statistic_detail['report_idc_statistic_id']=$report_idc_statistic_id;
                                $report_idc_statistic_detail['region_id']=$region_id;
                                $report_idc_statistic_detail['region_name']=$region_name;
                                $report_idc_statistic_detail['city_id']=$city_id;
                                $report_idc_statistic_detail['city_name']=$city_name;
                                $report_idc_statistic_detail['local_graph_id']=$local_graph_id;
                                $report_idc_statistic_detail['local_data_id']=$local_data_id;
                                $report_idc_statistic_detail['data_date']=$data_date;
                                $report_idc_statistic_detail['first_data_max_out']=$first_data_max_out;
                                $report_idc_statistic_detail['second_data_max_out']=$second_data_max_out;
                                $report_idc_statistic_detail['three_data_max_out']=$three_data_max_out;
                                $report_idc_statistic_detail['fourth_data_max_out']=$fourth_data_max_out;
                                $report_idc_statistic_detail['five_data_max_out']=$five_data_max_out;
                                $report_idc_statistic_detail['six_data_max_out']=$six_data_max_out;
                                $report_idc_statistic_detail['first_data_max_in']=$first_data_max_in;
                                $report_idc_statistic_detail['second_data_max_in']=$second_data_max_in;
                                $report_idc_statistic_detail['three_data_max_in']=$three_data_max_in;
                                $report_idc_statistic_detail['fourth_data_max_in']=$fourth_data_max_in;
                                $report_idc_statistic_detail['five_data_max_in']=$five_data_max_in;
                                $report_idc_statistic_detail['six_data_max_in']=$six_data_max_in;
                                $report_idc_statistic_detail['last_modified'] = date('Y-m-d H:i:s', time());
                                //cacti_log("<<<<<<<<<<<<<<<<<<report_idc_statistic_detail>>>>>>>>>>>>>>>>>>>>>>> " . json_encode($report_idc_statistic_detail));
                                $id=sql_save($report_idc_statistic_detail, 'plugin_report_idc_statistic_detail');
                                //报表状态
                                $save_report_idc_statistic=array();//一定要空
                                $save_report_idc_statistic['id']=$report_idc_statistic_id;
                                $save_report_idc_statistic['status_detail']='执行中';
                                $save_report_idc_statistic['last_modified'] = date('Y-m-d H:i:s', time());
                                $id=sql_save($save_report_idc_statistic, 'plugin_report_idc_statistic');
                            }
                        }
                        //日期集合遍历end
                    }
                }
                //第二层数据遍历end
            }
        }
    }
    //第一层数据遍历end
}
//遍历集合end