<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use App\Models\Sale\Sale;
use App\Models\Sale\SaleOrder;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use App\Services\GeneralDataService;
use App\Models\User;
use App\Services\StatusHistoryService;

class DeliveryController extends Controller
{
    use FormatNumber;
    use FormatsDateInputs;

    private $generalDataService;
    private $statusHistoryService;

    public function __construct(GeneralDataService $generalDataService, StatusHistoryService $statusHistoryService)
    {
        $this->generalDataService = $generalDataService;
        $this->statusHistoryService = $statusHistoryService;
    }

    /**
     * Display the delivery dashboard
     *
     * @return \Illuminate\View\View
     */
    public function dashboard(): View
    {
        return view('delivery.dashboard');
    }

    /**
     * List pending deliveries
     *
     * @return \Illuminate\View\View
     */
    public function pendingDeliveries(): View
    {
        return view('delivery.pending');
    }

    /**
     * List completed deliveries
     *
     * @return \Illuminate\View\View
     */
    public function completedDeliveries(): View
    {
        return view('delivery.completed');
    }

    /**
     * Datatable for pending deliveries
     */
    public function pendingDeliveriesDatatable(Request $request)
    {
        $data = Sale::with('user', 'party')
            ->where('sales_status', 'Delivery')
            ->when($request->party_id, function ($query) use ($request) {
                return $query->where('party_id', $request->party_id);
            })
            ->when($request->from_date, function ($query) use ($request) {
                return $query->where('sale_date', '>=', $this->toSystemDateFormat($request->from_date));
            })
            ->when($request->to_date, function ($query) use ($request) {
                return $query->where('sale_date', '<=', $this->toSystemDateFormat($request->to_date));
            });

        return DataTables::of($data)
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && $request->search['value']) {
                    $searchTerm = $request->search['value'];
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('sale_code', 'like', "%{$searchTerm}%")
                            ->orWhere('reference_no', 'like', "%{$searchTerm}%")
                            ->orWhereHas('party', function ($partyQuery) use ($searchTerm) {
                                $partyQuery->where('first_name', 'like', "%{$searchTerm}%")
                                    ->orWhere('last_name', 'like', "%{$searchTerm}%");
                            })
                            ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                                $userQuery->where('username', 'like', "%{$searchTerm}%");
                            });
                    });
                }
            })
            ->addIndexColumn()
            ->addColumn('created_at', function ($row) {
                return $row->created_at->format(app('company')['date_format']);
            })
            ->addColumn('username', function ($row) {
                return $row->user->username ?? '';
            })
            ->addColumn('sale_date', function ($row) {
                return $row->formatted_sale_date;
            })
            ->addColumn('sale_code', function ($row) {
                return $row->sale_code;
            })
            ->addColumn('party_name', function ($row) {
                return $row->party->first_name . " " . $row->party->last_name;
            })
            ->addColumn('grand_total', function ($row) {
                return $this->formatWithPrecision($row->grand_total);
            })
            ->addColumn('balance', function ($row) {
                return $this->formatWithPrecision($row->grand_total - $row->paid_amount);
            })
            ->addColumn('sales_status', function ($row) {
                return $row->sales_status;
            })
            ->addColumn('action', function ($row) {
                $id = $row->id;

                $detailsUrl = route('sale.invoice.details', ['id' => $id]);
                $updateStatusUrl = route('delivery.update-status', ['id' => $id]);

                $actionBtn = '<div class="dropdown ms-auto">
                            <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="' . $detailsUrl . '"><i class="bx bx-show-alt"></i> ' . __('app.details') . '</a>
                                </li>
                                <li>
                                    <a class="dropdown-item update-delivery-status" data-id="' . $id . '" href="javascript:void(0)"><i class="bx bx-check-circle"></i> ' . __('delivery.mark_as_delivered') . '</a>
                                </li>
                            </ul>
                        </div>';
                return $actionBtn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Datatable for completed deliveries
     */
    public function completedDeliveriesDatatable(Request $request)
    {
        $data = Sale::with('user', 'party')
            ->where('sales_status', 'Completed')
            ->when($request->party_id, function ($query) use ($request) {
                return $query->where('party_id', $request->party_id);
            })
            ->when($request->from_date, function ($query) use ($request) {
                return $query->where('sale_date', '>=', $this->toSystemDateFormat($request->from_date));
            })
            ->when($request->to_date, function ($query) use ($request) {
                return $query->where('sale_date', '<=', $this->toSystemDateFormat($request->to_date));
            });

        return DataTables::of($data)
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && $request->search['value']) {
                    $searchTerm = $request->search['value'];
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('sale_code', 'like', "%{$searchTerm}%")
                            ->orWhere('reference_no', 'like', "%{$searchTerm}%")
                            ->orWhereHas('party', function ($partyQuery) use ($searchTerm) {
                                $partyQuery->where('first_name', 'like', "%{$searchTerm}%")
                                    ->orWhere('last_name', 'like', "%{$searchTerm}%");
                            })
                            ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                                $userQuery->where('username', 'like', "%{$searchTerm}%");
                            });
                    });
                }
            })
            ->addIndexColumn()
            ->addColumn('created_at', function ($row) {
                return $row->created_at->format(app('company')['date_format']);
            })
            ->addColumn('username', function ($row) {
                return $row->user->username ?? '';
            })
            ->addColumn('sale_date', function ($row) {
                return $row->formatted_sale_date;
            })
            ->addColumn('sale_code', function ($row) {
                return $row->sale_code;
            })
            ->addColumn('party_name', function ($row) {
                return $row->party->first_name . " " . $row->party->last_name;
            })
            ->addColumn('grand_total', function ($row) {
                return $this->formatWithPrecision($row->grand_total);
            })
            ->addColumn('balance', function ($row) {
                return $this->formatWithPrecision($row->grand_total - $row->paid_amount);
            })
            ->addColumn('sales_status', function ($row) {
                return $row->sales_status;
            })
            ->addColumn('delivered_at', function ($row) {
                // Get the status history for when it was marked as completed
                $statusHistory = $row->statusHistory()->where('status', 'Completed')->latest()->first();
                return $statusHistory ? $statusHistory->formated_status_date : '';
            })
            ->addColumn('delivered_by', function ($row) {
                // Get the status history for when it was marked as completed
                $statusHistory = $row->statusHistory()->where('status', 'Completed')->latest()->first();
                return $statusHistory ? $statusHistory->createdBy->username : '';
            })
            ->make(true);
    }

    /**
     * Get delivery users for assignment
     */
    public function getDeliveryUsers(): JsonResponse
    {
        $deliveryUsers = User::whereHas('role', function ($query) {
            $query->where('name', 'Delivery');
        })->get();

        return response()->json([
            'status' => true,
            'data' => $deliveryUsers
        ]);
    }

    /**
     * Update delivery status
     */
    public function updateDeliveryStatus(Request $request, $id)
    {
        try {
            $sale = Sale::findOrFail($id);
            
            // Update status to completed
            $sale->sales_status = 'Completed';
            $sale->save();
            
            // Record status history
            $this->statusHistoryService->RecordStatusHistory($sale);
            
            return response()->json([
                'status' => true,
                'message' => __('delivery.status_updated_successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('app.something_went_wrong')
            ], 500);
        }
    }
}