<?php
declare(strict_types=1);

namespace App\Exports\Storages;


use App\Models\Material;
use App\Models\Vendor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EditStoragesExport extends StoragesExportBase
{
    protected Collection $vendorStoragesWithMaterials;
    protected Collection $activeMaterials;

    protected string $reason = 'Редактирование точек и материалов';

    public function __construct(Collection $materials,
                                Vendor     $vendor,
                                Collection $vendorStoragesWithMaterials,
                                Collection $activeMaterials,
    )
    {
        parent::__construct($materials, $vendor);
        $this->vendorStoragesWithMaterials = $vendorStoragesWithMaterials;
        $this->activeMaterials = $activeMaterials;
    }


    protected function fillMaterials(AfterSheet $event)
    {
        $activeMaterials = $this->activeMaterials->toArray();
        $materialIDTOMaterialName = [];

        /** @var Material $material */
        foreach ($activeMaterials as $material) {
            $materialIDTOMaterialName[$material['id']] = $material['full_name'];
        }

        $sheet = $event->getSheet();
        $columnStartIndex = self::COLUMN_START_MATERIAL_NAME_INDEX;
        $columnIndex = self::COLUMN_START_MATERIAL_NAME_INDEX;
        $columnEnd = 0;

        $pointNumber = 1;
        foreach ($this->vendorStoragesWithMaterials as $storageWithMaterials) {
            $existMaterialInStorage = [];
            foreach ($storageWithMaterials['materials'] as $material) {
                $coordinates = $storageWithMaterials['latitude'] . ' ' . $storageWithMaterials['longitude'];
                $columnEnd = $this->fillMaterial(
                    $sheet,
                    $material['material']['full_name'],
                    $material['material_id'],
                    $columnIndex,
                    $storageWithMaterials['id'],
                    $storageWithMaterials['address'],
                    $coordinates,
                    $material['vendor_material_id'],
                    $material['cubic_meter_price'],
                    $material['delivery_cost_per_cubic_meter_per_kilometer'],
                    $material['is_available'],
                );
                $columnIndex++;
                $existMaterialInStorage[$material['material_id']] = $material['material_id'];
            }

            foreach ($materialIDTOMaterialName as $materialID => $materialName) {
                if (isset($existMaterialInStorage[$materialID])) {
                    continue;
                }
                $columnEnd = $this->fillMaterial($sheet, $materialName, $materialID, $columnIndex,);
                $columnIndex++;
            }


            // объединяем строки А точек отгрузки
            // и делаем А серыми
            $sheet->mergeCells("A{$columnStartIndex}:A" . $columnEnd)
                ->setCellValue("A{$columnStartIndex}", "Точка отгрузки №{$pointNumber}")
                ->getStyle("A{$columnStartIndex}:A" . $columnEnd)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => self::RGBA_GRAY,
                        ],
                    ],
                ]);
            // объединяем строки В айди точки
            // и делаем В серыми
            $sheet->mergeCells("B{$columnStartIndex}:B" . $columnEnd)
                ->getStyle("B{$columnStartIndex}:B" . $columnEnd)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => self::RGBA_GRAY,
                        ],
                    ],
                ]);

            $sheet->mergeCells("C{$columnStartIndex}:C" . $columnEnd)
                ->getStyle("C{$columnStartIndex}:C" . $columnEnd)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => self::RGBA_GRAY,
                        ],
                    ],
                ]);
            $sheet->mergeCells("D{$columnStartIndex}:D" . $columnEnd)
                ->getStyle("D{$columnStartIndex}:D" . $columnEnd)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

            // выравниваем с А по С по центру
            $sheet->getStyle("A{$columnStartIndex}:C" . $columnEnd)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // делаем E и F. Название и айди товара серыми
            $sheet->getStyle("E{$columnStartIndex}:F" . $columnEnd)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => self::RGBA_GRAY,
                    ],
                ],
            ]);

            $columnStartIndex = $columnIndex;
            $pointNumber++;
        }

        $emptyStorageToAdd = $this->countOfStorages - count($this->vendorStoragesWithMaterials);
        $this->addEmptyStorages($sheet, $emptyStorageToAdd, $columnStartIndex, $columnIndex, $columnEnd);
    }

}

