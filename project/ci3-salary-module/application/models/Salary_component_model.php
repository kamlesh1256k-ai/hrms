<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Salary_component_model extends CI_Model
{
    protected $tableComponents = 'salary_components';
    protected $tableStructure = 'salary_structure';
    protected $tableStructureComponents = 'structure_components';
    protected $tableEmployeeSalary = 'employee_salary';

    public function get_components_by_structure($structureId)
    {
        return $this->db
            ->select('sc.*, stc.priority')
            ->from($this->tableStructureComponents . ' stc')
            ->join($this->tableComponents . ' sc', 'sc.id = stc.component_id', 'inner')
            ->where('stc.structure_id', (int) $structureId)
            ->order_by('stc.priority', 'ASC')
            ->order_by('sc.id', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_all_components()
    {
        return $this->db->order_by('id', 'ASC')->get($this->tableComponents)->result_array();
    }

    public function get_all_structures()
    {
        return $this->db->order_by('id', 'DESC')->get($this->tableStructure)->result_array();
    }

    public function get_component($id)
    {
        return $this->db->where('id', (int) $id)->get($this->tableComponents)->row_array();
    }

    public function save_component(array $data, $id = null)
    {
        $now = date('Y-m-d H:i:s');
        $payload = [
            'name' => trim((string) ($data['name'] ?? '')),
            'type' => (string) ($data['type'] ?? 'earning'),
            'calculation_type' => (string) ($data['calculation_type'] ?? 'fixed'),
            'value' => isset($data['value']) && $data['value'] !== '' ? (float) $data['value'] : null,
            'formula' => isset($data['formula']) ? trim((string) $data['formula']) : null,
            'condition_rule' => isset($data['condition_rule']) ? trim((string) $data['condition_rule']) : null,
            'status' => !empty($data['status']) ? 1 : 0,
            'updated_at' => $now,
        ];

        if ($id) {
            return $this->db->where('id', (int) $id)->update($this->tableComponents, $payload);
        }

        $payload['created_at'] = $now;
        $this->db->insert($this->tableComponents, $payload);
        return $this->db->insert_id();
    }

    public function delete_component($id)
    {
        return $this->db->where('id', (int) $id)->delete($this->tableComponents);
    }

    public function get_employee_salary($employeeId)
    {
        return $this->db->where('employee_id', (int) $employeeId)->get($this->tableEmployeeSalary)->row_array();
    }

    public function save_employee_salary(array $data)
    {
        $employeeId = (int) $data['employee_id'];
        $now = date('Y-m-d H:i:s');
        $payload = [
            'employee_id' => $employeeId,
            'ctc' => (float) $data['ctc'],
            'basic_percentage' => (float) ($data['basic_percentage'] ?? 50),
            'is_pf_enabled' => !empty($data['is_pf_enabled']) ? 1 : 0,
            'is_esic_enabled' => !empty($data['is_esic_enabled']) ? 1 : 0,
            'structure_id' => !empty($data['structure_id']) ? (int) $data['structure_id'] : null,
            'updated_at' => $now,
        ];

        $existing = $this->get_employee_salary($employeeId);
        if ($existing) {
            return $this->db->where('id', (int) $existing['id'])->update($this->tableEmployeeSalary, $payload);
        }

        $payload['created_at'] = $now;
        return $this->db->insert($this->tableEmployeeSalary, $payload);
    }
}
