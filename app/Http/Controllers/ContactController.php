<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewContactMessage;

class ContactController extends Controller
{
    public function index()
    {
        // Check if user is authenticated
        if (auth()->check()) {
            return view('contact.index');
        }

        // Show public contact page for guests
        return view('contact.public');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ], [
            'name.required' => 'الاسم مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'subject.required' => 'الموضوع مطلوب',
            'message.required' => 'الرسالة مطلوبة',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $contact = Contact::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        // Send email notification (optional - uncomment to enable)
        // try {
        //     Mail::to(config('mail.from.address'))->send(new NewContactMessage($contact));
        // } catch (\Exception $e) {
        //     \Log::error('Failed to send contact notification email: ' . $e->getMessage());
        // }

        return redirect()->back()->with('success', 'تم إرسال رسالتك بنجاح. سنتواصل معك قريباً');
    }

    public function list()
    {
        return view('contact.list');
    }

    public function datatableList(Request $request)
    {
        $contacts = Contact::query();

        return datatables()->of($contacts)
            ->addColumn('action', function ($contact) {
                return '<button class="btn btn-sm btn-primary view-contact" data-id="' . $contact->id . '">عرض</button>
                        <button class="btn btn-sm btn-danger delete-contact" data-id="' . $contact->id . '">حذف</button>';
            })
            ->editColumn('created_at', function ($contact) {
                return $contact->created_at->format('Y-m-d H:i');
            })
            ->editColumn('status', function ($contact) {
                $badges = [
                    'pending' => '<span class="badge bg-warning">قيد الانتظار</span>',
                    'replied' => '<span class="badge bg-success">تم الرد</span>',
                    'closed' => '<span class="badge bg-secondary">مغلق</span>',
                ];
                return $badges[$contact->status] ?? $contact->status;
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function show($id)
    {
        $contact = Contact::findOrFail($id);
        return response()->json($contact);
    }

    public function delete(Request $request)
    {
        $contact = Contact::findOrFail($request->id);
        $contact->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف الرسالة بنجاح']);
    }

    public function updateStatus(Request $request)
    {
        $contact = Contact::findOrFail($request->id);
        $contact->status = $request->status;
        $contact->save();

        return response()->json(['success' => true, 'message' => 'تم تحديث الحالة بنجاح']);
    }
}
