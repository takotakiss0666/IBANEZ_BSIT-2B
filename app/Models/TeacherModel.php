<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherModel extends Model
{
    protected $table = 'teachers';
    protected $primaryKey = 'id';
    
    // 1. DISABLE SOFT DELETES (Change to false)
    protected $useSoftDeletes = false; 
    protected $deletedField   = 'deleted_at';
    
    // 2. You can leave 'deleted_at' here or remove it, it won't hurt anything if disabled.
    protected $allowedFields = ['name', 'subject', 'email', 'phone', 'created_at', 'updated_at', 'deleted_at'];

    public function getRecords($start, $length, $searchValue = '')
    {
        $builder = $this->builder();
        $builder->select('*');

        // 3. REMOVE OR COMMENT OUT THIS LINE
        // $builder->where('deleted_at', null); 

        if (!empty($searchValue)) {
            $builder->groupStart()
                ->like('name', $searchValue)
                ->orLike('subject', $searchValue)
                ->orLike('email', $searchValue)
                ->groupEnd();
        }

        $filteredBuilder = clone $builder;
        $filteredRecords = $filteredBuilder->countAllResults(false);

        $builder->limit($length, $start);
        $data = $builder->get()->getResultArray();

        return ['data' => $data, 'filtered' => $filteredRecords];
    }
}