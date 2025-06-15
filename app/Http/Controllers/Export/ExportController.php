<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Items\Item;
use App\Models\Items\ItemTransaction;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportController extends Controller
{
    public function exportItems()
    {
        $items = Item::with(['category'])->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'SKU');
        $sheet->setCellValue('B1', 'ASIN');
        $sheet->setCellValue('C1', 'Category');
        $sheet->setCellValue('D1', 'Name');
        $sheet->setCellValue('E1', 'Weight');
        $sheet->setCellValue('F1', 'Volume');
        $sheet->setCellValue('G1', 'Price');
        $sheet->setCellValue('H1', 'Cargo Fee');
        $sheet->setCellValue('I1', 'Note');
        $sheet->setCellValue('J1', 'Customer Number');
        $sheet->setCellValue('K1', 'New Customer Number');
        $sheet->setCellValue('L1', 'Image URL');
        $sheet->setCellValue('M1', 'Image 2');
        $sheet->setCellValue('N1', 'Image 3');
        $sheet->setCellValue('O1', 'Opening Stock');

        $row = 2;
        foreach ($items as $item) {
            $sheet->setCellValue('A' . $row, $item->sku);
            $sheet->setCellValue('B' . $row, $item->asin);
            $sheet->setCellValue('C' . $row, $item->category->name ?? '');
            $sheet->setCellValue('D' . $row, $item->name);
            $sheet->setCellValue('E' . $row, $item->weight);
            $sheet->setCellValue('F' . $row, $item->volume);
            $sheet->setCellValue('G' . $row, $item->purchase_price);
            $sheet->setCellValue('H' . $row, $item->cargo_fee);
            $sheet->setCellValue('I' . $row, $item->description);
            $sheet->setCellValue('J' . $row, $item->cust_num);
            $sheet->setCellValue('K' . $row, $item->cust_num_t);
            $sheet->setCellValue('L' . $row, $item->image_url);
            $sheet->setCellValue('M' . $row, $item->image2);
            $sheet->setCellValue('N' . $row, $item->image3);

            // Get opening stock quantity from item transactions
            $openingStock = ItemTransaction::where('item_id', $item->id)
                ->where('transaction_type', 'opening')
                ->sum('quantity');
            $sheet->setCellValue('O' . $row, $openingStock);

            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'items-' . date('Y-m-d-His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}