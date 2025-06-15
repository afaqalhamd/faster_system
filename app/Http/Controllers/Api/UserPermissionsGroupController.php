<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PermissionGroupRequest;
use App\Models\PermissionGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPermissionsGroupController extends Controller
{
    /**
     * Get all permission groups
     */
    public function index(): JsonResponse
    {
        $groups = PermissionGroup::all();
        return response()->json([
            'status' => 'success',
            'data' => $groups
        ]);
    }

    /**
     * Get specific permission group
     */
    public function show($id): JsonResponse
    {
        $group = PermissionGroup::find($id);

        if (!$group) {
            return response()->json([
                'status' => 'error',
                'message' => 'Group not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $group
        ]);
    }

    /**
     * Create new permission group
     */
    public function store(PermissionGroupRequest $request): JsonResponse
    {
        $group = PermissionGroup::create([
            'name' => $request->name,
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Group created successfully',
            'data' => $group
        ], 201);
    }

    /**
     * Update permission group
     */
    public function update(PermissionGroupRequest $request, $id): JsonResponse
    {
        $group = PermissionGroup::find($id);

        if (!$group) {
            return response()->json([
                'status' => 'error',
                'message' => 'Group not found'
            ], 404);
        }

        $group->update([
            'name' => $request->name,
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Group updated successfully',
            'data' => $group
        ]);
    }

    /**
     * Delete permission group
     */
    public function destroy($id): JsonResponse
    {
        $group = PermissionGroup::find($id);

        if (!$group) {
            return response()->json([
                'status' => 'error',
                'message' => 'Group not found'
            ], 404);
        }

        try {
            $group->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Group deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete group'
            ], 409);
        }
    }
}