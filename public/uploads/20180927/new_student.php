<?php 
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Student extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('global_model');
		$this->load->model('system_model');
		$this->load->model('student_model');
		$this->load->library('My_permission');//权限类
		
		//$this->output->enable_profiler(TRUE);
	}

	//学员列表
	public function index($page=1)
	{
		$this->my_permission->check_per(',6_2,','查看学员');
		$admin_user_id = intval($this->session->userdata('admin_user_id'));
		$campus_id     = intval($this->session->userdata('campus_id'));
		$fz_campus_id  = $this->session->userdata('fz_campus_id');//负责校区，多个用逗号分开
		// 分页类开始
		if ($page ==0){//清除搜索session
			$this->session->unset_userdata('student_query');
			$page = 1;
		}

		/*
		默认查询条件
		如果是只看本校数据
		*/
		if ($this->my_permission->permi(',6_20,'))
		{
			if ($fz_campus_id)
			{
				$where = "campus_id in ($fz_campus_id)";
			}
			else
			{
				$where = "campus_id = $campus_id";
			}

		}
		else
		{
			$where = 'id > 0';
		}


		//搜索生成的session 查询
		$where_session = & $this->session->userdata('student_query');

		if (!empty($where_session))
		{
			$where .= $where_session;
		}

		//当前页码存入session，操作返回用
		$this->session->set_userdata('student_page',$page);

		$total_count = $this->global_model->count_all('student',$where);//返回记录总数
		$this->load->library('pagination');
		$config['base_url']         = site_url('student/index') ;//完整的 URL 路径通向包含你的分页控制器类/方法
		$config['prefix']           = ""; // 自定义的前缀添加到路径
		$config['suffix']           = '';// 自定义的前缀添加到路径
		$config['cur_page']         = $page;//当前页
		$config['total_rows']       = $total_count;//数据总行数
		$config['per_page']         = 15; //每页显示行数
		$config['num_tag_open']     = '<li>';//自定义“数字”链接
		$config['num_tag_close']    = '</li>';//自定义“数字”链接
		$config['cur_tag_open']     = '<li class="active"><a href="#" >';//自定义“当前页”链接
		$config['cur_tag_close']    = '</li></a>';//自定义“当前页”链接
		$config['anchor_class']     = "";//添加 CSS 类
		$config['num_links']        = 3;//放在你当前页码的前面和后面的“数字”链接的数量。
		$config['use_page_numbers'] = TRUE;
		$config['first_link']       = '首页';//你希望在分页的左边显示“第一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 。
		$config['first_tag_open']   = '<li>';//“第一页”链接的打开标签。
		$config['first_tag_close']  = '</li>';//“第一页”链接的关闭标签。
		$config['prev_link']        = '上一页';//你希望在分页中显示“上一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 。
		$config['prev_tag_open']    = '<li>';//“上一页”链接的打开标签。
		$config['prev_tag_close']   = '</li>';//“上一页”链接的关闭标签
		$config['last_link']        = '末页';
		$config['last_tag_open']    = '<li>';//“最后一页”链接的打开标签。
		$config['last_tag_close']   = '</li>';//“最后一页”链接的关闭标签。
		$config['next_link']        = '下一页';//你希望在分页中显示“下一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 
		$config['next_tag_open']    = '<li>';//“下一页”链接的打开标签。
		$config['next_tag_close']   = '</li>';//“下一页”链接的关闭标签。

		$this->pagination->initialize($config); 
		$data['pages'] = $this->pagination->create_links();
		$data['total_count'] = $total_count;
		$data['student'] = $this->global_model->get_page('student',$where,'*','id desc',$config['per_page'], $page);
		
		$this->load->view('header');
		$this->load->view('student_list',$data);
		$this->load->view('bottom');
	}
	
	public function listselect(){
		$data['message']    = '正在查询，请稍侯……';
		$data['return_url'] = site_url('close_window/index/student/studentlist/0');
		$this->load->view('success', $data);
	}
	
	//分段推送列表页
	public function studentlist($order=0,$date=0,$teacher=0,$page=1){
		
		header("Content-type: text/html; charset=utf-8"); 
		$this->my_permission->check_per(',6_2,','查看学员');
		$admin_user_id = intval($this->session->userdata('admin_user_id'));
		$campus_id     = intval($this->session->userdata('campus_id'));
		$fz_campus_id  = $this->session->userdata('fz_campus_id');//负责校区，多个用逗号分开
		// 分页类开始
		if ($page ==0){//清除搜索session
			$this->session->unset_userdata('student_query');
			$page = 1;
		}
		
		/*
		默认查询条件
		如果是只看本校数据
		*/
		if ($this->my_permission->permi(',6_20,')){
			if ($fz_campus_id){
				$where = "campus_id in ($fz_campus_id)";
			}else{
				$where = "campus_id = $campus_id";
			}
		}else{
			$where = 'hmsypx_student.id > 0';
		}
		if($teacher!=0){
			$data['teacher_id'] = $teacher_id= intval($teacher);
			if ($teacher_id){
				$where .=" and hmsypx_student.teacher_id = ".$teacher_id;
			}
		}else{
			$data['teacher_id'] = $teacher_id=0;
		}
		//搜索生成的session 查询
		$where_session = & $this->session->userdata('student_query');

		if (!empty($where_session)){
			$where .= $where_session;
		}

		//当前页码存入session，操作返回用
		$this->session->set_userdata('student_page',$page);

		$total_count = $this->global_model->count_all('student',$where);//返回记录总数
		$this->load->library('pagination');
		$config['base_url']         = site_url('student/studentlist/'.$order.'/'.$date.'/'.$teacher_id.'/') ;//完整的 URL 路径通向包含你的分页控制器类/方法
		$config['prefix']           = ""; // 自定义的前缀添加到路径
		$config['suffix']           = '';// 自定义的前缀添加到路径
		$config['cur_page']         = $page;//当前页
		$config['total_rows']       = $total_count;//数据总行数
		$config['per_page']         = 15; //每页显示行数
		$config['num_tag_open']     = '<li>';//自定义“数字”链接
		$config['num_tag_close']    = '</li>';//自定义“数字”链接
		$config['cur_tag_open']     = '<li class="active"><a href="#" >';//自定义“当前页”链接
		$config['cur_tag_close']    = '</li></a>';//自定义“当前页”链接
		$config['anchor_class']     = "";//添加 CSS 类
		$config['num_links']        = 3;//放在你当前页码的前面和后面的“数字”链接的数量。
		$config['use_page_numbers'] = TRUE;
		$config['first_link']       = '首页';//你希望在分页的左边显示“第一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 。
		$config['first_tag_open']   = '<li>';//“第一页”链接的打开标签。
		$config['first_tag_close']  = '</li>';//“第一页”链接的关闭标签。
		$config['prev_link']        = '上一页';//你希望在分页中显示“上一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 。
		$config['prev_tag_open']    = '<li>';//“上一页”链接的打开标签。
		$config['prev_tag_close']   = '</li>';//“上一页”链接的关闭标签
		$config['last_link']        = '末页';
		$config['last_tag_open']    = '<li>';//“最后一页”链接的打开标签。
		$config['last_tag_close']   = '</li>';//“最后一页”链接的关闭标签。
		$config['next_link']        = '下一页';//你希望在分页中显示“下一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 
		$config['next_tag_open']    = '<li>';//“下一页”链接的打开标签。
		$config['next_tag_close']   = '</li>';//“下一页”链接的关闭标签。

		$this->pagination->initialize($config); 
		$data['pages'] = $this->pagination->create_links();
		$data['total_count'] = $total_count;
		if($date){
			if($date==1){
				$orders= 'add_time asc';
			}else{
				$orders= 'add_time desc';
			}
			$data['date'] = $date;
		}else{
			$data['date'] = 0;
			$orders= 'id desc';
		}
		if($order){
			$data['order'] = $order;
			$pages = ($page-1)*$config['per_page'];
			$oder = '(hmsypx_student_grade.total_class_times-hmsypx_student_grade.class_times) desc';
			
			$sql = 'select hmsypx_student.id id,hmsypx_student.name name,hmsypx_student.birthday birthday,hmsypx_student.gender gender,hmsypx_student.mobile mobile,hmsypx_student.tel tel,hmsypx_student.adviser_name adviser_name,hmsypx_student.teacher_name teacher_name,hmsypx_student.student_status student_status,hmsypx_student.entry_date entry_date,hmsypx_student.campus_name campus_name,hmsypx_student_grade.total_class_times total_class_times,(hmsypx_student_grade.total_class_times-hmsypx_student_grade.class_times) cha from hmsypx_student_grade LEFT JOIN hmsypx_student on hmsypx_student.id=hmsypx_student_grade.student_id where '.$where.' ORDER BY '.$oder.' limit '.$pages.','.$config['per_page'];
		
			$data['student'] = $this->global_model->my_query_sql($sql);
			
		}else{
			$data['order'] = 0;
			$data['student'] = $this->global_model->get_page('student',$where,'*',$orders,$config['per_page'], $page);
		}
		
		$data['message']    ='填加成功，正在关闭窗口……';
		$this->load->view('header');
		$this->load->view('student_list_new',$data);
		$this->load->view('bottom');
	}


	//课次不足3次的学员
	public function class_times($page=1)
	{
		$this->my_permission->check_per(',6_2,','查看学员');
		$admin_user_id = intval($this->session->userdata('admin_user_id'));
		$campus_id     = intval($this->session->userdata('campus_id'));
		// 分页类开始
		if ($page ==0){//清除搜索session
			$this->session->unset_userdata('student_query');
			$page = 1;
		}

		/*
		默认查询条件
		如果是只看本校数据
		*/
		if ($this->my_permission->permi(',6_20,'))
		{
			$where = "s.campus_id = $campus_id  and total_class_times-class_times<=6";//and s.adviser_id = $admin_user_id

		}
		else
		{
			$where = "total_class_times - class_times <=6";
		}

		//学生剩余课次小于等于6次，
		$field    = 's.id,s.name';
		$group_by = 's.id asc';
		$result   = $this->global_model->my_query('student_grade as sg',$where,$field,$group_by,'student as s','sg.student_id = s.id');


		$total_count = count($result);//返回记录总数


		$this->load->library('pagination');
		$config['base_url']         = site_url('student/class_times') ;//完整的 URL 路径通向包含你的分页控制器类/方法
		$config['prefix']           = ""; // 自定义的前缀添加到路径
		$config['suffix']           = '';// 自定义的前缀添加到路径
		$config['cur_page']         = $page;//当前页
		$config['total_rows']       = $total_count;//数据总行数
		$config['per_page']         = 15; //每页显示行数
		$config['num_tag_open']     = '<li>';//自定义“数字”链接
		$config['num_tag_close']    = '</li>';//自定义“数字”链接
		$config['cur_tag_open']     = '<li class="active"><a href="#" >';//自定义“当前页”链接
		$config['cur_tag_close']    = '</li></a>';//自定义“当前页”链接
		$config['anchor_class']     = "";//添加 CSS 类
		$config['num_links']        = 3;//放在你当前页码的前面和后面的“数字”链接的数量。
		$config['use_page_numbers'] = TRUE;
		$config['first_link']       = '首页';//你希望在分页的左边显示“第一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 。
		$config['first_tag_open']   = '<li>';//“第一页”链接的打开标签。
		$config['first_tag_close']  = '</li>';//“第一页”链接的关闭标签。
		$config['prev_link']        = '上一页';//你希望在分页中显示“上一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 。
		$config['prev_tag_open']    = '<li>';//“上一页”链接的打开标签。
		$config['prev_tag_close']   = '</li>';//“上一页”链接的关闭标签
		$config['last_link']        = '末页';
		$config['last_tag_open']    = '<li>';//“最后一页”链接的打开标签。
		$config['last_tag_close']   = '</li>';//“最后一页”链接的关闭标签。
		$config['next_link']        = '下一页';//你希望在分页中显示“下一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 
		$config['next_tag_open']    = '<li>';//“下一页”链接的打开标签。
		$config['next_tag_close']   = '</li>';//“下一页”链接的关闭标签。

		$this->pagination->initialize($config); 
		$data['pages']       = $this->pagination->create_links();
		$data['total_count'] = $total_count;
		$data['student']     = $this->global_model->get_page_join('student_grade as sg',$where,$field='s.*,sg.total_class_times,sg.class_times',$order_by='s.id desc',$join='student as s',$join_where='sg.student_id = s.id',$config['per_page'], $page);

		$this->load->view('header');
		$this->load->view('student_list',$data);
		$this->load->view('bottom');
	}

	/*
	今日到校学员
	$page 页码
	$day  日期
	*/
	public function student_today($day=0, $teacher_id = 0,$page=1)
	{
		$this->my_permission->check_per(',6_1,','今日到校管理');
		
		$admin_user_id = intval($this->session->userdata('admin_user_id'));
		// 分页类开始
		if ($page ==0){//清除搜索session
			//$this->session->unset_userdata('student_query');
			$page = 1;
		}

		if ($day)
		{
			$day = strtotime($day);
		}
		else
		{
			$day = strtotime(date("Y-m-d"));
		}

		$teacher_id    = intval($teacher_id);
		
		if ($teacher_id)
		{
		$wheres = ' and teacher_id = '.$teacher_id;
		}
		else
		{
		$wheres = '';
		}
		
		$campus_id = intval($this->session->userdata('campus_id'));//校区id
		//默认查询条件
		$where = 'campus_id = '.$campus_id.' and syllabus_date = '.$day;
        $where = $where . $wheres;
//echo $where;


		//------取校区上课时间 以id为下标存入数组 开始 -----
		$schooltime      = $this->global_model->get('system_schooltime',"campus_id = $campus_id",'id,syllabus_time',$order_by='campus_id asc,syllabus_week asc,id asc');
		$syllabus_times = array();
		foreach ($schooltime as $st) 
		{
			$syllabus_times[$st['id']] = $st['syllabus_time'];
		}

		
		$data['syllabus_times'] = $syllabus_times;
		unset($syllabus_times);
		//------取校区上课时间 并按星期归类 存入二级数组 结束 -----




		//当前页码存入session，操作返回用
		$this->session->set_userdata('student_today_page',$page);
		$total_count = $this->global_model->count_all('syllabus_student',$where);//返回记录总数
		$this->load->library('pagination');
		$config['base_url']         = site_url('student/student_today/'.date("Y-m-d",$day).'/'.$teacher_id.'/');//完整的 URL 路径通向包含你的分页控制器类/方法
		$config['prefix']           = ""; // 自定义的前缀添加到路径
		$config['suffix']           = '';// 自定义的前缀添加到路径
		$config['cur_page']         = $page;//当前页
		$config['total_rows']       = $total_count;//数据总行数
		$config['per_page']         = 15; //每页显示行数
		$config['num_tag_open']     = '<li>';//自定义“数字”链接
		$config['num_tag_close']    = '</li>';//自定义“数字”链接
		$config['cur_tag_open']     = '<li class="active"><a href="#" >';//自定义“当前页”链接
		$config['cur_tag_close']    = '</li></a>';//自定义“当前页”链接
		$config['anchor_class']     = "";//添加 CSS 类
		$config['num_links']        = 3;//放在你当前页码的前面和后面的“数字”链接的数量。
		$config['use_page_numbers'] = TRUE;
		$config['first_link']       = '首页';//你希望在分页的左边显示“第一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 。
		$config['first_tag_open']   = '<li>';//“第一页”链接的打开标签。
		$config['first_tag_close']  = '</li>';//“第一页”链接的关闭标签。
		$config['prev_link']        = '上一页';//你希望在分页中显示“上一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 。
		$config['prev_tag_open']    = '<li>';//“上一页”链接的打开标签。
		$config['prev_tag_close']   = '</li>';//“上一页”链接的关闭标签
		$config['last_link']        = '末页';
		$config['last_tag_open']    = '<li>';//“最后一页”链接的打开标签。
		$config['last_tag_close']   = '</li>';//“最后一页”链接的关闭标签。
		$config['next_link']        = '下一页';//你希望在分页中显示“下一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 
		$config['next_tag_open']    = '<li>';//“下一页”链接的打开标签。
		$config['next_tag_close']   = '</li>';//“下一页”链接的关闭标签。

		$this->pagination->initialize($config); 
		$data['pages'] = $this->pagination->create_links();
		$data['total_count'] = $total_count;
		$data['student'] = $this->global_model->get_page('syllabus_student',$where,'*','syllabus_time asc',$config['per_page'], $page);

		$data['day']      = date("Y-m-d",$day);//当前日期
		$data['prev_day'] = date("Y-m-d", strtotime("-1 day", $day));//前一天
		$data['next_day'] = date("Y-m-d", strtotime("+1 day", $day));//后一天
		//取系统在职教师,菜单用到开始
		if ($this->my_permission->permi(',5_2,'))//只看自己的
		{
			$permi_where = ' and id = '.$admin_user_id;
		}
		else
		{
			$permi_where = '';
		}
		$where                = "department_path like '%,6,%' and status > 0 and campus_id = $campus_id and is_shangke = 1 $permi_where";
		$data['teacher_list'] = $this->global_model->get('admin',$where,'id,full_name','id asc');

		//取系统在职教师,菜单用到结束
		
		
		$this->load->view('header');
		$this->load->view('student_list_today',$data);
		$this->load->view('bottom');
	}


	/*
	添加修改学员
	$id 学员id, 等于0时为添加
	$info_id 资源id,非0是表示从资源导入
	$visit_id 客户到店登记id
	*/
	public function student_set($id=0,$info_id=0, $visit_id=0)
	{
		$this->my_permission->check_per(',6_3,','添加学员');//权限
		if ($id){
			$this->my_permission->check_per(',6_4,','修改学员');//权限修改学员信息
		}

		$this->load->helper('form');
		$this->load->library('form_validation');
		$id        = intval($id);//学员id
		$info_id   = intval($info_id);// 资源id
		$visit_id  = intval($visit_id);
		$campus_id = intval($this->session->userdata('campus_id'));//校区id
		$this->form_validation->set_rules('name','姓名', 'required');
		$this->form_validation->set_error_delimiters('<div class="alert alert-error">', '</div>');

		$action_name         = ($id)? '修改' : '添加' ;
		$data['action_name'] = $action_name;

		if ($this->form_validation->run() === FALSE )
		{
			//如果没有提交，显示提交页
			$data['id']         = $id;
			$data['info_id']    = $info_id;
			$data['visit_id']   = $visit_id;
			$data['group']      = $this->system_model->get_system('system_group',array('status' => 1));//学习对象
			$data['source']     = $this->system_model->get_source(0,array('status' => 1));//资源来源
			$data['department'] = $this->system_model->get_department(0,array('status' => 1));//部门
			$data['region']     = $this->system_model->get_region(0,array('status' => 1));//地区
			$data['user']       = $this->global_model->get('admin',array('status > ' => 0),'','department_id asc,campus_id asc');//取系统在职用户，用于 收单人，邀约人
			$data['adviser']    = $this->global_model->get('admin',"status > 0 and ( department_path like '%,3,%' or department_path like '%,7,%' )",'id,full_name','full_name asc');//取系统在职顾问
			$data['teacher']    = $this->global_model->get('admin',array('status > ' => 0,'department_path like' =>'%,6,%'),'id,full_name','full_name asc');//取系统在职教师
			//填加
			if (empty($id))
			{
				if ($info_id)//取资源信息，导入学员
				{
					$data['student_item']=$this->global_model->get_one('info',$info_id);
				}
				else
				{
					if ($visit_id)
					{
						$data['student_item']=$this->global_model->get_one('visit',$visit_id);
					}
				}
				//取班级信息 只在添加时
				$data['grade'] = $this->global_model->get('grade','campus_id = '.$campus_id.' and grade_status = 0','*','grade_teacher_id desc,id desc');//只取状态为 正常的
			}
			//修改
			else
			{
				//取学员信息用于编辑
				$data['student_item'] = $this->global_model->get_one('student',$id);
			}
			$this->load->view('header');
			$this->load->view('student_set',$data);
			$this->load->view('bottom');
		}
		else
		{
			//提交页
			// 按id取下拉框选项的名称
			$group_id          = intval($this->input->post('group_id'));
			$region_id         = intval($this->input->post('region_id'));
			$department_id     = intval($this->input->post('department_id'));
			$source_id         = intval($this->input->post('source_id'));
			$gather_id         = intval($this->input->post('gather_id'));
			$invite_id         = intval($this->input->post('invite_id'));
			$sign_id           = intval($this->input->post('sign_id'));
			$koubei_teacher_id = intval($this->input->post('koubei_teacher_id'));
			$adviser_id        = intval($this->input->post('adviser_id'));//课程顾问
			$tiyan_teacher_id  = intval($this->input->post('tiyan_teacher_id'));//体验老师
			
			
			$group_name          = '';
			$region_name         = '';
			$department_name     = '';
			$source_name         = '';
			$gather_name         = '';
			$invite_name         = '';
			$sign_name           = '';
			$koubei_teacher_name = '';
			$adviser_name        = '';
			$tiyan_teacher_name  = '';

			//=============判断手机号有否重复开始=================
			$mobile = trim($this->input->post('mobile'));
			$pass = trim($this->input->post('pass'));
			
			//修改时
			if ($id)
			{
				$result = $this->global_model->count_all('student',"id != $id and mobile = '$mobile'");
				$num    = intval($result);
				if ($num)
				{
					$data['message'] = $action_name.'手机号重复，修改失败，正在返回……';
					$data['return_url'] = site_url('student/student_set/'.$id);
					$this->load->view('error', $data);
					exit;
				}
				
			}
			else
			{
			//添加时
				$result = $this->global_model->count_all('student',"mobile = '$mobile'");
				$num    = intval($result);
				
				if ($num)
				{
					$data['message'] = $action_name.'手机号重复，添加失败，正在返回……';
					$data['return_url'] = site_url('student/student_set');
					$this->load->view('error', $data);
					exit;
				}
				
				
			if (empty($pass))
				{
						$data['message'] = $action_name.'您的密码没有填写，添加失败，正在返回……';
						$data['return_url'] = site_url('student/student_set');
						$this->load->view('error', $data);
						exit;
				}
					
			}		
			
			//=============判断手机号有否重复开始=================



			if ($group_id)//学习对象，成人或儿童
			{
				$row        = $this->system_model->get_system_one('system_group',$group_id);
				$group_name = $row['name'];
			}

			if ($region_id)//地区
			{
				$row         = $this->system_model->get_region($region_id);
				$region_name = $row['name'];
			}

			if ($department_id) //部门
			{
				$row             =  $this->system_model->get_department($department_id);
				$department_name = $row['name'];
			}



			if ($source_id)
			{
				$row         = $this->system_model->get_system_one('system_source',$source_id);
				$source_name = $row['name'];
			}

			if ($gather_id)
			{
				$row = $this->global_model->get_one('admin',$gather_id,'full_name');
				$gather_name = $row['full_name'];
			}

			if ($invite_id)
			{
				$row = $this->global_model->get_one('admin',$invite_id,'full_name');
				$invite_name = $row['full_name'];
			}

			if ($sign_id)//签约人
			{
				$row = $this->global_model->get_one('admin',$sign_id,'full_name');
				$sign_name = $row['full_name'];
			}
			if ($koubei_teacher_id)//口碑老师
			{
				$row = $this->global_model->get_one('admin',$koubei_teacher_id,'full_name');
				$koubei_teacher_name = $row['full_name'];
			}
			if ($adviser_id)//课程顾问
			{
				$row = $this->global_model->get_one('admin',$adviser_id,'full_name');
				$adviser_name = $row['full_name'];
			}
			if($tiyan_teacher_id)
			{
				$row = $this->global_model->get_one('admin',$tiyan_teacher_id,'full_name');
				$tiyan_teacher_name = $row['full_name'];
			}

			$data =  array(
				'c_no'                => trim($this->input->post('c_no')),
				'name'                => trim($this->input->post('name')),
				'birthday'            => strtotime($this->input->post('birthday')),
				'gender'              => trim($this->input->post('gender')),
				'tel'                 => trim($this->input->post('tel')),
				'mobile'              => $mobile,
				'email'               => trim($this->input->post('email')),
				'address'             => trim($this->input->post('address')),
				'remark'              => trim($this->input->post('remark')),
				'group_id'            => $group_id,
				'group_name'          => $group_name,
				'region_id'           => $region_id,
				'region_name'         => $region_name,
				'department_id'       => $department_id,
				'department_name'     => $department_name,
				'source_id'           => $source_id,
				'source_name'         => $source_name,
				'gather_id'           => $gather_id,
				'gather_name'         => $gather_name,
				'invite_id'           => $invite_id,
				'invite_name'         => $invite_name,
				'sign_id'             => $sign_id,
				'sign_name'           => $sign_name,
				'koubei_student_id'   => 0,
				'koubei_student_name' => trim($this->input->post('koubei_student_name')),
				'koubei_teacher_id'   => $koubei_teacher_id,
				'koubei_teacher_name' => $koubei_teacher_name,
				'adviser_id'          => $adviser_id,
				'adviser_name'        => $adviser_name,
				'tiyan_teacher_id'    => $tiyan_teacher_id,
				'tiyan_teacher_name'  => $tiyan_teacher_name,
				'entry_year'          => date("Y",strtotime($this->input->post('entry_date'))),
				'entry_month'         => date("n",strtotime($this->input->post('entry_date'))),
				'entry_date'          => strtotime($this->input->post('entry_date')),
				'student_status'      => intval($this->input->post('student_status')),
				'add_user_id'         => intval($this->session->userdata('admin_user_id')),
				'add_user_name'       => $this->session->userdata('admin_user_name'),
			);
			
			if ($id==0)
			{
				if ($campus_id) //校区名称
				{
					$row         =  $this->system_model->get_system_one('system_campus',$campus_id);
					$campus_name = $row['name'];
				}
				$data['info_id']      = intval($this->input->post('info_id'));
				$data['campus_id']    = $campus_id;
				$data['campus_name']  = $campus_name;
				$data['add_time']     = time();
				
				$data['total_fees']   = 0;//总消费，累加
				$data['shishou_fees'] = 0;
				$data['youhui_fees']  = 0;
				$data['qian_fees']    = 0;
				//如果存在欠费，更新学员状态为欠费
				if (intval($data['qian_fees']))
				{
					$data['student_status'] = 4;
				}
			}
			
			if (!empty($pass)){
				
					$data['pass'] = md5($pass);	
					
			}
		
			//$update  = array('pass' => md5($pass));
			//$results  = $this->global_model->update ('student',"id <> 1",$update);//修改所有密码

			//print_r ($results);
			//die;
			
			
			$result = $this->global_model->set('student',$id,$data);//如果是新插入将返回插入的id
			if ($result)
			{
				if ($id==0)
				{
					$return_url = site_url('student');

				}
				else
				{
					$return_url = site_url('student/index/'.intval($this->session->userdata('student_page')));
				}
				$data['message']    = $action_name.'成功，正在进入列表页……';
				$data['return_url'] = $return_url;
				$this->load->view('success', $data);
			}
			else
			{
				$data['message'] = $return_str.$action_name.'失败，正在返回……';
				$data['return_url'] = site_url('info/set_info');
				$this->load->view('error', $data);
			}
		}
	}

    //删除学员
    public function del_stu($id=0)
    {
        $id = intval($id);
        if ($id)
        {
            $this->my_permission->check_per(',6_30,','删除学员');//权限
            $result = $this->student_model->del_stu($id);

            if ($result)
            {
                //删除电防记录
                //$this->global_model->del('info_call',0,"info_id = $id");//已屏蔽，不允许删除
                $data['message']    = '删除成功，正在返回……';
                $data['return_url'] = site_url('close_window');
                $this->load->view('success', $data);
            }
            else
            {
                $data['message'] = '删除失败，正在返回……';
                $data['return_url'] = site_url('close_window');
                $this->load->view('error', $data);
            }
        }
        else
        {
            $data['message'] = '参数错误，正在返回……';
            $data['return_url'] = site_url('close_window');
            $this->load->view('error', $data);
        }
    }

	/*首页快速搜索*/
	public function student_sokey()
	{
		$where = "";
		//姓名
		$key = trim($this->input->get('key'));
		if (!empty($key))
		{
			$where .= " and (name like '%$key%' or tel like '%$key%' or mobile like '%$key%')";
		}
		
		

		//查询条件存入session
		$this->session->set_userdata('student_query',$where);
		$data['message']    = '正在查询，请稍侯……';
		$data['return_url'] = site_url('close_window/index/student');
		$this->load->view('success', $data);

	}
	
	public function student_so_new(){
		$this->load->helper('form');
		$this->load->library('form_validation');
		$this->form_validation->set_rules('so','安全验证', 'required');
		
		$data = array();
		if ($this->form_validation->run() === FALSE ){
			//如果没有提交，显示提交页
			$data['source']     = $this->system_model->get_system('system_source',array('status' => 1));//资源来源
			$data['campus']     = $this->system_model->get_system('system_campus',array('status' => 1));//校区
			$data['department'] = $this->system_model->get_department(0,array('status' => 1));//部门
			$data['group']      = $this->system_model->get_system('system_group',array('status' => 1));//学习对象
			$data['user']       = $this->global_model->get('admin',array('status > ' => 0),'id,full_name','full_name asc');//取系统在职用户，用于 收单人，邀约人
			$data['adviser']    = $this->global_model->get('admin',array('status > ' => 0,'department_path like' =>'%,3,%'),'id,full_name','full_name asc');//取系统在职用户，课程顾门
			$data['teacher']    = $this->global_model->get('admin',array('status > ' => 0,'department_path like' =>'%,6,%'),'id,full_name','full_name asc');//取系统在职教师

			$this->load->view('header');
			$this->load->view('student_so_new',$data);
			$this->load->view('bottom');
		}else{
			$where = "";
			$name = trim($this->input->post('name'));
			if (!empty($name)){
				$where .= " and name like '%$name%'";
			}
			$tel = trim($this->input->post('tel'));
			if (!empty($tel)){
				$where .= " and ( tel like '%$tel%' or mobile like '%$tel%' )";
			}
			
			//查询条件存入session
			$this->session->set_userdata('student_query',$where);
			$data['message']    = '正在查询，请稍侯……';
			$data['return_url'] = site_url('close_window/index/student/studentlist');
			$this->load->view('success', $data);
		}
	}
	
	//搜索页
	public function student_so()
	{
		$this->load->helper('form');
		$this->load->library('form_validation');
		$this->form_validation->set_rules('so','安全验证', 'required');
		
		$data = array();
		if ($this->form_validation->run() === FALSE )
		{
			//如果没有提交，显示提交页
			$data['source']     = $this->system_model->get_system('system_source',array('status' => 1));//资源来源
			$data['campus']     = $this->system_model->get_system('system_campus',array('status' => 1));//校区
			$data['department'] = $this->system_model->get_department(0,array('status' => 1));//部门
			$data['group']      = $this->system_model->get_system('system_group',array('status' => 1));//学习对象
			$data['user']       = $this->global_model->get('admin',array('status > ' => 0),'id,full_name','full_name asc');//取系统在职用户，用于 收单人，邀约人
			$data['adviser']    = $this->global_model->get('admin',array('status > ' => 0,'department_path like' =>'%,3,%'),'id,full_name','full_name asc');//取系统在职用户，课程顾门
			$data['teacher']    = $this->global_model->get('admin',array('status > ' => 0,'department_path like' =>'%,6,%'),'id,full_name','full_name asc');//取系统在职教师

			$this->load->view('header');
			$this->load->view('student_so',$data);
			$this->load->view('bottom');
		}
		else
		{
			//提交页,处理查询条件
			$where = "";

			//日期开始 ,开始和结束日期全选时按时间段查询，只选一项查所选日期当天的
			$start_date = trim($this->input->post('start_date'));
			$end_date   = trim($this->input->post('end_date'));
			if (!empty($start_date) && !empty($end_date) ) 
			{
				$where .= " and entry_date >= ".strtotime($start_date)." and entry_date <= ".strtotime($end_date);
			}
			else
			{
				if (!empty($start_date))
				{
					$where .=" and entry_date = ".strtotime($start_date);
				}
				if (!empty($end_date))
				{
					$where .=" and entry_date = ".strtotime($end_date);
				}
			}
			//日期结束
			
			//校区
			$campus_id = intval($this->input->post('campus_id'));
			if ($campus_id)
			{
				$where .=" and campus_id = $campus_id";
			}


			
			$group_id = intval($this->input->post('group_id'));
			if ($group_id)
			{
				$where .=" and group_id = ".$group_id;
			}


			//学员状态
			$student_status = intval($this->input->post('student_status'));
			if ($student_status<999)
			{
				$where .=" and student_status = ".$student_status;
			}

			//收单人
			$gather_id = intval($this->input->post('gather_id'));
			if ($gather_id)
			{
				$where .=" and gather_id = ".$gather_id;
			}

			//邀约人
			$invite_id = intval($this->input->post('invite_id'));
			if ($invite_id)
			{
				$where .=" and invite_id = ".$invite_id;
			}

			//课程顾问
			$adviser_id = intval($this->input->post('adviser_id'));
			if ($adviser_id)
			{
				$where .=" and adviser_id = ".$adviser_id;
			}

			//任课老师
			$teacher_id = intval($this->input->post('teacher_id'));
			if ($teacher_id)
			{
				$where .=" and teacher_id = ".$teacher_id;
			}
			
			//编号
			$id = trim($this->input->post('id'));
			if (!empty($id))
			{
				$where .= " and id = ".$id;
			}

			//姓名
			$name = trim($this->input->post('name'));
			if (!empty($name))
			{
				$where .= " and name like '%$name%'";
			}
			
			//电话
			$tel = trim($this->input->post('tel'));
			if (!empty($tel))
			{
				$where .= " and ( tel like '%$tel%' or mobile like '%$tel%' )";
			}

			//查询条件存入session
			$this->session->set_userdata('student_query',$where);
			$data['message']    = '正在查询，请稍侯……';
			$data['return_url'] = site_url('close_window/index/student');
			$this->load->view('success', $data);
		}
	}


	/*
	学员框架页
	@ $id 学员id
	@ $return_url  返回按扭的地址，今日到校学员里传来
	*/

	public function iframe($id,$return_url="index")
	{
		$data = array();
		$id = intval($id);
		if ($id)
		{
			$data['return_url'] = $return_url;
			$data['info_item']  = $this->global_model->get_one('student',$id);//
		}
		$this->load->view('header');
		$this->load->view('student',$data);
		$this->load->view('bottom');
	}





	/*
	学员详细信息
	@ $id 学员id
	@ $return_url  返回按扭的地址，今日到校学员里传来
	*/

	public function disp($id,$return_url="index")
	{
		$data = array();
		$id = intval($id);
		if ($id)
		{
			$data['return_url'] = $return_url;
			$data['info_item']  = $this->global_model->get_one('student',$id);//资源详细
		}
		$this->load->view('header');
		$this->load->view('student_disp',$data);
		$this->load->view('bottom');
	}




	//ajax请求计算价格
	public function price()
	{
		$this->output->enable_profiler(FALSE);
		$grade             = (isset($_REQUEST['grade'])) ? $_REQUEST['grade'] : 0 ;//班级id
		$num               = 0;
		$total_class_times = 0;//总课时
		//$grade = array('2','3');
		$total_fees = 0.00;
		if (!empty($grade))
		{
			$grade_id = intval($grade);
			$row      = $this->global_model->get_one('grade',$grade_id,'lesson_times,tuition_fees');
			if (!empty($row))
			{
				$num               = 1;
				$total_fees        = $row['tuition_fees'];
				$total_class_times = $row['lesson_times'];
			}

		}

		$result = array('total_fees' => number_format($total_fees,2,'.',''),'num' => $num , 'total_class_times' => $total_class_times);
		echo json_encode($result);
		
	}


	//学员所报班级 iframe
	public function student_grade($student_id=0)
	{
		$data['student_id'] = intval($student_id);
		$this->load->view('header');
		$this->load->view('student_grade',$data);
		$this->load->view('bottom');
	}

	//已报班级
	public function student_grade_list($student_id=0)
	{
		$this->load->model('student_model');
		$student_id = intval($student_id);
		$data       = array();
		if ($student_id){
			$data['student_id'] = $student_id;
			$data['grade']      = $this->student_model->get_student_grade($student_id);
		}
		$this->load->view('header');
		$this->load->view('student_grade_list',$data);
		$this->load->view('bottom');
	}

	//上课记录
	public function student_grade_log($student_id=0,$sg_id=0,$page=1)
	{
		$data               = array();
		$student_id         = intval($student_id);//学员id
		$data['student_id'] = $student_id;

		if ($student_id)
		{
			$where = 'student_id = '.$student_id;
			if ($sg_id)
			{
				$where .= ' and sg_id = '.$sg_id;
			}

			$total_count = $this->global_model->count_all('student_grade_log',$where);//返回记录总数
			$this->load->library('pagination');
			$config['base_url']         = site_url('student/student_grade_log') ;//完整的 URL 路径通向包含你的分页控制器类/方法
			$config['prefix']           = "/$student_id/$sg_id/"; // 自定义的前缀添加到路径
			$config['suffix']           = '';// 自定义的前缀添加到路径
			$config['cur_page']         = $page;//当前页
			$config['total_rows']       = $total_count;//数据总行数
			$config['per_page']         = 10; //每页显示行数
			$config['num_tag_open']     = '<li>';//自定义“数字”链接
			$config['num_tag_close']    = '</li>';//自定义“数字”链接
			$config['cur_tag_open']     = '<li class="active"><a href="#" >';//自定义“当前页”链接
			$config['cur_tag_close']    = '</li></a>';//自定义“当前页”链接
			$config['anchor_class']     = "";//添加 CSS 类
			$config['num_links']        = 3;//放在你当前页码的前面和后面的“数字”链接的数量。
			$config['use_page_numbers'] = TRUE;
			$config['first_link']       = '首页';//你希望在分页的左边显示“第一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 。
			$config['first_tag_open']   = '<li>';//“第一页”链接的打开标签。
			$config['first_tag_close']  = '</li>';//“第一页”链接的关闭标签。
			$config['prev_link']        = '上一页';//你希望在分页中显示“上一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 。
			$config['prev_tag_open']    = '<li>';//“上一页”链接的打开标签。
			$config['prev_tag_close']   = '</li>';//“上一页”链接的关闭标签
			$config['last_link']        = '末页';
			$config['last_tag_open']    = '<li>';//“最后一页”链接的打开标签。
			$config['last_tag_close']   = '</li>';//“最后一页”链接的关闭标签。
			$config['next_link']        = '下一页';//你希望在分页中显示“下一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 
			$config['next_tag_open']    = '<li>';//“下一页”链接的打开标签。
			$config['next_tag_close']   = '</li>';//“下一页”链接的关闭标签。

			$this->pagination->initialize($config); 
			$data['pages']       = $this->pagination->create_links();
			$data['total_count'] = $total_count;
			$data['log']         = $this->global_model->get_page('student_grade_log',$where,'*','id desc',$config['per_page'], $page);
			
		}
		$this->load->view('header');
		$this->load->view('student_grade_log',$data);
		$this->load->view('bottom');
	}

	/*
	上课登记 正课
	$student_id 学生id 
	$sg_id 学生所报班级记录表 id (表 hmsypx_student_grade 的 id)
	$return_url 成功后调用的关闭窗口 页 默认为 index
	*/
	public function add_grade_log($student_id=0,$sg_id=0,$return_url='index')
	{
		$this->my_permission->check_per(',6_6,','上课登记');//权限
		$this->load->model('student_model');
		$this->load->helper('form');
		$this->load->library('form_validation');
		$student_id = intval($student_id);
		$sg_id      = intval($sg_id);
		$this->form_validation->set_rules('add_time','日期不能为空', 'required');
		$this->form_validation->set_rules('sg_id','id不能为空', 'required');

		$this->form_validation->set_error_delimiters('<div class="alert alert-error">', '</div>');
		$data = array();
		if ($this->form_validation->run() === FALSE )
		{	
			if ($student_id && $sg_id)
			{
				$data['grade'] = $this->student_model->get_student_grade_one($sg_id);
				$data['return_url'] = $return_url;
				$this->load->view('header');
				$this->load->view('student_grade_log_add',$data);
				$this->load->view('bottom');
			}
		}
		else
		{
			$grade_id      = intval($this->input->post('grade_id'));
			$sg_id         = intval($this->input->post('sg_id'));
			$syllabus_date = strtotime(date("Y-m-d"));
			$updata        = array(
			'sign_in'      => 1,
			'sign_in_time' => $this->input->post('add_time'),
			);

			$result = $this->global_model->update ('syllabus_student',"student_id = $student_id and grade_id = $grade_id and sg_id = $sg_id and syllabus_date = $syllabus_date and sign_in = 0",$updata);//更新已签到
			
			if ($result)//update 返回的永远都是1，没有记录也是1，需要时再修改
			{
				//填加上课登记记录
				
				$data = array(
					'student_id'    => $student_id,
					'grade_id'      => $grade_id,
					'sg_id'         => $sg_id,
					'grade_name'    => $this->input->post('grade_name'),
					'add_time'      => $this->input->post('add_time'),
					'add_time_day'  => strtotime(date("Y-m-d",$this->input->post('add_time'))),
					'add_user_id'   => intval($this->session->userdata('admin_user_id')),
					'add_user_name' => $this->session->userdata('admin_user_name')
				);
				$result = $this->global_model->set('student_grade_log',0,$data);//如果是新插入将返回插入的id
				if ($result)
				{
					//上课次数加1 
					$this->global_model->set_num($table='student_grade',"id = $sg_id",'class_times',1);

					$data['message']    = '登记成功，正在返回……';
					$data['return_url'] = site_url('close_window/'.$return_url);
					$this->load->view('success', $data);
				}

			}
			else
			{
				//当天没有排课记录
				$data['message']    = '失败，当天没有排课记录。正在返回……';
				$data['return_url'] = site_url('close_window/'.$return_url);
				$this->load->view('error', $data);
			}

			
			
			
		}

	}



	/*
	上课登记 体验课
	$info_id 资源id 
	$grade_id 班级id
	$call_id 外呼资源
	*/
	public function add_tiyan_log($grade_id=0 ,$info_id=0, $call_id=0)
	{
		$this->load->model('student_model');
		$this->load->helper('form');
		$this->load->library('form_validation');
		$info_id  = intval($info_id);
		$grade_id = intval($grade_id);
		$this->form_validation->set_rules('add_time','日期不能为空', 'required');

		$this->form_validation->set_error_delimiters('<div class="alert alert-error">', '</div>');
		$data = array();
		if ($this->form_validation->run() === FALSE )
		{	
			//资源
			if ($info_id && $grade_id)
			{
				$data['syllabus_student'] = $this->global_model->get('syllabus_student',"info_id = $info_id and grade_id = $grade_id",'*', "syllabus_id asc",1);//课程安排信息
			}

			//外呼
			if ($call_id && $grade_id)
			{
				$data['syllabus_student'] = $this->global_model->get('syllabus_student',"call_id = $call_id and grade_id = $grade_id",'*', "syllabus_id asc",1);//课程安排信息
			}
			$this->load->view('header');
			$this->load->view('student_tiyan_log_add',$data);
			$this->load->view('bottom');
		}
		else
		{

			$updata = array(
				'sign_in'      => 1,
				'sign_in_time' => $this->input->post('add_time'),
			);


			//客服资源
			if ($info_id)
			{
				$result = $this->global_model->update ('syllabus_student',"info_id = $info_id and grade_id = $grade_id",$updata);//更新已签到
				if ($result)
				{
					$this->global_model->update('info',"id = $info_id",array('info_status' => 50,'info_visit'=>20,'info_visit_time'=>strtotime(date('Y-m-d',time())),'is_goshop'=>'2'));//更新资源为已体验
					//资源 操作记录（电访记录）数据
					$info_data = array(
						'info_id'       => $info_id,
						'call_year'     => date('Y',$this->input->post('add_time')),
						'call_month'    => date('n',$this->input->post('add_time')),
						'call_date'     => $this->input->post('add_time'),
						'call_remark'   => "已体验，签到时间：".date("Y-m-d H:i:s"),
						'validity'      => 1,
						'info_status'   => 50,
						'add_user_id'   => intval($this->session->userdata('admin_user_id')),
						'add_user_name' => $this->session->userdata('admin_user_name'),
						'add_time'      => $this->input->post('add_time')
					);
					$this->global_model->set('info_call',0,$info_data);

					//添加到到店
					$is_before = 0;//是否往期
                    $visit_times = 1;//来访次数
                    $twice_visit_time = 0;
                    $zy = $this->db->query('select * from hmsypx_info where id = '.$info_id)->row_array();
                    $tel = trim($zy['mobile']);
                    $results = $this->global_model->get('visit',"tel = '$tel'",'visit_times,first_visit_time,twice_visit_time,id', $order_by="id desc",1);
					$grade_info = $this->db->select('*')->where('id = '.$grade_id)->get('grade')->row_array();
                    if (!empty($results)){
                        $id = $result['id'];
                        $is_before = 1;
                        $visit_times = $result['visit_times']+1;//来访次数
                        $twice_visit_time = $result['first_visit_time'];//初次来访日期
                    }else{
                        $id = '';
                    }
                    $tiyan_lesson_id = $grade_info['lesson_id'];
                    $tiyan_lesson_name = '';
                    if (!empty($grade_info['lesson_id']))
					{
						$row  = $this->global_model->get('system_lesson',"id = $tiyan_lesson_id",'name', $order_by="id asc",1);
						$tiyan_lesson_name = $row['name'];
					}
                	$vist['is_before']              =   $is_before;
                    $vist['visit_times']            =   $visit_times;
                    $vist['twice_visit_time']       =   $twice_visit_time;
                    $vist['first_visit_time']       =   time();
                    $vist['is_tiyan']               =   '1';
                    $vist['tiyan_lesson_id']        =   $tiyan_lesson_id;
                    $vist['tiyan_lesson_name']      =   $tiyan_lesson_name;
                    $vist['reception_teacher_id']   =   $grade_info['grade_teacher_id'];
                    $vist['reception_teacher_name'] =   $grade_info['grade_teacher_name'];
                    if($id<1){
	                    $vist['visit_name']             =   $zy['name'];
	                    $vist['gender']                 =   $zy['gender'];
	                    $vist['group_id']               =   $zy['group_id'];
	                    $vist['group_name']             =   $zy['group_name'];
	                    $vist['age']                    =   intval($zy['age']);
	                    $vist['tel']                    =   trim($zy['mobile']);
	                    $vist['address']                =   trim($zy['address']);
	                    $vist['source_id']              =   $zy['source_id'];
	                    $vist['source_name']            =   $zy['source_name'];
	                    $vist['lesson_id']              =   $zy['lesson_id'];
	                    $vist['lesson_name']            =   $zy['lesson_name'];
	                    $vist['campus_id']              =   $zy['campus_id'];
	                    $vist['campus_name']            =   $zy['campus_name'];
	                    $vist['invite_id']              =   $zy['invite_id'];
	                    $vist['invite_name']            =   $zy['invite_name'];
	                    $vist['add_time']               =   time();
	                    $vist['add_user_id']            =   intval($this->session->userdata('admin_user_id'));
	                    $vist['add_user_name']          =   $this->session->userdata('admin_user_name');
                    }
                    //==================检查手机号是否重复结束========================
                    $this->global_model->set('visit',$id,$vist);//如果是新插入将返回插入的id
				}
			}

			//外呼资源
			if ($call_id)
			{
				$result = $this->global_model->update ('syllabus_student',"call_id = $call_id and grade_id = $grade_id",$updata);//更新已签到
				$this->global_model->update('callcenter_info',"id = $call_id",array('visit_status' => 20));//更新资源为已体验
				//外呼资源 操作记录（电访记录）数据
				$info_data = array(
					'info_id'       => $call_id,
					'call_year'     => date('Y',$this->input->post('add_time')),
					'call_month'    => date('n',$this->input->post('add_time')),
					'call_date'     => $this->input->post('add_time'),
					'call_remark'   => "已体验，签到时间：".date("Y-m-d H:i:s"),
					'validity'      => 1,
					'info_status'   => 50,
					'add_user_id'   => intval($this->session->userdata('admin_user_id')),
					'add_user_name' => $this->session->userdata('admin_user_name'),
					'add_time'      => $this->input->post('add_time')
				);
				$this->global_model->set('callcenter_info_call',0,$info_data);
			}


			
			if ($result)
			{
				$data['return_url'] = site_url('close_window/index/student/student_today');
				$this->load->view('success', $data);
			}
			
		}

	}




	/*
	删除上课登记
	$sg_id 学生所报班级表里的 id 
	$log_id 上课记录表 id (表 hmsypx_student_grade_log 的 id)
	*/
	public function del_grade_log($sg_id=0,$log_id=0)
	{
		$this->my_permission->check_per(',6_9,','撤销上课登记');//权限
		$this->load->model('student_model');
		$this->load->helper('form');
		$this->load->library('form_validation');
		$sg_id  = intval($sg_id);
		$log_id = intval($log_id);
		$this->form_validation->set_rules('sg_id','参数错误', 'required');
		$this->form_validation->set_rules('log_id','参数错误', 'required');
		$this->form_validation->set_error_delimiters('<div class="alert alert-error">', '</div>');
		$data = array();
		if ($this->form_validation->run() === FALSE )
		{	
			if ($sg_id && $log_id)
			{
				$data['sg_id']  = $sg_id;
				$data['log_id'] = $log_id;
				$this->load->view('header');
				$this->load->view('student_grade_log_del',$data);
				$this->load->view('bottom');
			}
		}
		else
		{
			$this->my_permission->check_per(',6_9,','撤销上课登记');//权限
			$sg_id  = $this->input->post('sg_id');
			$log_id = $this->input->post('log_id');
			//班级id
			$result = $this->student_model->del_log($sg_id,$log_id);
			if ($result)
			{
				$this->global_model->set_num($table='student_grade',"id = $sg_id",'class_times',-1);//上课次数减1
				//====排课表，已签到改为未签到 开始 ==========
				$update = array('sign_in' => 0 );
				$this->global_model->update('syllabus_student','',$update);
				//====排课表，已签到改为未签到 开始 ==========

				$data['message'] = '成功，正在返回……';
				$data['return_url'] = site_url('close_window/student');
				$this->load->view('success', $data);
			}
			
		}

	}


	//导入资源（从资源导入到学员，成为正式学员）
	public function load ()
	{
		$where = 'campus_id = '.intval($this->session->userdata('campus_id')).' and (info_status = 70 or info_status = 80)';//临时加80
		$data['info'] = $this->global_model->get('info',$where,$field='*', $order_by="id desc");

		$this->load->view('header');
		$this->load->view('student_load',$data);
		$this->load->view('bottom');
	}



	/*
	学员添加班级第一步 班级搜索
	*/
	public function add_grade_so($student_id = 0)
	{
		$this->my_permission->check_per(',6_5,','学员班级管理');//权限

		$this->load->helper('form');
		$this->load->library('form_validation');
		$student_id         = intval($student_id);
		$campus_id          = intval($this->session->userdata('campus_id'));
		$data['campus_id']  = $campus_id;
		$data['student_id'] = $student_id;
		$this->form_validation->set_rules('campus_id','不能为空','required');
		$this->form_validation->set_error_delimiters('div class="alert alert_error"' , '</div>');
		if ($this->form_validation->run() === FALSE )
		{
			//没提交时
			$data['teacher']   = $this->global_model->get('admin',array('status > ' => 0,'campus_id' =>$campus_id ,'department_path like' =>'%,6,%'),'id,full_name','full_name asc');//取系统在职教师
			$this->load->view('header');
			$this->load->view('student_add_grade_so',$data);
			$this->load->view('bottom');
		}
		else
		{
			$where = "campus_id = $campus_id";
			if ($this->input->post('grade_teacher_id'))//教师
			{
				$where .= ' and grade_teacher_id = '.intval($this->input->post('grade_teacher_id'));
			}
			if ($this->input->post('grade_week'))
			{
				$where.= ' and grade_week ='.intval($this->input->post('grade_week'));
			}
			if ($this->input->post('grade_time'))
			{
				$where .=' and grade_time ='.intval($this->input->post('grade_time'));
			}
			if ($this->input->post('grade_status') !== '999')
			{
				$where .=' and grade_status = '.intval($this->input->post('grade_status'));
			}
			if ($this->input->post('grade_name'))
			{
				$grade_name = trim($this->input->post('grade_name'));
				$where      .= " and grade_name like '%$grade_name%'";
			}

			$this->session->set_userdata('add_grade_session',$where);//查询条件存入session

			redirect("student/add_grade/$student_id");
		}
	}

	/*
	学员添加班级
	$student_id  学员id
	
	*/
	public function add_grade($student_id)
	{
		$this->load->helper('form');
		$this->load->library('form_validation');
		$student_id = intval($student_id);//学员id
		$campus_id  = intval($this->session->userdata('campus_id'));//校区id
		$this->form_validation->set_rules('shishou_fees','实收金额', 'required');
		$this->form_validation->set_error_delimiters('<div class="alert alert-error">', '</div>');

		if ($this->form_validation->run() === FALSE )
		{
			//如果没有提交，显示提交页
			$data['adviser']    = $this->global_model->get('admin',"status > 0 and campus_id = $campus_id ",'id,full_name','full_name asc');//取系统在职本校区所有人员
			$data['teacher']   = $this->global_model->get('admin',array('status > ' => 0,'campus_id' =>$campus_id ,'department_path like' =>'%,6,%'),'id,full_name','full_name asc');//取系统在职教师
			$data['source']     = $this->system_model->get_source(0,array('status' => 1));//资源来源
			//填加
			if ($student_id)
			{
				//学员信息
				$data['student_item'] = $this->global_model->get_one('student',$student_id,"id,name");
				//取班级信息 只在添加时
				if ( $this->session->userdata('add_grade_session'))
				{
					$where = $this->session->userdata('add_grade_session');
				}
				else
				{
					$where = "campus_id = $campus_id and grade_status = 0";
				}
				$data['grade'] = $this->global_model->get('grade',$where,'*','grade_teacher_id asc,grade_name desc');//只取状态为 正常的
			}
			$data['student_id'] = $student_id;
			$this->load->view('header');
			$this->load->view('student_add_grade',$data);
			$this->load->view('bottom');
		}
		else
		{
			//提交页
			$total_fees           = $this->input->post('total_fees');
			$shishou_fees         = $this->input->post('shishou_fees');
			$youhui_fees          = $this->input->post('youhui_fees');
			$qian_fees            = $total_fees-$shishou_fees-$youhui_fees;

			//取学员信息
			$row                  = $this->global_model->get_one('student',$student_id,"info_id,name,total_fees,shishou_fees,youhui_fees,qian_fees,adviser_id,adviser_name");
			$data                 =  array();
			$data['total_fees']   = $total_fees+$row['total_fees'];//总消费，累加
			$data['shishou_fees'] = $shishou_fees+$row['shishou_fees'];
			$data['youhui_fees']  = $youhui_fees+$row['youhui_fees'];
			$data['qian_fees']    = $qian_fees+$row['qian_fees'];
			$student_name         = $row['name'];
			$adviser_id           = $row['adviser_id'];
			$adviser_name         = $row['adviser_name'];
			$info_id              = $row['info_id'];
			//如果存在欠费，更新学员状态为欠费
			if (intval($data['qian_fees']))
			{
				$data['student_status'] = 4;
			}
			unset($row);
		
			// $result = $this->global_model->set('student',$student_id,$data);//更新学员信息
			$result = 1;
			if ($result)
			{
				
				//填加课程
				$sgid_array = array();//记录学员所报班级id
				$sg_id      = 0;//记录学员所报班级id
				$grade      = array($this->input->post('grade'));
				if (!empty($grade))
				{
					foreach ($grade as $grade_id) 
					{
						//取班级总课次
						$total_class_times = intval($this->input->post('total_class_times'));
						//取班级信息
						$grade_item = $this->global_model->get_one('grade',$grade_id,'lesson_id,class_id,grade_teacher_id,grade_teacher_name');

						if (!empty($grade_item['lesson_id']))
						{
							$lesson_id = $grade_item['lesson_id'];
							$ln = $this->global_model->get_one('system_lesson',$lesson_id,'name');
							$lesson_name = $ln['name'];
						}
						else
						{
							$lesson_id = 0;
							$lesson_name = '';
						}

						if (!empty($grade_item['class_id']))
						{
							$class_id = $grade_item['class_id'];
							$cn = $this->global_model->get_one('system_class',$class_id,'name');
							$class_name = $cn['name'];
						}
						else
						{
							$class_id = 0;
							$class_name = '';
						}

						$tiyan_teacher_id = $this->input->post('tiyan_teacher_id');
						if ($tiyan_teacher_id)
						{
							$tn = $this->global_model->get_one('admin',$tiyan_teacher_id,'full_name');
							$tiyan_teacher_name = $tn['full_name'];
						}
						else
						{
							$tiyan_teacher_name = '';
						}
						
						$teacher_id         = (!empty($grade_item['grade_teacher_id']))   ? $grade_item['grade_teacher_id'] : 0 ;
						$teacher_name       = (!empty($grade_item['grade_teacher_name'])) ? $grade_item['grade_teacher_name'] : '' ;

						$g_data = array(
							'student_id'         => $student_id,
							'grade_id'           => $grade_id,
							
							'lesson_id'          => $lesson_id,
							'lesson_name'        => $lesson_name,
							'class_id'           => $class_id,
							'class_name'         => $class_name,
							'tiyan_teacher_id'   => $tiyan_teacher_id,
							'tiyan_teacher_name' => $tiyan_teacher_name,
							'teacher_id'         => $teacher_id,
							'teacher_name'       => $teacher_name,

							'total_class_times'  => $total_class_times,
							'add_user_id'        => intval($this->session->userdata('admin_user_id')),
							'add_user_name'      => $this->session->userdata('admin_user_name'),
							'add_time'           => time(),
						);
						$sg_id = $sgid_array[$grade_id] = $this->global_model->set('student_grade',0,$g_data);
						//班级现有人数加1
						$this->global_model->set_num('grade',"id = $grade_id",'now_number',1);
					}

				}
				//更新学员任课老师
				$row = $this->global_model->get_one('grade',$grade_id,'grade_teacher_id,grade_teacher_name');//取班级任课老师
				$updata = array(
					'teacher_id'   => $row['grade_teacher_id'],
					'teacher_name' => $row['grade_teacher_name'],
				);
				$this->global_model->set('student',$student_id,$updata);

				//填加交费记录
				$responsible_id = $this->input->post('responsible_id');//经办人
				$responsible_name = '';
				if ($responsible_id)
				{
					$row = $this->global_model->get_one('admin',$responsible_id,'full_name');
					$responsible_name = $row['full_name'];
				}

				$add_time = trim($this->input->post('add_time'));
				if (empty($add_time))
				{
					$add_time = date("Y-m-d H:i:s");
				}

				/*交费渠道*/
				$source_id   = intval($this->input->post('source_id'));
				$source_name = '';
				if ($source_id)
				{
					$row = $this->global_model->get_one('system_source',$source_id,'name');
					$source_name = $row['name'];
				}




				$fees = array(
					'student_id'       => $student_id,
					'sg_id'            => $sg_id,
					'campus_id'        => $campus_id,
					'yingshou_fees'    => $total_fees,
					'youhui_fees'      => $youhui_fees,
					'shishou_fees'     => $shishou_fees,
					'qian_fees'        => $qian_fees,
					'fees_remark'      => trim($this->input->post('fees_remark')),
					'add_user_id'      => intval($this->session->userdata('admin_user_id')),
					'add_user_name'    => $this->session->userdata('admin_user_name'),
					'adviser_id'       => $adviser_id,
					'adviser_name'     => $adviser_name,
					'responsible_id'   => $responsible_id,
					'responsible_name' => $responsible_name,
					'fees_type'        => intval($this->input->post('fees_type')),//交费类型(交费，续费，还款，商品交费)
					'pay_type'         => intval($this->input->post('pay_type')),
					'source_id'        => $source_id,
					'source_name'      => $source_name,
					'add_year'         => date('Y',strtotime($add_time)),
					'add_month'        => date('n',strtotime($add_time)),
					'add_time_day'     => strtotime(date('Y-m-d',strtotime($add_time))),
					'add_time'         => strtotime($add_time),
				);
				$this->global_model->set('student_fees',0,$fees);
				unset($fess);

				//**********如果是从资源导入，并第一次交费时，添加资源操作记录 开始**************
				//如果是从资源导入 改变资源状态为80已交费 和填加操作记录（电访记录）
				if($info_id)
				{
					//检测是否是第一次交费
					$jiaofei = $this->global_model->count_all('student_fees','student_id = '.$student_id);

					if ($jiaofei == 1)
					{
						//改变资源状态为80已交费
						$this->global_model->set('info',$info_id,array('info_status' => 80));
						//填加操作记录（电访记录）
						$info_data = array(
							'info_id'       => $info_id,
							'call_year'     => date('Y'),
							'call_month'    => date('n'),
							'call_date'     => time(),
							'call_remark'   => "交费成功。",
							'validity'      => 1,
							'info_status'   => 80,
							'add_user_id'   => intval($this->session->userdata('admin_user_id')),
							'add_user_name' => $this->session->userdata('admin_user_name'),
							'add_time'      => time()
						); 
						$this->global_model->set('info_call',0,$info_data);
						unset($info_data);
					}
				}
				//**********如果是从资源导入，并第一次交费时，添加资源操作记录 结束**************

				//---------------添加排课开始
				//$this->student_model->auto_stu_syllabus($grade,$student_id);
				
				//---------------添加排课结束


				$data['message']    ='填加成功，正在关闭窗口……';
				$data['return_url'] = site_url('close_window/student');
				$this->load->view('success', $data);
			}
			else
			{
				$data['message']    = '失败，正在返回……';
				$data['return_url'] = site_url('student/add_grade/'.$student_id);
				$this->load->view('error', $data);
			}
		}
	}


	//学员交费管理
	public function fee($student_id=0)
	{
		$this->my_permission->check_per(',6_10,','学员交费管理');//权限
		$student_id = intval($student_id);
		$data       = array();
		if ($student_id){
			$data['student_id'] = $student_id;
			$data['fee_log']  = $this->global_model->get('student_fees','student_id = '.$student_id,"*","id desc");
			//$field              = "sf.id,sf.add_time,sf.yingshou_fees,sf.youhui_fees,sf.shishou_fees,sf.qian_fees,sf.fees_type,sf.pay_type,sf.add_user_name,sf.responsible_name,sf.fees_remark,";
			//$data['fee_log']    = $this->global_model->my_query('student_fees as sf',"sf.student_id = $student_id",$field,'','student_grade as sg',"sf.student_id = sg.student_id",'sf.id desc');
		}
		$this->load->view('header');
		$this->load->view('student_fee_list',$data);
		$this->load->view('bottom');
	}

