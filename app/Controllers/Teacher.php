<?php

namespace App\Controllers;

use App\Models\TeacherModel;
use App\Models\LogModel;
use CodeIgniter\Controller;

class Teacher extends Controller
{
    public function index(){
        return view('teacher/index');
    }

    public function save(){
        $model = new TeacherModel();
        $logModel = new LogModel();

        $data = [
            'name'       => $this->request->getPost('name'),
            'birthday'   => $this->request->getPost('birthday'),
            'address'    => $this->request->getPost('address'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($model->insert($data)) {
            $logModel->addLog('Added new teacher: ' . $data['name'], 'ADD');
            return $this->response->setJSON(['status' => 'success']);
        } else {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to save teacher']);
        }
    }

    public function edit($id){
        $model = new TeacherModel();
        $teacher = $model->find($id);

        if ($teacher) {
            return $this->response->setJSON(['data' => $teacher]);
        } else {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Teacher not found']);
        }
    }

    public function update(){
        $model = new TeacherModel();
        $logModel = new LogModel();
        
        $id = $this->request->getPost('id');

        if (empty($id)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Error: Teacher ID is missing.']);
        }

        $data = [
            'name'       => $this->request->getPost('name'),
            'birthday'   => $this->request->getPost('birthday'),
            'address'    => $this->request->getPost('address'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $updated = $model->set($data)->where('id', $id)->update();

        if ($updated) {
            $logModel->addLog('Updated teacher: ' . $data['name'], 'UPDATED');
            return $this->response->setJSON(['success' => true, 'message' => 'Teacher updated successfully.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Error updating teacher. No changes were made.']);
        }
    }

    public function delete($id){
        $model = new TeacherModel();
        $logModel = new LogModel();
        $teacher = $model->find($id);

        if (!$teacher) {
            return $this->response->setJSON(['success' => false, 'message' => 'Teacher not found.']);
        }

        if ($model->delete($id)) {
            $logModel->addLog('Deleted teacher: ' . $teacher['name'], 'DELETED');
            return $this->response->setJSON(['success' => true, 'message' => 'Teacher deleted successfully.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to delete teacher.']);
        }
    }

    public function fetchRecords()
    {
        $request = service('request');
        $model = new TeacherModel();

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