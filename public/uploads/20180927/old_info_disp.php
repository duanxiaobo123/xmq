<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>
<script src="/public/laydate/laydate.js"></script> 
<link rel="stylesheet" href="/public/css/uniform.css" />
<link rel="stylesheet" href="/public/css/select2.css" />
<script type="text/javascript">
$("#info_visit").live('change',function(){
    var info_visit = $(this).val();
    $.ajax({ //一个Ajax过程
        type:"get", 
        cache:false,
        url :"<?php echo site_url('info/ajax_info_status') ?>/"+info_visit,
        dataType:'json',//从php返回的值以 JSON方式 解释
        data:'', //发给php的数据
        success: function(json){//如果调用php成功
            var html = '';
            html+='<option value="0" selected="selected" >--选择状态--</option>';
            for(i in json){
                html+="<option value='"+i+"' >"+json[i]+"</option>";
                
            }
            $("#teacher_id").parent().parent().remove();
            $(".banji").remove();   
            $(".ht").remove();
            $("#info_status").html(html);
        }
    });
})
</script>
</head>
<body>
<?php 
require_once('inc/info_status.php');
require_once('inc/info_age.php'); 
require_once('inc/info_type.php'); 
require_once('inc/info_follow_up.php'); 
require_once('inc/info_visit.php');
?>
<div id="content">
  <div id="content-header">
    <div id="breadcrumb"> 
      <a href="<?php echo site_url('home/desktop');?>" title="返回桌面" class="tip-bottom"><i class="icon-home"></i> 桌面</a> 
      <a href="<?php echo site_url('info'); ?>" class="">资源列表</a> 
      <a href="#" class="current"><?php echo $info_item['name']; ?></a> 
    </div>
    <!-- <h1>资源列表</h1> -->
    <p class="operation">
      <button class="btn btn-primary" onclick="jacascript:location.href='<?php echo site_url('info/index/'.intval($this->session->userdata('info_page'))); ?>';"><i class="icon-undo"></i> 返回 </button>
       <?php 
      //如果是添加者可编辑.但分配给顾问后就不能再编辑。
      if (empty($info_item['adviser_id']) && intval($info_item['add_user_id']) == intval($this->session->userdata('admin_user_id')) ) 
      {
      ?>
      <button class="btn btn-info" onclick="jacascript:location.href='<?php echo site_url('info/set_info/'.$info_item['id']); ?>';"><i class="icon-edit"></i> 编辑 </button>
      <?php 
      }
      ?>

      <?php 
      //所属顾问可修改基本信息
      if (intval($info_item['adviser_id']) == intval($this->session->userdata('admin_user_id')) ) 
      {
      ?>
      <button class="btn btn-info" onclick="jacascript:location.href='<?php echo site_url('info/set_info/'.$info_item['id']); ?>';"><i class="icon-edit"></i> 编辑 </button>
      <?php 
      }
      ?>

      <?php if ($this->my_permission->permi(',0_7,')) {?>
      <button class="btn btn-info" onclick="open_window('交接资源','<?php echo site_url("info/edit_add_user/$info_item[id]"); ?>','500px','300px');"><i class="icon-share"></i> 交接资源 </button>
      <?php } ?>
      <?php if ($this->my_permission->permi(',0_8,')) {?>
      <button class="btn btn-info" onclick="open_window('重新分配校区 <?php echo $info_item['name'] ;?>','<?php echo site_url("info/to_campus/".$info_item['id']); ?>','500px','400px')"><i class="icon-retweet"></i> 重新分配 </button>
      <?php } ?>
      
      <!-- <?php if ($this->my_permission->permi(',11_3,')) {?>
      <button class="btn btn-info" onclick="open_window('发送短信 <?php echo $info_item['name'] ;?>','<?php echo site_url("sms/sms_send/".$info_item['id']); ?>','550px','420px')"><i class="icon-comments"></i> 发送短信 </button>
      <?php } ?> -->

      <button class="btn btn-info" onclick="window.location.reload()"><i class="icon-refresh"></i> 刷新 </button>
    </p>
  </div>


  <div class="container-fluid"><hr>
    <div class="row-fluid">
      <div class="span12">
        <div class="widget-box">
          <div class="widget-title"> <span class="icon"> <i class="icon-briefcase"></i> </span>
            <h5 ><?php echo $info_item['name']; ?></h5>
          </div>
          <div class="widget-content">
            <div class="row-fluid">
              <div class="span4">
                <table class="table-condensed">
                  <tbody>
                    <tr>
                      <td><h4><?php echo $info_item['name']; ?></h4></td>
                    </tr>
                    <tr>
                      <td>
                        <?php if($info_item['gender']) echo '男'; else echo '女'; ?>&nbsp;&nbsp;&nbsp;&nbsp;
                        <?php if($info_item['age']) echo $info_item['age'].' 岁'; ?>&nbsp;&nbsp;&nbsp;&nbsp;
                        <?php if($info_item['age_extent']) echo $info_age[$info_item['age_extent']] ; ?>
                      </td>
                    </tr>
                    <tr>
                      <td><b>座机</b>：<?php echo $info_item['tel']; ?></td>
                    </tr>
                    <tr>
                      <td><b>手机</b>：
                      <?php 
                        $admin_user_id = intval($this->session->userdata('admin_user_id'));
                        if ($admin_user_id == 277 )
                        {
                          echo '***********';
                        }
                        else
                        {
                          echo $info_item['mobile'];
                        }

                      ?>
                    </tr>
                    <tr>
                      <td><b>Email</b>：<?php echo $info_item['email']; ?></td>
                    </tr>
                     <tr>
                      <td><b>地址</b>：<?php echo $info_item['address']; ?></td>
                    </tr>
                     <tr>
                      <td><b>学习意向</b>：
                        <?php echo $info_item['lesson_name']; ?>
                        <?php echo ' / '.$info_item['group_name']; ?>
                        <?php echo ' / '.$info_item['class_name']; ?>
                      </td>
                    </tr>
                     <tr>
                      <td><b>备注</b>：<?php echo $info_item['remark']; ?></td>
                    </tr>
                    <tr>
                      <td><b>预约体验时间</b>：<?php if (!empty($info_item['tiyan_date'])) echo date("Y-m-d H:i:s",$info_item['tiyan_date']); ?></td>
                    </tr>

                  </tbody>
                </table>
              </div>
              <div class="span5">
                <table class="table table-bordered table-invoice">
                  <tbody>
                    
                    <tr>
                      <td class="width30">收单日期：</td>
                      <td class="width70"><strong><?php echo date("Y-m-d",$info_item['info_date']); ?></strong></td>
                    </tr>
                    <tr>
                      <td class="width30">地区：</td>
                      <td class="width70"><strong><?php echo $info_item['region_name']; ?> 
                        <?php if (!empty($info_item['region_remark'])) echo '('.$info_item['region_remark'].')' ?></strong></td>
                    </tr>
                    <tr>
                      <td>具体位置：</td>
                      <td><strong><?php echo $info_item['region_remark']; ?></strong></td>
                    </tr>
                   <tr> 
                  <td class="width30">收单人：</td>
                    <td><strong><?php echo $info_item['gather_name']; ?></strong></td>
                  </tr>
                  <tr>
                      <td>所属部门：</td>
                      <td><strong><?php echo $info_item['department_name']; ?></strong></td>
                    </tr>
                     <tr>
                      <td>邀约人</td>
                      <td><strong><?php echo $info_item['invite_name']; ?></strong></td>
                    </tr>


                     <tr>
                      <td>来源：</td>
                      <td><strong><?php echo $info_item['source_name']; ?></strong></td>
                    </tr>
                    <tr>
                      <td>分配校区：</td>
                      <td><strong><?php echo $info_item['campus_name']; ?></strong></td>
                    </tr>
                    <tr>
                      <td>课程顾问：</td>
                      <td><strong><?php echo $info_item['adviser_name']; ?></strong></td>
                    </tr>
                    <tr>
                      <td>资源类型</td>
                      <td><strong><?php if ($info_item['info_type']) echo $info_type[$info_item['info_type']]; ?></strong></td>
                    </tr>
                    </tbody>
                  
                </table>
              </div>
            </div>

             <!-- <div class="row-fluid">
              <div class="span12">
                <div class="widget-box">
                  <div class="widget-title"> <span class="icon"> <i class="icon-list"></i> </span>
                    <h5>短信记录</h5>
                  </div>
                  <div class="widget-content nopadding">
                      <table class="table table-bordered table-invoice-full">
                        <thead>
                          <tr>
                            <th class="head0">内容</th>
                            <th nowrap class="head1">发送人</th>
                            <th nowrap class="head1">发送时间</th>
                            <th nowrap class="head1">状态</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php 
                          foreach ($sms_log as $log) 
                          {
                          ?>
                          <tr>
                            <td><?php echo $log['sms_content']; ?></td>
                            <td nowrap><?php echo $log['send_user_name']; ?></td>
                            <td nowrap><?php echo date("Y-m-d H:i:s",$log['send_time']); ?></td>
                            <td nowrap><?php echo $log['send_status']; ?></td>
                          </tr>
                          <?php
                          }
                          ?>       
                        </tbody>
                      </table>
                  </div>
                </div>
              </div>
            </div> -->


             <div class="row-fluid">
              <div class="span12">
                <div class="widget-box">
                  <div class="widget-title"> <span class="icon"> <i class="icon-list"></i> </span>
                    <h5>分配记录</h5>
                  </div>
                  <div class="widget-content nopadding">
                      <table class="table table-bordered table-invoice-full">
                        <thead>
                          <tr>
                            <th class="head0">分配人</th>
                            <th class="head1">接收人</th>
                            <th class="head1">校区</th>
                            <th class="head1">备注</th>
                            <th calss="head1">日期</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php 
                          foreach ($allocation as $allocation_item) 
                          {
                          ?>
                          <tr>
                            <td><?php echo $allocation_item['from_user_name']; ?></td>
                            <td><?php echo $allocation_item['to_user_name']; ?></td>
                            <td><?php echo $allocation_item['campus_name'];?></td>
                            <td><?php echo $allocation_item['remark'];?></td>
                            <td><?php echo date("Y-m-d H:i:s",$allocation_item['to_time']); ?></td>
                          </tr>
                          <?php
                          }
                          ?>       
                        </tbody>
                      </table>
                  </div>
                </div>
              </div>
            </div>

            <div class="row-fluid">
              <div class="span12">
                <div class="widget-box">
                  <div class="widget-title"> <span class="icon"> <i class="icon-list"></i> </span>
                    <h5>电访记录</h5>
                  </div>
                  <div class="widget-content nopadding">
                      <table class="table table-bordered table-invoice-full">
                        <thead>
                          <tr>
                            <th nowrap class="head0">电访日期</th>
                            <th nowrap class="head1">详细</th>
                            <th nowrap class="head1">有效性</th>
                            <th nowrap class="head1">状态</th>
                            <th nowrap class="head1">操作者</th>
                            <th nowrap calss="head1">添加日期</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php 
                          foreach ($call as $call_item) 
                          {
                          ?>
                          <tr>
                            <td nowrap><?php echo date("Y-m-d",$call_item['call_date']); ?></td>
                            <td><?php echo nl2br($call_item['call_remark']); ?></td>
                            <td nowrap><?php echo ($call_item['validity'])? 'Y' : '<span class="ft_h">N</span>' ;?></td>
                            <td nowrap>
                              <?php
                              if ($call_item['info_status'] == 110)
                              {
                                echo $info_status[$call_item['info_status2']]; 
                              } 
                              else
                              {
                                echo $info_status[$call_item['info_status']]; 
                              }

                              ?>
                            </td>
                            <td nowrap><?php echo $call_item['add_user_name']; ?></td>
                            <td nowrap><?php echo date("Y-m-d H:i:s",$call_item['add_time']); ?></td>
                          </tr>
                          <?php
                          }
                          ?>       
                        </tbody>
                      </table>
                  </div>
                </div>
              </div>
            </div>
          <?php  
          //课程顾问电访内容
          //客服电访只能加自己的，客服主管可加全部
          $istel = $this->my_permission->permi(',0_12,');//全部权限

          if (intval($info_item['adviser_id']) == intval($this->session->userdata('admin_user_id')) or intval($info_item['add_user_id']) == intval($this->session->userdata('admin_user_id')) or $istel )
          {
          ?>
            <div class="widget-box">
              <div class="widget-title"> <span class="icon"><i class="icon-align-justify"></i></span>
                <h5>添加电话记录</h5>
              </div>
              <div class="widget-content nopadding">
                <?php
                $attribute = array('class' => 'form-horizontal','name' => 'call_validate','id' => 'call_validate','enctype'=>'multipart/form-data');
                echo form_open("info/set_call/", $attribute);
                ?>
                   <div class="control-group">
                    <label class="control-label">电访日期:</label>
                    <div class="controls">
                      <input id="info_id" name="info_id" type="hidden" value="<?php echo $info_item['id']; ?>">
                      <input type="text" id="call_date" name="call_date" class="span6 laydate-icon" placeholder="" value="<?php echo date("Y-m-d"); ?>" />
                      <input type="hidden" id="info_visit_time" name="info_visit_time" class="span6 laydate-icon" placeholder="" value="<?php if ($info_item['info_visit_time']) echo date("Y-m-d",$info_item['info_visit_time']); else echo date("Y-m-d"); ?>" />
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label">详细 :</label>
                    <div class="controls">
                      <textarea rows="4" class="span6" name="call_remark" ></textarea>
                    </div>
                  </div>  

                   <div class="control-group ">
                    <label class="control-label" style="color:#f00;">回访提醒时间:</label>
                    <div class="controls">
                      <input type="text" id="alert_date" name="alert_date" class="span6 laydate-icon" placeholder="如需系统提醒请输入提醒时间" value="" />
                    </div>
                  </div>
                  
                  <div class="control-group">
                    <label class="control-label">跟进人员 (可回收资源):</label>
                    <div class="controls">
                      <select class="span6 m-wrap" name="follow_up" id="follow_up">
                        <?php
                          foreach ($follow_up as $key => $s) 
                          {
                            $select = '';
                            if ($info_item['follow_up'] == $key)
                            {
                              $select = 'selected="selected"';
                            }
                            else
                            {
                              if($key == 20)
                              {
                                $select = 'selected="selected"';
                              }
                              else
                              {
                                $select = '';
                              }
                              
                            }

                            if ($key) echo '<option value="'.$key.'" '.$select.' >'.$s.'</option>';//不要key 为 0 的
                          }
                        ?>  
                        
                      </select>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label">是否上门 :</label>
                    <div class="controls">
                      <select class="span6 m-wrap" name="info_visit" id="info_visit">
                        <option value="0">--请选择--</option>
                        <?php
                          foreach ($info_visit as $key => $s) 
                          {
                            $select = '';
                            if ($info_item['info_visit'] == $key)
                            {
                              $select = 'selected="selected"';
                            }
                            else
                            {
                              $select = '';
                            }
                            if ($key) echo '<option value="'.$key.'" '.$select.' >'.$s.'</option>';//不要key 为 0 的
                          }
                        ?>  
                        
                      </select>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label">电访状态 :</label>
                    <div class="controls" id='asd'>
                      <select class="span6 m-wrap" name="info_status" id="info_status">
                        <option value="0">--选择状态--</option>
                        <?php
                        $info_status = [];
                        if($info_item['info_visit'] == 10){
                            $info_status['10'] = '未处理'; 
                            $info_status['30'] = '承诺上门(可以安排体验课)'; 
                            $info_status['43'] = '死单'; 
                            $info_status['45'] = '无效资源'; 
                        }
                        if($info_item['info_visit'] == 20){
                            $info_status['30'] = '承诺上门(可以安排体验课)'; 
                            $info_status['57'] = '上门未体验'; 
                        }
                          foreach ($info_status as $key => $s) 
                          {
                             //if ($key && $key!=50 && $key!=80) echo '<option value="'.$key.'" >'.$s.'</option>';//不要key 为 0 的,50已体验和80已交费不要这里显示
                             if ($key) echo '<option value="'.$key.'">'.$s.'</option>';//不要key 为 0 的
                          }
                        ?>  
                        
                      </select>
                    </div>
                  </div>
                <div class="control-group tiyan_date">
                    <label class="control-label">预约体验日期:</label>
                    <div class="controls">
                      <input type="text" id="tiyan_date" name="tiyan_date" class="span6 laydate-icon" placeholder="" value="<?php if (!empty($info_item['tiyan_date'])) echo date("Y-m-d H:i:s",$info_item['tiyan_date']); ?>" />
                    </div>
                </div>
                 

                  <input type="hidden" name="validity"  value="1" > <!-- 有效性 -->
                  <div class="form-actions">
                    <button type="submit" class="btn btn-success"><i class="icon-ok"></i> 确 定</button>
                  </div>
                  <?php echo form_close(); ?>
              </div>
            </div>
            <?php  
            }
            else
            {
              //客服电访记录，如果分配给课程顾问，客服就不能再加电访记录
              if (empty($info_item['adviser_id']))
              {
            ?>
            <div class="widget-box">
              <div class="widget-title"> <span class="icon"><i class="icon-align-justify"></i></span>
                <h5>添加电话记录</h5>
              </div>
              <div class="widget-content nopadding">
                <?php
                $attribute = array('class' => 'form-horizontal','name' => 'call_validate','id' => 'call_validate');
                echo form_open("info/set_call/", $attribute);
                ?>
                   <div class="control-group">
                    <label class="control-label">电访日期:</label>
                    <div class="controls">
                      <input id="info_id" name="info_id" type="hidden" value="<?php echo $info_item['id']; ?>">
                      <input type="text" id="call_date" name="call_date" class="span6 laydate-icon" placeholder="" value="<?php echo date("Y-m-d"); ?>" />
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label">详细 :</label>
                    <div class="controls">
                      <textarea rows="4" class="span6" name="call_remark" ></textarea>
                    </div>
                  </div>  

                  <div class="control-group">
                    <label class="control-label">有效性 :</label>
                    <div class="controls">
                      <label class="radio-inline span3">
                        <input type="radio" name="validity" id="inlineRadio1" value="1" checked> 有效
                      </label>
                      <label class="radio-inline span3 ">
                        <input type="radio" name="validity" id="inlineRadio2" value="0"> 无效
                      </label>
                    </div>
                  </div>  

                    
                  <div class="form-actions">
                    <button type="submit" class="btn btn-success"><i class="icon-ok"></i> 确 定</button>
                  </div>
                  <?php echo form_close(); ?>
              </div>
            </div>
            <?php  
              }
            }
            ?>

          </div>

           

        </div>
      </div>
    </div>
  </div>

  <div class="row-fluid">
      <p class="operation">
      <button class="btn btn-primary" onclick="jacascript:location.href='<?php echo site_url('info/index/'.intval($this->session->userdata('info_page'))); ?>';"><i class="icon-undo"></i> 返回 </button>
       <?php 
      //如果是添加者可编辑.但分配给顾问后就不能再编辑。
      if (empty($info_item['adviser_id']) && intval($info_item['add_user_id']) == intval($this->session->userdata('admin_user_id')) ) 
      {
      ?>
      <button class="btn btn-info" onclick="jacascript:location.href='<?php echo site_url('info/set_info/'.$info_item['id']); ?>';"><i class="icon-edit"></i> 编辑 </button>
      <?php 
      }
      ?>

      <?php 
      //所属顾问可修改基本信息
      if (intval($info_item['adviser_id']) == intval($this->session->userdata('admin_user_id')) ) 
      {
      ?>
      <button class="btn btn-info" onclick="jacascript:location.href='<?php echo site_url('info/set_info/'.$info_item['id']); ?>';"><i class="icon-edit"></i> 编辑 </button>
      <?php 
      }
      ?>

      <?php if ($this->my_permission->permi(',0_7,')) {?>
      <button class="btn btn-info" onclick="open_window('交接资源','<?php echo site_url("info/edit_add_user/$info_item[id]"); ?>','500px','300px');"><i class="icon-share"></i> 交接资源 </button>
      <?php } ?>
      <?php if ($this->my_permission->permi(',0_8,')) {?>
      <button class="btn btn-info" onclick="open_window('重新分配校区 <?php echo $info_item['name'] ;?>','<?php echo site_url("info/to_campus/".$info_item['id']); ?>','500px','400px')"><i class="icon-retweet"></i> 重新分配 </button>
      <?php } ?>
        
      <?php if ($this->my_permission->permi(',11_3,')) {?>
      <button class="btn btn-info" onclick="open_window('发送短信 <?php echo $info_item['name'] ;?>','<?php echo site_url("sms/sms_send/".$info_item['id']); ?>','550px','420px')"><i class="icon-comments"></i> 发送短信 </button>
      <?php } ?>

      <button class="btn btn-info" onclick="window.location.reload()"><i class="icon-refresh"></i> 刷新 </button>
    </p>
  </div>

