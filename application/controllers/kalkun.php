<?php
/**
 * Kalkun
 * An open source web based SMS Management
 *
 * @package		Kalkun
 * @author		Kalkun Dev Team
 * @license		http://kalkun.sourceforge.net/license.php
 * @link		http://kalkun.sourceforge.net
 */

// ------------------------------------------------------------------------

/**
 * Kalkun Class
 *
 * @package		Kalkun
 * @subpackage	Base
 * @category	Controllers
 */
class Kalkun extends MY_Controller {

	/**
	 * Constructor
	 *
	 * @access	public
	 */	
	function Kalkun()
	{
		parent::MY_Controller();	
	}		
		
	// --------------------------------------------------------------------
	
	/**
	 * Index/Dashboard
	 *
	 * Display dashboard page
	 *
	 * @access	public   		 
	 */		
	function index() 
	{
		$data['main'] = 'main/dashboard/home';
		$data['title'] = 'Dashboard';
        $data['data_url'] = site_url('kalkun/get_statistic');
		$this->load->view('main/layout', $data);
	}

	// --------------------------------------------------------------------
	
	/**
	 * About
	 *
	 * Display about page
	 *
	 * @access	public   		 
	 */
	function about()
	{
		$data['main'] = 'main/about';
		$this->load->view('main/layout', $data);		
	}	

	// --------------------------------------------------------------------
	
	/**
	 * Get Statistic
	 *
	 * Get statistic data that used to render the graph
	 *
	 * @access	public   		 
	 */	
	function get_statistic()
	{
		// generate 7 data points
		for ($i=0; $i<=7; $i++)
		{
		    $x[] = mktime(0, 0, 0, date("m"), date("d")-$i, date('Y'));	    
		    $param['sms_date'] = date('Y-m-d', mktime(0,0,0,date("m"),date("d")-$i,date("Y")));
		    $param['user_id'] = $this->session->userdata('id_user');		    
		    $y[] = $this->Kalkun_model->get_sms_used('date', $param);
		}
		$this->_render_statistic($x, $y, 'bar');
	}
	
