<?php

namespace App\Exports;

use App\Models\ReportPemasukan;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class ExportEnvtInMain implements FromCollection, ShouldQueue, WithTitle, WithEvents
{

    use Exportable, Queueable;

    public $timeout = 1200; // 20 minutes

    protected $fromDate;
    protected $toDate;
    protected $keywords;


    public function __construct(String $fromDate, String $toDate, $keywords)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->keywords = $keywords;
    }

    /**
     * Get collection with grouped data and subtotals
     */
    public function collection()
    {
        $keyword = $this->keywords;
        $fromDate = $this->fromDate;
        $toDate = $this->toDate;

        $tableName = (new ReportPemasukan)->getTable();
        $query = ReportPemasukan::selectRaw("CASE 
                WHEN BCTYPE = 9 THEN 'BC40'
                WHEN BCTYPE = 10 THEN 'BC27'
                WHEN BCTYPE = 11 THEN 'BC23'
                WHEN BCTYPE = 12 THEN 'BC262'
                WHEN BCTYPE = 13 THEN 'BC30'
                WHEN BCTYPE = 14 THEN 'BC25'
                WHEN BCTYPE = 15 THEN 'BC261'
                WHEN BCTYPE = 16 THEN 'BC27'
                WHEN BCTYPE = 17 THEN 'BC41'
                ELSE 'UNKNOWN'
            END as BC_CODE_NAME")
            ->addSelect([
                "NOMORDAFTAR",
                "TANGGALDAFTAR",
                "NOMORPENERIMAAN",
                "TANGGALPENERIMAAN",
                "PENGIRIM",
                "KODEBARANG",
                "NAMABARANG",
                "JUMLAH",
                "SATUAN",
                "NILAI",
                "CURRENCY"
            ])
            ->orderBy("$tableName.NOMORDAFTAR", "desc")
            ->orderBy("$tableName.KODEBARANG")
            ->whereBetween("$tableName.TANGGALDAFTAR", [$fromDate, $toDate]);

        if ($keyword != null) {
            $query->when($keyword, function ($query, $keyword) use ($tableName) {
                $query->where(function ($q) use ($keyword, $tableName) {
                    $q->where("$tableName.NOMORDAFTAR", 'like', "%$keyword%")
                        ->orWhere("$tableName.KODEBARANG", 'like', "%$keyword%")
                        ->orWhere("$tableName.NAMABARANG", 'like', "%$keyword%")
                        ->orWhere("$tableName.NOMORPENERIMAAN", 'like', "%$keyword%")
                        ->orWhere("$tableName.PENGIRIM", 'like', "%$keyword%");
                });
            });
        }

        $data = $query->get();
        
        // Group data by NOMORDAFTAR and add subtotals
        $result = new Collection();
        $grouped = $data->groupBy('NOMORDAFTAR');
        $groupNumber = 0;
        
        foreach ($grouped as $nomor => $items) {
            $groupNumber++;
            $subtotalJumlah = 0;
            $subtotalNilai = 0;
            $isFirstRowInGroup = true;
            
            // Add data rows for this group
            foreach ($items as $item) {
                $result->push([
                    $isFirstRowInGroup ? $groupNumber : '', // Group number only on first row
                    $item->BC_CODE_NAME,
                    $item->NOMORDAFTAR,
                    $item->TANGGALDAFTAR,
                    $item->NOMORPENERIMAAN,
                    $item->TANGGALPENERIMAAN,
                    $item->PENGIRIM,
                    $item->KODEBARANG,
                    $item->NAMABARANG,
                    $item->JUMLAH,
                    $item->SATUAN,
                    $item->CURRENCY,
                    $item->NILAI,
                ]);
                
                $subtotalJumlah += $item->JUMLAH;
                $subtotalNilai += $item->NILAI;
                $isFirstRowInGroup = false;
            }
            
            // Add subtotal row for this group
            $result->push([
                'Sub Total', // Row number - will be in column A and merged to I
                '', // BC_CODE_NAME
                '', // NOMORDAFTAR
                '', // TANGGALDAFTAR
                '', // NOMORPENERIMAAN
                '', // TANGGALPENERIMAAN
                '', // PENGIRIM
                '', // KODEBARANG
                '', // NAMABARANG
                $subtotalJumlah, // Jumlah
                '', // Satuan
                '', // Currency
                $subtotalNilai, // Nilai
            ]);
        }
        
        return $result;
    }
    /**
     * Sheet title
     */
    public function title(): string
    {
        return 'Laporan Pemasukan';
    }



    /**
     * Configure sheet events
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $sheet->insertNewRowBefore(1, 10);
                
                // Company header
                $sheet->setCellValue('A1', 'HOGY');
                $sheet->mergeCells('A1:F1');
                
                $sheet->setCellValue('A2', 'PT Hogy Indonesia');
                $sheet->mergeCells('A2:F2');
                
                $sheet->setCellValue('A3', 'MM 2100 Industrial Town, Blok M3-1');
                $sheet->mergeCells('A3:F3');
                
                $sheet->setCellValue('A4', 'Cikarang Barat, Bekasi 17520');
                $sheet->mergeCells('A4:F4');
                
                $sheet->setCellValue('A5', 'P: +62 21 8980165, F: +62 21 8980166, E: purchasing@hogy.co.id');
                $sheet->mergeCells('A5:F5');
                
                // Report title
                $sheet->setCellValue('A6', 'Laporan Pemasukan Barang Per Dokumen Pabean');
                $sheet->mergeCells('A6:F6');
                
                $sheet->setCellValue('A7', 'Period ' . $this->fromDate . ' to ' . $this->toDate);
                $sheet->mergeCells('A7:F7');
                
                // Table headers - Row 9 (first header row)
                $sheet->setCellValue('A9', 'No');
                $sheet->setCellValue('B9', 'Dokumen pabean');
                $sheet->setCellValue('E9', 'Bukti penerimaan barang');
                $sheet->setCellValue('G9', 'Pengirim barang');
                $sheet->setCellValue('H9', 'Kode barang');
                $sheet->setCellValue('I9', 'Nama barang');
                $sheet->setCellValue('J9', 'Jumlah');
                $sheet->setCellValue('K9', 'Satuan');
                $sheet->setCellValue('L9', 'Currency');
                $sheet->setCellValue('M9', 'Nilai');
                
                // Table headers - Row 10 (second header row)
                $sheet->setCellValue('B10', 'Jenis');
                $sheet->setCellValue('C10', 'Nomor');
                $sheet->setCellValue('D10', 'Tanggal');
                $sheet->setCellValue('E10', 'Nomor');
                $sheet->setCellValue('F10', 'Tanggal');
                
                // Style company header
                $sheet->getStyle('A1:A5')->getFont()->setBold(false)->setSize(9);
                $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A7')->getFont()->setBold(false)->setSize(9);
                
                // Header row styling (row 9-10)
                $sheet->getStyle('A9:M10')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 10,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'C0504D'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                
                // Merge cells for headers
                $sheet->mergeCells('A9:A10');
                $sheet->mergeCells('B9:D9');
                $sheet->mergeCells('E9:F9');
                $sheet->mergeCells('G9:G10');
                $sheet->mergeCells('H9:H10');
                $sheet->mergeCells('I9:I10');
                $sheet->mergeCells('J9:J10');
                $sheet->mergeCells('K9:K10');
                $sheet->mergeCells('L9:L10');
                $sheet->mergeCells('M9:M10');

                // Auto-size columns
                foreach (range('A', 'M') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                
                // Style subtotal rows and format data
                $lastRow = $sheet->getHighestRow();
                if ($lastRow > 10) {
                    // Loop through all rows to identify and style subtotal rows
                    for ($i = 11; $i <= $lastRow; $i++) {
                        $cellA = $sheet->getCell('A' . $i)->getValue();
                        
                        // Check if this is a subtotal row (starts with "Sub Total")
                        if (is_string($cellA) && strpos($cellA, 'Sub Total') === 0) {
                            // Style subtotal row with fill color
                            $sheet->getStyle('A' . $i . ':M' . $i)->applyFromArray([
                                'font' => [
                                    'bold' => true,
                                    'size' => 10,
                                ],
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'D9E1F2'], // Light blue color
                                ],
                                'alignment' => [
                                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                                ],
                            ]);
                            
                            // Merge cells for subtotal label (A to I for the SUBTOTAL text)
                            $sheet->mergeCells('A' . $i . ':I' . $i);
                        }
                    }
                    
                    // Apply borders to all data cells
                    $sheet->getStyle('A11:M' . $lastRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                    ]);
                    
                    // Format Jumlah and Nilai columns with thousand separator and 2 decimals
                    $sheet->getStyle('J11:J' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle('M11:M' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
                    
                    // Add Grand Total row
                    $grandTotalRow = $lastRow + 1;
                    $sheet->setCellValue('I' . $grandTotalRow, 'GRAND TOTAL');
                    
                    // Sum only data rows (excluding subtotal rows) - sum column J and M where column A has numbers
                    $sumFormulaJ = '=SUM(J11:J' . $lastRow . ')/2';
                    $sumFormulaM = '=SUM(M11:M' . $lastRow . ')/2';
                    
                    $sheet->setCellValue('J' . $grandTotalRow, $sumFormulaJ);
                    $sheet->setCellValue('M' . $grandTotalRow, $sumFormulaM);
                    
                    // Style Grand Total row with header color
                    $sheet->getStyle('A' . $grandTotalRow . ':M' . $grandTotalRow)->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF'],
                            'size' => 10,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'C0504D'],
                        ],
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                    ]);
                    
                    // Format Grand Total numbers with thousand separator and 2 decimals
                    $sheet->getStyle('J' . $grandTotalRow)->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle('M' . $grandTotalRow)->getNumberFormat()->setFormatCode('#,##0.00');
                    
                    // Merge cells for "GRAND TOTAL" label
                    $sheet->mergeCells('A' . $grandTotalRow . ':I' . $grandTotalRow);
                }
            },
        ];
    }
}
