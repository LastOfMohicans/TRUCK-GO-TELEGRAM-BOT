<?php
declare(strict_types=1);

namespace App\Exports\Storages;

use App\Models\Material;
use App\Models\Vendor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Sheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StoragesExportBase implements WithEvents, WithStyles, WithColumnWidths
{

    /**
     * Цель документа
     * @var string
     */
    protected string $reason = "";
    protected const RGBA_GRAY = "d3d3d3";
    protected const RGBA_LIGHT_GOLD = "b29700";

    protected Collection $materials;

    protected Vendor $vendor;
    protected int $countOfStorages = 20;

    protected const COLUMN_STORAGE_ID = "B";
    protected const COLUMN_STORAGE_ADDRESS = "C";
    protected const COLUMN_STORAGE_COORDINATES= "D";
    protected const COLUMN_START_MATERIAL_NAME = "E";
    protected const COLUMN_START_MATERIAL_NAME_INDEX = 10;
    protected const COLUMN_START_MATERIAL_ID = "F";

    protected const COLUMN_VENDOR_MATERIAL_ID = "G";
    protected const COLUMN_VENDOR_PRICE = "H";

    protected const COLUMN_SERVICE_FEE = "I";
    protected const COLUMN_PRICE_FOR_CLIENT = "J";
    protected const COLUMN_VENDOR_MATERIAL_INCOME = "K";
    protected const COLUMN_VENDOR_DELIVERY_PRICE = "L";
    protected const COLUMN_DELIVERY_FEE = "M";
    protected const COLUMN_OVER_DELIVERY_FEE = "N";
    protected const COLUMN_VENDOR_DELIVERY_INCOME = "O";
    protected const COLUMN_IS_AVAILABLE = "P";
    protected const COLUMN_VENDOR_NAME = "H2";
    protected const COLUMN_VENDOR_INN = "I2";
    protected const COLUMN_VENDOR_ID = "J2";
    protected const COLUMN_FILE_CREATED_AT = "K2";
    protected const COLUMN_REASON = "L2";

    /**
     * @param Collection $materials
     * @param Vendor $vendor
     */
    public function __construct(Collection $materials, Vendor $vendor)
    {
        $this->materials = $materials;
        $this->vendor = $vendor;
    }


    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $event->sheet->mergeCells('A1:D8')
                    ->setCellValue("A1", "Точки отгрузки");

                $event->sheet->setCellValue("A9", "Номер точки:");
                $event->sheet->setCellValue("B9", "Айди точки:");
                $event->sheet->setCellValue("C9", "Адрес точки:");
                $event->sheet->setCellValue("D9", "Координаты точки:");

                $event->sheet->mergeCells('E1:E9')
                    ->setCellValue("E1", "Материалы:");

                $event->sheet->mergeCells('F1:G8')
                    ->setCellValue("F1", "Код материала:");

                $event->sheet->setCellValue("F9", "Сервиса:")
                    ->setCellValue("G9", "Поставщика:");

                $event->sheet->mergeCells('P1:P9')
                    ->setCellValue("P1", "В наличии");

                // make gray color
                $event->sheet->getStyle('A1:O3')
                    ->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => self::RGBA_GRAY,
                            ],
                        ],
                    ]);


                // закрепляем строки с A до D 10
                $event->sheet->getDelegate()->freezePane('E10');

                $this->makeVendorInfoMenu($event);
                $this->makePriceMenu($event);
            },

            AfterSheet::class => function (AfterSheet $event) {

                $event->sheet->getDelegate()->getRowDimension(5)->setRowHeight(150);


                $this->fillGeneralInfo($event);
                $this->setFileReason($event);

                $this->fillMaterials($event);

                // Get the highest row and column with data
                $highestRow = $event->sheet->getHighestRow();
                $highestColumn = $event->sheet->getHighestColumn();

                // Calculate the range
                $cellRange = 'A1:' . $highestColumn . $highestRow;

                // Apply the border style to the range
                $event->sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ]);
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        //possible A:C, A1, A1:C9
        return [
            'A1:G9' => [
                'font' => [
                    'size' => 14,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => self::RGBA_GRAY,
                    ],
                ],
            ],
            'A9:G9' => [
                'font' => [
                    'size' => 10,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            'H4:O5' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => "90ee90",
                    ],
                ],
                'font' => [
                    'size' => 10,
                ],
                'alignment' => [
                    'wrap_text' => true,
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            'K5' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => self::RGBA_LIGHT_GOLD,
                    ],
                ],
                'font' => [
                    'size' => 10,
                ],
                'alignment' => [
                    'wrap_text' => true,
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            'O5' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => self::RGBA_LIGHT_GOLD,
                    ],
                ],
                'font' => [
                    'size' => 10,
                ],
                'alignment' => [
                    'wrap_text' => true,
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            'P1' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => self::RGBA_GRAY,
                    ],
                ],
                'font' => [
                    'size' => 10,
                ],
                'alignment' => [
                    'wrap_text' => true,
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 20,
            'D' => 40,
            'E' => 20,
            'F' => 20,
            'G' => 20,
            'H' => 20,
            'I' => 20,
            'J' => 20,
            'K' => 20,
            'L' => 20,
            'M' => 20,
            'N' => 20,
            'O' => 20,
        ];
    }


    protected function makeVendorInfoMenu(BeforeSheet $event)
    {
        $event->sheet->setCellValue("H1", "Поставщик:");
        $event->sheet->setCellValue("I1", "ИНН Поставщика:");
        $event->sheet->setCellValue("J1", "Номер Поставщика:");
        $event->sheet->setCellValue("K1", "Дата создания запроса:");
        $event->sheet->setCellValue("L1", "Основание");


        // make gray color
        $event->sheet->getStyle('H1:P3')
            ->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => self::RGBA_GRAY,
                    ],
                ],
            ]);
    }

    protected function makePriceMenu(BeforeSheet $event)
    {
        $event->sheet->mergeCells("H3:O4")->setCellValue("H3", "Актуальная цена:");

        $event->sheet->mergeCells("H5:H9");
        $event->sheet->setCellValue("H5", "Ваша цена за материал М3")
            ->getStyle("H")->getAlignment()->setWrapText(true);

        $event->sheet->mergeCells("I5:I9");
        $event->sheet->setCellValue("I5", "Комиссия сервиса - материал М3(15 %)")
            ->getStyle("I")->getAlignment()->setWrapText(true);

        $event->sheet->mergeCells("J5:J9");
        $event->sheet->setCellValue("J5", "Цена за материал для потребителя")
            ->getStyle("J")->getAlignment()->setWrapText(true);

        $event->sheet->mergeCells("K5:K9");
        $event->sheet->setCellValue("K5", "Выход за материал поставщику:")
            ->getStyle("K")->getAlignment()->setWrapText(true);

        $event->sheet->mergeCells("L5:L9");
        $event->sheet->setCellValue("L5", "Ваша цена за доставку на М3\n за 1км маршрута от адреса точки отгрузки")
            ->getStyle("L")->getAlignment()->setWrapText(true);

        $event->sheet->mergeCells("M5:M9");
        $event->sheet->setCellValue("M5", "Комиссия сервиса - доставка(5 %)")
            ->getStyle("M")->getAlignment()->setWrapText(true);

        $event->sheet->mergeCells("N5:N9");
        $event->sheet->setCellValue("N5", "Цена за доставку на М3 за 1км маршрута от адреса точки отгрузки для потребителя")
            ->getStyle("N")->getAlignment()->setWrapText(true);

        $event->sheet->mergeCells("O5:O9");
        $event->sheet->setCellValue("O5", "Выход за доставку поставщику:")
            ->getStyle("O")->getAlignment()->setWrapText(true);
    }


    /**
     * Заполняем данные поставщика
     *
     * @param AfterSheet $event
     * @return void
     */
    protected function fillGeneralInfo(AfterSheet $event)
    {
        $sheet = $event->getSheet();


        $sheet->setCellValue(self::COLUMN_VENDOR_NAME, $this->vendor->company_name);
        $sheet->setCellValue(self::COLUMN_VENDOR_INN, $this->vendor->inn);
        $sheet->setCellValue(self::COLUMN_VENDOR_ID, $this->vendor->id);
        $sheet->setCellValue(self::COLUMN_FILE_CREATED_AT, Carbon::now()->toDateTime());
    }

    protected function setFileReason(AfterSheet $event)
    {
        $sheet = $event->getSheet();
        $sheet->setCellValue(self::COLUMN_REASON, $this->reason);
    }


    protected function fillMaterials(AfterSheet $event)
    {
        $sheet = $event->getSheet();
        $this->addEmptyStorages($sheet, $this->countOfStorages);
    }

    protected function addEmptyStorages(Sheet $sheet, int $countToAdd, $columnStartIndex = null, $columnIndex = null, $columnEnd = null)
    {
        if ($columnStartIndex == null) {
            $columnStartIndex = self::COLUMN_START_MATERIAL_NAME_INDEX;
        }
        if ($columnIndex == null) {
            $columnIndex = self::COLUMN_START_MATERIAL_NAME_INDEX;
        }
        if ($columnEnd == null) {
            $columnEnd = 0;
        }


        for ($i = 1; $i < $countToAdd + 1; $i++) {
            /** @var Material $material */
            foreach ($this->materials as $material) {
                $columnEnd = $this->fillMaterial($sheet, $material->full_name, $material->id, $columnIndex);
                $columnIndex++;
            }


            $sheet->mergeCells("A{$columnStartIndex}:A" . $columnEnd)
                ->setCellValue("A{$columnStartIndex}", "Точка отгрузки №{$i}")
                ->getStyle("A{$columnStartIndex}:C{$columnIndex}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => self::RGBA_GRAY,
                        ],
                    ],
                ]);
            $sheet->mergeCells("B{$columnStartIndex}:B" . $columnEnd);
            $sheet->mergeCells("C{$columnStartIndex}:C" . $columnEnd);
            $sheet->mergeCells("D{$columnStartIndex}:D" . $columnEnd);

            $sheet->getStyle("A{$columnStartIndex}:C" . $columnEnd)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $sheet->getStyle("E{$columnStartIndex}:F" . $columnEnd)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => self::RGBA_GRAY,
                    ],
                ],
            ]);

            $columnStartIndex = $columnIndex;
        }
    }

    protected function fillMaterial(
        Sheet $sheet,
              $materialName,
              $materialID,
        int   $columnIndex,
              $storageID = null,
              $address = null,
              $coordinates = null,
              $vendorMaterialID = null,
              $price = null,
              $deliveryPrice = null,
              $isAvailable = null,
    )
    {
        // заполняем данные материала которые должны быть всегда
        $sheet->setCellValue(self::COLUMN_START_MATERIAL_NAME . $columnIndex, $materialName);
        $sheet->setCellValue(self::COLUMN_START_MATERIAL_ID . $columnIndex, $materialID);

        // заполнения полей юзера
        if ($storageID) {
            $sheet->setCellValue(self::COLUMN_STORAGE_ID . $columnIndex, $storageID);
        }
        if ($address) {
            $sheet->setCellValue(self::COLUMN_STORAGE_ADDRESS . $columnIndex, $address);
        }
        if ($coordinates) {
            $sheet->setCellValue(self::COLUMN_STORAGE_COORDINATES . $columnIndex, $coordinates);
        }
        if ($vendorMaterialID) {
            $sheet->setCellValue(self::COLUMN_VENDOR_MATERIAL_ID . $columnIndex, $vendorMaterialID);
        }
        if ($price) {
            $sheet->setCellValue(self::COLUMN_VENDOR_PRICE . $columnIndex, $price);
        }
        if ($deliveryPrice) {
            $sheet->setCellValue(self::COLUMN_VENDOR_DELIVERY_PRICE . $columnIndex, $deliveryPrice);
        }
        if ($isAvailable) {
            $sheet->setCellValue(self::COLUMN_IS_AVAILABLE . $columnIndex, "да");
        } else if (!is_null($isAvailable)) {
            $sheet->setCellValue(self::COLUMN_IS_AVAILABLE . $columnIndex, "нет");
        }


        // заполняем расчет комиссии и доставки
        $sheet->setCellValue(self::COLUMN_SERVICE_FEE . $columnIndex, "=H{$columnIndex} * 0.15",);
        $sheet->setCellValue(self::COLUMN_PRICE_FOR_CLIENT . $columnIndex, "=H{$columnIndex}");
        $sheet->setCellValue(self::COLUMN_VENDOR_MATERIAL_INCOME . $columnIndex, "=J{$columnIndex} - I{$columnIndex}");
        $sheet->getStyle(self::COLUMN_SERVICE_FEE . $columnIndex . ":" . self::COLUMN_VENDOR_MATERIAL_INCOME . $columnIndex)
            ->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => self::RGBA_GRAY,
                    ],
                ],
            ]);

        $sheet->setCellValue(self::COLUMN_DELIVERY_FEE . $columnIndex, "=L{$columnIndex} * 0.05");
        $sheet->setCellValue(self::COLUMN_OVER_DELIVERY_FEE . $columnIndex, "=L{$columnIndex}");
        $sheet->setCellValue(self::COLUMN_VENDOR_DELIVERY_INCOME . $columnIndex, "=N{$columnIndex} - M{$columnIndex}");
        $sheet->getStyle(self::COLUMN_DELIVERY_FEE . $columnIndex . ":" . self::COLUMN_VENDOR_DELIVERY_INCOME . $columnIndex)
            ->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => self::RGBA_GRAY,
                    ],
                ],
            ]);

        return $columnIndex;
    }
}