	function _render_statistic($x = array(), $y = array(), $type='bar')
	{
		$this->load->helper('date');
		$this->load->library('OpenFlashChartLib', NULL, 'OFCL');
		$data_1 = array();
		$data_2 = array();		

		switch($type)
		{	
			case 'bar':
				for($i=0; $i<=7;$i++)
				{
				    $data_1[$i] = date('M-d', $x[$i]);
				    $data_2[$i] = (int)$y[$i]; // force to integer
				}				
				
				$data_1 = array_reverse($data_1);
				$data_2 = array_reverse($data_2);
				
				$bar = new bar();
				$bar->set_values($data_2);
				$bar->set_colour('#21759B'); 
				$bar->set_tooltip('#x_label#<br>#val# SMS');
				$bar->set_key("SMS used in last 7 days", 10);
				
				$x = new x_axis();				
				$labels = new x_axis_labels();
				$labels->set_labels($data_1);
				$labels->set_steps(1);
				$x->set_labels($labels);
				
				$y = new y_axis();
				if(max($data_2)>0) $max=max($data_2); else $max=10;
				$y->set_range(0, $max, 10); 
				
				$element = $bar;
			break;
			
			case 'line':
				for($i=0; $i<=7;$i++)
				{
				    $data_1[$i] = new scatter_value($x[$i], $y[$i]);
				    $data_2[$i] = $y[$i];
				}
				    		
				$def = new solid_dot();
				$def->size(4)->halo_size(0)->colour('#21759B')->tooltip('#date:d M y#<br>#val# SMS');
				
				$line = new scatter_line('#21759B', 3); 
				$line->set_values($data_1);
				$line->set_default_dot_style($def);
				$line->set_key("SMS used in last 7 days", 10);

				$x = new x_axis();
				// grid line and tick every 10
				$x->set_range(
				    mktime(0, 0, 0, date("m"), date("d")-7, date('Y')), // <-- min == 7 day before
				    mktime(0, 0, 0, date("m"), date("d"), date('Y'))    // <-- max == Today
				    );
				
				// show ticks and grid lines for every day:
				$x->set_steps(86400);
				
				$labels = new x_axis_labels();
				// tell the labels to render the number as a date:
				$labels->text('#date:M-d#');
				// generate labels for every day
				$labels->set_steps(86400);
				// only display every other label (every other day)
				$labels->visible_steps(1);
				$labels->rotate(45);
				
				// finally attach the label definition to the x axis
				$x->set_labels($labels);
				
				$y = new y_axis();
				if(max($data_2)>0) $max=max($data_2); else $max=10;
				$y->set_range(0, $max, 10);	
							
				$element = $line;
			break;
		}		
		$chart = new open_flash_chart();
		$chart->add_element($element);
		$chart->set_x_axis($x);
		$chart->set_y_axis($y);
		
		echo $chart->toPrettyString();			
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Notification
	 *
	 * Display notification
	 * Modem status
	 * Used by the autoload function and called via AJAX.
	 *
	 * @access	public   		 
	 */		
	function notification()
	{
		$this->load->view('main/notification');
	}	

	// --------------------------------------------------------------------
	
	/**
	 * Unread Inbox
	 *
	 * Show unread inbox and alert when new sms arrived
	 * Used by the autoload function and called via AJAX.
	 *
	 * @access	public   		 
	 */		
	function unread_inbox()
	{		
		$tmp_unread = $this->Message_model->get_messages(array('readed' => FALSE))->num_rows();
		echo ($tmp_unread > 0)? "(".$tmp_unread.")" : "";		
	}	

	// --------------------------------------------------------------------
	
	/**
	 * Add Folder
	 *
	 * Add custom folder
	 *
	 * @access	public   		 
	 */				
	function add_folder()
	{
		$this->Kalkun_model->add_folder(); 
		redirect($this->input->post('source_url'));
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Rename Folder
	 *
	 * Rename custom folder
	 *
	 * @access	public   		 
	 */	
	function rename_folder()
	{
		$this->Kalkun_model->rename_folder();
		redirect($this->input->post('source_url'));
	}

	// --------------------------------------------------------------------
	
	/**
	 * Delete Folder
	 *
	 * Delete custom folder
	 *
	 * @access	public   		 
	 */		
	function delete_folder($id_folder=NULL)
	{
		$this->Kalkun_model->delete_folder($id_folder);
		redirect('/', 'refresh');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Settings
	 *
	 * Display and handle change on settings/user preference
	 *
	 * @access	public   		 
	 */	
	function settings()
	{
		$data['title'] = 'Settings';
		$type = $this->uri->segment(2);
		$valid_type = array('general', 'personal', 'appearance', 'password', 'save');
		if(!in_array($type, $valid_type)) show_404();
		
		if($_POST && $type=='save') { 		
			$option = $this->input->post('option');
			// check password
			if($option=='password' && sha1($this->input->post('current_password'))!=$this->Kalkun_model->get_setting()->row('password')) 
			{
				$this->session->set_flashdata('notif', 'You entered wrong password');
				redirect('settings/'.$option);
			}
			else if($option=='personal') 
			{
				if($this->input->post('username')!=$this->session->userdata('username'))
				{
					if($this->Kalkun_model->check_setting(array('option' => 'username', 'username' => $this->input->post('username')))->num_rows>0) 
					{
						$this->session->set_flashdata('notif', 'Username already exist');
						redirect('settings/'.$option);					
					}
				}
			}
			$this->Kalkun_model->update_setting($option);
			$this->session->set_flashdata('notif', 'Your settings has been saved');
			redirect('settings/'.$option);
		}
		$data['main'] = 'main/settings/setting';
		$data['settings'] = $this->Kalkun_model->get_setting();
		$data['type'] = 'main/settings/'.$type;
		$this->load->view('main/layout', $data);
	}
	
}

/* End of file kalkun.php */
/* Location: ./application/controllers/kalkun.php */