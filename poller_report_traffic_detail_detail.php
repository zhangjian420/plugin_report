<?php
chdir(__DIR__);
chdir("../../");
require("./include/cli_check.php");
include_once($config['base_path'] . '/lib/rrd.php');
include_once($config['base_path'] . '/rrd_util_functions.php');
include_once($config['base_path'] . '/plugins/report/report_functions.php');//报表管理公共函数文件
//cacti_log("<<<<<<<<<<<<<<<<流量明细统计详情时任务执行>>>>>>>>>>>>>>>> " . date('Y-m-d H:i:s', time()));
$report_traffic_detail_array = db_fetch_assoc("select * from plugin_report_traffic_detail where status_detail!='已执行'");
//遍历集合begin
foreach($report_traffic_detail_array as $report_traffic_detail) {
    $report_traffic_detail_id=$report_traffic_detail['id'];//流量明细统计ID
    $begin_date=$report_traffic_detail['exec_date'];//开始日期--之前是begin_date，后面改成exec_date了
    $current_date=date('Y-m-d', time());//今天
    $extension=json_decode($report_traffic_detail['extension'],true);//将json字符串转为对象
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
                        if( strtotime($current_date)>strtotime($begin_date)){//表示已经过期
                            $date_array = getDateFromRange($begin_date,date('Y-m-d',(strtotime($current_date)-86400)));//今天没有过完，只能统计前一天的数据
                        }
                        //日期集合遍历begin
                        foreach ($date_array as $data_date){
                            $traffic_detail_detail_id = db_fetch_cell_prepared("select id from plugin_report_traffic_detail_detail where 
                                report_traffic_detail_id=" . $report_traffic_detail_id . " and region_id=" . $region_id 
                                . " and city_id=" . $city_id . " and data_date='" . $data_date ."'");
                            if($traffic_detail_detail_id==''){ // 为空，说明这个报表这个时间内没有统计过，需要统计
                                $local_graph_id=$secondtData['local_graph_id'];//图形ID
                                $start_time = strtotime($data_date . " 00:00:00");
                                $datas = get_traffic_detail_by_graph($local_graph_id,$start_time,$start_time+86400,300);
                                //拼装保存数据
                                $report_traffic_detail_detail=array();//一定要空
                                $report_traffic_detail_detail['report_traffic_detail_id']=$report_traffic_detail_id;
                                $report_traffic_detail_detail['region_id']=$region_id;
                                $report_traffic_detail_detail['region_name']=$region_name;
                                $report_traffic_detail_detail['city_id']=$city_id;
                                $report_traffic_detail_detail['city_name']=$city_name;
                                $report_traffic_detail_detail['local_graph_id']=$local_graph_id;
                                $report_traffic_detail_detail['datas']=json_encode($datas);
                                $report_traffic_detail_detail['data_date']=$data_date;
                                $report_traffic_detail_detail['last_modified'] = date('Y-m-d H:i:s', time());
                                //cacti_log("<<<<<<<<<<<<<<<<<<<report_traffic_detail_detail>>>>>>>>>>>>>>>>>>>>>> " . json_encode($report_traffic_detail_detail));
                                sql_save($report_traffic_detail_detail, 'plugin_report_traffic_detail_detail');
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
    //报表状态更新
    $save_report_traffic_detail=array();//一定要空
    $save_report_traffic_detail['id']=$report_traffic_detail_id;
    $save_report_traffic_detail['status_detail']='执行中';
    $save_report_traffic_detail['last_modified'] = date('Y-m-d H:i:s', time());
    // 把开始时间更新了，不然每次都是从创建时间开始算，时间久了效率越来越低
    $save_report_traffic_detail['exec_date'] = date('Y-m-d', time()-86400);
    sql_save($save_report_traffic_detail, 'plugin_report_traffic_detail');
}
//遍历集合end