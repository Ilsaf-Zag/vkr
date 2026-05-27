<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CrudPlaceholderController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'message' => 'CRUD endpoint planned. Implement repository/query logic for this resource.',
            'filters' => $request->query(),
        ]);
    }

    public function store(Request $request)
    {
        return response()->json([
            'message' => 'Create endpoint planned. Add validation and service layer logic.',
            'payload' => $request->all(),
        ], 202);
    }

    public function show(string $id)
    {
        return response()->json([
            'message' => 'Show endpoint planned.',
            'id' => $id,
        ]);
    }

    public function update(Request $request, string $id)
    {
        return response()->json([
            'message' => 'Update endpoint planned. Add validation and audit logging.',
            'id' => $id,
            'payload' => $request->all(),
        ], 202);
    }

    public function destroy(string $id)
    {
        return response()->json([
            'message' => 'Delete endpoint planned. Prefer soft business deactivation for users, drivers and vehicles.',
            'id' => $id,
        ], 202);
    }
}

