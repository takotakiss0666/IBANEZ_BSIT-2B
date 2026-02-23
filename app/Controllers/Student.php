<?php

namespace App\Controllers;

use App\Models\StudentModel;
use App\Models\LogModel;
use CodeIgniter\Controller;

class Student extends Controller
{
    public function index(){
        return view('student/index');
    }

    public function save(){
        $model = new StudentModel();
        $logModel = new LogModel();

        $data = [
            'name'       => $this->request->getPost('name'),
            'birthday'   => $this->request->getPost('birthday'),
            'address'    => $this->request->getPost('address'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($model->insert($data)) {
            $logModel->addLog('Added new student: ' . $data['name'], 'ADD');
            return $this->response->setJSON(['status' => 'success']);
        } else {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to save student']);
        }
    }

    public function edit($id){
        $model = new StudentModel();
        $student = $model->find($id);

        if ($student) {
            return $this->response->setJSON(['data' => $student]);
        } else {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Student not found']);
        }
    }

    public function update(){
        $model = new StudentModel();
        $logModel = new LogModel();
        
        $id = $this->request->getPost('id');

        if (empty($id)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Error: Student ID is missing.']);
        }

        $data = [
            'name'       => $this->request->getPost('name'),
            'birthday'   => $this->request->getPost('birthday'),
            'address'    => $this->request->getPost('address'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $updated = $model->set($data)->where('id', $id)->update();

        if ($updated) {
            $logModel->addLog('Updated student: ' . $data['name'], 'UPDATED');
            return $this->response->setJSON(['success' => true, 'message' => 'Student updated successfully.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Error updating student. No changes were made.']);
        }
    }

    public function delete($id){
        $model = new StudentModel();
        $logModel = new LogModel();
        $student = $model->find($id);

        if (!$student) {
            return $this->response->setJSON(['success' => false, 'message' => 'Student not found.']);
        }

        if ($model->delete($id)) {
            $logModel->addLog('Deleted student: ' . $student['name'], 'DELETED');
            return $this->response->setJSON(['success' => true, 'message' => 'Student deleted successfully.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to delete student.']);
        }
    }

    public function fetchRecords()
    {
        $request = service('request');
        $model = new StudentModel();

        $start = $request->getPost('start') ?? 0;
        $length = $request->getPost('length') ?? 10;
        $searchValue = $request->getPost('search')['value'] ?? '';

        $totalRecords = $model->countAll();
        $result = $model->getRecords($start, $length, $searchValue);

        $data = [];
        $counter = $start + 1;
        foreach ($result['data'] as $row) {
            $row['row_number'] = $counter++;
            $data[] = $row;
        }

        return $this->response->setJSON([
            'draw' => intval($request->getPost('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $result['filtered'],
            'data' => $data,
        ]);
    }
}