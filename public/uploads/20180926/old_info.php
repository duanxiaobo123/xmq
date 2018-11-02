<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Info extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('global_model');
		$this->load->model('system_model');
		$this->load->model('info_model');
		$this->load->library('My_permission');//权限类
		$this->load->library('user_agent');
		//$this->output->enable_profiler(TRUE);
	}


    //资源列表主页
    //order_by 排序
    public function index($page=1, $order_by = 'id',$by = 'desc')
    {
        $this->load->helper('form');
        $is_callback     = 0;//是否显示待回访资源
        $admin_user_id   = intval($this->session->userdata('admin_user_id'));
        $campus_id       = intval($this->session->userdata('campus_id'));
        $fz_campus_id    = $this->session->userdata('fz_campus_id');//负责校区，多个用逗号分开
        $user_department = $this->session->userdata('user_department');
        //检查权限
        $this->my_permission->check_per(',0_2,','查看资源');

        $data['order_by'] = $order_by;
        $data['by']       = $by;
        // 分页类开始
        if ($page ==0){//清除搜索session
            $this->session->unset_userdata('info_query');
            $this->session->unset_userdata('history_sql');
            $page = 1;
        }
        //查询当前用户所在部门id
        $user_department = $this->system_model->get_user_department($admin_user_id);

        //查询条件 如果有查看全部全权，否则只看自己的信息
        if ($this->my_permission->permi(',0_10,'))
        {
            //$where = 'id > 0';
            $where = 'is_del = 0 and is_goshop = 1';
        }
        else
        {
            //如果有查看下级人员的权限，可看下级人员的资源否侧只能看自己的
            if ($this->my_permission->permi(',0_6,'))
            {
                //取下级人员id，多个用逗号分开。
                $user_sub = $this->system_model->get_user_sub($admin_user_id);
                if ($user_sub)
                {
                    $where = "is_del = 0 and is_goshop = 1 and add_user_id in ($user_sub,$admin_user_id)";
                }
                else{
                    $where = "is_del = 0 and is_goshop = 1 and add_user_id = ".$admin_user_id;
                }

            }
            else
            {
                //如果有查看本校区全部资源的权限，可看本校所有，否侧只能看自己的
                if ($this->my_permission->permi(',0_9,'))
                {
                    if ($fz_campus_id)
                    {
                        $where = "is_del = 0 and is_goshop = 1 and (campus_id in ($fz_campus_id)  or add_user_id = $admin_user_id)";
                    }
                    else
                    {
                        $where = "is_del = 0 and is_goshop = 1 and (campus_id = $campus_id or add_user_id = $admin_user_id)";
                    }


                }
                else
                {
                    //只看本部门
                    if ($this->my_permission->permi(',0_20,'))
                    {
                        $where = 'is_del = 0 and is_goshop = 1 and department_id = '.$user_department;
                    }
                    else
                    {
                        $where = "is_del = 0 and is_goshop = 1 and add_user_id = ".$admin_user_id;
                    }

                }
            }

        }
//echo "where=".$where;

        ///课程顾问查询

        if ($user_department==3)//课程顾问
        {
            //如果有查看下级人员的权限，可看下级人员的资源否侧只能看自己的
            if ($this->my_permission->permi(',0_6,'))// 顾问主管
            {
                //取下级人员id，多个用逗号分开。
                $user_sub = $this->system_model->get_user_sub($admin_user_id);
                if ($user_sub)
                {
                    $where = "is_del = 0 and is_goshop = 1 and (add_user_id = ".$admin_user_id." or adviser_id in ($user_sub,$admin_user_id))";
                }
                else{
                    $where = "is_del = 0 and is_goshop = 1 and (add_user_id = ".$admin_user_id." or adviser_id = ".$admin_user_id.")";
             //       echo "开始";
                }
            }
            else // 普通顾问
            {
                //$where .= " or adviser_id = $admin_user_id or (campus_id = $campus_id and adviser_id = 0)";
              /*  if ($fz_campus_id)
                {
                    $where = "is_del = 0  and (adviser_id = $admin_user_id or (campus_id in ($fz_campus_id) ))";
                }
                else
                {
                    $where = "is_del = 0  and (adviser_id = $admin_user_id or (campus_id = $campus_id ))";
                }*/
                $where = "is_del = 0 and is_goshop = 1 and (add_user_id = ".$admin_user_id." or adviser_id = ".$admin_user_id.")";
                //$where .= "and (follow_up = 20 or follow_up = 0 or follow_up = 10 or add_user_id = $admin_user_id)";
           //     $where .= "and (follow_up = 20 or follow_up = 0 or follow_up = 10 or add_user_id = $admin_user_id)";

            }
            $where .= "  and follow_up != 30";
            //检查顾问是否有当天要回访的资源，如果有显示要回访的资源，不处理不可操作其他资源
            // $gw_callback_date = strtotime(date("Y-m-d"));
            // $check_where      = "is_del = 0 and adviser_id = $admin_user_id and gw_callback_date>0 and gw_callback_date <= $gw_callback_date";
            // $call = $this->global_model->get('info',$check_where,$field='*', "id asc");
            // if (!empty($call))
            // {
            // 	$is_callback = 1;
            // 	$data['callback'] = $call;
            // 	$this->load->view('header');
            // 	$this->load->view('info_list_callback',$data);
            // 	$this->load->view('bottom');
            // }


        }
    //    echo "<br>where2=".$where;

        if (empty($is_callback))
        {
            //搜索生成的session 查询
            $where_session = & $this->session->userdata('info_query');

            if (!empty($where_session))
            {
                $where .= $where_session;
            }

            //当前页码存入session，操作返回用
            $this->session->set_userdata('info_page',$page);

            $total_count = $this->info_model->count_all($where);//返回记录总数
            $this->load->library('pagination');
            $config['base_url']         = site_url('info/index') ;//完整的 URL 路径通向包含你的分页控制器类/方法
            $config['prefix']           = ""; // 自定义的前缀添加到路径
            $config['suffix']           = "/$order_by/$by/";// 自定义的前缀添加到路径
            $config['cur_page']         = $page;//当前页
            $config['total_rows']       = $total_count;//数据总行数
            $config['per_page']         = 20; //每页显示行数
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
            
            //排序

            if (empty($order_by))
            {
                $order_by = "is_read asc,id desc";
            }
            else
            {
                $order_by = "$order_by $by,id desc";
            }

//echo "<br>order_by=".$order_by;
            $data['info'] = $this->info_model->get_info($config['per_page'], $page, $where,$order_by);
    //        echo "<br>info=".var_dump($data['info']);
            //==================== 表头搜索菜单查询 开始 ================
            $data['region']  = $this->system_model->get_region(0,array('status' => 1));//地区
        //    $data['region']  = $this->system_model->get_region(0,array('status' => 1));//地区



            $where_user      = "status > 0 and (department_path like '%,12,%' or department_path like '%,1,%' or department_path like ',2,')";
            $data['user']    = $this->global_model->get('admin',$where_user,'id,full_name','full_name asc');//取系统在职用户，用于 收单人

            $data['campus']  = $this->system_model->get_system('system_campus',array('status' => 1));//校区

            $where_user      = "status > 0 and (department_path like '%,3,%' or department_path like '%,7,%')";
            $data['adviser'] = $this->global_model->get('admin',$where_user,'id,full_name','full_name asc');//取系统在职用户，课程顾门



            //==================== 表头搜索菜单查询 开始 ================

            $this->load->view('header');
            $this->load->view('info_list',$data);
            $this->load->view('bottom');
        }
    }
	
	public function index_lists($page=1,$stauts=1,$order_by = 'id',$by = 'desc')
    {
        $this->load->helper('form');
		
        $is_callback     = 0;//是否显示待回访资源
        $admin_user_id   = intval($this->session->userdata('admin_user_id'));
        $campus_id       = intval($this->session->userdata('campus_id'));
        $fz_campus_id    = $this->session->userdata('fz_campus_id');//负责校区，多个用逗号分开
        $user_department = $this->session->userdata('user_department');
        //检查权限
        $this->my_permission->check_per(',0_2,','查看资源');

        $data['order_by'] = $order_by;
        $data['by']       = $by;
        // 分页类开始
        if ($page ==0){//清除搜索session
            $this->session->unset_userdata('info_query');
            $this->session->unset_userdata('history_sql');
            $page = 1;
        }
        //查询当前用户所在部门id
        $user_department = $this->system_model->get_user_department($admin_user_id);

        //查询条件 如果有查看全部全权，否则只看自己的信息
        if ($this->my_permission->permi(',0_10,'))
        {
            //$where = 'id > 0';
            $where = 'is_del = 0';
        }
        else
        {
            //如果有查看下级人员的权限，可看下级人员的资源否侧只能看自己的
            if ($this->my_permission->permi(',0_6,'))
            {
                //取下级人员id，多个用逗号分开。
                $user_sub = $this->system_model->get_user_sub($admin_user_id);
                if ($user_sub)
                {
                    $where = "is_del = 0 and add_user_id in ($user_sub,$admin_user_id)";
                }
                else{
                    $where = "is_del = 0 and add_user_id = ".$admin_user_id;
                }

            }
            else
            {
                //如果有查看本校区全部资源的权限，可看本校所有，否侧只能看自己的
                if ($this->my_permission->permi(',0_9,'))
                {
                    if ($fz_campus_id)
                    {
                        $where = "is_del = 0 and (campus_id in ($fz_campus_id)  or add_user_id = $admin_user_id)";
                    }
                    else
                    {
                        $where = "is_del = 0 and (campus_id = $campus_id or add_user_id = $admin_user_id)";
                    }


                }
                else
                {
                    //只看本部门
                    if ($this->my_permission->permi(',0_20,'))
                    {
                        $where = 'is_del = 0 and department_id = '.$user_department;
                    }
                    else
                    {
                        $where = "is_del = 0 and add_user_id = ".$admin_user_id;
                    }

                }
            }

        }
//echo "where=".$where;

        ///课程顾问查询

        if ($user_department==3)//课程顾问
        {
            //如果有查看下级人员的权限，可看下级人员的资源否侧只能看自己的
            if ($this->my_permission->permi(',0_6,'))// 顾问主管
            {
                //取下级人员id，多个用逗号分开。
                $user_sub = $this->system_model->get_user_sub($admin_user_id);
                if ($user_sub)
                {
                    $where = "is_del = 0 and (hmsypx_info.add_user_id = ".$admin_user_id." or hmsypx_info.adviser_id in ($user_sub,$admin_user_id))";
                }
                else{
                    $where = "is_del = 0 and (hmsypx_info.add_user_id = ".$admin_user_id." or hmsypx_info.adviser_id = ".$admin_user_id.")";
             //       echo "开始";
                }
            }
            else // 普通顾问
            {
                //$where .= " or adviser_id = $admin_user_id or (campus_id = $campus_id and adviser_id = 0)";
              /*  if ($fz_campus_id)
                {
                    $where = "is_del = 0  and (adviser_id = $admin_user_id or (campus_id in ($fz_campus_id) ))";
                }
                else
                {
                    $where = "is_del = 0  and (adviser_id = $admin_user_id or (campus_id = $campus_id ))";
                }*/
                $where = "is_del = 0 and (hmsypx_info.add_user_id = ".$admin_user_id." or hmsypx_info.adviser_id = ".$admin_user_id.")";
                //$where .= "and (follow_up = 20 or follow_up = 0 or follow_up = 10 or add_user_id = $admin_user_id)";
           //     $where .= "and (follow_up = 20 or follow_up = 0 or follow_up = 10 or add_user_id = $admin_user_id)";

            }
            $where .= "  and follow_up != 30";
            //检查顾问是否有当天要回访的资源，如果有显示要回访的资源，不处理不可操作其他资源
            // $gw_callback_date = strtotime(date("Y-m-d"));
            // $check_where      = "is_del = 0 and adviser_id = $admin_user_id and gw_callback_date>0 and gw_callback_date <= $gw_callback_date";
            // $call = $this->global_model->get('info',$check_where,$field='*', "id asc");
            // if (!empty($call))
            // {
            // 	$is_callback = 1;
            // 	$data['callback'] = $call;
            // 	$this->load->view('header');
            // 	$this->load->view('info_list_callback',$data);
            // 	$this->load->view('bottom');
            // }


        }
    //    echo "<br>where2=".$where;


        if (empty($is_callback))
        {
            //搜索生成的session 查询
            $where_session = & $this->session->userdata('info_query');

            if (!empty($where_session))
            {
                $where .= $where_session;
            }

            //当前页码存入session，操作返回用
            $this->session->set_userdata('info_page',$page);
			$stauts= intval($stauts);
			if($stauts==1){
				$total_count = $this->info_model->count_all_student($where);//返回记录总数
			}else{
				$total_count = $this->info_model->count_all_student_w($where);//返回记录总数
			}
            $this->load->library('pagination');
            $config['base_url']         = site_url('info/index_lists') ;//完整的 URL 路径通向包含你的分页控制器类/方法
            $config['prefix']           = ""; // 自定义的前缀添加到路径
			if($stauts==1){
				$config['suffix'] = "/1";// 自定义的前缀添加到路径
			}else{
				$config['suffix'] = "/2";// 自定义的前缀添加到路径
			}
            $config['cur_page']         = $page;//当前页
            $config['total_rows']       = $total_count;//数据总行数
            $config['per_page']         = 20; //每页显示行数
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

            //排序

            if (empty($order_by))
            {
                $order_by = "is_read asc,id desc";
            }
            else
            {
                $order_by = "$order_by $by,id desc";
            }

//echo "<br>order_by=".$order_by;
			if($stauts==1){
				$data['info'] = $this->info_model->get_info_student($config['per_page'], $page, $where);
			}else{
				$data['info'] = $this->info_model->get_info_student_w($config['per_page'], $page, $where);
			}
    //        echo "<br>info=".var_dump($data['info']);

            //==================== 表头搜索菜单查询 开始 ================
            $data['region']  = $this->system_model->get_region(0,array('status' => 1));//地区
        //    $data['region']  = $this->system_model->get_region(0,array('status' => 1));//地区



            $where_user      = "status > 0 and (department_path like '%,12,%' or department_path like '%,1,%' or department_path like ',2,')";
            $data['user']    = $this->global_model->get('admin',$where_user,'id,full_name','full_name asc');//取系统在职用户，用于 收单人

            $data['campus']  = $this->system_model->get_system('system_campus',array('status' => 1));//校区

            $where_user      = "status > 0 and (department_path like '%,3,%' or department_path like '%,7,%')";
            $data['adviser'] = $this->global_model->get('admin',$where_user,'id,full_name','full_name asc');//取系统在职用户，课程顾门



            //==================== 表头搜索菜单查询 开始 ================

            $this->load->view('header');
            $this->load->view('info_list',$data);
            $this->load->view('bottom');
        }
    }

	public function set_info($id=0)
	{
		$admin_user_id = intval($this->session->userdata('admin_user_id'));
        $campus_id     = intval($this->session->userdata('campus_id'));
        $id = intval($id);
        //检查权限
        if ($id)
        {
            $this->my_permission->check_per(',0_3,','修改资源');
        }
        else
        {
            $this->my_permission->check_per(',0_1,','资源上传');
        }


        $this->load->helper('form');
        $this->load->library('form_validation');

        $this->form_validation->set_rules('name','姓名', 'required');
        $this->form_validation->set_rules('age','年龄','numeric');
        $this->form_validation->set_error_delimiters('<div class="alert alert-error">', '</div>');

        $action_name         = ($id)? '修改' : '添加' ;
        $data['action_name'] = $action_name;

        if ($this->form_validation->run() === FALSE )
        {
            //如果没有提交，显示提交页
            $data['lesson']          = $this->system_model->get_system('system_lesson',array('status' => 1));//取课程  只显示启用的
            $data['group']           = $this->system_model->get_system('system_group',array('status' => 1));//学习对象
            $data['class']           = $this->system_model->get_system('system_class',array('status' => 1));//班型
            $data['source']          = $this->system_model->get_source(0,array('status' => 1));//资源来源
            $data['department']      = $this->system_model->get_department(0,array('status' => 1));//部门
            $data['region']          = $this->system_model->get_region(0,array('status' => 1));//地区

            //如果是只能看本校信息，只调用要校人员
            if ($this->my_permission->permi(',0_9,'))
            {
                $where          = "campus_id = '$campus_id' and is_lock =0 and status > 0 and department_path not like '%6%' and department_path not like '%9%'"; //修改成所有人除了 老师 校长 前台
                $data['user'] = $this->global_model->get('admin',$where,'id,full_name,campus_name','full_name asc');//邀约人

                // $data['user'] = $this->global_model->get('admin',array('campus_id' => $campus_id,'status > ' => 0,'department_path not like' =>'%,6,%','department_path not like' =>'%,9,%'),'id,full_name','full_name asc');//取系统在职用户，用于 收单人，邀约人
            }
            else
            {
                $where          = "is_lock =0 and status > 0 and department_path not like '%6%' and department_path not like '%9%'"; //修改成所有人除了 老师 校长 前台
                $data['user'] = $this->global_model->get('admin',$where,'id,full_name,campus_name','full_name asc');//邀约人

                //   $data['user'] = $this->global_model->get('admin',array('status > ' => 0,'department_path not like'=> '%[69]%'),'id,full_name','full_name asc1');//取系统在职用户，用于 收单人，邀约人
            }


            //查询当前用户所在部门id
            $data['user_department'] = $this->system_model->get_user_department($admin_user_id);
            if ($id)
            {
                $data['info_item'] = $this->info_model->get_info_one($id);

            }
            $this->load->view('header');
            $this->load->view('info_set',$data);
            $this->load->view('bottom');
        }
        else
        {
            //提交页
            //==================检查手机号是否重复开始========================
            if ($id==0)
            {
                $mobile = trim($this->input->post('mobile'));
                $result = $this->global_model->count_all('info',"mobile = '$mobile' and is_del = 0");
                $num    = intval($result);
                if ($num)
                {
                    $data['message'] = $action_name.'手机号重复，添加失败，正在返回……';
                    $data['return_url'] = site_url('info/set_info');
                    $this->load->view('error', $data);
                    exit;
                }
            }

            //==================检查手机号是否重复结束========================


            // 按id取下拉框选项的名称
            $lesson_id     = intval($this->input->post('lesson_id'));
            $group_id      = intval($this->input->post('group_id'));
            $class_id      = intval($this->input->post('class_id'));
            $region_id     = intval($this->input->post('region_id'));
            $department_id = intval($this->input->post('department_id'));
            $source_id     = intval($this->input->post('source_id'));
            $gather_id     = intval($this->input->post('gather_id'));
            $invite_id     = intval($this->input->post('invite_id'));

           // $follow_up = intval($this->input->post('follow_up'));

            $lesson_name     = '';
            $group_name      = '';
            $class_name      = '';
            $region_name     = '';
            $department_name = '';
            $source_name     = '';
            $gather_name     = '';
            $invite_name     = '';

            if ($lesson_id)
            {
                $row         = $this->system_model->get_system_one('system_lesson',$lesson_id);
                $lesson_name = $row['name'];
            }

            if ($group_id)
            {
                $row        = $this->system_model->get_system_one('system_group',$group_id);
                $group_name = $row['name'];
            }

            if ($class_id)
            {
                $row        = $this->system_model->get_system_one('system_class',$class_id);
                $class_name = $row['name'];
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



            $data =  array(
                'name'            => trim($this->input->post('name')),
                'age'             => intval(trim($this->input->post('age'))),
                'age_extent'      => intval(trim($this->input->post('age_extent'))),
                'gender'          => $this->input->post('gender'),
                'tel'             => trim($this->input->post('mobile')),
                'mobile'          => trim($this->input->post('mobile')),
                'email'           => trim($this->input->post('email')),
                'lesson_id'       => $lesson_id,
                'lesson_name'     => $lesson_name,
                'group_id'        => $group_id,
                'group_name'      => $group_name,
                'class_id'        => $class_id,
                'class_name'      => $class_name,
                'address'         => trim($this->input->post('address')),
                'remark'          => trim($this->input->post('remark')),
                'region_id'       => $region_id,
                'region_name'     => $region_name,
                'region_remark'   => trim($this->input->post('region_remark')),
                'level'    => intval($this->input->post('level'))
            );

            if ($id==0)
            {
                $data['add_time']      = time();
                $data['add_user_id']   = intval($this->session->userdata('admin_user_id'));
                $data['add_user_name'] = $this->session->userdata('admin_user_name');
            }


            //收单日期
            if($this->input->post('info_date_time'))
            {
                $data['info_date']      = strtotime(date("Y-m-d",strtotime(trim($this->input->post('info_date_time')))));//只存日期不要时间
                $data['info_year']      = date("Y",$data['info_date']);//资源年份
                $data['info_month']     = date("n",$data['info_date']);//资源月份不带前导零
                $data['info_date_time'] = strtotime(trim($this->input->post('info_date_time')));
            }
            // 收单人gather_id
            if ($gather_id)
            {
                $data['gather_id']   = $gather_id;
                $data['gather_name'] = $gather_name;
            }
            //部门
            if ($department_id)
            {
                $data['department_id']   = $department_id;
                $data['department_name'] = $department_name;
            }
            //来源
            if ($source_id)
            {
                $data['source_id']   = $source_id;
                $data['source_name'] = $source_name;
            }
            //邀约人
            if ($invite_id)
            {
                $data['invite_id']   = $invite_id;
                $data['invite_name'] = $invite_name;
            }
            //资源类型
            if ($this->input->post('info_type'))
            {
                $data['info_type'] = intval($this->input->post('info_type'));
            }
            //资源关键词
            if ($this->input->post('info_keyword'))
            {
                $data['info_keyword'] = trim($this->input->post('info_keyword'));
            }
            //资源链接
            if ($this->input->post('info_link'))
            {
                $data['info_link'] = trim($this->input->post('info_link'));
            }
            //跟进状态 客服还是顾问
            if ($this->input->post('follow_up'))
            {
                $data['follow_up'] = trim($this->input->post('follow_up'));
            }


            $result = $this->info_model->set_info($id,$data);//如果是新插入将返回插入的id
            if ($result)
            {
                //***********************首次电访记录开始(这里只是首次添加资源时用到)******************
                $call_remark = trim($this->input->post('call_remark'));
                $info_id     = intval($result);
                if (!empty($call_remark))
                {

                    $call_data = array(
                        'info_id'       =>$info_id,
                        'call_year'     => date('Y',strtotime(trim($this->input->post('call_date')))),
                        'call_month'    => date('n',strtotime(trim($this->input->post('call_date')))),
                        'call_date'     =>strtotime(trim($this->input->post('call_date'))),
                        'call_remark'   =>trim($this->input->post('call_remark')),
                        'validity'      =>intval($this->input->post('validity')),
                        'add_user_id'   => intval($this->session->userdata('admin_user_id')),
                        'add_user_name' => $this->session->userdata('admin_user_name'),
                        'add_time'      =>time(),
                    );
                    $result = $this->global_model->set('info_call',0,$call_data);
                    //更新资源的有效性
                    if ($result)
                    {
                        $updata = array('validity' => intval($this->input->post('validity')));
                        $this->info_model->set_info($info_id,$updata);
                    }
                }

                //***********************首次电访记录结束*****************
                if ($id)
                {
                    $return_url = site_url('info/index/'.intval($this->session->userdata('info_page')));
                }
                else
                {
                    $return_url = site_url('info');
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

	//
	//删除资源
	public function del_info($id=0)
	{
		$id = intval($id);
		if ($id)
		{
		//	$result = $this->info_model->del_info($id); 原始 改变is_del字段
            $result = $this->global_model->del('info',$id);//真实删除
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


	//资源详细
	public function disp_info($id)
	{
		$this->load->helper('form');

		$data = array();
		$id = intval($id);
		if ($id)
		{
			$data['info_item']  = $info_item = $this->info_model->get_info_one($id);//资源详细
			$data['call']       = $this->global_model->get('info_call',array('info_id'=>$id),'*', "id desc");//电访记录
			$data['allocation'] = $this->global_model->get('info_allocation',array('info_id'=>$id),'*', "id desc");//分配记录
			$data['sms_log']    = $this->global_model->get('sms_log',array('to_mobile'=>$info_item['mobile']),'*', "id desc");//短信记录
			//如果是课程顾问，更新为已读
			if(intval($data['info_item']['adviser_id']) == intval($this->session->userdata('admin_user_id')) )
			{
				$this->global_model->set('info',$id,array('is_read' => 1));
			}

		}
        echo "<pre>";
        print_r($data);
        echo "</pre>";
        exit;
		$this->load->view('header');
		$this->load->view('info_disp',$data);
		$this->load->view('bottom');
	}



	/*
	*********************添加修改电话记录***************************************
	$id ,不为空是修改
	*/
	public function set_call($id=0)
	{
		$this->load->helper('form');
		$this->load->library('form_validation');

		$id = intval($id);
		$this->form_validation->set_rules('call_date','电访日期', 'required');
		$this->form_validation->set_rules('call_remark','电访详细','required');
		$this->form_validation->set_error_delimiters('<div class="alert alert-error">', '</div>');
		$action_name = ($id)? '修改' : '添加' ;
		$data = array();
		if ($this->form_validation->run() === FALSE )
		{
			//如果没有提交，显示提交页
			// if ($id)
			// {
			// 	// $data['lesson_item'] = $this->system_model->get_system_one('system_lesson',$id);
			// }
			// $this->load->view('header');
			// $this->load->view('',$data);
			// $this->load->view('bottom');
		}
		else
		{
			//提交页
			$info_id      = intval($this->input->post('info_id'));
			$info_status  = intval($this->input->post('info_status'));
			$info_status2 = 0;
			//查看状态是否重复，如果重复状态值为 110
			$where = "info_id = $info_id and info_status = $info_status";
			$row = $this->global_model->get('info_call',$where,'id', $order_by="id asc",1);
			if (!empty($row))
			{
				$info_status2 = $info_status;
				$info_status = 110;
			}

			$data =  array(
				'info_id'       => $info_id,
				'call_year'     => date('Y',strtotime(trim($this->input->post('call_date')))),
				'call_month'    => date('n',strtotime(trim($this->input->post('call_date')))),
				'call_date'     => strtotime(trim($this->input->post('call_date'))),
				'call_remark'   => $this->input->post('call_remark'),
				'validity'      => intval($this->input->post('validity')),
				'info_status'   => $info_status,
				'info_status2'  => $info_status2,
				'add_user_id'   => intval($this->session->userdata('admin_user_id')),
				'add_user_name' => $this->session->userdata('admin_user_name'),
				'add_time'      => time()

			);

			//系统提醒
			if (trim($this->input->post('alert_date')))
			{
				$data['alert_date'] = strtotime(trim($this->input->post('alert_date')));
				$data['is_alert']   = 0;
			}
			//+++++++++++++++++如果状态 没有承诺上门，直接到了 已安排体验65，已体验50，承诺交费70，交费80，定金90,则自动加承诺上门30
			if ($info_status == 65 || $info_status == 50 || $info_status == 70 || $info_status == 80 || $info_status == 90)
			{
				//检查是否存在30状态
				$where = "info_id = $info_id and info_status = 30";
				$row = $this->global_model->get('info_call',$where,'id', $order_by="id asc",1);
				if(empty($row))
				{
					$data['info_status'] = 30;
					$this->global_model->set('info_call',0,$data);
				}
				$data['info_status'] = $info_status;
			}
			//++++++++++++++++++++++++++

			//++++++++++++++如果状态 没有已体验，直接到了 承诺交费70，交费80，定金90 则自动加上已体验50+++++++++++++++++++++
			if ($info_status == 70 || $info_status == 80 || $info_status == 90)
			{
				//检查是否存在50状态
				$where = "info_id = $info_id and info_status = 50";
				$row = $this->global_model->get('info_call',$where,'id',$order_by="id asc",1);
				if (empty($row))
				{
					$data['info_status'] = 50;
					$this->global_model->set('info_call',0,$data);
				}
				$data['info_status'] = $info_status;
			}
			//+++++++++++++++++++++++++++++



			$result = $this->global_model->set('info_call',$id,$data);

			if ($result){
				//更新资源的有效性和状态
				$updata = array(
					'validity'           => intval($this->input->post('validity')),
					'info_status'        => intval($this->input->post('info_status')),
				);
				$tiyan_date = $this->input->post('tiyan_date');
				if (!empty($tiyan_date))
				{
					$updata['tiyan_date'] = strtotime($tiyan_date);
				}

				$updata['follow_up']  = $this->input->post('follow_up');

				$info_visit =  $this->input->post('info_visit');
				if (empty($info_visit))
				{
					$info_visit = 10;
				}
				$updata['info_visit'] = $info_visit;

                if ($updata['info_visit'] == 20)
                {
                    $updata['info_visit_time'] = strtotime(trim($this->input->post('info_visit_time')));
                }

                if ($updata['info_status']>45){
                    $post = $this->input->post();
                    //资源数据添加到到店表里面
                    //==================检查手机号是否重复开始========================
                    $is_before = 0;//是否往期
                    $visit_times = 1;//来访次数
                    $twice_visit_time = 0;
                    $zy = $this->db->query('select * from hmsypx_info where id = '.$post['info_id'].'')->row_array();
                    $tel = trim($zy['mobile']);
                    $result = $this->global_model->get('visit',"tel = '$tel'",'visit_times,first_visit_time,twice_visit_time,id', $order_by="id desc",1);
                    if (!empty($result)){
                        $id = $result['id'];
                        $is_before = 1;
                        $visit_times = $result['visit_times']+1;//来访次数
                        $twice_visit_time = $result['first_visit_time'];//初次来访日期
                    }else{
                        $id = '';
                    }
                    //==================检查手机号是否重复结束========================
                    $vist['is_before']              =   $is_before;
                    $vist['visit_times']            =   $visit_times;
                    $vist['twice_visit_time']       =   $twice_visit_time;
                    $vist['first_visit_time']       =   time();
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
                    //修改资源表是该数据不展示
                    $updata['is_goshop']  = 2;

                    if (($updata['info_status'] == 50) || ($updata['info_status'] == 57) || ($updata['info_status'] == 63) || ($updata['info_status'] == 70)){
                        if( ($updata['info_status'] == 50) || ($updata['info_status'] == 70) ){
                            $vist['is_tiyan']  = '1';
                        }else{
                            $vist['is_tiyan']  = '0';
                        }
                        $vist['is_order']    =   '0';
                        $this->db->trans_begin();
                        //两条sql 都执行失败
                        $result = $this->global_model->set('visit',$id,$vist);//如果是新插入将返回插入的id
                        // $result = $this->global_model->set('info',$post['info_id'],$goshop);//如果是新插入将返回插入的id
                        $this->info_model->set_info($info_id,$updata);
                        if ($this->db->trans_status() === false) {
                            $this->db->trans_rollback();
                        } else {
                            $this->db->trans_commit();
                        }
                        if($result){
                            $data['message']    = $action_name.'成功，正在进入列表页……';
                            $data['return_url'] = site_url('visit/index');
                            $this->load->view('success', $data);
                        }
                    }
                    if ($updata['info_status'] == 80){//当选择的交费并提交信息
                        $vist['is_order']               =   '1';
                        $vist['order_time']             =   time();
                        //取文件信息
                        $arr = $_FILES["file"];
                        //加限制条件 1.文件类型 2.文件大小 3.保存的文件名不重复
                        if(($arr["type"]=="image/jpeg" || $arr["type"]=="image/png" ) && $arr["size"]<10241000 ){
                            //临时文件的路径
                            $arr["tmp_name"];
                            $type = explode('.',$arr['name'])[(count(explode('.', $arr['name']))-1)];
                            //上传的文件存放的位置
                            //避免文件重复: 
                            //1.加时间戳.time()加用户名.$uid或者加.date('YmdHis')
                            //2.类似网盘，使用文件夹来防止重复
                            $filename = "./uploads/".date('YmdHis').rand(1000,9999).'.'.$type;
                            //保存之前判断该文件是否存在
                            if(file_exists($filename)){
                                $data['message']    = $action_name.'文件已存在';
                                $data['return_url'] = site_url('info/disp_info/'.$post['info_id']);
                                $this->load->view('error', $data);
                            }else{
                                //中文名的文件出现问题，所以需要转换编码格式
                                $filename = iconv("UTF-8","gb2312",$filename);
                                //移动临时文件到上传的文件存放的位置（核心代码）
                                //括号里：1.临时文件的路径, 2.存放的路径
                                $sc = move_uploaded_file($arr["tmp_name"],$filename);
                            }
                        }else{
                            $data['message']    = $action_name.'上传的文件大小或类型不符';
                            $data['return_url'] = site_url('info/disp_info/'.$post['info_id']);
                            $this->load->view('error', $data);
                        }
                        if($sc){
                            $imgurl = $filename;
                        }
                        //把资源转为成学员
                        //=============根据手机号判断是否已设置为学员=================
                        $mobile = trim($zy['mobile']);
                        $student_info = $this->db->query('select * from hmsypx_student where mobile = '.$mobile.'')->row_array();
                        if (!empty($student_info)){
                            $student_id = $student_info['id'];
                            $this->db->trans_begin();
                            //两条sql 都执行失败
                            $result = $this->global_model->set('visit',$id,$vist);//如果是新插入将返回插入的id
                            // $result = $this->global_model->set('info',$post['info_id'],$goshop);//如果是新插入将返回插入的id
                            $this->info_model->set_info($info_id,$updata);
                            if ($this->db->trans_status() === false) {
                                $this->db->trans_rollback();
                            } else {
                                $this->db->trans_commit();
                            }
                        }else{
                            $student =  array(
                                'c_no'                => trim($this->input->post('c_no')),
                                'info_id'             => $info_id,
                                'name'                => trim($zy['name']),
                                'gender'              => trim($zy['gender']),
                                'tel'                 => trim($zy['tel']),
                                'mobile'              => $mobile,
                                'email'               => trim($zy['email']),
                                'address'             => trim($zy['address']),
                                'group_id'            => $zy['group_id'],
                                'group_name'          => $zy['group_name'],
                                'region_id'           => $zy['region_id'],
                                'region_name'         => $zy['region_name'],
                                'department_id'       => $zy['department_id'],
                                'department_name'     => $zy['department_name'],
                                'source_id'           => $zy['source_id'],
                                'source_name'         => $zy['source_name'],
                                'gather_id'           => $zy['gather_id'],
                                'gather_name'         => $zy['gather_name'],
                                'invite_id'           => $zy['invite_id'],
                                'invite_name'         => $zy['invite_name'],
                                'birthday'            => 0,  
                                // 'sign_id'             => $post['responsible_id'],
                                // 'sign_name'           => $sign_name,
                                'adviser_id'          => $zy['adviser_id'],
                                'adviser_name'        => $zy['adviser_name'],
                                'campus_id'           => $zy['campus_id'],
                                'campus_name'         => $zy['campus_name'],
                                'entry_year'          => date("Y",time()),
                                'entry_month'         => date("n",time()),
                                'entry_date'          => time(),
                                // 'student_status'      => intval($this->input->post('student_status')),
                                'add_user_id'         => intval($this->session->userdata('admin_user_id')),
                                'add_user_name'       => $this->session->userdata('admin_user_name'),
                                'total_fees'          => 0,
                                'shishou_fees'        => 0,
                                'youhui_fees'         => 0,
                                'qian_fees'           => 0,
                            );
                            $student['pass'] = md5('123456'); 
                            $this->db->trans_begin();
                            //三条sql 都执行失败
                            $student_id = $this->global_model->set('student',0,$student);//如果是新插入将返回插入的id
                            $result = $this->global_model->set('visit',$id,$vist);//如果是新插入将返回插入的id
                            $this->info_model->set_info($info_id,$updata);
                            // $result = $this->global_model->set('info',$post['info_id'],$goshop);//如果是新插入将返回插入的id
                            if ($this->db->trans_status() === false) {
                                $this->db->trans_rollback();
                            } else {
                                $this->db->trans_commit();
                            }
                        }
                        if($student_id>0){
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
                            if (intval($data['qian_fees'])){
                                $data['student_status'] = 4;
                            }
                            unset($row);
                            $result = $this->global_model->set('student',$student_id,$data);//更新学员信息
                            if ($result){
                                //填加课程
                                $sgid_array = array();//记录学员所报班级id
                                $sg_id      = 0;//记录学员所报班级id
                                $grade      = array($this->input->post('grade'));
                                if (!empty($grade)){
                                    foreach ($grade as $grade_id) {
                                        //取班级总课次
                                        $total_class_times = intval($this->input->post('total_class_times'));
                                        //取班级信息
                                        $grade_item = $this->global_model->get_one('grade',$grade_id,'lesson_id,class_id,grade_teacher_id,grade_teacher_name');
                                        if (!empty($grade_item['lesson_id'])){
                                            $lesson_id = $grade_item['lesson_id'];
                                            $ln = $this->global_model->get_one('system_lesson',$lesson_id,'name');
                                            $lesson_name = $ln['name'];
                                        }else{
                                            $lesson_id = 0;
                                            $lesson_name = '';
                                        }
                                        if (!empty($grade_item['class_id'])){
                                            $class_id = $grade_item['class_id'];
                                            $cn = $this->global_model->get_one('system_class',$class_id,'name');
                                            $class_name = $cn['name'];
                                        }else{
                                            $class_id = 0;
                                            $class_name = '';
                                        }
                                        $tiyan_teacher_id = $this->input->post('tiyan_teacher_id');
                                        if ($tiyan_teacher_id){
                                            $tn = $this->global_model->get_one('admin',$tiyan_teacher_id,'full_name');
                                            $tiyan_teacher_name = $tn['full_name'];
                                        }else{
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
                                            'img_url'            => $imgurl,
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
                                if ($responsible_id){
                                    $row = $this->global_model->get_one('admin',$responsible_id,'full_name');
                                    $responsible_name = $row['full_name'];
                                }
                                $add_time = trim($this->input->post('add_time'));
                                if (empty($add_time)){
                                    $add_time = date("Y-m-d H:i:s");
                                }
                                /*交费渠道*/
                                $source_id   = intval($this->input->post('source_id'));
                                $source_name = '';
                                if ($source_id){
                                    $row = $this->global_model->get_one('system_source',$source_id,'name');
                                    $source_name = $row['name'];
                                }
                                $fees = array(
                                    'student_id'       => $student_id,
                                    'sg_id'            => $sg_id,
                                    'campus_id'        => $zy['campus_id'],
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
                                    'add_time'         => time(),
                                );
                                $this->global_model->set('student_fees',0,$fees);
                                unset($fess);

                                //**********如果是从资源导入，并第一次交费时，添加资源操作记录 开始**************
                                //如果是从资源导入 改变资源状态为80已交费 和填加操作记录（电访记录）
                                if($info_id){
                                    //检测是否是第一次交费
                                    $jiaofei = $this->global_model->count_all('student_fees','student_id = '.$student_id);
                                    if ($jiaofei == 1){
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
                            }
                        }
                        $data['message']    = $action_name.'成功，正在进入列表页……';
                        $data['return_url'] = site_url('student/index');
                        $this->load->view('success', $data);
                    }
                }else{
    				$this->info_model->set_info($info_id,$updata);


    				/*===============计算下次回访时间 回访次数 开始================
    				* 已体验到资源回访定了3个回访时间，第一次在体验后的2天，第二次在体验后的5天之内，第三次在体验后的15天以内 超过三次回收给校长
    				* 非已体验资源7天一次回访，超3次回收给校长
    				*/

    				/*
    				$admin_user_id = intval($this->session->userdata('admin_user_id'));
    				$user_department = $this->system_model->get_user_department($admin_user_id);
    				if ($user_department==3)//课程顾问
    				{
    					//查询是否已体验
    					$adviser_id = 9999;
    					$rs_call    = $this->global_model->get('info_call',"info_id = $info_id and info_status = 50",'*', $order_by="id asc",1);
    					$rs_jiaofei = $this->global_model->get('info_call',"info_id = $info_id and (info_status = 80 or info_status = 90)",'info_id', $order_by="id asc",1);
    					$rs_info    = $this->global_model->get_one('info',$info_id,'*');
    					$gw_tiyan_time = 0; //体验时间

    					//


    					//已体验 没交费
    					if ((!empty($rs_call) || $rs_info['info_visit']==20) && empty($rs_jiaofei) )
    					{

    						$gw_tiyan_time =  $rs_info['gw_tiyan_time'];
    						if (empty($gw_tiyan_time))
    						{
    							$gw_tiyan_time = strtotime(date("Y-m-d"));
    						}



    						switch ($rs_info['gw_callback_times'])
    						{
    							case 0:
    								$gw_callback_date  = strtotime(date("Y-m-d",strtotime("+2 day")));//下次回访日期
    								$gw_callback_times = $rs_info['gw_callback_times']+1;
    								$gw_tiyan_time     = strtotime(date("Y-m-d"));
    								break;
    							case 1:
    								$gw_callback_date  = mktime(0,0,0,date("m",$gw_tiyan_time),date("d",$gw_tiyan_time)+5,date("Y",$gw_tiyan_time));//hour,minute,second,month,day,year
    								$gw_callback_times = $rs_info['gw_callback_times']+1;
    								break;
    							case 2:
    								$gw_callback_date  = mktime(0,0,0,date("m",$gw_tiyan_time),date("d",$gw_tiyan_time)+15,date("Y",$gw_tiyan_time));//hour,minute,second,month,day,year
    								$gw_callback_times = $rs_info['gw_callback_times']+1;
    								break;

    							default:
    								//校长回收
    								$gw_is_schoolmaster = 1;
    								$gw_callback_date   = 0;
    								$adviser_id         = 0;
    								$adviser_name       = '';
    								$gw_callback_times  = 0;
    								break;
    						}


    					}
    					else
    					{
    						//已交费 定金
    						if ($rs_info['info_status'] == 80 || $rs_info['info_status'] == 90)
    						{
    							$gw_callback_date   = 0;
    							$gw_callback_times  = 0;
    						}
    						else
    						{
    							//未体验
    							$gw_callback_date = $rs_info['gw_callback_date'];
    							if (empty($gw_callback_date))
    							{
    								$gw_callback_date  = strtotime(date("Y-m-d",strtotime("+7 day")));//下次回访日期
    							}

    							switch ($rs_info['gw_callback_times'])
    							{
    								case 0:
    									$gw_callback_date  = strtotime(date("Y-m-d",strtotime("+7 day")));//下次回访日期
    									$gw_callback_times = $rs_info['gw_callback_times']+1;
    									break;
    								case 1:
    									$gw_callback_date  = mktime(0,0,0,date("m",$gw_callback_date),date("d",$gw_callback_date)+7,date("Y",$gw_callback_date));//hour,minute,second,month,day,year
    									$gw_callback_times = $rs_info['gw_callback_times']+1;
    									break;
    								case 2:
    									$gw_callback_date  = mktime(0,0,0,date("m",$gw_callback_date),date("d",$gw_callback_date)+7,date("Y",$gw_callback_date));//hour,minute,second,month,day,year
    									$gw_callback_times = $rs_info['gw_callback_times']+1;
    									break;

    								default:
    									//校长回收
    									$gw_is_schoolmaster = 1;
    									$gw_callback_date   = 0;
    									$adviser_id         = 0;
    									$adviser_name       = '';
    									$gw_callback_times  = 0;
    									break;
    							}
    						}

    					}

    					$updata = array(
    						'gw_callback_date'   => $gw_callback_date,
    						'gw_callback_times'  => $gw_callback_times,
    					);

    					if ($gw_tiyan_time)
    					{
    						$updata['gw_tiyan_time'] = $gw_tiyan_time;
    					}

    					if (!empty($gw_is_schoolmaster))
    					{
    						$updata['gw_is_schoolmaster'] = $gw_is_schoolmaster;
    					}
    					if (empty($adviser_id))
    					{
    						$updata['adviser_id']   = 0;
    						$updata['adviser_name'] = '';
    					}

    					$this->info_model->set_info($info_id,$updata);
    				}
    				*/

    				/*===============计算下次回访时间 回访次数 结束================*/

    				$data['message']    = $action_name.'成功，正在进入列表页……';
    				$data['return_url'] = site_url('info/disp_info/'.$info_id);
    				$this->load->view('success', $data);
                    
                }
			}else{
				$data['message'] = $return_str.$action_name.'失败，正在返回……';
				$data['return_url'] = site_url('info/disp_info/'.$info_id);
				$this->load->view('error', $data);
			}
		}
	}


	/*
	搜索页
	*/
	public function so_info()
	{
		$this->load->helper('form');
		$this->load->library('form_validation');
		$this->form_validation->set_rules('so','安全验证', 'required');

		$data = array();
		if ($this->form_validation->run() === FALSE )
		{
			//如果没有提交，显示提交页
			$data['lesson']     = $this->system_model->get_system('system_lesson',array('status' => 1));//取课程  只显示启用的
			$data['group']      = $this->system_model->get_system('system_group',array('status' => 1));//学习对象
			$data['class']      = $this->system_model->get_system('system_class',array('status' => 1));//班型
			$data['source']     = $this->system_model->get_source(0,array('status' => 1));//资源来源
			$data['campus']     = $this->system_model->get_system('system_campus',array('status' => 1));//校区
			$data['department'] = $this->system_model->get_department(0,array('status' => 1));//部门
			$data['region']     = $this->system_model->get_region(0,array('status' => 1));//地区
			$data['user']       = $this->global_model->get('admin',array('status > ' => 0),'id,full_name,full_name_index','full_name asc');//取系统在职用户，用于 收单人，邀约人

			$where_user      = "status > 0 and (department_path like '%,3,%' or department_path like '%,7,%')";
			$data['adviser'] = $this->global_model->get('admin',$where_user,'id,full_name,full_name_index','full_name asc');//取系统在职用户，课程顾门
			$this->load->view('header');
			$this->load->view('info_so',$data);
			$this->load->view('bottom');
		}
		else
		{
			//提交页,处理查询条件

			$history_sql = array();//保存历史查询条件，存入session
			$where       = "";
			//日期开始 ,开始和结束日期全选时按时间段查询，只选一项查所选日期当天的
			$start_date = trim($this->input->post('start_date'));
			$end_date   = trim($this->input->post('end_date'));
			if (!empty($start_date) && !empty($end_date) )
			{
				$where .= " and info_date >= ".strtotime($start_date)." and info_date <= ".strtotime($end_date);

			}
			else
			{
				if (!empty($start_date))
				{
					$where .=" and info_date = ".strtotime($start_date);
				}
				if (!empty($end_date))
				{
					$where .=" and info_date = ".strtotime($end_date);
				}
			}
			$history_sql['start_date'] = $start_date;
			$history_sql['end_date']   = $end_date;



			//日期结束

			//学习对象
			$group_id = intval($this->input->post('group_id'));
			if ($group_id)
			{
				$where .=" and group_id = ".$group_id;
			}

			//校区
			$campus = $this->input->post('campus_id');
			if (is_array($campus))
			{
				$campus_id_str = implode(',', $campus);
				$where .= " and campus_id in ($campus_id_str)";
				$history_sql['campus'] = $campus;
			}
			else
			{
				$campus_id = intval($campus);
				if ($campus_id)
				{
					$where .=" and campus_id = ".$campus_id;
				}
			}



			//部门
			$department_id = intval($this->input->post('department_id'));
			if ($department_id)
			{
				$where .=" and department_id = ".$department_id;
			}

			//课程
			$lesson_id = intval($this->input->post('lesson_id'));
			if ($lesson_id)
			{
				$where .=" and lesson_id = ".$lesson_id;
			}


			//班型
			$class_id = intval($this->input->post('class_id'));
			if ($class_id)
			{
				$where .=" and class_id = ".$class_id;
			}

			//地区
			$region = $this->input->post('region_id');
			if (is_array($region))
			{
				$region_id_str = implode(',', $region);
				$where .=" and region_id in ($region_id_str)";
				$history_sql['region'] = $region;
			}
			else
			{
				$region_id = intval($region);
				if ($region_id)
				{
					$where .=" and region_id = ".$region_id;
				}
			}

            //资源等级
            $level = $this->input->post('level');
            if (is_array($level))
            {
                $level_str = implode(',', $level);
                $where .=" and level in ($level_str)";
                $history_sql['level'] = $level;
            }
            else
            {
                $level_id = intval($level);
                if ($level_id)
                {
                    $where .=" and level = ".$level_id;
                }
            }

			//来源
			$source_id = intval($this->input->post('source_id'));
			if ($source_id)
			{
				$nid = $this->system_model->get_source_nextid($source_id);
				$nid = $source_id.$nid;
				$where .=" and source_id in ($nid) ";
			}

			//收单人
			$gather = $this->input->post('gather_id');
			if (is_array($gather))
			{
				$gather_id_str = implode(',', $gather);
				$where .= " and gather_id in ($gather_id_str)";
				$history_sql['gather'] = $gather;
			}
			else
			{
				$gather_id = intval($gather);
				if ($gather_id)
				{
					$where .=" and gather_id = ".$gather_id;
				}
			}



			//邀约人
			$invite_id = intval($this->input->post('invite_id'));
			if ($invite_id)
			{
				$where .=" and invite_id = ".$invite_id;
			}

			//课程顾问
			$adviser = $this->input->post('adviser_id');
			if (is_array($adviser))
			{
				$adviser_id_str = implode(',', $adviser);
				$where .= " and adviser_id in ($adviser_id_str)";
				$history_sql['adviser'] = $adviser;
			}
			else
			{
				$adviser_id = intval($adviser);
				if ($adviser_id)
				{
					$where .=" and adviser_id = $adviser_id";
				}
			}


			//资源历史状态
			$info_status_history = intval($this->input->post('info_status_history'));
			if($info_status_history)
			{
				$where .= ' and id in (select info_id from hmsypx_info_call where info_status = '.$info_status_history.')';
			}

			//资源当前状态
			$info_status = $this->input->post('info_status');
			if (is_array($info_status))
			{
				$info_status_id_str = implode(',',$info_status);
				$where .= " and info_status in ($info_status_id_str)";
				$history_sql['info_status'] = $info_status;
			}
			else
			{
				$info_status_id = intval($info_status);
				if ($info_status_id)
				{
					$where .= " and info_status = $info_status_id";
				}

			}


			//跟进人员
			$follow_up = $this->input->post('follow_up');
			if (is_array($follow_up))
			{
				$follow_up_str = implode(',', $follow_up);
				$where .= " and follow_up in ($follow_up_str)";
				$history_sql['follow_up'] = $follow_up;
			}
			else
			{
				$follow_up_id = intval($follow_up);
				if ($follow_up_id)
				{
					$where .= " and follow_up = $follow_up_id";
				}

			}



			//姓名
			$name = trim($this->input->post('name'));
			if (!empty($name))
			{
				$where .= " and name like '%$name%'";
				$history_sql['name'] = $name;
			}

			//电话
			$tel = trim($this->input->post('tel'));
			if (!empty($tel))
			{
				$where .= " and ( tel like '%$tel%' or mobile like '%$tel%' )";
				$history_sql['tel'] = $tel;
			}


			//id
			$info_id = intval($this->input->post('info_id'));
			if ($info_id)
			{
				$where .= " and id =$info_id ";
				$history_sql['info_id'] = $info_id;
			}

			//是否上门
			$info_visit = $this->input->post('info_visit');
			if (is_array($info_visit))
			{
				$info_visit_id_str = implode(',', $info_visit);
				$where .= " and info_visit in ($info_visit_id_str)";
				$history_sql['info_visit'] = $info_visit;
			}
			else
			{
				$info_visit_id = intval($info_visit);
				if ($info_visit_id)
				{
					$where .= " and info_visit = $info_visit_id";
				}
			}


			//承诺上门时间
			$start_tiyan_date = trim($this->input->post('cn_start_tiyan_date'));
			$end_tiyan_date   = trim($this->input->post('cn_end_tiyan_date'));
			if (!empty($start_tiyan_date) && !empty($end_tiyan_date))
			{
				$start_tiyan_date = strtotime($start_tiyan_date);//当天开始时间戳
				$end_tiyan_date   = strtotime($end_tiyan_date)+86399;//当天结束时间戳
				$where .= " and tiyan_date BETWEEN $start_tiyan_date AND $end_tiyan_date ";
			}
			else
			{
				if (!empty($start_tiyan_date))
				{
					$start_tiyan_date = strtotime($start_tiyan_date);//当天开始时间戳
					$end_tiyan_date   = $start_tiyan_date+86399;//当天结束时间戳
					$where .= " and tiyan_date BETWEEN $start_tiyan_date AND $end_tiyan_date ";
				}
			}



			//上门时间
			$start_tiyan_date = trim($this->input->post('start_tiyan_date'));
			$end_tiyan_date   = trim($this->input->post('end_tiyan_date'));
			if (!empty($start_tiyan_date) && !empty($end_tiyan_date))
			{
				$start_tiyan_date = strtotime($start_tiyan_date);//当天开始时间戳
				$end_tiyan_date   = strtotime($end_tiyan_date)+86399;//当天结束时间戳
				//$where .= " and id in (select info_id from hmsypx_info_call where ( info_status = 50 or info_status = 57 ) and call_date BETWEEN $start_tiyan_date AND $end_tiyan_date )";
				$where .= " and info_visit_time BETWEEN $start_tiyan_date AND $end_tiyan_date ";
			}
			else
			{
				if (!empty($start_tiyan_date))
				{
					$start_tiyan_date = strtotime($start_tiyan_date);//当天开始时间戳
					$end_tiyan_date   = $start_tiyan_date+86399;//当天结束时间戳
					//$where .= " and id in (select info_id from hmsypx_info_call where ( info_status = 50 or info_status = 57 ) and call_date BETWEEN $start_tiyan_date AND $end_tiyan_date )";
					$where .= " and info_visit_time BETWEEN $start_tiyan_date AND $end_tiyan_date ";
				}
			}

			//未联系资源 如7天未联系，输入7

			$wlx_day = intval($this->input->post("wlx_day"));
			if ($wlx_day)
			{
				$now       = time();
				$start_day = $now-86400;
				$end_day   = $now-86400*$wlx_day;

				$where .= ' and id not in (select DISTINCT info_id from hmsypx_info_call where  add_time > '.$end_day.' order by add_time desc)';
				$history_sql['wlx_day'] = $wlx_day;
			}






			//查询条件存入session
			$this->session->set_userdata('info_query',$where);
			$this->session->set_userdata('history_sql',$history_sql);
			$data['message']    = '正在查询，请稍侯……';
			$data['return_url'] = site_url('close_window/index/info');

			$re       = $this->agent->referrer();
			$referrer = explode("/",$re);
			//print_r($referrer);
			if (isset($referrer['5']) && $referrer['5'] == 'so_info')
			{
				$this->load->view('success', $data);
			}
			else
			{
				redirect('info');
			}


		}
	}


	/*
	分配校区
	$id: 资源id
	@ $adviser_id 如果顾问id为1000 为顾问自己分配，先见先得。
	@ $reset = 1 时为重新分配

	*/
	public function to_campus($id=0, $reset=0)
	{
		//检查权限
		$this->my_permission->check_per(',0_4,','分配资源');
		$this->load->model('email_model');
		$this->load->helper('form');
		$this->load->library('form_validation');
		$admin_user_id = intval($this->session->userdata('admin_user_id'));
		$fz_campus_id    = $this->session->userdata('fz_campus_id');//负责校区，多个用逗号分开
		$id = intval($id);
		$this->form_validation->set_rules('campus_id','分配校区', 'required');
		$this->form_validation->set_error_delimiters('<div class="alert alert-error">', '</div>');
		$data = array();

		$data['reset'] = intval($reset);
		$data['admin_user_id'] = $admin_user_id;
		if ($this->form_validation->run() === FALSE )
		{
			//如果没有提交，显示提交页
			$data['campus']    = $this->system_model->get_system('system_campus',array('status' => 1));//校区
//			$data['user']      = $this->global_model->get('admin',array('status > ' => 0,'department_path like' =>'%,3,%'),'id,full_name_index,full_name','full_name asc');//取系统在职用户，课程顾门

            $where          = "status > 0 and (department_path like '%,3,%' or department_path like '%,7,%')";
            $data['user']   = $this->global_model->get('admin',$where,'id,full_name_index,full_name','full_name asc');//取系统在职用户，课程顾问

            $data['info_item'] = $this->info_model->get_info_one($id);//资源详细
			//查询当前用户所在部门id
			$data['user_department'] = $this->system_model->get_user_department($this->session->userdata('admin_user_id'));

			$data['info_id']   = $id;
			$this->load->view('header');
			$this->load->view('info_to_campus',$data);
			$this->load->view('bottom');
		}
		else
		{
			//提交页
			$is_ok        = FALSE;
			$is_send_mail = TRUE;//是否发邮件
			$message      = "分配成功，正在进入列表页……";
			$info_id      = intval($this->input->post('info_id'));//资源id



			$campus_id    = intval($this->input->post('campus_id'));//分配校区id
			//$adviser_id   = intval($this->input->post('adviser_id'));//课程顾问id
            $adviser_id=0;
            $add_user_id2 =  $this->input->post('add_user_id');//新顾问id

//echo "add_user_id2-=".$add_user_id2."pp".var_dump($add_user_id2);exit;
            //根据来源渠道 平均分配 开始
            $infoitem = $this->info_model->get_info_one($info_id);//资源详细
            $souce_pid=$this->system_model->get_source_pid($infoitem['source_id']);//获取1级来源ID
            $souce_pid2=$this->system_model->get_source_nextid($souce_pid);//获取这个大类来源下所有子ID；
            $souce_pid2=$souce_pid.$souce_pid2;
            if($add_user_id2){ //顾问平均分配
               // $adviser_id=$this->info_model->getone_adviser2($campus_id,$add_user_id2);
                $adviser_id=$this->info_model->getone_adviser3($campus_id,$add_user_id2,$souce_pid2); //根据大类平均分配
            }

			//查询当前用户所在部门id
			$user_department = $this->system_model->get_user_department($this->session->userdata('admin_user_id'));


			//校区名称
			if ($campus_id)
			{
				$row = $this->global_model->get_one('system_campus',$campus_id,'name');
				$campus_name = $row['name'];
			}

			//**************************课程顾问分配，如果传入顾问id，直接分给指定的顾问不计分配数量，否则找出本月分配最少的顾问分配（自动分配只分给有自动接收资源的在职人员，不包括实习和兼职），
			$year    = date("Y");//当前年
			$month   = date("m");//当前月
      //     echo "adviser_id-=".$adviser_id."tt";
			if ($adviser_id)
			{
				//如果为1000 顾问自己分配
				if ($adviser_id == 1000)
				{
					$adviser_id   = 0;
					$adviser_name = '';
					$is_send_mail = FALSE;
                    $is_ok        = TRUE;
				}
				else
				{
   					//课程顾问名称
					$row               = $this->global_model->get_one('admin',$adviser_id,'full_name,campus_id,email');
					$adviser_name      = $row['full_name'];
					$adviser_campus_id = $row['campus_id'].','.$fz_campus_id.',';
					$adviser_email     = $row['email'];//顾问邮件，发子邮件用
					//如果课程顾问所属校区与选择的校区不一至，不分配。




					//if ($campus_id == $adviser_campus_id)
					if (stristr($adviser_campus_id, $campus_id.','))
					{
						//检查是否有分配数据记录，存在加1，不存在添加
						$row = $this->global_model->get('info_allocation_num',array( 'campus_id' => $campus_id, 'adviser_id' =>$adviser_id, 'year' => $year, 'month' => $month),'id,info_num','',1);
						if (!empty($row))
						{
							$info_num = $row['info_num']+1;
							$id       = $row['id'];
							//更新分配总数 目前不需计数，如果需要 打开即可
							// $this->global_model->set('info_allocation_num',$id,array('info_num' => $info_num));
						}
						else
						{
							//添加
							$data = array(
								'year'       => $year,
								'month'      => $month,
								'adviser_id' => $adviser_id,
								'campus_id'  => $campus_id,
								'info_num'   => 1
							);
							$this->global_model->set('info_allocation_num',0,$data);
						}
                        $is_ok        = TRUE;
					}
					else
					{
						$is_ok = FALSE;
						$message    = $adviser_name." 不属于 $campus_name ，不能分配 。正在进入列表页……";
					}
				}


			}
			else
			{

                $is_ok = FALSE;
                $message =$campus_name.' 没有找到 课程顾问！正在返回……';
			}
			//***************************************
			if ($is_ok)
			{
				$info_data = array(
					'campus_id'          => $campus_id,
					'campus_name'        => $campus_name,
					'adviser_id'         => $adviser_id,
					'adviser_name'       => $adviser_name,
					//'info_status'      => 10,
                    'follow_up'      => 20,
					'is_read'            => 0,
					'gw_callback_date'   => strtotime(date("Y-m-d",strtotime("+1 day"))),//下次回访日期,
					'gw_callback_times'  => 0,
					'gw_is_schoolmaster' => 0,

				);
				//更新资源所属校区和状态
				$result = $this->info_model->set_info($info_id,$info_data);
				if ($result)
				{
					//添加分配记录
					$data = array(
						'info_id'        => $info_id,
						'from_user_id'   => intval($this->session->userdata('admin_user_id')),
						'from_user_name' => $this->session->userdata('admin_user_name'),
						'to_user_id'     => $adviser_id,
						'to_user_name'   => $adviser_name,
						'campus_id'      => $campus_id,
						'campus_name'    => $campus_name,
						'remark'         => trim($this->input->post('remark')),
						'to_time'        => time(),
					);
					$result = $this->global_model->set('info_allocation',0,$data);
				}
				//发送邮件通知课程顾问
                /*
				if ($result && $is_send_mail)
				{
					//取资源信息
					$mail_info   = $this->global_model->get_one('info',$info_id);
					$mail_gender = ($mail_info['gender'])? '男' : '女' ;

					//资源年龄区间
					$info_age = array();
					$info_age['0']  = '';
					$info_age['10'] = '学龄前';
					$info_age['20'] = '小学';
					$info_age['30'] = '中学';
					$info_age['40'] = '20-40';
					$info_age['50'] = '40以上';
					$age = '';
					if ($mail_info['age'])
					{
						$age = $mail_info['age'];
					}
					$age .=' '.$info_age[$mail_info['age_extent']];

					//电访记录
					$row = $this->global_model->get('info_call',array('info_id'=>$info_id),'call_date,call_remark', "id desc");//电访记录
					$mail_call = '';
					foreach ($row as $key => $r) {
						$mail_call .= "<div style='color:#333; border-top:#999 dashed 1px; padding:5px;'><b style='font-size:12px'>日期：".date('Y-m-d',$r['call_date'])."</b><br>".nl2br($r['call_remark'])."</div>";
					}
					unset($row);

					$from_user_name = $this->session->userdata('admin_user_name');
					$mail_date  = date("Y-m-d H:i:s");
					$mail_to    = $adviser_email;
					$mail_title = '习墨去-教学系统 新资源通知';
					$mail_body  = "<div style='font-size:14px; color:#000; line-height: 2;padding:5px;' >
					<div>你好，<b>$adviser_name </b></div>
					<div>$from_user_name 于 $mail_date 提供一个新的资源信息，如下：</div>
					<div><b>姓名：</b>$mail_info[name] </div>
					<div><b>年龄：</b>$age </div>
					<div><b>性别：</b>$mail_gender</div>
					<div><b>座机：</b><a href='tel:$mail_info[tel]'>$mail_info[tel]</a></div>
					<div><b>手机：</b><a href='tel:$mail_info[mobile]'>$mail_info[mobile]</a></div>
					<div><b>意向：</b>$mail_info[lesson_name] / $mail_info[group_name] / $mail_info[class_name]</div>
					<div><b>备注：</b>$mail_info[remark]</div>
					<div><b>地区：</b>$mail_info[region_name]</div>
					<div><b>位置：</b>$mail_info[region_remark]</div>
					<hr>
					<div><b>电话记录</b></div>
					$mail_call
					<hr>
					<div style='line-height:normal;'><img src='http://www.ybyhpx.com/logo_120.png'><br><p>习墨去-教学系统<br>$mail_date</p></div>
					</div>
					";

					$this->email_model->send_mail($mail_to,$mail_title,$mail_body);//发邮件
				}
*/
			}

			//显示处理结果
			if ($is_ok)
			{
				$data['message']    = $message;
				$data['return_url'] = site_url('close_window');
				$this->load->view('success', $data);
			}
			else
			{
				$data['message']    = $message;
				$data['return_url'] = site_url('close_window');
				$this->load->view('error', $data);
			}
		}
	}


	/*
	资源交接
	$info_id 多个用下划线分开
	*/
	public function edit_add_user($info_id='')
	{
		$this->my_permission->check_per(',0_7,','客服交接资源');
		$campus_id = intval($this->session->userdata('campus_id'));
		$this->load->helper('form');
		$this->load->library('form_validation');
		$this->form_validation->set_rules('add_user_id','接收者', 'required');
		$this->form_validation->set_rules('info_id','请选择资源', 'required');
		$admin_user_id = intval($this->session->userdata('admin_user_id'));
		$data            = array();
		$info_id = rtrim($info_id,'_');//去除最后的下划线
		$info_id = ltrim($info_id,'0_');//去除前边的0和下划线
		$data['info_id'] = $info_id;

		if ($this->form_validation->run() === FALSE )
		{
			//如果没有提交，显示提交页

			//查询当前用户所在部门，只能交接给本部门
			$user_department = $this->system_model->get_user_department($admin_user_id);

			//如果是只能看本校信息，只调用要校人员
			if ($this->my_permission->permi(',0_9,'))
			{
				$data['user'] = $this->global_model->get('admin',array('campus_id' => $campus_id,'id !=' => $admin_user_id,'status > ' => 0,'department_path like' => "%,$user_department,%"),'id,full_name','full_name asc');//取系统在职用户，用于 收单人，邀约人
			}
			else
			{
				$data['user'] = $this->global_model->get('admin',array('id !=' => $admin_user_id,'status > ' => 0,'department_path like' => "%,$user_department,%"),'id,full_name','full_name asc');//取系统在职用户，用于 收单人，邀约人
			}

			$this->load->view('header');
			$this->load->view('info_edit_add',$data);
			$this->load->view('bottom');
		}
		else
		{
			//提交页,处理查询条件
			$add_user_id = intval($this->input->post('add_user_id'));//新填加者id
			if ($add_user_id)
			{
				$row           = $this->global_model->get_one('admin',$add_user_id,'full_name');
				$add_user_name = $row['full_name'];
			}


			$info_id = $this->input->post('info_id');
			$info_id = str_replace('_',',',$info_id);
			$update  = array('add_user_id' => $add_user_id,'add_user_name' => $add_user_name);

			$result  = $this->global_model->update ('info',"add_user_id = $admin_user_id and id in ($info_id)",$update);//只有自己名下的才能交接

			if ($result)
			{
				//添加分配记录
				$data       = array();
				$info_array = explode(",",$info_id);
				foreach ($info_array as $info) {
					$data[] = array(
						'info_id'        => $info,
						'from_user_id'   => intval($this->session->userdata('admin_user_id')),
						'from_user_name' => $this->session->userdata('admin_user_name'),
						'to_user_id'     => $add_user_id,
						'to_user_name'   => $add_user_name,
						'campus_id'      => 0,
						'campus_name'    => '',
						'remark'         => '资源交接',
						'to_time'        => time(),
					);
				}
				$this->global_model->insert_batch('info_allocation',$data);


				$data['message']    = '正在查询，请稍侯……';
				$data['return_url'] = site_url('close_window');
				$this->load->view('success', $data);
			}
			else
			{
				$data['message'] = '失败，正在返回……';
				$data['return_url'] = site_url('close_window');
				$this->load->view('error', $data);
			}



		}
	}



	/*
	顾问资源交接
	$info_id 多个用下划线分开
	*/
    /**
     * @param string $info_id
     */
    public function edit_add_adviser($info_id='')
	{
		$this->my_permission->check_per(',0_11,','顾问交接资源');

		$campus_id = intval($this->session->userdata('campus_id'));
		$this->load->helper('form');
		$this->load->library('form_validation');
		$this->form_validation->set_rules('add_user_id','接收者', 'required');
		$this->form_validation->set_rules('info_id','请选择资源', 'required');
		$admin_user_id   = intval($this->session->userdata('admin_user_id'));
		$data            = array();
		$info_id         = rtrim($info_id,'_');//去除最后的下划线
		$info_id         = ltrim($info_id,'0_');//去除前边的0和下划线
		$data['info_id'] = $info_id;

		if ($this->form_validation->run() === FALSE )
		{
			//如果没有提交，显示提交页

			//查询当前用户所在部门，只能交接给本部门
			//$user_department = $this->system_model->get_user_department($admin_user_id);

			$data['campus'] = $this->system_model->get_system('system_campus',array('status' => 1));//校区
			$where          = "status > 0 and (department_path like '%,3,%' or department_path like '%,7,%')";
			$data['user']   = $this->global_model->get('admin',$where,'id,full_name_index,full_name','full_name asc');//取系统在职用户，课程顾问

			$this->load->view('header');
			$this->load->view('info_edit_adviser',$data);
			$this->load->view('bottom');
		}
		else
		{
            $add_user_id2 =  $this->input->post('add_user_id');//新顾问id

			//提交页,处理查询条件
            $add_user_id=0;
          $new_campus_id = intval($this->input->post('campus_id'));
       //     $add_user_id   = intval($this->input->post('add_user_id'));//新顾问id

            //根据来源渠道 平均分配 开始
            $infoitem = $this->info_model->get_info_one($info_id);//资源详细
            $souce_pid=$this->system_model->get_source_pid($infoitem['source_id']);//获取1级来源ID
            $souce_pid2=$this->system_model->get_source_nextid($souce_pid);//获取这个大类来源下所有子ID；
            $souce_pid2=$souce_pid.$souce_pid2;
           if($add_user_id2){ //顾问平均分配

               $add_user_id=$this->info_model->getone_adviser3($new_campus_id,$add_user_id2,$souce_pid2); //根据大类平均分配

          //     $add_user_id=$this->info_model->getone_adviser2($new_campus_id,$add_user_id2);
             //  echo "add_user_id333==".$add_user_id;exit;
           }
         //   echo "add_user_id==".$add_user_id;exit;
			if ($add_user_id)
			{
				$row           = $this->global_model->get_one('admin',$add_user_id,'full_name');
				$add_user_name = $row['full_name'];
			}

			if ($new_campus_id)
			{
				$row             = $this->global_model->get_one('system_campus',$new_campus_id,'name');
				$new_campus_name = $row['name'];
			}

			$info_id = $this->input->post('info_id');
			$info_id = str_replace('_',',',$info_id);
			$update  = array(
				'adviser_id'   => $add_user_id,
				'adviser_name' => $add_user_name,
				'campus_id'    => $new_campus_id,
				'campus_name'  => $new_campus_name,
                 'follow_up'      => 20,
			);

			//是否提醒对方
			$is_alert = intval($this->input->post('is_alert'));

			if ($is_alert)
			{
				$update['is_read'] = 0;
			}


			$result  = $this->global_model->update ('info',"id in ($info_id)",$update);

			if ($result)
			{
				//添加分配记录
				$data       = array();
				$info_array = explode(",",$info_id);
				foreach ($info_array as $info) {
					$data[] = array(
						'info_id'        => $info,
						'from_user_id'   => intval($this->session->userdata('admin_user_id')),
						'from_user_name' => $this->session->userdata('admin_user_name'),
						'to_user_id'     => $add_user_id,
						'to_user_name'   => $add_user_name,
						'campus_id'      => $new_campus_id,
						'campus_name'    => $new_campus_name,
						'remark'         => '资源交接',
						'to_time'        => time(),
					);
				}
				$this->global_model->insert_batch('info_allocation',$data);


				$data['message']    = '正在查询，请稍侯……';
				$data['return_url'] = site_url('close_window');
				$this->load->view('success', $data);
			}
			else
			{
				$data['message'] = '失败，正在返回……';
				$data['return_url'] = site_url('close_window');
				$this->load->view('error', $data);
			}



		}
	}


	/*
	ajax按校区取课程顾问
	$grade_id 班级id
	*/
	public function ajax_get_adviser($campus_id = 0)
	{
		$this->output->enable_profiler(FALSE);//FALSE
		$fz_campus_id    = $this->session->userdata('fz_campus_id');//负责校区，多个用逗号分开
		$campus_id = intval($campus_id);
		$num       = 1;//返回的记录数
	//	$html      = 'tttttt你好<option value="1000">顾问自己分配</option><option value="999">顾问平均分配</option>';//返回的html 下拉框代码
        $html="";
		$fz_campus_id = $fz_campus_id.','.$campus_id;
		$fz_campus_id = ltrim($fz_campus_id,',');

		if (!empty($campus_id))
		{
            $where          = "status > 0 and (department_path like '%,3,%') and is_lock=0 and campus_id = $campus_id";
            $adviser   = $this->global_model->get('admin',$where,'id,campus_id,full_name','department_id asc,id asc');//取系统在职用户，课程顾问 根据校区 按照顾问主管 老员工排序


        //    $where = "campus_id in ($campus_id) and status > 0 and (department_path like '%,3,%' or department_path like '%,7,%')";
		//	$adviser = $this->global_model->get('admin',$where,'id,full_name_index,full_name','full_name asc');//取系统在职用户，课程顾问
			$num = count($adviser);
			foreach ($adviser as $adviser_item)
			{
                $html .= '<input type="checkbox" class="ss"  name="add_user_id[]" value="'.$adviser_item['id'].'"  checked />'.$adviser_item['full_name'];

           //     $html .= '<option value="'.$adviser_item['id'].'">'.$adviser_item['full_name'].'</option>';
			}
			unset($adviser);

		}

		if (!$num)
		{
			$num = 1;
		}
		//===============post方式结束=====================
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
		$html = '手机号重复，不允许添加！';
		$tel  = trim($tel);
		$month3_time = time()-5184000;//三月前时间戳

		if ($tel)
		{
			$where  = "is_del = 0 and mobile = '$tel' ";
			//$result = $this->global_model->count_all('info',$where);
			$rs = $this->global_model->get('info',$where,$field='add_time,info_visit,gather_name,campus_name,adviser_name,department_name', "id desc",1);

			if (!empty($rs))
			{
				if ($rs['info_visit'] == 10 and $rs['add_time']<$month3_time)
				{
					$num = 1;//三个月以前的，也算重复，如果不算重复，设置为0即可
				}
				else
				{
					$num = 1;
				}
				$html = '与'.$rs['department_name'].'资源重复！(收单人：'.$rs['gather_name'].' ,添加日期：'.date('Y-m-d',$rs['add_time']).' '.$rs['campus_name'].' '.$rs['adviser_name'].')';
			}


			if (empty($num))//检查是否与呼叫中心重复
			{
				$where  = "is_del = 0 and tel = '$tel'";
				$rs = $this->global_model->get('callcenter_info',$where,'*','id desc',1);
				if (!empty($rs))
				{
					$num    = 1;
					$html   = '与呼叫中心重复，不允许添加！(邀约人:'.$rs['invite_name'].' ,添加日期：'.date('Y-m-d',$rs['info_date']).' '.$rs['campus_name'].' '.$rs['adviser_name'].')';
				}

			}
		}
		$json_data = array('num' => $num, 'html' => $html);
		echo json_encode( $json_data );
	}

	/*
	ajax 资源事件提醒
	*/
	public function ajax_alert($user_id = 0)
	{
		$this->output->enable_profiler(FALSE);
		$user_id = intval($user_id);
		$num        = 0;
		$html       = '';
		$start_time = time();
		$end_time   = $start_time+1800;//开始时间和结束时间间隔为30分钟
		$where      = "c.add_user_id = $user_id and ((c.alert_date >= $start_time and c.alert_date <= $end_time and c.is_alert = 0) or (c.alert_date < $start_time and c.is_alert = 0))";
		$row        = $this->global_model->my_query('info_call as c',$where ,'c.id,c.info_id,i.name', $group_by="",$join='info as i',$join_where='c.info_id = i.id',$order_by='',$limit='');
		$num        = count($row);
		foreach ($row as $key => $item) {
			$html .= '<a href="'.site_url('info/disp_info/'.$item['info_id']).'" target="doright">'.$item['name'].'</a>&nbsp;&nbsp;';
			//更新为已提醒
			$result = $this->global_model->update('info_call','id = '.$item['id'],$data = array('is_alert' => 1));
		}

		$json_data = array('num' => $num, 'html' => $html);
		echo json_encode( $json_data );
	}
    public function ajax_info_status($info_visit = 0){
        $info_visit = intval($info_visit);
        $info_status = array();
        $info_status['0']   = '<span style="color:#f00">待分配</span>';
        $info_status['10']  = '<span style="color:#f00">未处理</span>';
        $info_status['20']  = '<span style="color:#f00">电邀中</span>';//原为 电邀中
        $info_status['30']  = '<span style="color:#f00">承诺上门</span>';
        $info_status['35']  = '<span style="color:#f00">承诺未上门</span>';
        $info_status['43']  = '<span style="color:#f00">死单</span>';
        $info_status['45']  = '<span style="color:#f00">无效资源</span>';

        $info_status['50']  = '<span style="color:#f00">已体验</span>';
        $info_status['57']  = '<span style="color:#f00">上门未体验</span>';
        $info_status['63']  = '<span style="color:#f00">已安排体验</span>';
        //$info_status['60']  = '<span style="color:#f00">考虑交费</span>';
        $info_status['70']  = '<span style="color:#f00">承诺交费</span>';
        $info_status['80']  = '交费';
        $info_status['90']  = '定金';
        if($info_visit == 10){
            foreach ($info_status as $k => $v) {
                if($k>49){
                    unset($info_status[$k]);
                }
            }
        }
        if($info_visit == 20){
            foreach ($info_status as $k => $v) {
                if($k<50){
                    unset($info_status[$k]);
                }
            }
        }
        echo json_encode( $info_status );
    }


    public function pl_edit_status(){
        $query = $this->db->query('select id,info_status from hmsypx_info')->result_array();
        foreach ($query as $key => $value) {
            if($value['info_status'] == 55){
                $query[$key]['info_status'] = 35;
            }
            if($value['info_status'] == 53){
                $query[$key]['info_status'] = 43;
            }
            if($value['info_status'] == 100){
                $query[$key]['info_status'] = 45;
            }
            if($value['info_status'] == 40){
                $query[$key]['info_status'] = 65;
            }
        }
        foreach ($query as $key => $value) {
            $this->db->where('id',$value['id']);
            $this->db->update('hmsypx_info',['info_status'=>$value['info_status']]);
        }
    }

    public function pl_edit_goshop(){
        $query = $this->db->query('select id,info_status from hmsypx_info where is_del = 0')->result_array();
        foreach ($query as $key => $value) {
            if($value['info_status']>46){
                $this->db->where('id',$value['id']);
                $this->db->update('hmsypx_info',['is_goshop'=>'2']);    
            }
        }    
    }
    public function aaa(){
        $query = $this->db->query('select id,is_goshop,info_status,info_visit from hmsypx_info where is_del = 0')->result_array();
        echo "<pre>";
        print_r($query);
        echo "</pre>";
        exit;

    }
    public function bbb(){
        $query = $this->db->query('select name,age,age_extent,gender,mobile,tel,email,group_id,group_name,lesson_id,lesson_name,class_id,class_name,address,region_id,region_name,region_remark,level,remark,gather_id,gather_name,department_id,department_name,source_id,source_name,invite_id,invite_name,info_type,add_time,add_user_id,add_user_name,info_date,info_year,info_month,info_date_time,validity from hmsypx_info where is_del = 0 order by id asc')->result_array();
        file_put_contents('./wenjian.php',json_encode($query));
    }
    public function test(){
        $data = json_decode(file_get_contents('./wenjian.php'),true);
        $id = 0;
        foreach ($data as $key => $value) {
            $result = $this->info_model->set_info($id,$value);//如果是新插入将返回插入的id
            if ($result){
                //***********************首次电访记录开始(这里只是首次添加资源时用到)******************
                $info_id  = intval($result);
                $call_remark = '';
                $m = 0;
                $m = rand(0,50);
                for ($i=0; $i<$m; $i++) {
                    // 使用chr()函数拼接双字节汉字，前一个chr()为高位字节，后一个为低位字节
                    $a = chr(mt_rand(0xB0,0xD0)).chr(mt_rand(0xA1, 0xF0));
                    // 转码
                    $call_remark .= iconv('GB2312', 'UTF-8', $a);
                }
                if (!empty($call_remark)){
                    $call_data = array(
                        'info_id'       => $info_id,
                        'call_year'     => date('Y',time()),
                        'call_month'    => date('n',time()),
                        'call_date'     => time(),
                        'call_remark'   => $call_remark,
                        'validity'      => 1,
                        'add_user_id'   => intval($this->session->userdata('admin_user_id')),
                        'add_user_name' => $this->session->userdata('admin_user_name'),
                        'add_time'      =>time(),
                    );
                    $result = $this->global_model->set('info_call',0,$call_data);
                }
                //***********************首次电访记录结束******************
            }
        }
    }
}

