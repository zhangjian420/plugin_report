<?php
$channel_utilization_actions= array(
    31 => __('删除'),
    32 => __('下载'),
    33 => __('立即执行')
);
//宽带通道预警新增编辑页面
function channel_utilization_edit(){
    report_tabs('channel_utilization');//宽带通道预警管理选项卡
    $data = array();//页面显示data
    if (!isempty_request_var('id')) {
        $data= db_fetch_row_prepared('SELECT * FROM plugin_report_channel_utilization WHERE id = ?', array(get_request_var('id')));
    }
	$field_array = array(
		'id' => array(
			'friendly_name' => '宽带通道预警id',
			'method' => 'hidden',
			'value' => isset_request_var('id') ? get_request_var('id'):0
        ),
        'status_detail' => array(
			'friendly_name' => '状态',
			'method' => 'hidden',
			'value' => (isset($data['status_detail']) ? $data['status_detail']:'未执行')
		),
		'name' => array(
			'friendly_name' => '宽带通道预警名称',
			'method' => 'textbox',
			'max_length' => 100,
			'description' =>'请正确填写宽带通道预警名称',
			'value' => (isset($data['name']) ? $data['name']:'')
        ),
        'utilization_ratio_threshold' => array(
			'friendly_name' => '宽带通道利用率阈值',
			'method' => 'drop_sql',
			'description' => '请选择宽带通道利用率阈值',
			'value' => isset($data['utilization_ratio_threshold']) ? $data['utilization_ratio_threshold'] : '0',
            'none_value' =>'请选择',
			'default' => '0',
			'sql' => "SELECT value as id , name as name FROM plugin_report_dictionary where type_code='channel_utilization_utilization_ratio_threshold'"
        ),
        'is_day' => array(
			'friendly_name' => __('日统计'),
			'method' => 'checkbox',
			'description' => __('是否开启日统计'),
			'default' => '',
			'value' => (isset($data['is_day']) ? $data['is_day']:'')
        ),
        'notification_id_day' => array(
			'friendly_name' => '日统计邮箱',
			'method' => 'drop_sql',
			'description' => '请选择接收宽带通道预警的日统计邮箱',
			'value' => isset($data['notification_id_day']) ? $data['notification_id_day'] : '0',
            'none_value' =>'请选择',
			'default' => '0',
			'sql' => 'SELECT id, name FROM plugin_notification_lists'
        ),
        'is_week' => array(
			'friendly_name' => __('周统计'),
			'method' => 'checkbox',
			'description' => __('是否开启周统计'),
			'default' => '',
			'value' => (isset($data['is_week']) ? $data['is_week']:'')
        ),
        'notification_id_week' => array(
			'friendly_name' => '周统计邮箱',
			'method' => 'drop_sql',
			'description' => '请选择接收宽带通道预警的周统计邮箱',
			'value' => isset($data['notification_id_week']) ? $data['notification_id_week'] : '0',
            'none_value' =>'请选择',
			'default' => '0',
			'sql' => 'SELECT id, name FROM plugin_notification_lists'
        ),
        'is_month' => array(
			'friendly_name' => __('月统计'),
			'method' => 'checkbox',
			'description' => __('是否开启月统计'),
			'default' => '',
			'value' => (isset($data['is_month']) ? $data['is_month']:'')
        ),
        'notification_id_month' => array(
			'friendly_name' => '月统计邮箱',
			'method' => 'drop_sql',
			'description' => '请选择接收宽带通道预警的月统计邮箱',
			'value' => isset($data['notification_id_month']) ? $data['notification_id_month'] : '0',
            'none_value' =>'请选择',
			'default' => '0',
			'sql' => 'SELECT id, name FROM plugin_notification_lists'
        ),
		'description' => array(
			'friendly_name' => '宽带通道预警描述',
			'method' => 'textarea',
            'textarea_rows' => '4',
			'textarea_cols' => '80',
			'description' =>'请正确填写宽带通道预警描述',
			'value' => (isset($data['description']) ? $data['description']:'')
        ),
        'graph_tree_id' => array(
			'friendly_name' => '图形树',
			'method' => 'drop_sql',
			'description' => '请选择配置的图形树',
			'value' => isset($data['graph_tree_id']) ? $data['graph_tree_id'] : '0',
            'none_value' =>'请选择',
			'default' => '0',
			'sql' => 'SELECT id, name FROM graph_tree'
        ),
        'extension' => array(
			'friendly_name' => '扩展字段',
			'method' => 'hidden',
			'value' => isset($data['extension']) ? $data['extension'] : '',
		)
	);
	form_start('report.php', 'channel_utilization_edit',false);//宽带通道预警编辑form开始
	if (isset($data['id'])) {
		html_start_box(__('宽带通道预警 [编辑: %s]', html_escape($data['name'])), '100%', true, '3', 'center', '');
	} else {
		html_start_box(__('宽带通道预警 [新增]'), '100%', true, '3', 'center', '');
	}
	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => $field_array
		)
    );
    ?>
    <div class="formRow odd">
        <div class="formColumnLeft">
            <div class="formFieldName">选择图形
                <div class="formTooltip">
                    <div class="cactiTooltipHint fa fa-question-circle"><span style="display:none;">请勾选相关的图形</span></div>
                </div>
            </div>
        </div>
        <div class="formColumnRight">
            <input type="button" value="全选" id="selectAll"/>
            <div id="tree" systle="padding-left: 0px"></div>
        </div>
    </div>
    <!-- 操作按钮 -->
    <table style='width:100%;text-align:center;'>
		<tr>
			<td class='saveRow'>
                <input type="hidden" name="action" value="channel_utilization_save">
                <input type="button" onclick="window.location.href='report.php?action=channel_utilization';" value="返回" role="button">
                <input type="submit" id="submit" value="保存" role="button">
			</td>
		</tr>
	</table>
    <script>
        var tree_data=[];//tree数据源全局记录
        //封装treeData，如果选中增加checked=true状态方便报表生成使用
        function encapsulationData(tree_data,id){
            tree_data.forEach(function(data,index){
                if(data.children){
                    encapsulationData(data.children,id);
                }
                if(data.id==id){
                    data.checked=true;
                }
            });
        }
        //创建相关图形树begin
        function createGraphTree(){
            $('#tree').jstree("destroy");//当多次调用树的时候，一定要销毁树才能再次调用
            $('#tree').data('jstree', false).empty().jstree({
                'types' : {
                    'device' : {
                        icon : urlPath+'images/server.png',
                        max_children : 0
                    },
                    'graph' : {
                        icon : urlPath+'images/server_chart_curve.png',
                        max_children : 0
                    },
                    'site' : {
                        icon : urlPath+'images/site.png',
                        max_children : 0
                    }
                },
                'core': {
                    data: function (node, callback) {
                        $.ajax({
                            url: 'report.php?action=ajax_tree&graph_tree_id=' + $("#graph_tree_id").val(),
                            dataType: "json",
                            success: function (data) {
                                tree_data=data;
                                if(tree_data.length==0){
                                    $("#selectAll").hide();
                                }else{
                                    $("#selectAll").show();
                                }
                                callback.call(this, tree_data);
                            }
                        })
                    },
                    "check_callback": true,
                    'multiple': false,
                },
                "force_text": true,
                'plugins' : [ 'types', 'state', 'checkbox', 'search' ],
                "checkbox": {
                    "keep_selected_style": false,//是否默认选中
                    "three_state": true,//父子级别级联选择
                    "tie_selection": false
                },
            });
            $('#tree').on("loaded.jstree", function(event, data) {
                data.instance.clear_state(); //清除jstree保存的选中状态cookie记录
            });
        }
        //创建相关图形树end
		$(document).ready(function(){
            createGraphTree();//首次初始化
            //tree加载完成后调用
            $('#tree').on('ready.jstree', function(e, data) { 
                if($("#extension").val()){
                    var extension=JSON.parse($("#extension").val());//扩展字段
                    if(extension&&extension.ids_checked){
                        $('#tree').jstree(true).check_node(extension.ids_checked);
                    }
                }                        
            }); 
            //图形树下拉框begin
            $("#graph_tree_id").change(function(){
                createGraphTree();
            });
            //图形树下拉框end
            //提交按钮验证begin
            $("#submit").on('click', function() {  
                //扩展字段操作begin
                var extension={};
                var  tree = $('#tree').jstree(true); // 获取整个树
                var  ids_checked = tree.get_checked(); // 获取选中的所有节点
                var  ids_undetermined=tree.get_undetermined();// 获取所有半选的节点
                var  ids_concat=ids_checked.concat(ids_undetermined);//合并id
                for(var i=0;i<ids_concat.length;i++){
                    encapsulationData(tree_data,ids_concat[i]);//封装数据
                } 
                extension.ids_checked=ids_checked;//选中的ids
                extension.datas_checked=tree_data;
                $("#extension").val(JSON.stringify(extension));
                //扩展字段操作end
                return true;
            });  
            //提交按钮验证end
            //全选按钮事件
            $("#selectAll").click(function(){
                var tree = $('#tree').jstree(true);//获取整个树
                tree.check_all();//选择所有
            });
            //全选按钮事件
		});
	</script>
    <?php
    html_end_box(true, true);
    form_end(false);//表单编辑结束
}
//宽带通道预警信息修改操作
function channel_utilization_save(){
    global $config;
    $save=array();
    $save['id'] = get_filter_request_var('id');
    if($save['id']==''){//表示新增
        $save['begin_date'] = date('Y-m-01',time());
    }
    $save['name'] = form_input_validate(get_nfilter_request_var('name'), 'name', '', false, 3);
    $save['utilization_ratio_threshold'] = form_input_validate(get_nfilter_request_var('utilization_ratio_threshold'), 'utilization_ratio_threshold', '', true, 3);
    $save['is_day'] = (isset_request_var('is_day') ? 'on':'');
    $save['notification_id_day'] = form_input_validate(get_nfilter_request_var('notification_id_day'), 'notification_id_day', '', true, 3);
    $save['is_week'] = (isset_request_var('is_week') ? 'on':'');
    $save['notification_id_week'] = form_input_validate(get_nfilter_request_var('notification_id_week'), 'notification_id_week', '', true, 3);
    $save['is_month'] = (isset_request_var('is_month') ? 'on':'');
    $save['notification_id_month'] = form_input_validate(get_nfilter_request_var('notification_id_month'), 'notification_id_month', '', true, 3);
    $save['description'] = form_input_validate(get_nfilter_request_var('description'), 'description', '', true, 3);
    $save['graph_tree_id'] = form_input_validate(get_nfilter_request_var('graph_tree_id'), 'graph_tree_id', '', true, 3);
    $save['extension'] = form_input_validate(get_nfilter_request_var('extension'), 'extension', '', true, 3);
    $save['last_modified'] = date('Y-m-d H:i:s', time());
    $save['modified_by'] = $_SESSION['sess_user_id'];
    if (is_error_message()) {
        header('Location: report.php?action=channel_utilization_edit&id=' . (empty($id) ? get_nfilter_request_var('id') : $id));
		exit;
	}else{
        $id=sql_save($save, 'plugin_report_channel_utilization');
        if ($id) {
            raise_message(1);
            header('Location: report.php?action=channel_utilization');
            exit;
        } else {
            raise_message(2);
            header('Location: report.php?action=channel_utilization_edit&id=' . (empty($id) ? get_nfilter_request_var('id') : $id));
            exit;
        }
    }
}
/**
 * 宽带通道预警入口
 */
