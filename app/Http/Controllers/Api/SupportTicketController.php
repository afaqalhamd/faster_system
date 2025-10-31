<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Helpers\AttachmentHelper;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SupportTicketController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Check if authenticated user is admin
     *
     * @return void
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    private function checkAdminAccess()
    {
        $user = auth()->user();

        if (!$user || !$user->isAdmin()) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Admin access required.'
                ], 403)
            );
        }
    }

    /**
     * Send notification to all admin users
     *
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    private function notifyAdmins($title, $body, $data = [])
    {
        try {
            // Get all admin users with FCM tokens
            $admins = User::whereHas('roles', function($query) {
                $query->where('name', 'Admin');
            })
            ->whereNotNull('fc_token')
            ->where('fc_token', '!=', '')
            ->get();

            if ($admins->isEmpty()) {
                \Log::info('No admin users with FCM tokens found');
                return;
            }

            $tokens = $admins->pluck('fc_token')->toArray();

            \Log::info('Sending notification to admins', [
                'admin_count' => count($tokens),
                'title' => $title,
            ]);

            $this->firebaseService->sendNotificationToMultipleDevices(
                $tokens,
                $title,
                $body,
                $data
            );

            \Log::info('Admin notification sent successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to send admin notification: ' . $e->getMessage());
        }
    }

    /**
     * Send notification to ticket owner
     *
     * @param SupportTicket $ticket
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    private function notifyTicketOwner($ticket, $title, $body, $data = [])
    {
        \Log::info('🔔 Starting notifyTicketOwner process', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        try {
            // Get the ticket owner (User or Party)
            \Log::info('🔍 Getting ticket owner...');
            $owner = $ticket->getCreator();
            
            if (!$owner) {
                \Log::error('❌ No ticket owner found', [
                    'ticket_id' => $ticket->id,
                    'ticketable_type' => $ticket->ticketable_type,
                    'ticketable_id' => $ticket->ticketable_id,
                ]);
                return;
            }

            \Log::info('✅ Ticket owner found', [
                'owner_id' => $owner->id,
                'owner_type' => get_class($owner),
                'owner_email' => $owner->email ?? 'N/A',
                'owner_name' => ($owner->first_name ?? '') . ' ' . ($owner->last_name ?? ''),
            ]);

            // Check if owner has FCM token
            \Log::info('🔍 Checking FCM token...');
            if (empty($owner->fc_token)) {
                \Log::warning('⚠️ Ticket owner has no FCM token', [
                    'owner_id' => $owner->id,
                    'owner_type' => get_class($owner),
                    'owner_email' => $owner->email ?? 'N/A',
                ]);
                return;
            }

            \Log::info('✅ FCM token found', [
                'owner_id' => $owner->id,
                'token_length' => strlen($owner->fc_token),
                'token_preview' => substr($owner->fc_token, 0, 20) . '...',
            ]);

            \Log::info('📤 Sending notification to ticket owner', [
                'owner_id' => $owner->id,
                'owner_type' => get_class($owner),
                'ticket_id' => $ticket->id,
                'title' => $title,
                'body' => $body,
                'fcm_token' => substr($owner->fc_token, 0, 20) . '...',
            ]);

            // Check if firebaseService exists
            if (!$this->firebaseService) {
                \Log::error('❌ Firebase service not initialized');
                return;
            }

            \Log::info('✅ Firebase service is available');

            $result = $this->firebaseService->sendNotificationToMultipleDevices(
                [$owner->fc_token],
                $title,
                $body,
                $data
            );

            \Log::info('📬 Firebase notification result', [
                'result' => $result,
                'result_type' => gettype($result),
            ]);

            \Log::info('✅ Ticket owner notification sent successfully', [
                'ticket_id' => $ticket->id,
                'owner_id' => $owner->id,
                'title' => $title,
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Failed to send ticket owner notification', [
                'ticket_id' => $ticket->id,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get all tickets for authenticated user (or all tickets if admin)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && method_exists($user, 'isAdmin') && $user->isAdmin();

        // If admin, get all tickets with creator info. Otherwise, get only user's tickets
        if ($isAdmin) {
            $query = SupportTicket::with([
                'ticketable', // Polymorphic relationship
                'user:id,first_name,last_name,email', // Legacy
                'messages' => function($q) {
                    $q->latest()->limit(1);
                }
            ])->withCount('messages');
        } else {
            // Get tickets for current user (User or Party)
            $ticketableType = get_class($user);
            $query = SupportTicket::forTicketable($ticketableType, $user->id)
                ->with(['messages' => function($q) {
                    $q->latest()->limit(1);
                }])
                ->withCount('messages');
        }

        // Apply filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->byStatus($request->status);
        }

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('priority')) {
            $query->byPriority($request->priority);
        }

        // Admin-only: Filter by user ID or party ID
        if ($isAdmin && $request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($isAdmin && $request->has('party_id')) {
            $query->forParty($request->party_id);
        }

        // Search
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search, $isAdmin) {
                $q->where('ticket_number', 'LIKE', "%{$search}%")
                  ->orWhere('subject', 'LIKE', "%{$search}%");

                // Admin can search by creator name (User or Party)
                if ($isAdmin) {
                    $q->orWhereHasMorph('ticketable', ['App\\Models\\User', 'App\\Models\\Party\\Party'],
                        function($morphQuery) use ($search) {
                            $morphQuery->where('first_name', 'LIKE', "%{$search}%")
                                      ->orWhere('last_name', 'LIKE', "%{$search}%")
                                      ->orWhere('email', 'LIKE', "%{$search}%");
                        }
                    );
                }
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 20);
        $tickets = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $tickets
        ]);
    }


    /**
     * Create new support ticket
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Log incoming request for debugging
        \Log::info('Support ticket creation request', [
            'all_data' => $request->all(),
            'has_files' => $request->hasFile('attachments'),
            'files_count' => $request->hasFile('attachments') ? count($request->file('attachments')) : 0,
            'content_type' => $request->header('Content-Type'),
        ]);

        $validator = Validator::make($request->all(), [
            'category' => 'required|in:technical,financial,delivery,orders,account,general',
            'priority' => 'required|in:urgent,high,medium,low',
            'subject' => 'required|string|min:10|max:255',
            'description' => 'required|string|min:50|max:5000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240', // 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();

        // Generate unique ticket number
        do {
            $ticketNumber = 'TKT-' . strtoupper(Str::random(8));
        } while (SupportTicket::where('ticket_number', $ticketNumber)->exists());

        // Create ticket with polymorphic relationship
        $ticket = SupportTicket::create([
            'ticket_number' => $ticketNumber,
            'ticketable_id' => $user->id,
            'ticketable_type' => get_class($user),
            'user_id' => $user->id, // Keep for backward compatibility
            'category' => $request->category,
            'priority' => $request->priority,
            'status' => 'new',
            'subject' => $request->subject,
            'description' => $request->description,
        ]);

        // Handle attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $path = AttachmentHelper::storeTicketAttachment($file, $ticket->id);

                    if ($path) {
                        TicketAttachment::create([
                            'ticket_id' => $ticket->id,
                            'file_name' => $file->getClientOriginalName(),
                            'file_path' => $path,
                            'file_type' => $file->getClientOriginalExtension(),
                            'file_size' => $file->getSize(),
                            'mime_type' => $file->getMimeType(),
                        ]);
                    }
                }
            }
        }

        // Send notifications to admins
        $creatorName = $ticket->creator_name;
        $this->notifyAdmins(
            'تذكرة دعم جديدة',
            "تذكرة جديدة من {$creatorName}: {$ticket->subject}",
            [
                'type' => 'new_ticket',
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'priority' => $ticket->priority,
                'category' => $ticket->category,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket created successfully',
            'data' => $ticket->load('attachments')
        ], 201);
    }

    /**
     * Get ticket details
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = auth()->user();
        $isAdmin = $user && method_exists($user, 'isAdmin') && $user->isAdmin();

        // If admin, can view any ticket. Otherwise, only own tickets
        if ($isAdmin) {
            $ticket = SupportTicket::with([
                'ticketable', // Polymorphic
                'user:id,first_name,last_name,email', // Legacy
                'messages.user',
                'messages.attachments',
                'attachments',
                'statusHistory.changedBy',
                'assignedTo:id,first_name,last_name'
            ])->findOrFail($id);
        } else {
            $ticketableType = get_class($user);
            $ticket = SupportTicket::forTicketable($ticketableType, $user->id)
                ->with([
                    'messages.user',
                    'messages.attachments',
                    'attachments',
                    'statusHistory.changedBy'
                ])
                ->findOrFail($id);
        }

        // Mark messages as read (only for non-admin users)
        if (!$isAdmin) {
            $ticket->messages()
                ->where('is_staff_reply', true)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);

            // Reset unread count
            $ticket->update(['unread_messages_count' => 0]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $ticket
        ]);
    }

    /**
     * Add message to ticket
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addMessage(Request $request, $id)
    {
        // Log incoming request for debugging
        \Log::info('Add message request', [
            'ticket_id' => $id,
            'all_data' => $request->all(),
            'has_files' => $request->hasFile('attachments'),
            'message_length' => strlen($request->input('message', '')),
        ]);

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|min:10|max:2000',
            'attachments' => 'nullable|array|max:3',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
        ]);

        if ($validator->fails()) {
            \Log::error('Message validation failed', [
                'errors' => $validator->errors()->toArray(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        $ticketableType = get_class($user);

        $ticket = SupportTicket::forTicketable($ticketableType, $user->id)->findOrFail($id);

        // Check if ticket is closed
        if ($ticket->status === 'closed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot add message to closed ticket'
            ], 400);
        }

        // Create message
        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $request->message,
            'is_staff_reply' => false,
        ]);

        // Handle attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $path = AttachmentHelper::storeMessageAttachment($file, $ticket->id);

                    if ($path) {
                        TicketAttachment::create([
                            'ticket_id' => $ticket->id,
                            'message_id' => $message->id,
                            'file_name' => $file->getClientOriginalName(),
                            'file_path' => $path,
                            'file_type' => $file->getClientOriginalExtension(),
                            'file_size' => $file->getSize(),
                            'mime_type' => $file->getMimeType(),
                        ]);
                    }
                }
            }
        }

        // Update ticket status if resolved
        if ($ticket->status === 'resolved') {
            $ticket->update(['status' => 'open']);
        }

        // Send notifications to admins about new message
        $senderName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        $this->notifyAdmins(
            'رسالة جديدة في تذكرة',
            "رسالة جديدة من {$senderName} في التذكرة #{$ticket->ticket_number}",
            [
                'type' => 'new_message',
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'message_id' => $message->id,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Message added successfully',
            'data' => $message->load('attachments')
        ], 201);
    }

    /**
     * Close ticket
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function close($id)
    {
        try {
            \Log::info("🔄 Attempting to close ticket: $id");
            
            $user = auth()->user();
            if (!$user) {
                \Log::error("❌ No authenticated user found");
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            \Log::info("👤 User authenticated: " . $user->id . " (" . get_class($user) . ")");
            $ticketableType = get_class($user);

            $ticket = SupportTicket::forTicketable($ticketableType, $user->id)->where('id', $id)->first();
            
            if (!$ticket) {
                \Log::error("❌ Ticket not found: $id for user: " . $user->id);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ticket not found or access denied'
                ], 404);
            }
            
            \Log::info("🎫 Ticket found: " . $ticket->id . " - Status: " . $ticket->status);

            if (!$ticket->canBeClosed()) {
                \Log::error("❌ Cannot close ticket - Status: " . $ticket->status);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot close ticket in current status: ' . $ticket->status . '. Allowed statuses: open, pending, resolved'
                ], 400);
            }

        \Log::info("✅ Closing ticket: " . $ticket->id);
        $oldStatus = $ticket->status;
        $ticket->markAsClosed();

        // Log status change
        $ticket->statusHistory()->create([
            'old_status' => $oldStatus,
            'new_status' => 'closed',
            'changed_by' => $user->id,
            'notes' => 'Closed by user'
        ]);

        \Log::info("✅ Ticket closed successfully: " . $ticket->id);

        // Send notifications
        // event(new TicketClosed($ticket));

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket closed successfully'
        ]);
        
        } catch (\Exception $e) {
            \Log::error("❌ Exception in close method: " . $e->getMessage());
            \Log::error("❌ Stack trace: " . $e->getTraceAsString());
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reopen ticket
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reopen($id)
    {
        $user = auth()->user();
        $ticketableType = get_class($user);

        $ticket = SupportTicket::forTicketable($ticketableType, $user->id)->findOrFail($id);

        if (!$ticket->canBeReopened()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket cannot be reopened (must be closed within 7 days)'
            ], 400);
        }

        $ticket->reopen();

        // Log status change
        $ticket->statusHistory()->create([
            'old_status' => 'closed',
            'new_status' => 'open',
            'changed_by' => $user->id,
            'notes' => 'Reopened by user'
        ]);

        // Send notifications
        // event(new TicketReopened($ticket));

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket reopened successfully'
        ]);
    }

    /**
     * Get statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics()
    {
        $user = auth()->user();
        $isAdmin = $user && method_exists($user, 'isAdmin') && $user->isAdmin();

        // If admin, get statistics for all tickets. Otherwise, only user's tickets
        if ($isAdmin) {
            $stats = [
                'total' => SupportTicket::count(),
                'new' => SupportTicket::byStatus('new')->count(),
                'open' => SupportTicket::byStatus('open')->count(),
                'pending' => SupportTicket::byStatus('pending')->count(),
                'resolved' => SupportTicket::byStatus('resolved')->count(),
                'closed' => SupportTicket::byStatus('closed')->count(),
                'unread_messages' => SupportTicket::sum('unread_messages_count'),
                'unassigned' => SupportTicket::whereNull('assigned_to')->count(),
            ];
        } else {
            $ticketableType = get_class($user);
            $stats = [
                'total' => SupportTicket::forTicketable($ticketableType, $user->id)->count(),
                'new' => SupportTicket::forTicketable($ticketableType, $user->id)->byStatus('new')->count(),
                'open' => SupportTicket::forTicketable($ticketableType, $user->id)->byStatus('open')->count(),
                'pending' => SupportTicket::forTicketable($ticketableType, $user->id)->byStatus('pending')->count(),
                'resolved' => SupportTicket::forTicketable($ticketableType, $user->id)->byStatus('resolved')->count(),
                'closed' => SupportTicket::forTicketable($ticketableType, $user->id)->byStatus('closed')->count(),
                'unread_messages' => SupportTicket::forTicketable($ticketableType, $user->id)
                    ->sum('unread_messages_count'),
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    /**
     * Update ticket status (Admin only)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTicketStatus(Request $request, $id)
    {
        $this->checkAdminAccess();

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:new,open,pending,resolved,closed',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket = SupportTicket::findOrFail($id);
        $oldStatus = $ticket->status;

        $ticket->update(['status' => $request->status]);

        // Log status change
        $ticket->statusHistory()->create([
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'changed_by' => auth()->id(),
            'notes' => $request->notes
        ]);

        // Send notification to user
        // event(new TicketStatusChanged($ticket));

        return response()->json([
            'status' => 'success',
            'message' => 'Status updated successfully',
            'data' => $ticket->fresh(['user', 'statusHistory.changedBy'])
        ]);
    }

    /**
     * Update ticket priority (Admin only)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTicketPriority(Request $request, $id)
    {
        $this->checkAdminAccess();

        $validator = Validator::make($request->all(), [
            'priority' => 'required|in:urgent,high,medium,low'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket = SupportTicket::findOrFail($id);
        $ticket->update(['priority' => $request->priority]);

        return response()->json([
            'status' => 'success',
            'message' => 'Priority updated successfully',
            'data' => $ticket->fresh(['user'])
        ]);
    }

    /**
     * Assign ticket to staff (Admin only)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignTicket(Request $request, $id)
    {
        $this->checkAdminAccess();

        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket = SupportTicket::findOrFail($id);
        $ticket->update(['assigned_to' => $request->assigned_to]);

        // Send notification to assigned staff
        // event(new TicketAssigned($ticket));

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket assigned successfully',
            'data' => $ticket->fresh(['user', 'assignedTo'])
        ]);
    }

    /**
     * Add staff reply to ticket (Admin only)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addStaffReply(Request $request, $id)
    {
        $this->checkAdminAccess();

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|min:10|max:2000',
            'attachments' => 'nullable|array|max:3',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket = SupportTicket::findOrFail($id);

        // Check if ticket is closed
        if ($ticket->status === 'closed') {
  return response()->json([
                'status' => 'error',
                'message' => 'Cannot add message to closed ticket'
            ], 400);
        }

        // Create message as staff reply
        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $request->message,
            'is_staff_reply' => true,
        ]);

        // Handle attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $path = AttachmentHelper::storeMessageAttachment($file, $ticket->id);

                    if ($path) {
                        TicketAttachment::create([
                            'ticket_id' => $ticket->id,
                            'message_id' => $message->id,
                            'file_name' => $file->getClientOriginalName(),
                            'file_path' => $path,
                            'file_type' => $file->getClientOriginalExtension(),
                            'file_size' => $file->getSize(),
                            'mime_type' => $file->getMimeType(),
                        ]);
                    }
                }
            }
        }

        // Update ticket status to open if it was new
        if ($ticket->status === 'new') {
            $ticket->update(['status' => 'open']);
        }

        // Send notification to user
        \Log::info('🚀 Admin is sending reply notification', [
            'admin_id' => auth()->id(),
            'admin_email' => auth()->user()->email ?? 'N/A',
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'message_id' => $message->id,
        ]);

        $this->notifyTicketOwner(
            $ticket,
            'رد جديد على تذكرتك',
            "تم الرد على تذكرتك #{$ticket->ticket_number} من قبل فريق الدعم",
            [
                'type' => 'staff_reply',
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'message_id' => $message->id,
            ]
        );

        \Log::info('✅ Admin reply notification process completed', [
            'ticket_id' => $ticket->id,
            'message_id' => $message->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Reply added successfully',
            'data' => $message->load('attachments')
        ], 201);
    }
}
