<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Display a listing of customer notifications.
     * Requirements: 14.1, 14.2, 14.3, 14.4, 14.5
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $customer = $request->user();
            $query = $customer->notifications();

            // Support filtering by read/unread status
            if ($request->has('filter')) {
                $filter = $request->input('filter');
                if ($filter === 'read') {
                    $query->whereNotNull('read_at');
                } elseif ($filter === 'unread') {
                    $query->whereNull('read_at');
                }
            }

            // Order by newest first
            $query->orderBy('created_at', 'desc');

            // Paginate with 30 items per page
            $notifications = $query->paginate(30);

            return response()->json([
                'status' => true,
                'data' => [
                    'notifications' => $notifications->map(function ($notification) {
                        return [
                            'id' => $notification->id,
                            'type' => $notification->type,
                            'data' => $notification->data,
                            'read_at' => $notification->read_at,
                            'is_read' => !is_null($notification->read_at),
                            'created_at' => $notification->created_at,
                        ];
                    }),
                    'pagination' => [
                        'current_page' => $notifications->currentPage(),
                        'per_page' => $notifications->perPage(),
                        'total' => $notifications->total(),
                        'last_page' => $notifications->lastPage(),
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch customer notifications', [
                'customer_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب الإشعارات. يرجى المحاولة لاحقاً.',
            ], 500);
        }
    }

    /**
     * Mark a specific notification as read.
     * Requirements: 14.6
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        try {
            $customer = $request->user();
            $notification = $customer->notifications()->find($id);

            if (!$notification) {
                return response()->json([
                    'status' => false,
                    'message' => 'الإشعار غير موجود.',
                ], 404);
            }

            // Mark as read if not already read
            if (is_null($notification->read_at)) {
                $notification->markAsRead();
            }

            return response()->json([
                'status' => true,
                'message' => 'تم تحديد الإشعار كمقروء بنجاح.',
                'data' => [
                    'notification' => [
                        'id' => $notification->id,
                        'type' => $notification->type,
                        'data' => $notification->data,
                        'read_at' => $notification->read_at,
                        'is_read' => true,
                        'created_at' => $notification->created_at,
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read', [
                'customer_id' => $request->user()->id ?? null,
                'notification_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث الإشعار. يرجى المحاولة لاحقاً.',
            ], 500);
        }
    }

    /**
     * Mark all notifications as read.
     * Requirements: 14.6
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $customer = $request->user();
            $customer->unreadNotifications->markAsRead();

            return response()->json([
                'status' => true,
                'message' => 'تم تحديد جميع الإشعارات كمقروءة بنجاح.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read', [
                'customer_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تحديث الإشعارات. يرجى المحاولة لاحقاً.',
            ], 500);
        }
    }
}