function channel_utilization(){
    global $config;
    global $channel_utilization_actions,$item_rows;
    /* ================= input validation and session storage ================= */
    $filters = array(
        'rows' => array(
            'filter' => FILTER_VALIDATE_INT,
            'pageset' => true,
            'default' => '-1'
        ),
        'page' => array(
            'filter' => FILTER_VALIDATE_INT,
            'default' => '1'
        ),
        'filter' => array(
            'filter' => FILTER_CALLBACK,
            'pageset' => true,
            'default' => '',
            'options' => array('options' => 'sanitize_search_string')
        ),
        'sort_column' => array(
            'filter' => FILTER_CALLBACK,
            'default' => 'id',
            'options' => array('options' => 'sanitize_search_string')
        ),
        'sort_direction' => array(
            'filter' => FILTER_CALLBACK,
            'default' => 'ASC',
            'options' => array('options' => 'sanitize_search_string')
        )
    );
    validate_store_request_vars($filters, 'sess_channel_utilization');//
    /* ================= input validation ================= */
    /* if the number of rows is -1, set it to the default */
    if (get_request_var('rows') == -1) {
        $rows = read_config_option('num_rows_table');
    } else {
        $rows = get_request_var('rows');
    }
    html_start_box("新增宽带通道预警", '100%', '', '3', 'center', 'report.php?action=channel_utilization_edit');
    ?>
    <tr class='even'>
        <td>
            <form id='form_channel_utilization' action='report.php?action=channel_utilization'>
                <table class='filterTable'>
                    <tr>
                        <td>
                            <?php print __('Search');?>
                        </td>
                        <td>
                            <input type='text' class='ui-state-default ui-corner-all' id='filter' size='25' value='<?php print html_escape_request_var('filter');?>'>
                        </td>
                        <td>
                            宽带通道预警记录
                        </td>
                        <td>
                            <select id='rows' onChange='applyFilter()'>
                                <option value='-1'<?php print (get_request_var('rows') == '-1' ? ' selected>':'>') . __('Default');?></option>
                                <?php
                                if (cacti_sizeof($item_rows)) {
                                    foreach ($item_rows as $key => $value) {
                                        print "<option value='" . $key . "'"; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . html_escape($value) . "</option>\n";
                                    }
                                }
                                ?>
                            </select>
                        </td>
                        <td>
						<span>
							<input type='button' class='ui-button ui-corner-all ui-widget' id='refresh' value='<?php print __esc('Go');?>' title='<?php print __esc('Set/Refresh Filters');?>'>
							<input type='button' class='ui-button ui-corner-all ui-widget' id='clear' value='<?php print __esc('Clear');?>' title='<?php print __esc('Clear Filters');?>'>
						</span>
                        </td>
                    </tr>
                </table>
            </form>
            <script type='text/javascript'>
                //查询操作函数
                function applyFilter() {
                    strURL  = 'report.php?action=channel_utilization&header=false';
                    strURL += '&filter='+$('#filter').val();
                    strURL += '&rows='+$('#rows').val();
                    loadPageNoHeader(strURL);
                }
                //重置查询函数
                function clearFilter() {
                    strURL = 'report.php?action=channel_utilization&clear=1&header=false';
                    loadPageNoHeader(strURL);
                }
                $(function() {
                    $('#refresh').click(function() {
                        applyFilter();
                    });
                    $('#clear').click(function() {
                        clearFilter();
                    });
                    $('#form_channel_utilization').submit(function(event) {
                        event.preventDefault();
                        applyFilter();
                    });
                });
            </script>
        </td>
    </tr>
    <?php
    html_end_box();
    /* form the 'where' clause for our main sql query */
    $sql_where = '';
    if (get_request_var('filter') != '') {
        $sql_where =$sql_where . " AND (name LIKE '%" . get_request_var('filter') . "%')";
    }
    $sql_join = "LEFT JOIN user_auth_perms b on b.item_id = a.id and b.type = 103 and b.user_id =". $_SESSION['sess_user_id'];
    $sql_where .= ' and b.item_id is null';
    
    $total_rows = db_fetch_cell("SELECT COUNT(a.*) FROM plugin_report_channel_utilization a $sql_join WHERE 1=1 $sql_where");
    $sql_order = get_order_string();
    $sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
    $channel_utilization_list = db_fetch_assoc("SELECT a.* FROM plugin_report_channel_utilization a $sql_join WHERE 1=1 $sql_where $sql_order $sql_limit");
    //cacti_log("SELECT * FROM plugin_report_channel_utilization WHERE 1=1 " . $sql_where . $sql_order . $sql_limit);
    $nav = html_nav_bar('report.php?action=channel_utilization&filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, 5, "宽带通道预警", 'page', 'main');
    form_start('report.php?action=channel_utilization', 'chk');//分页表单开始
    print $nav;
    html_start_box('', '100%', '', '3', 'center', '');
    $display_text = array(
        'id'      => array('display' => __('ID'),        'align' => 'left', 'sort' => 'ASC', 'tip' => "ID"),
        'name'    => array('display' => "名称", 'align' => 'left',  'sort' => 'ASC', 'tip' => "名称"),
        'description'    => array('display' => "描述", 'align' => 'left',  'sort' => 'ASC', 'tip' => "描述"),
        'status_detail' => array('display' => __('Status'), 'align' => 'left', 'sort' => 'ASC', 'tip' => "状态"),
        'last_modified' => array('display' => __('Last Edited'), 'align' => 'left', 'sort' => 'ASC', 'tip' => "最后编辑时间"),
        'modified_by'    => array('display' => "编辑人", 'align' => 'left',  'sort' => 'ASC', 'tip' => "编辑人")
    );
    html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false,'report.php?action=channel_utilization');
    if (cacti_sizeof($channel_utilization_list)) {
        foreach ($channel_utilization_list as $channel_utilization) {
            form_alternate_row('line' . $channel_utilization['id'], true);
            form_selectable_cell($channel_utilization['id'], $channel_utilization['id'], '');
            form_selectable_cell(filter_value($channel_utilization['name'], get_request_var('filter'), 'report.php?action=channel_utilization_edit&id=' . $channel_utilization['id']) , $channel_utilization['id']);
            form_selectable_cell($channel_utilization['description'],$channel_utilization['id'],'');
            form_selectable_cell($channel_utilization['status_detail'],$channel_utilization['id'],'');
            form_selectable_cell(substr($channel_utilization['last_modified'],0,16), $channel_utilization['id'], '');
            form_selectable_cell(get_username($channel_utilization['modified_by']),$channel_utilization['id'],'');
            form_checkbox_cell($channel_utilization['name'], $channel_utilization['id']);
            form_end_row();
        }
    } else {
        print "<tr class='tableRow'><td colspan='" . (cacti_sizeof($display_text)+1) . "'><em>" . "没有数据" . "</em></td></tr>\n";
    }
    html_end_box(false);//与谁对应
    if (cacti_sizeof($channel_utilization_list)) {
        print $nav;
    }
    draw_actions_dropdown($channel_utilization_actions);
    form_end();//分页form结束
}
//统计导出页面
function channel_utilization_import(){
    global $config;
    $channel_utilization_id=get_request_var('channel_utilization_id');
    $report_channel_utilization= db_fetch_row_prepared('SELECT * FROM plugin_report_channel_utilization WHERE id = ?', array($channel_utilization_id));
    $report_channel_utilization_excel_array= db_fetch_assoc("SELECT * FROM plugin_report_channel_utilization_excel WHERE  excel_type='手工统计' and report_channel_utilization_id = ". $channel_utilization_id . " order by last_modified desc limit 10");
    form_start('report.php');
    html_start_box('立即执行', '60%', '', '3', 'center', '');
    print "<input type='hidden' name='channel_utilization_id' value='" . $report_channel_utilization['id'] . "'/>\n";
    print "<input type='hidden' name='action' value='do_channel_utilization_import'/>\n";
    print "<table style='width:100%'>";
    print "<tr>
                <td class='textArea' style='padding-left: 40px;padding-top: 15px;padding-bottom: 15px;' class='odd'>
                    <div class='itemlist'>[" . $report_channel_utilization['name'] . "]有效日期范围(" . $report_channel_utilization['begin_date'] . "至" . date('Y-m-d',(strtotime(date('Y-m-d', time()))-86400)) . ")</div>
                </td>
            </tr>\n";
    print "<tr>
                <td class='textArea' style='padding-left: 40px;padding-bottom: 15px;' class='odd'>
                    <div class='itemlist'>
                    起止日期<input type='text' name='channel_utilization_import_begin_date' id='channel_utilization_import_begin_date'/>-<input type='text' name='channel_utilization_import_end_date' id='channel_utilization_import_end_date'/>
                    <input type='submit' id='submit' value='立即执行' role='button'>
                    <input type='button' onclick='window.location.href=\"report.php?action=channel_utilization\"' value='返回' role='button'>
                    </div>
                </td>
           </tr>\n";
    print "<tr>
                <td class='textArea' style='padding-left: 40px;' class='odd'>
                    <h3>最近10条导出记录</h3>
                </td>
           </tr>\n";
    if(cacti_count($report_channel_utilization_excel_array)==0){
        print "<tr>
                    <td class='textArea' style='padding-left: 40px;padding-bottom: 15px;' class='odd'>
                        <div class='itemlist'>暂无记录</div>
                    </td>
               </tr>\n";
    }else{
        foreach ($report_channel_utilization_excel_array as $report_channel_utilization_excel){
            print "<tr>
                        <td class='textArea' style='padding-left: 40px;padding-bottom: 15px;' class='odd'>
                            <div class='itemlist'><a target='_blank' href='" . $config['url_path'] . $report_channel_utilization_excel['excel_path'] . "' download='" . $report_channel_utilization_excel['excel_name'] . "'>" . html_escape($report_channel_utilization_excel['excel_name']) . "</a><div>
                        </td>
                   </tr>\n";
                
        }
    }
    print "</table>";
    ?>
    <script>
        $(document).ready(function(){
            $("#channel_utilization_import_begin_date").prop("readonly", true).datepicker({
                changeMonth: false,
                dateFormat: "yy-mm-dd",
                onClose: function(selectedDate) {

                }
            });
            $("#channel_utilization_import_end_date").prop("readonly", true).datepicker({
                changeMonth: false,
                dateFormat: "yy-mm-dd",
                onClose: function(selectedDate) {

                }
            });
        });
    </script>
    <?php
    form_end();//表单编辑结束
}
//执行手动导出操作
function do_channel_utilization_import(){
    global $config;
    $channel_utilization_id = get_filter_request_var('channel_utilization_id');
    $channel_utilization_import_begin_date = form_input_validate(get_nfilter_request_var('channel_utilization_import_begin_date'), 'channel_utilization_import_begin_date', '', true, 3);
    $channel_utilization_import_end_date = form_input_validate(get_nfilter_request_var('channel_utilization_import_end_date'), 'channel_utilization_import_end_date', '', true, 3);
    $report_channel_utilization= db_fetch_row_prepared('SELECT * FROM plugin_report_channel_utilization WHERE id = ?', array($channel_utilization_id));
    $current_date=date('Y-m-d', time());//今天
    if ($channel_utilization_import_begin_date=='') {
        raise_message(2,"开始日期不能为空",MESSAGE_LEVEL_ERROR);
    }
    else if($channel_utilization_import_end_date==''){
        raise_message(2,"结束日期不能为空",MESSAGE_LEVEL_ERROR);
    }
    else if(strtotime($channel_utilization_import_begin_date)<strtotime($report_channel_utilization['begin_date'])){
        raise_message(2,"开始日期不能小于".$report_channel_utilization['begin_date'],MESSAGE_LEVEL_ERROR);
    }
    else if(strtotime($channel_utilization_import_end_date)>=strtotime($current_date)){
        raise_message(2,"结束日期不能大于等于".$current_date,MESSAGE_LEVEL_ERROR);
    }
    else if(strtotime($channel_utilization_import_end_date)<strtotime($channel_utilization_import_begin_date)){
        raise_message(2,"结束日期不能小于开始日期",MESSAGE_LEVEL_ERROR);
    }
    else{
        $data_begin_date=$channel_utilization_import_begin_date;
        $data_end_date=$channel_utilization_import_end_date;
        channel_utilization_excel($report_channel_utilization,'手工统计',$data_begin_date,$data_end_date);
    }
    header('Location: report.php?action=channel_utilization_import&channel_utilization_id=' . (empty($channel_utilization_id) ? get_nfilter_request_var('channel_utilization_id') : $channel_utilization_id));
	exit;
}
