<?php
chdir(__DIR__);
chdir("../../");
require("./include/cli_check.php");
include_once($config['base_path'] . '/lib/rrd.php');
include_once($config['base_path'] . '/plugins/report/report_functions.php');//报表管理公共函数文件
//cacti_log("<<<<<<<<<<<<<<<<宽带通道预警详情时任务执行>>>>>>>>>>>>>>>> " . date('Y-m-d H:i:s', time()));
$report_channel_utilization_array = db_fetch_assoc("select * from plugin_report_channel_utilization where status_detail!='已执行'");
//遍历集合begin
foreach($report_channel_utilization_array as $report_channel_utilization) {
    $report_channel_utilization_id=$report_channel_utilization['id'];//宽带通道预警ID
    $begin_date=$report_channel_utilization['exec_date'];//开始日期--之前是begin_date，后面改成exec_date了
    $current_date=date('Y-m-d', time());//今天
    $extension=json_decode($report_channel_utilization['extension'],true);//将json字符串转为对象
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
                            $channel_utilization_detail_id = db_fetch_cell_prepared("select id from plugin_report_channel_utilization_detail where 
                                report_channel_utilization_id=" . $report_channel_utilization_id . " and region_id=" . $region_id 
                                . " and city_id=" . $city_id . " and data_date='" . $data_date ."'");
                            
                            if($channel_utilization_detail_id==''){ // 为空，说明这个报表这个时间内没有统计过，需要统计
                                $local_graph_id=$secondtData['local_graph_id'];//图形ID
                                $local_data=get_local_data($secondtData['local_graph_id']);//根据图形ID查找数据源ID
                                $local_data_id = 0;
                                $upper_limit=0;
                                $data_max_out=0;
                                $data_max_in=0;
                                $data_max=0;
                                $utilization_ratio_out=0;
                                $utilization_ratio_in=0;
                                $utilization_ratio=0;
                                if(empty($local_data)){ //说明是聚合图形
                                    $upper_limit = getUnitVal(db_fetch_cell_prepared("select upper_limit from graph_templates_graph where local_graph_id=" . $local_graph_id));
                                    // $graph_data_array = array("graph_start"=>strtotime($data_date . " 00:00:00"),"graph_end"=>strtotime($data_date . " 23:59:59"),"export_csv"=>true);
                                    $graph_data_array = array("graph_start"=>strtotime($data_date . " 00:00:00")-300,"graph_end"=>strtotime($data_date . " 23:59:59")+300,"export_csv"=>true);
                                    $xport_meta = array();
                                    $xport_array = rrdtool_function_xport($local_graph_id, 0, $graph_data_array, $xport_meta);
                                    //cacti_log("xport_array=". json_encode($xport_array));
                                    if (!empty($xport_array["data"])) {
                                        $traffic_in=array(0);
                                        $traffic_out=array(0);
                                        foreach ($xport_array["data"] as $data){
                                            $datas = array_values($data);
                                            if(cacti_sizeof($datas)>=2){
                                                $traffic_in[]=getUnitVal($datas[sizeof($datas)-2]);
                                                $traffic_out[]=getUnitVal(end($datas));
                                            }
                                        }
                                        rsort($traffic_in);//降序操作
                                        rsort($traffic_out);//降序操作
                                        //流量赋值
                                        $data_max_out=$traffic_out[0];
                                        $data_max_in=$traffic_in[0];
                                        $data_max= $data_max_out>=$data_max_in ? $data_max_out : $data_max_in;
                                        //流量利用率
                                        if($upper_limit!=0){
                                            $utilization_ratio_out= $data_max_out/$upper_limit;
                                            $utilization_ratio_in= $data_max_in/$upper_limit;
                                            $utilization_ratio= $utilization_ratio_out>=$utilization_ratio_in ? $utilization_ratio_out : $utilization_ratio_in;
                                        }
                                    }
                                }else{//普通图形
                                    $local_data_id = $local_data['local_data_id'];//根据图形ID查找数据源ID
                                    $upper_limit = getUnitVal($local_data['upper_limit']);//根据图像ID查找数据源ID
                                    $traffic_max_value=get_traffic_max_value($local_data_id, strtotime($data_date . " 00:00:00"), strtotime($data_date . " 23:59:59"));
                                    //流量赋值
                                    $data_max_out=$traffic_max_value['traffic_out'];
                                    $data_max_in=$traffic_max_value['traffic_in'];
                                    $data_max= $data_max_out>=$data_max_in ? $data_max_out : $data_max_in;
                                    //流量利用率
                                    if($upper_limit!=0){
                                        $utilization_ratio_out= $data_max_out/$upper_limit;
                                        $utilization_ratio_in= $data_max_in/$upper_limit;
                                        $utilization_ratio= $utilization_ratio_out>=$utilization_ratio_in ? $utilization_ratio_out : $utilization_ratio_in;
                                    }
                                }                                
                                //拼装保存数据
                                $report_channel_utilization_detail=array();//一定要空
                                $report_channel_utilization_detail['report_channel_utilization_id']=$report_channel_utilization_id;
                                $report_channel_utilization_detail['region_id']=$region_id;
                                $report_channel_utilization_detail['region_name']=$region_name;
                                $report_channel_utilization_detail['city_id']=$city_id;
                                $report_channel_utilization_detail['city_name']=$city_name;
                                $report_channel_utilization_detail['local_graph_id']=$local_graph_id;
                                $report_channel_utilization_detail['local_data_id']=$local_data_id;
                                $report_channel_utilization_detail['upper_limit']=$upper_limit;
                                $report_channel_utilization_detail['data_date']=$data_date;
                                $report_channel_utilization_detail['data_max_out']=$data_max_out;
                                $report_channel_utilization_detail['utilization_ratio_out']=$utilization_ratio_out;
                                $report_channel_utilization_detail['data_max_in']=$data_max_in;
                                $report_channel_utilization_detail['utilization_ratio_in']=$utilization_ratio_in;
                                $report_channel_utilization_detail['data_max']=$data_max;
                                $report_channel_utilization_detail['utilization_ratio']=$utilization_ratio;
                                $report_channel_utilization_detail['last_modified'] = date('Y-m-d H:i:s', time());
                                //cacti_log("<<<<<<<<<<<<<<<<<<<report_channel_utilization_detail>>>>>>>>>>>>>>>>>>>>>> " . json_encode($report_channel_utilization_detail));
                                sql_save($report_channel_utilization_detail, 'plugin_report_channel_utilization_detail');
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
    $save_report_channel_utilization=array();//一定要空
    $save_report_channel_utilization['id']=$report_channel_utilization_id;
    $save_report_channel_utilization['status_detail']='执行中';
    $save_report_channel_utilization['last_modified'] = date('Y-m-d H:i:s', time());
    // 把开始时间更新了，不然每次都是从创建时间开始算，时间久了效率越来越低
    $save_report_channel_utilization['exec_date'] = date('Y-m-d', time()-86400);
    sql_save($save_report_channel_utilization, 'plugin_report_channel_utilization');
}
//遍历集合end