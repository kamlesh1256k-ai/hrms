<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Salary_structure extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Salary_component_model', 'salaryModel');
        $this->load->library('SalaryCalculator');
    }

    public function index()
    {
        $data = [
            'structures' => $this->salaryModel->get_all_structures(),
            'components' => $this->salaryModel->get_all_components(),
        ];
        $this->load->view('salary/index', $data);
    }

    public function save_component($id = null)
    {
        if ($this->input->method() !== 'post') {
            show_error('Invalid request method', 405);
        }

        $payload = [
            'name' => $this->input->post('name', true),
            'type' => $this->input->post('type', true),
            'calculation_type' => $this->input->post('calculation_type', true),
            'value' => $this->input->post('value', true),
            'formula' => $this->input->post('formula', true),
            'condition_rule' => $this->input->post('condition_rule', true),
            'status' => $this->input->post('status', true),
        ];

        $this->salaryModel->save_component($payload, $id ? (int) $id : null);
        redirect('salary-structure');
    }

    public function delete_component($id)
    {
        $this->salaryModel->delete_component((int) $id);
        redirect('salary-structure');
    }

    public function calculate()
    {
        if ($this->input->method() === 'post') {
            $ctc = (float) $this->input->post('ctc');
            $basicPercentage = (float) $this->input->post('basic_percentage');
            $isPfEnabled = (int) $this->input->post('is_pf_enabled');
            $isEsicEnabled = (int) $this->input->post('is_esic_enabled');
            $structureId = (int) $this->input->post('structure_id');

            if ($structureId <= 0) {
                $structureId = 1;
            }

            $components = $this->salaryModel->get_components_by_structure($structureId);
            $result = $this->salarycalculator->calculate(
                [
                    'ctc' => $ctc,
                    'basic_percentage' => $basicPercentage > 0 ? $basicPercentage : 50,
                    'is_pf_enabled' => $isPfEnabled,
                    'is_esic_enabled' => $isEsicEnabled,
                ],
                $components
            );

            if ($this->input->is_ajax_request()) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode($result));
            }

            return $this->load->view('salary/calculate', [
                'result' => $result,
                'structures' => $this->salaryModel->get_all_structures(),
                'input' => $this->input->post(),
            ]);
        }

        return $this->load->view('salary/calculate', [
            'result' => null,
            'structures' => $this->salaryModel->get_all_structures(),
            'input' => [
                'ctc' => '',
                'basic_percentage' => 50,
                'is_pf_enabled' => 1,
                'is_esic_enabled' => 1,
                'structure_id' => 1,
            ],
        ]);
    }
}