//学员交费重置
    public function flash_fee($student_id=0)
    {
        $this->my_permission->check_per(',6_10,','学员交费管理');//权限
        $student_id = intval($student_id);
        $data       = array();




        if ($student_id){
            $data['student_id'] = $student_id;
            $data['fee_log']  = $this->global_model->get('student_fees','student_id = '.$student_id,"*","id desc");
        }
        $t_yingshou_fees = 0.00;
        $t_shishou_fees  = 0.00;
        $t_qian_fees     = 0.00;
        $t_youhui_fees   = 0.00;
        foreach ($data['fee_log'] as $key => $log) {
            $t_yingshou_fees += $log['yingshou_fees'];
            $t_shishou_fees += $log['shishou_fees'];
            $t_qian_fees += $log['qian_fees'];
            $t_youhui_fees += $log['youhui_fees'];
        }
        $data1['total_fees']=$t_yingshou_fees;
        $data1['shishou_fees']=$t_shishou_fees;
        $data1['qian_fees']=$t_qian_fees;
        $data1['youhui_fees']=$t_youhui_fees;


        $result = $this->global_model->set('student',$student_id,$data1);//更新学员交费总信息
        if ($result)
        {

            $data['message']    ='重置成功，正在关闭窗口……';
            $data['return_url'] = site_url('student/disp/'.$student_id);
            $this->load->view('success', $data);
        }
        else
        {
            $data['message']    = '失败，正在返回……';
            $data['return_url'] = site_url('student/disp/'.$student_id);
            $this->load->view('error', $data);
        }

    }



	//学员交费
	public function add_fee($student_id=0)
	{
		$this->load->helper('form');
		$this->load->library('form_validation');
		$student_id = intval($student_id);//学员id
		$campus_id  = intval($this->session->userdata('campus_id'));//校区id
		$this->form_validation->set_rules('shishou_fees','实收金额', 'required');
		$this->form_validation->set_error_delimiters('<div class="alert alert-error">', '</div>');

		if ($this->form_validation->run() === FALSE )
		{
			//如果没有提交，显示提交页
			$data['adviser']    = $this->global_model->get('admin',array('status > ' => 0,'campus_id' => $campus_id),'id,full_name','full_name asc');//取本校区全部在职人员
			//填加
			if ($student_id)
			{
				//学员信息
				$data['student_item'] = $this->global_model->get_one('student',$student_id,"id,name");
			}
			$data['student_id'] = $student_id;
			$this->load->view('header');
			$this->load->view('student_add_fee',$data);
			$this->load->view('bottom');
		}
		else
		{
			//提交页
			$total_fees           = $this->input->post('total_fees');
			$shishou_fees         = $this->input->post('shishou_fees');
			$youhui_fees          = $this->input->post('youhui_fees');
			$qian_fees            = $total_fees-$shishou_fees-$youhui_fees;

			//取学员信息
			$row          = $this->global_model->get_one('student',$student_id,"name,total_fees,shishou_fees,youhui_fees,qian_fees,adviser_id,adviser_name,source_id,source_name");
			$adviser_id   = $row['adviser_id'];
			$adviser_name = $row['adviser_name'];
            $source_id = $row['source_id'];
            $source_name = $row['source_name'];

			$data         =  array();
			$fees_type    = intval($this->input->post('fees_type'));//交费类型

			//如果是还款（状态码 20）
			if ($fees_type == 20)
			{
				$data['qian_fees']    = $row['qian_fees']-$shishou_fees;//欠款
				$data['shishou_fees'] = $shishou_fees+$row['shishou_fees'];//实收费用 累计
				$data['youhui_fees']  = $youhui_fees+$row['youhui_fees'];//累计优惠
				//如果存在欠费，更新学员状态为欠费
				if ($data['qian_fees']>0)
				{
					$data['student_status'] = 4;
				}
				else
				{
					$data['student_status'] = 0;
				}
			}
			else
			{
				$data['qian_fees']    = $qian_fees+$row['qian_fees'];//欠款
				$data['total_fees']   = $total_fees+$row['total_fees'];//总消费，累加
				$data['shishou_fees'] = $shishou_fees+$row['shishou_fees'];//实收费用 累计
				$data['youhui_fees']  = $youhui_fees+$row['youhui_fees'];//累计优惠
				//如果存在欠费，更新学员状态为欠费
				if (intval($data['qian_fees']))
				{
					$data['student_status'] = 4;
				}
			}

            unset($row);
		
			$result = $this->global_model->set('student',$student_id,$data);//更新学员交费总信息
			if ($result)
			{
				//填加交费记录
				$responsible_id = $this->input->post('responsible_id');//经办人
				$responsible_name = '';
				if ($responsible_id)
				{
					$row = $this->global_model->get_one('admin',$responsible_id,'full_name');
					$responsible_name = $row['full_name'];
				}

				$fees = array(
					'student_id'       => $student_id,
					'campus_id'        => $campus_id,
					'yingshou_fees'    => $total_fees,
					'youhui_fees'      => $youhui_fees,
					'shishou_fees'     => $shishou_fees,
					'qian_fees'        => $qian_fees,
					'fees_remark'      => trim($this->input->post('fees_remark')),
					'add_user_id'      => intval($this->session->userdata('admin_user_id')),
					'add_user_name'    => $this->session->userdata('admin_user_name'),
					'adviser_id'       => $adviser_id,
					'adviser_name'     => $adviser_name,
					'responsible_id'   => $responsible_id,
					'responsible_name' => $responsible_name,
					'fees_type'        => $fees_type,
                    'source_id'        => $source_id,
                    'source_name'      => $source_name,
					'pay_type'         => intval($this->input->post('pay_type')),
					'add_year'         => date('Y',strtotime($this->input->post('add_time'))),
					'add_month'        => date('n',strtotime($this->input->post('add_time'))),
					'add_time_day'     => strtotime(date('Y-m-d',strtotime($this->input->post('add_time')))),
					'add_time'         => strtotime($this->input->post('add_time'))
				);
				$this->global_model->set('student_fees',0,$fees);
				unset($fess);

				$data['message']    ='填加成功，正在关闭窗口……';
				$data['return_url'] = site_url('close_window/student_fees');
				$this->load->view('success', $data);
			}
			else
			{
				$data['message']    = '失败，正在返回……';
				$data['return_url'] = site_url('student/add_fee/'.$student_id);
				$this->load->view('error', $data);
			}
		}

	}



	//删除学员交费
	public function fee_del($id = 0)
	{
		$this->my_permission->check_per(',6_13,','删除交费记录');//权限
		$id = intval($id);
		if ($id) 
		{
			$fee    = $this->global_model->get_one('student_fees',$id,$field='*');
			$user   = $this->global_model->get_one('student',$fee['student_id'],'*');

			$result = $this->global_model->del('student_fees',$id,'');

			if ($result)
			{
				//如果有对应班级，也同时删除
				if ($fee['sg_id'])
				{
					$row      = $this->global_model->get_one('student_grade',$fee['sg_id'],'grade_id');
					$grade_id = $row['grade_id'];
					$isok     = $this->global_model->del('student_grade',$fee['sg_id'],'');
					//原班级人数减1
					if ($isok && $grade_id)
					{
						$this->global_model->set_num('grade',"id = $grade_id",$set='now_number',$num=-1);
					}	
				}

				
				//更新学员表，消费总记录的值
				$updata = array(
					'total_fees'   => $user['total_fees']-$fee['yingshou_fees'],
					'qian_fees'    => $user['qian_fees']-$fee['qian_fees'],
					'shishou_fees' => $user['shishou_fees']-$fee['shishou_fees'],
					'youhui_fees'  => $user['youhui_fees']-$fee['youhui_fees'],
				);

				$result = $this->global_model->update('student','id = '.$fee['student_id'],$updata);

				$data['message']    = '删除成功，正在返回……';
				$data['return_url'] = site_url('close_window');
				$this->load->view('success', $data);
			}
			else
			{
				$data['message'] = $action_name.'删除失败，正在返回……';
				$data['return_url'] = site_url('close_window');
				$this->load->view('error', $data);
			}
		}
		else
		{
			$data['message'] = $action_name.'参数错误，正在返回……';
			$data['return_url'] = site_url('close_window');
			$this->load->view('error', $data);
		}
	}


	//删除学员所报班级和交费记录
	//$s_id:学员所报班级记录的id
	public function grade_del($s_id = 0)
	{
		$this->my_permission->check_per(',6_14,','删除学员班级');//权限
		$s_id = intval($s_id);
		if ($s_id) 
		{
			$row      = $this->global_model->get_one('student_grade',"$s_id",'*');
			$grade_id = $row['grade_id'];
			
			$result   = $this->global_model->del('student_grade',$s_id,'');

			if ($result)
			{
				//原班级人数减1
				if ($grade_id)
				{
					$this->global_model->set_num('grade',"id = $grade_id",$set='now_number',$num=-1);
				}	
				//如果有对应交费记录也删除
				$fee = $this->global_model->get('student_fees',"sg_id = $s_id","*","id asc",1);
				if (!empty($fee))
				{
					$isok = $this->global_model->del('student_fees','',"sg_id = $s_id");
					
					//更新学员表，消费总记录的值
					if ($isok)
					{
						$user   = $this->global_model->get_one('student',$fee['student_id'],'*');
						$updata = array(
						'total_fees'   => $user['total_fees']-$fee['yingshou_fees'],
						'qian_fees'    => $user['qian_fees']-$fee['qian_fees'],
						'shishou_fees' => $user['shishou_fees']-$fee['shishou_fees'],
						'youhui_fees'  => $user['youhui_fees']-$fee['youhui_fees'],
						);
						$result = $this->global_model->update('student','id = '.$fee['student_id'],$updata);
					}
				}

				$data['message']    = '删除成功，正在返回……';
				$data['return_url'] = site_url('close_window');
				$this->load->view('success', $data);
			}
			else
			{
				$data['message'] = $action_name.'删除失败，正在返回……';
				$data['return_url'] = site_url('close_window');
				$this->load->view('error', $data);
			}
		}
		else
		{
			$data['message'] = $action_name.'参数错误，正在返回……';
			$data['return_url'] = site_url('close_window');
			$this->load->view('error', $data);
		}
	}




	//学员商品消费管理
	public function goods($student_id=0)
	{
		$this->my_permission->check_per(',6_11,','学员商品管理');//权限
		$student_id = intval($student_id);
		$data       = array();
		if ($student_id){
			$data['student_id'] = $student_id;
			$data['goods']      = $this->global_model->get('student_fees_goods','student_id = '.$student_id,"*","id desc");
		}
		$this->load->view('header');
		$this->load->view('student_goods',$data);
		$this->load->view('bottom');
	}


	//学员体验
	public function tiyan($tel='')
	{
		$data = array();
		if ($tel){
			$data['tiyan'] = $this->global_model->get('visit','tel = '.$tel,"*","id asc");
		}
		$this->load->view('header');
		$this->load->view('student_tiyan',$data);
		$this->load->view('bottom');
	}




	//添加学员交费
	public function add_goods($student_id=0)
	{
		$this->my_permission->check_per(',6_11,','学员商品管理');//权限
		$this->load->helper('form');
		$this->load->library('form_validation');
		$student_id = intval($student_id);//学员id
		$campus_id  = intval($this->session->userdata('campus_id'));//校区id
		$this->form_validation->set_rules('goods_name','商品名', 'required');
		$this->form_validation->set_error_delimiters('<div class="alert alert-error">', '</div>');

		if ($this->form_validation->run() === FALSE )
		{
			//如果没有提交，显示提交页
			$data['user']    = $this->global_model->get('admin',array('status > ' => 0,'campus_id' =>$campus_id),'id,full_name,full_name_index','full_name asc');//取系统在职顾问
			//填加
			if ($student_id)
			{
				//学员信息
				$data['student_item'] = $this->global_model->get_one('student',$student_id,"id,name");
			}
			$data['student_id'] = $student_id;
			$data['campus_id']  = $campus_id;
			$this->load->view('header');
			$this->load->view('student_goods_add',$data);
			$this->load->view('bottom');
		}
		else
		{
			//提交页

			$total_fees   = $this->input->post('total_fees');
			
			//取学员信息
			$row          = $this->global_model->get_one('student',$student_id,"name,total_fees,shishou_fees,adviser_id,adviser_name");
			$data         =  array();
			$data['total_fees']   = $total_fees+$row['total_fees'];//总消费，累加
			$data['shishou_fees'] = $total_fees+$row['shishou_fees'];//实收费用 累计
			unset($row);
		
			$result = $this->global_model->set('student',$student_id,$data);//更新学员交费总信息
			if ($result)
			{
				//填加商品交费记录

				$user_id   = $this->input->post('user_id');//销售人员
				$user_name = '';
				if ($user_id)
				{
					$row       = $this->global_model->get_one('admin',$user_id,'full_name');
					$user_name = $row['full_name'];
				}
				//校区名秒
				$campus_name = '';
				if ($campus_id)
				{
					$row         = $this->global_model->get_one('system_campus',$campus_id,'name');
					$campus_name = $row['name'];
				}


				$fees = array(
					'campus_id'     => $campus_id,
					'campus_name'   => $campus_name,
					'student_id'    => $student_id,
					'student_name'  => $this->input->post('student_name'),
					'goods_name'    => trim($this->input->post('goods_name')),
					'goods_num'     => intval($this->input->post('goods_num')),
					'total_fees'    => $this->input->post('total_fees'),
					'add_user_id'   => intval($this->session->userdata('admin_user_id')),
					'add_user_name' => $this->session->userdata('admin_user_name'),
					'pay_type'      => intval($this->input->post('pay_type')),
					'user_id'       => $user_id,
					'user_name'     => $user_name,
					'fees_remark'   => trim($this->input->post('fees_remark')),
					'add_year'      => date('Y',strtotime($this->input->post('add_time'))),
					'add_month'     => date('n',strtotime($this->input->post('add_time'))),
					'add_time_day'  => strtotime(date('Y-m-d',strtotime($this->input->post('add_time')))),
					'add_time'      => strtotime($this->input->post('add_time'))
				);
				$this->global_model->set('student_fees_goods',0,$fees);
				unset($fess);

				$data['message']    ='填加成功，正在关闭窗口……';
				$data['return_url'] = site_url('close_window/student_fees');
				$this->load->view('success', $data);
			}
			else
			{
				$data['message']    = '失败，正在返回……';
				$data['return_url'] = site_url('student/add_goods/'.$student_id);
				$this->load->view('error', $data);
			}
		}

	}



	//删除学员商品交费
	public function goods_del($id = 0)
	{
		$this->my_permission->check_per(',6_13,','删除交费记录');//权限
		$id = intval($id);
		if ($id) 
		{
			$fee    = $this->global_model->get_one('student_fees_goods',$id,$field='*');
			$user   = $this->global_model->get_one('student',$fee['student_id'],'*');

			$result = $this->global_model->del('student_fees_goods',$id,'');

			if ($result)
			{
				//更新学员表，消费总记录的值
				$updata = array(
					'total_fees'   => $user['total_fees']-$fee['total_fees'],
					'shishou_fees' => $user['shishou_fees']-$fee['total_fees'],
				);

				$result = $this->global_model->update('student','id = '.$fee['student_id'],$updata);

				$data['message']    = '删除成功，正在返回……';
				$data['return_url'] = site_url('close_window/student_fees');
				$this->load->view('success', $data);
			}
			else
			{
				$data['message'] = $action_name.'删除失败，正在返回……';
				$data['return_url'] = site_url('close_window/student_fees');
				$this->load->view('error', $data);
			}
		}
		else
		{
			$data['message'] = $action_name.'参数错误，正在返回……';
			$data['return_url'] = site_url('close_window/student_fees');
			$this->load->view('error', $data);
		}
	}



	//学员电访记录
	public function call($student_id=0,$page=1)
	{
		$this->my_permission->check_per(',6_12,','电访记录管理');//权限
		$page = intval($page);
		$student_id = intval($student_id);
		$data       = array();
		if ($student_id)
		{
			$where = 'student_id = '.$student_id;
			$total_count = $this->global_model->count_all('student_call',$where);//返回记录总数
			$this->load->library('pagination');
			$config['base_url']         = site_url('student/call') ;//完整的 URL 路径通向包含你的分页控制器类/方法
			$config['prefix']           = "/$student_id/"; // 自定义的前缀添加到路径
			$config['suffix']           = '';// 自定义的前缀添加到路径
			$config['cur_page']         = $page;//当前页
			$config['total_rows']       = $total_count;//数据总行数
			$config['per_page']         = 10; //每页显示行数
			$config['num_tag_open']     = '<li>';//自定义“数字”链接
			$config['num_tag_close']    = '</li>';//自定义“数字”链接
			$config['cur_tag_open']     = '<li class="active"><a href="#" >';//自定义“当前页”链接
			$config['cur_tag_close']    = '</li></a>';//自定义“当前页”链接
			$config['anchor_class']     = "";//添加 CSS 类
			$config['num_links']        = 3;//放在你当前页码的前面和后面的“数字”链接的数量。
			$config['use_page_numbers'] = TRUE;
			$config['first_link']       = '首页';//你希望在分页的左边显示“第一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 。
			$config['first_tag_open']   = '<li>';//“第一页”链接的打开标签。
			$config['first_tag_close']  = '</li>';//“第一页”链接的关闭标签。
			$config['prev_link']        = '上一页';//你希望在分页中显示“上一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 。
			$config['prev_tag_open']    = '<li>';//“上一页”链接的打开标签。
			$config['prev_tag_close']   = '</li>';//“上一页”链接的关闭标签
			$config['last_link']        = '末页';
			$config['last_tag_open']    = '<li>';//“最后一页”链接的打开标签。
			$config['last_tag_close']   = '</li>';//“最后一页”链接的关闭标签。
			$config['next_link']        = '下一页';//你希望在分页中显示“下一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 
			$config['next_tag_open']    = '<li>';//“下一页”链接的打开标签。
			$config['next_tag_close']   = '</li>';//“下一页”链接的关闭标签。

			$this->pagination->initialize($config); 
			$data['pages']       = $this->pagination->create_links();
			$data['total_count'] = $total_count;
			$data['call']        = $this->global_model->get_page('student_call',$where,'*','id desc',$config['per_page'], $page);
			$data['student_id']  = $student_id;

		}

		$this->load->view('header');
		$this->load->view('student_call_list',$data);
		$this->load->view('bottom');
	}



	//填加电访记录
	public function add_call($student_id = 0)
	{
		$this->load->helper('form');
		$this->load->library('form_validation');
		$student_id = intval($student_id);//学员id
		$this->form_validation->set_rules('add_time','电访日期不能为空', 'required');
		$this->form_validation->set_rules('remark','电访内容不能为空','required');
		$this->form_validation->set_error_delimiters('<div class="alert alert-error">', '</div>');

		if ($this->form_validation->run() === FALSE )
		{
			$data['student_id'] = $student_id;
			$this->load->view('header');
			$this->load->view('student_add_call',$data);
			$this->load->view('bottom');
		}
		else
		{
			//提交页
			if ($student_id)
			{
				$data = array(
					'student_id'    => $student_id,
					'call_date'     => strtotime($this->input->post('add_time')),
					'call_remark'   => $this->input->post('remark'),
					'add_user_id'   => intval($this->session->userdata('admin_user_id')),
					'add_user_name' => $this->session->userdata('admin_user_name'),
					'add_time'      => time(),
				);
				$result = $this->global_model->set('student_call',0,$data);
			}
		
			if ($result)
			{
				$data['message']    ='填加成功，正在关闭窗口……';
				$data['return_url'] = site_url('close_window/student_fees');
				$this->load->view('success', $data);
			}
			else
			{
				$data['message']    = '失败，正在返回……';
				$data['return_url'] = site_url('student/add_call/'.$student_id);
				$this->load->view('error', $data);
			}
		}
	}


	/*
	修改上课次数
	$student_id 学员id
	$sg_id 学生所报班级id （student_grade）
	*/
	public function eidt_class_times($student_id = 0 ,$sg_id = 0)
	{
		$this->my_permission->check_per(',6_7,','修改上课次数');

		$this->load->helper('form');
		$this->load->library('form_validation');
		$student_id = intval($student_id);//学员id
		$sg_id      = intval($sg_id);//班级id
		$this->form_validation->set_rules('total_class_times','总课次不能为空', 'required');
		$this->form_validation->set_rules('class_times','已上课次不能为空','required');
		$this->form_validation->set_error_delimiters('<div class="alert alert-error">', '</div>');

		if ($this->form_validation->run() === FALSE )
		{
			$data['student_id']    = $student_id;
			$data['sg_id']         = $sg_id;
			$data['student_grade'] = $this->global_model->get_one('student_grade',$sg_id,'total_class_times,class_times,is_alert');

			$this->load->view('header');
			$this->load->view('student_eidt_class_times',$data);
			$this->load->view('bottom');
		}
		else
		{
			//提交页

			if ($student_id && $sg_id)
			{
				
				$data = array(
					'total_class_times' => intval($this->input->post('total_class_times')),
					'class_times'       => intval($this->input->post('class_times')),
					'is_alert'          => intval($this->input->post('is_alert')),
				);
				$result = $this->global_model->update('student_grade',"id = $sg_id and student_id = $student_id",$data);

			}
		
			if ($result)
			{
				$data['message']    ='填加成功，正在关闭窗口……';
				$data['return_url'] = site_url('close_window/student');
				$this->load->view('success', $data);
			}
			else
			{
				$data['message']    = '失败，正在返回……';
				$data['return_url'] = site_url("student/eidt_class_times/$student_id/$sg_id");
				$this->load->view('error', $data);
			}
		}
	}

	/*
	学生转班级.
	$source_grade_id 源班级id
	$student_id 学生id 
	$source_s_grade_id 源学生所报班级id

	*/
	public function turn_grade ($student_id = 0 , $source_grade_id = 0 ,$source_s_grade_id=0)
	{
		$this->my_permission->check_per(',6_8,','转班级');//权限
		$this->load->helper('form');
		$this->load->library('form_validation');
		$source_grade_id   = intval($source_grade_id);//源班级id
		$source_s_grade_id = intval($source_s_grade_id);
		$student_id        = intval($student_id);//学员id
		$campus_id         = intval($this->session->userdata('campus_id'));//校区id
		$this->form_validation->set_rules('grade_id','班级', 'required');
		$this->form_validation->set_error_delimiters('<div class="alert alert-error">', '</div>');

		if ($this->form_validation->run() === FALSE )
		{
			//如果没有提交，显示提交页
			$data['campus']  = $this->system_model->get_system('system_campus',array('status' => 1));//校区
			$data['adviser'] = $this->global_model->get('admin',array('status > ' => 0,'department_path like' =>'%,3,%'),'id,full_name','full_name asc');//取系统在职顾问
			$data['teacher'] = $this->global_model->get('admin',array('status > ' => 0,'campus_id' =>$campus_id ,'department_path like' =>'%,6,%'),'id,full_name','full_name asc');//取系统在职教师

			//取班级信息 
			$data['grade']             = $this->global_model->get('grade','campus_id = '.$campus_id.' and grade_status = 0','*','id desc');//只取状态为 正常的
			$data['source_grade_id']   = $source_grade_id;
			$data['source_s_grade_id'] = $source_s_grade_id;
			$data['student_id']        = $student_id;
			$this->load->view('header');
			$this->load->view('student_turn_grade',$data);
			$this->load->view('bottom');
		}
		else
		{
			//提交页
			$campus_id  = $this->input->post('campus_id');
			$teacher_id = $this->input->post("teacher_id");
			$grade_id   = $this->input->post('grade_id');

			//校区名字
			$campus_name = '';
			$row = $this->global_model->get_one('system_campus',$campus_id,"name");
			if (!empty($row))
			{
				$campus_name = $row['name'];
			}
			//教师名字
			$teacher_name ='';
			$row = $this->global_model->get_one('admin',$teacher_id,'full_name');
			if (!empty($row))
			{
				$teacher_name = $row['full_name'];
			}

			//班级名称
			$grade_name = '';
			$row = $this->global_model->get_one('grade',$grade_id,'grade_name');
			if (!empty($row))
			{
				$grade_name = $row['grade_name'];
			}
			unset($row);

			if ($grade_id && $source_grade_id)
			{
				//取新班级信息
				$row = $this->global_model->get_one('grade',$grade_id,'*');
				$lesson_id = $row['lesson_id'];
				$class_id = $row['class_id'];
				//更新学员所报班级记录
				$row = $this->global_model->get_one('system_lesson',$lesson_id,'name');
				$lesson_name = $row['name'];

				$row = $this->global_model->get_one('system_class',$class_id,'name');
				$class_name = $row['name'];


				$updata = array(
					'grade_id'     => $grade_id,
					'lesson_id'    => $lesson_id,
					'lesson_name'  => $lesson_name,
					'class_id'     => $class_id,
					'class_name'   => $class_name,
					'teacher_id'   => $teacher_id,
					'teacher_name' => $teacher_name,
				);

				$result = $this->global_model->update('student_grade',"student_id = $student_id and grade_id = $source_grade_id and id = $source_s_grade_id",$updata);
			}

			if ($result)
			{
				//取学员信息
				$data = array(
					'campus_id' => $campus_id,
					'campus_name' => $campus_name,
					'teacher_id' => $teacher_id,
					'teacher_name' => $teacher_name,
				);
				$result = $this->global_model->set('student',$student_id,$data);//重新学员信息

				//新班级人数加1
				if ($result)
				{
					$this->global_model->set_num('grade',"id = $grade_id",$set='now_number',$num=1);
				}

				//原班级人数减1
				if ($result)
				{
					$this->global_model->set_num('grade',"id = $source_grade_id",$set='now_number',$num=-1);
				}				

			}


			if ($result)
			{
				$data['message']    ='操作成功，正在关闭窗口……';
				$data['return_url'] = site_url('close_window/student');
				$this->load->view('success', $data);
			}
			else
			{
				$data['message']    = '失败，正在返回……';
				$data['return_url'] = site_url("student/turn_grade/$student_id/$source_grade_id");
				$this->load->view('error', $data);
			}

		}
	}



	/*
	ajax按校区筛选教师
	$grade_id 班级id
	*/
	public function ajax_campus_teacher($campus_id = 0)
	{
		$this->output->enable_profiler(FALSE);//FALSE
		$campus_id = intval($campus_id);
		$num       = 0;//返回的记录数
		$html      = '<option value="0">--请选择--</option>';//返回的html 下拉框代码
		if ($campus_id)
		{
			$where = "campus_id = $campus_id and status > 0 and (department_path like '%,8,%' or department_path like '%,7,%')";
			$teacher = $this->global_model->get('admin',$where,'id,full_name_index,full_name','full_name asc');//取系统在职用户，课程顾问
			$num = count($teacher);
			foreach ($teacher as $teacher_item) 
			{
				$html .= '<option value="'.$teacher_item['id'].'">'.$teacher_item['full_name'].'</option>';
			}
			unset($teacher);

		}

		//===============post方式结束=====================
		$json_data = array('num' => $num,'html' => $html);
		echo json_encode( $json_data );
	}

	
	/*
	ajax 按任课教师筛选班级
	$teacher_id 教师id
	*/
	public function ajax_teacher_grade($teacher_id = 0)
	{
		$this->output->enable_profiler(FALSE);
		$teacher_id = intval($teacher_id);
		$num        = 0;
		$html       = '<option value="0">--请选择--</option>';
		if ($teacher_id)
		{
			$where = "grade_teacher_id = $teacher_id";
			$grade = $this->global_model->get('grade',$where,'id,grade_name','grade_name asc');//取班级
			$num = count($grade);
			foreach ($grade as $g) 
			{
				$html .= '<option value="'.$g['id'].'">'.$g['grade_name'].'</option>';
			}
			unset($grade);
		}	
		$json_data = array('num' => $num,'html' => $html);
		echo json_encode( $json_data );

	}



	/*
	ajax 检查手机是否重复	
	*/
	public function ajax_check_tel($tel = '')
	{
		$this->output->enable_profiler(FALSE);
		$num  = 0;
		$html = '';
		$tel  = trim($tel);
		if ($tel)
		{
			$where  = "mobile = '$tel'";
			$result = $this->global_model->count_all('student',$where);
			$num    = intval($result);
			$html = '手机号重复，不允许添加！';
		}
		$json_data = array('num' => $num, 'html' => $html);
		echo json_encode( $json_data );
	}

	/*
	ajax 资源直接缴费第一步获取老师信息
	*/
	public function ajax_add_grade_so(){
		$campus_id = intval($this->session->userdata('campus_id'));
		$teacher   = $this->global_model->get('admin',array('status > ' => 0,'campus_id' =>$campus_id ,'department_path like' =>'%,6,%'),'id,full_name','full_name asc');//取系统在职教师
		echo json_encode( $teacher );
	}
	/*
	ajax 资源直接缴费第二步按任课教师筛选班级
	$teacher_id 教师id
	*/
	public function ajax_second_teacher_grade($teacher_id = 0)
	{
		$this->output->enable_profiler(FALSE);
		$teacher_id = intval($teacher_id);
		$campus_id          = intval($this->session->userdata('campus_id'));
		$data['campus_id']  = $campus_id;
		$data['adviser']    = $this->global_model->get('admin',"status > 0 and campus_id = $campus_id ",'id,full_name','full_name asc');//取系统在职本校区所有人员
		$data['teacher']   = $this->global_model->get('admin',array('status > ' => 0,'campus_id' =>$campus_id ,'department_path like' =>'%,6,%'),'id,full_name','full_name asc');//取系统在职教师
		$data['source']     = $this->system_model->get_source(0,array('status' => 1));//资源来源
		$where = "campus_id = $campus_id and grade_teacher_id = $teacher_id and grade_status = 0";
		$data['grade'] = $this->global_model->get('grade',$where,'*','grade_teacher_id asc,grade_name desc');//只取状态为 正常的
		//交费类型
		$fees_type       = array();
		$fees_type['1']  = '新增';
		$fees_type['10'] = '续费';

		$fees_type['20'] = '还款';
		$fees_type['25'] = '升单';
		$fees_type['28'] = '口碑';
		$fees_type['30'] = '活动';
		$fees_type['40'] = '定金';
		$fees_type['41'] = '拓课';
		/*$fees_type['50'] = '商品交费';*/
		$fees_type['70'] = '其他';
		$fees_type['110'] = '企业课';
		$fees_type['100'] = '退费';
		$data['fees_type'] = $fees_type;
		//支付方式
		$pay_type       = array();
		$pay_type['10'] = '现金';
		//$pay_type['20'] = '刷卡(停用)';
		//$pay_type['21'] = '招商银行 刷卡';
		$pay_type['22'] = '刷卡';
		$pay_type['24'] = '公户';//由支票 改为 公户
		$pay_type['25'] = '刷卡+现金';
		$pay_type['30'] = '转账';
		$pay_type['40'] = '小牛分期';
		$data['pay_type'] = $pay_type;
		echo json_encode( $data );
	}

}