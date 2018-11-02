<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>
<link rel="stylesheet" href="/public/css/uniform.css" />
<link rel="stylesheet" href="/public/css/select2.css" />
<script type="text/javascript">
// 弹窗
$(document).ready(function(){
    $('.so').on('click',  function(){           
        parent.$.layer({
            type: 2,
            maxmin: true,
            title: '搜索',
            area: ['580px', '500px'],
            shade: [0], //不显示遮罩
            fadeIn: 500,//用于控制层渐显弹出
            iframe: {src: '<?php echo site_url("student/student_so") ?>'}
        })
    });
})

</script>
</head>
<body>
<?php require_once('inc/student_status.php') ; ?>
<div id="content">
  <div id="content-header">
    <div id="breadcrumb"> <a href="<?php echo site_url('home/desktop');?>" title="返回桌面" class="tip-bottom"><i class="icon-home"></i> 桌面</a> 
      <a href="#" class="current">学员管理</a> 
    </div>
    <!-- <h1>资源列表</h1> -->
    <p class="operation">
      <button class="btn btn-success so"><i class="icon-search"></i> 搜索 </button>
      <!-- <button class="btn btn-info"> 删除 </button> -->
      <!-- <button class="btn btn-info" onclick="jacascript:location.href='market_list_excel.html';"><i class="icon-table"></i> 导出 </button> -->
      <button class="btn btn-info" onclick="window.location.reload()"><i class="icon-refresh"></i> 刷新 </button>
    </p>
  </div>
  <div class="container-fluid">
    <hr>
    <div class="row-fluid">
      <div class="span12">
        <div class="widget-box">
          <div class="widget-title"> 
            <span class="icon">
            <input type="checkbox" id="title-checkbox" name="title-checkbox" />
            </span>
            <h5>学员列表</h5>
          </div>
          <div class="widget-content nopadding">
            <div class="table-roll">
            <table class="table table-bordered table-striped with-check table-hover">
              <thead>
                <tr>
                  <th nowrap ><i class="icon-resize-vertical"></i></th>
                  <th nowrap >学号</th>
                  <th nowrap >姓名</th>
                  <th nowrap >年龄</th>
                  <th nowrap >性别</th>
                  <th nowrap >联系方式</th>
                  <th nowrap >课程顾问</th>
                  <th nowrap >任课老师</th>
                  <th nowrap >学员状态</th>
                  <th nowrap >入学时间</th>
                  <th nowrap >校区</th>
                  <th nowrap >操作</th>
                </tr>
              </thead>
              <tbody>

                <?php 
                foreach ($student as $key => $student) 
                {
                ?>
                <?php 
                    if( (($student['total_class_times']-$student['class_times']) < 1) && ($student['total_class_times'] > 0) ){ 
                        $a = 'style="color:red"';
                    }else{
                        $a = '';
                    } 
                ?>
                <tr>
                  <td nowrap <?php echo $a ?> ><input type="checkbox"/></td>
                  <td nowrap <?php echo $a ?> ><?php echo $student['id']; ?></td>
                  <td nowrap <?php echo $a ?> ><a href="<?php echo site_url('student/iframe/'.$student['id']) ?>"><b <?php echo $a ?>><?php echo $student['name']; ?></b></a></td>
                  <td nowrap <?php echo $a ?> ><?php if($student['birthday']) echo date("Y")-date("Y",$student['birthday']) ?></td>
                  <td nowrap <?php echo $a ?> ><?php if($student['gender']) echo '男'; else echo '女'; ?></td>
                  <td nowrap <?php echo $a ?> ><?php if (!empty($student['mobile'])) echo $student['mobile']; else echo $student['tel'];?></td>
                  <td nowrap <?php echo $a ?> ><?php echo $student['adviser_name']; ?></td>
                  <td nowrap <?php echo $a ?> ><?php echo $student['teacher_name']; ?></td>
                  <td nowrap <?php echo $a ?> ><?php echo $student_status[$student['student_status']];?></td>
                  <td nowrap <?php echo $a ?> ><?php echo date("Y-m-d",$student['entry_date']); ?></td>
                  <td nowrap <?php echo $a ?> ><?php echo $student['campus_name']; ?></td>
                  
                  <td nowrap <?php echo $a ?>  class="taskOptions">
                    <a href="<?php echo site_url('student/student_set/'.$student['id']) ;?>" class="btn" data-original-title="修改"><i class="icon-edit"></i> </a>&nbsp;&nbsp;
                    <a href="<?php echo site_url('syllabus/month_student/0/0/'.intval($student['id'])) ?>" class="btn" data-original-title="课程安排"><i class="icon-calendar"></i> </a>&nbsp;&nbsp;
                      <?php
                      $admin_name=$this->session->userdata('admin_name');
                      if($admin_name=='admin'){

                          ?>
                          <a href="javascript:;" onclick="del_dialog('<?php echo site_url('system/system_del/student/del_stu/'.$student['id']); ?>')" class="btn" data-original-title="删除"><i class="icon-remove"></i> </a>
                          <?php
                      }
                      ?>
                      </td>
                </tr>
                <?php  
                }
                ?>

              </tbody>
            </table>
          </div>
          </div>
        </div>
        
        <div class="page pagination alternate"> 
          <ul>
          <?php echo $pages;?>
          </ul>
          <div> 共 <?php echo $total_count;?> 条 </div>
        </div> 
    </div>
  </div>
</div>
</div>

<script src="/public/js/jquery.ui.custom.js"></script> 
<script src="/public/js/bootstrap.min.js"></script> 
<script src="/public/js/jquery.uniform.js"></script> 
<script src="/public/js/select2.min.js"></script> 
<script src="/public/js/jquery.dataTables.min.js"></script> 
<script src="/public/js/matrix.tables.js"></script>
