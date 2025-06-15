<?php

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class ImportController extends Controller
{
    /**
     * The view name for the import form
     *
     * @var string
     */
    protected $viewName;

    /**
     * The sample file path
     *
     * @var string
     */
    protected $sampleFilePath;

    /**
     * The success message translation key
     *
     * @var string
     */
    protected $successMessageKey;

    /**
     * Display the import form
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view($this->viewName);
    }

    /**
     * Import data from Excel/CSV file
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Just return a success message for now
        return response()->json([
            'status' => true,
            'message' => __($this->successMessageKey)
        ]);
    }

    /**
     * Download sample import file
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadSample()
    {
        $file = public_path($this->sampleFilePath);

        // If file doesn't exist, return a message
        if (!file_exists($file)) {
            return back()->with('error', 'Sample file not found.');
        }

        return response()->download($file);
    }
}