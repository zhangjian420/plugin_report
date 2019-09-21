<?php
chdir(dirname(__FILE__));
chdir('../../');
include_once('./include/global.php');
include_once($config['base_path'] . '/lib/rrd.php');
include_once($config['base_path'] . '/plugins/report/phpexcel/PHPExcel.php');//PHPExcel函数文件
include_once($config['base_path'] . '/plugins/report/report_functions.php');//报表管理公共函数文件
cacti_log("<<<<<<<<<<<<<<<<IDC统计邮箱发送定时任务执行>>>>>>>>>>>>>>>> " . date('Y-m-d H:i:s', time()));
$report_idc_statistic_array = db_fetch_assoc("select report_idc_statistic.*, notification_lists_day.emails as emails_day,notification_lists_week.emails as emails_week,notification_lists_month.emails as emails_month from plugin_report_idc_statistic as report_idc_statistic left join plugin_notification_lists as notification_lists_day on report_idc_statistic.notification_id_day=notification_lists_day.id left join plugin_notification_lists as notification_lists_week on report_idc_statistic.notification_id_week=notification_lists_week.id left join plugin_notification_lists as notification_lists_month on report_idc_statistic.notification_id_month=notification_lists_month.id where report_idc_statistic.status_report!='已执行'");
//遍历集合begin
foreach($report_idc_statistic_array as $report_idc_statistic) {
    $report_idc_statistic_id=$report_idc_statistic['id'];//IDC统计ID
    $begin_date=$report_idc_statistic['begin_date'];//开始日期
    $is_day=$report_idc_statistic['is_day'];//日报表
    $is_week=$report_idc_statistic['is_week'];//周报表
    $is_month=$report_idc_statistic['is_month'];//月报表
    $current_date=date('Y-m-d', time());//今天
    if(strtotime($current_date)>strtotime($begin_date)){
        cacti_log('======================有效期内的IDC报表统计邮箱发送操作======================');
        //日统计操作begin
        if($is_day=='on'){
            $data_begin_date=$begin_date;
            $data_end_date=date('Y-m-d',strtotime("-1 day",strtotime($current_date)));//统计前一天的
            cacti_log('日统计data_end_date=' . $data_end_date);
            cacti_log('日统计data_begin_date=' . $data_begin_date);
            idc_statistic_excel($report_idc_statistic,'日统计',$data_begin_date,$data_end_date);
        }
        //日统计操作end
        //周统计操作begin
        if($is_week=='on'){
            $week=date("l",strtotime($current_date));//当前周几
            if($week=='Monday'){
                $data_begin_date=date('Y-m-d',strtotime("-8 day",strtotime($current_date)));
                $data_end_date=date('Y-m-d',strtotime("-1 day",strtotime($current_date)));
                cacti_log('周统计data_end_date=' . $data_end_date);
                cacti_log('周统计data_begin_date=' . $data_begin_date);
                idc_statistic_excel($report_idc_statistic,'周统计',$data_begin_date,$data_end_date);
            }
        }
        //周统计操作end
        //月统计操作begin
        if($is_month=='on'){
            $day=date("j",strtotime($current_date));//日期中的哪日
            if($day==1){
                $data_begin_date = date('Y-m-d', strtotime("first day of last month 00:00:00"));
                $data_end_date   = date('Y-m-d', strtotime("last day of last month 23:59:59"));
                cacti_log('月统计data_end_date=' . $data_end_date);
                cacti_log('月统计data_begin_date=' . $data_begin_date);
                idc_statistic_excel($report_idc_statistic,'月统计',$data_begin_date,$data_end_date);
            }
        }
        //月统计操作end
        //实时统计操作begin
        $data_begin_date=$begin_date;
        $data_end_date=date('Y-m-d',strtotime("-1 day",strtotime($current_date)));//统计前一天的
        cacti_log('实时统计data_end_date=' . $data_end_date);
        cacti_log('实时统计data_begin_date=' . $data_begin_date);
        idc_statistic_excel($report_idc_statistic,'实时统计',$data_begin_date,$data_end_date);
        $save=array();
        $save['id']=$report_idc_statistic['id'];
        $save['status_report']='执行中';
        sql_save($save, 'plugin_report_idc_statistic');
        //实时统计操作end
    }
}