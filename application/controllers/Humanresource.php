<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Humanresource extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        is_logged_in();
    }

    public function index()
    {

        $data['title'] = 'Data Freelance';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $this->load->model('Menu_model', 'menu');
        $data['freelance'] = $this->menu->getFreelance();
        $data['useraja'] = $this->db->get_where('user', ['role_id' => 2])->result_array();

        $this->form_validation->set_rules('name', 'Name', 'required|is_unique[freelance.name]', [
            'is_unique' => 'This name has already exist in this table!'
        ]);
        $this->form_validation->set_rules('alamat', 'Alamat', 'required');
        $this->form_validation->set_rules('no_telp', 'Telepon', 'required');
        $this->form_validation->set_rules('email', 'Email', 'required|is_unique[freelance.email]', [
            'is_unique' => 'This email has already exist in this table!'
        ]);
        $this->form_validation->set_rules('language', 'Language', 'required');

        if ($this->form_validation->run() == false) {
            $this->load->view('templates/header', $data);
            $this->load->view('templates/sidebar', $data);
            $this->load->view('templates/topbar', $data);
            $this->load->view('humanresource/index', $data);
            $this->load->view('templates/footer');
        } else {
            $email = $this->input->post('email');
            $data = [
                'name' => $this->input->post('name'),
                'alamat' => $this->input->post('alamat'),
                'no_telp' => $this->input->post('no_telp'),
                'email' => $email,
                'language' => $this->input->post('language')
            ];

            $this->db->insert('freelance', $data);
            $this->session->set_flashdata('menus', '<div class="alert alert-success alert-dismissible" role="alert">Congratulation, new freelance has been added! </div>');
            redirect('humanresource');
        }
    }

    public function deleteFreelance($id)
    {
        $data['title'] = 'User Management';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();

        $data['user'] = $this->db->get('freelance')->result_array();


        $this->load->model('Menu_model', 'menu');

        if ($this->menu->deleteFreelance($id) > 0) {
            $this->session->set_flashdata('menus', '<div class="alert alert-success alert-dismissible" role="alert">Data freelance successfully deleted! </div>');
            redirect('humanresource');
        } else {
            $this->session->set_flashdata('menus', '<div class="alert alert-danger alert-dismissible" role="alert">Error while deleting data freelance! </div>');
            redirect('humanresource');
        }
    }

    public function getubahfreelance()
    {

        $this->load->model('Menu_model', 'menu');
        echo json_encode($this->menu->getDataUbahFree($_POST['id']));
    }

    public function editfreelance()
    {
        $data['title'] = 'Data Freelance';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();

        $this->load->model('Menu_model', 'menu');
        $data['freelance'] = $this->menu->getFreelance();

        $this->form_validation->set_rules('name', 'Name', 'required');
        $this->form_validation->set_rules('alamat', 'Alamat', 'required');
        $this->form_validation->set_rules('no_telp', 'Telepon', 'required');
        $this->form_validation->set_rules('language', 'Language', 'required');
        $this->load->model('Menu_model', 'menu');
        if ($this->menu->ubahfree($_POST) > 0) {
            $this->session->set_flashdata('menus', '<div class="alert alert-success alert-dismissible" role="alert">Data freelance successfully changed! </div>');
            redirect('humanresource');
        } else {
            $this->session->set_flashdata('menus', '<div class="alert alert-danger alert-dismissible" role="alert">Error while changing data freelance! </div>');
            redirect('humanresource');
        }
    }

    public function taskInvoice()
    {
        $data['title'] = 'Task to Invoice';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();

        $this->load->model('Menu_model', 'menu');
        $data['taskinvoice'] = $this->menu->gettasksinvoice();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('humanresource/taskinvoice', $data);
        $this->load->view('templates/footer');
    }

    public function verify_taskFinal()
    {
        $email = $this->input->get('email');
        $file = $this->input->get('file');
        $token = $this->input->get('token');
        $task_id = $this->input->get('task_id');
        $user_email = $this->input->get('user');

        $user = $this->db->get_where('user', ['email' => $email])->row_array();
        // $user = $this->db->get_where('user_token', ['file' => $file])->row_array();

        if ($user) {
            $hr_token = $this->db->get_where('hr_token', ['token' => $token])->row_array();

            if ($hr_token) {
                $this->db->set('status', 'Ready to invoicing');
                $this->db->where('email', $email);
                $this->db->where('file_final', $file);
                $this->db->update('task_invoice');

                $this->db->set('status', 'finished');
                $this->db->where('email', $email);
                $this->db->where('id', $task_id);
                $this->db->update('request_task');

                $this->db->delete('hr_token', ['email' => $email, 'file' => $file, 'user' => $user_email]);

                $this->session->set_flashdata('menus', '<div class="alert alert-success alert-dismissible" role="alert">Task has been accepted and invoiced!</div>');
                redirect('humanresource/taskInvoice');
            } else {
                $this->session->set_flashdata('menus', '<div class="alert alert-danger alert-dismissible" role="alert">Invalid token task, please contact admin!</div>');
                redirect('humanresource/taskInvoice');
            }
        } else {
            $this->session->set_flashdata('menus', '<div class="alert alert-danger alert-dismissible" role="alert">Failed when sending task! Wrong email </div>');
            redirect('humanresource/taskInvoice');
        }
    }

    function downloadtaskfinal($id)
    {
        $data = $this->db->get_where('task_invoice', ['id' => $id])->row();
        force_download('assets/taskfilesfinal/' . $data->file_final, NULL);

        redirect('humanresource/taskInvoice');
    }
    public function deletetaskfinal($id)
    {
        $data['title'] = 'Request Tasks';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();

        $this->load->model('Menu_model', 'menu');

        $data['taskinvoice'] = $this->menu->gettasksinvoice();

        if ($this->menu->deleteTaskInvoice($id) > 0) {
            $this->session->set_flashdata('menus', '<div class="alert alert-success alert-dismissible" role="alert">Task successfully deleted! </div>');
            redirect('humanresource/taskInvoice');
        } else {
            $this->session->set_flashdata('menus', '<div class="alert alert-danger alert-dismissible" role="alert">Error while deleting task! </div>');
            redirect('humanresource/taskInvoice');
        }
    }

    public function invoicedata()
    {
        $data['title'] = 'Data Invoice';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();

        $this->load->model('Menu_model', 'menu');
        $data['datainvoice'] = $this->menu->dataInvoice();
        $data['taskinvoice'] = $this->db->get_where('task_invoice', ['status' => 'Ready to invoicing'])->result_array();

        if ($this->form_validation->run() == false) {
            $this->load->view('templates/header', $data);
            $this->load->view('templates/sidebar', $data);
            $this->load->view('templates/topbar', $data);
            $this->load->view('humanresource/invoicedata', $data);
            $this->load->view('templates/footer');
        } else {
            $this->load->model('Menu_model', 'menu');
            $email = $this->input->post('email_freelance');

            $data = [
                'id_task_invoice' => $this->input->post('id_task_invoice'),
                'task_type' => $this->input->post('task_type'),
                'target_lang' => $this->input->post('target_lang'),
                'source_lang' => $this->input->post('source_lang'),
                'id_freelance' => $this->input->post('id_freelance'),
                'job_value' => $this->input->post('job_value'),
                'status' => 'Waiting for invoice',
                'date_completed' => $this->input->post('date_completed'),
                'email_freelance' => htmlspecialchars($email),
                'file_invoice' => $file = $this->_filetouploadinvoice()
            ];

            $token = base64_encode(random_bytes(32));
            $invoice_token = [
                'file' => $file,
                'email' => $email,
                'token' => $token,
                'date_created' => time()
            ];

            $this->db->insert('task_invoice', $data);
            $this->db->insert('invoice_token', $invoice_token);
            $this->_sendEmailInvoice($token, 'verify_Invoice', $file);

            $this->session->set_flashdata('menus', '<div class="alert alert-success" role="alert">The task successfully submitted!</div>');
            redirect('user/invoicedata');
        }
    }
    private function _sendEmailInvoice($token, $type, $file)
    {
        $config = [
            'protocol' => 'smtp',
            'smtp_host' => 'ssl://smtp.gmail.com',
            'smtp_user' => 'allandanshiva@gmail.com',
            'smtp_pass' => 'alandanshiva',
            'smtp_port' => '465',
            'mailtype' => 'html',
            'charset' => 'utf-8',
            'newline' => "\r\n"
        ];

        $this->load->library('email', $config);
        $this->email->initialize($config);

        $this->email->from('allandanshiva@gmail.com', 'HR PT. STAR Software Indonesia');
        $this->email->to($this->input->post('email'));

        if ($type == 'verify_task') {
            $this->email->subject('You have a new request task!');
            $this->email->message('Click this link to login & get invoice : <a href=" ' . base_url() . 'user/verify_Invoice?email=' . $this->input->post('email') . '& token=' . urlencode($token) .  '& file=' . $file . '">Accept </a>');
        }
        if ($this->email->send()) {
            return true;
        } else {
            echo $this->email->print_debugger();
            die;
        }
    }

    private function _filetouploadinvoice()
    {

        $config['upload_path'] = './assets/invoicefiles/';
        $config['allowed_types'] = 'doc|docx|pdf|xlsx|csv|zip|rar';
        $config['max_size']     = 0;

        $this->load->library('upload', $config);

        if ($this->upload->do_upload('file_invoice')) {
            return $this->upload->data("file_name");
        }

        return true;
    }

    public function deleteinvoice($id)
    {
        $data['title'] = 'Request Tasks';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();

        $this->load->model('Menu_model', 'menu');

        $data['datainvoice'] = $this->menu->dataInvoice();

        if ($this->menu->deleteInvoice($id) > 0) {
            $this->session->set_flashdata('menus', '<div class="alert alert-success alert-dismissible" role="alert">Invoice successfully deleted! </div>');
            redirect('humanresource/invoicedata');
        } else {
            $this->session->set_flashdata('menus', '<div class="alert alert-danger alert-dismissible" role="alert">Error while deleting invoice! </div>');
            redirect('humanresource/invoicedata');
        }
    }

    function downloadinvoice($id)
    {
        $data = $this->db->get_where('invoice', ['id' => $id])->row();
        force_download('assets/invoicefiles/' . $data->file_invoice, NULL);

        redirect('humanresource/invoicedata');
    }
}