</div>
<script src="/public/js/ajax_fees.js"></script> 

<script src="/public/js/jquery.ui.custom.js"></script> 
<script src="/public/js/jquery.uniform.js"></script> 
<script src="/public/js/select2.min.js"></script> 
<script src="/public/js/jquery.validate.js"></script> 
<script src="/public/js/matrix.form_validation.js"></script>
<script src="/public/js/matrix.form_common.js"></script> 



<script type="text/javascript">
  //日期插件开始
  laydate.skin('molv');  //加载皮肤，参数lib为皮肤名 

  laydate({
      elem: '#call_date', //目标元素。由于laydate.js封装了一个轻量级的选择器引擎，因此elem还允许你传入class、tag但必须按照这种方式 '#id .class'
      max:laydate.now(), 
      event: 'focus' //响应事件。如果没有传入event，则按照默认的click
  });


  laydate({
      elem: '#tiyan_date', //目标元素。由于laydate.js封装了一个轻量级的选择器引擎，因此elem还允许你传入class、tag但必须按照这种方式 '#id .class'
      min:laydate.now(), 
      format: 'YYYY-MM-DD hh:mm:ss',
      istime: true,
      fixed: false, //是否固定在可视区域
      event: 'focus' //响应事件。如果没有传入event，则按照默认的click

  });

  laydate({
      elem: '#alert_date', //目标元素。由于laydate.js封装了一个轻量级的选择器引擎，因此elem还允许你传入class、tag但必须按照这种方式 '#id .class'
      min:laydate.now(), 
      format: 'YYYY-MM-DD hh:mm:ss',
      istime: true,
      fixed: false, //是否固定在可视区域
      event: 'focus' //响应事件。如果没有传入event，则按照默认的click

  });

  




  //日期插件结束
</script>
