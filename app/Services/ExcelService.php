<?php
declare(strict_types=1);

namespace App\Services;


use App\Exports\Storages\CreateStoragesExport;
use App\Exports\Storages\EditStoragesExport;
use App\Imports\StoragesImport;
use App\Models\Vendor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExcelService
{
    protected MaterialService $materialsService;
    protected VendorStorageService $vendorStorageService;
    protected CoordinateService $coordinateService;

    const string ACTIVE_STORAGE_POSITIVE_VALUE = 'да';
    const string ACTIVE_STORAGE_NEGATIVE_VALUE = 'нет';

    protected array $rulesToParseCreateStorageWithMaterial = [
        'address' => 'required|max:255',
        'material_id' => 'required|max:100',
        'vendor_material_id' => 'nullable|max:255',
        'price' => 'required|numeric|min:1',
        'delivery_price' => 'required|numeric|min:1',
        'is_available' => 'required|boolean',
    ];

    protected array $attributesToParseCreateStorageWithMaterial = [
        'address' => 'Координаты точки',
        'material_id' => 'Идентификатор материала сервиса',
        'vendor_material_id' => 'Идентификатор поставщика',
        'price' => 'Ваша цена за материал',
        'delivery_price' => 'Ваша цена за доставку',
        'is_available' => 'В наличии',
    ];

    public function __construct(MaterialService $materialsService, VendorStorageService $vendorStorageService, CoordinateService $coordinateService)
    {
        $this->materialsService = $materialsService;
        $this->vendorStorageService = $vendorStorageService;
        $this->coordinateService = $coordinateService;
    }

    /**
     * Создаем чистый файл для заполнения точек и материалов поставщику для создания точек и материалов.
     *
     * Не забыть удалить файл руками, если не будете использовать бинарный респонс.
     *
     * @param Vendor $vendor
     * @return BinaryFileResponse
     */
    function makeStoragesExportFileToCreate(Vendor $vendor): BinaryFileResponse
    {
        $materials = $this->materialsService->getActiveMaterials();

        $dateTime = Carbon::now()->format('Y-m-d_H:i:s.u');
        return Excel::download(new CreateStoragesExport($materials, $vendor), fileName: "storages_create_{$dateTime}.xlsx");
    }

    /**
     * Создаем и заполняем файл данными поставщика для редактирования данных.
     *
     * Не забыть удалить файл руками, если не будете использовать бинарный респонс.
     *
     * @param Vendor $vendor
     * @return BinaryFileResponse
     */
    function makeStoragesExportFileToEdit(Vendor $vendor): BinaryFileResponse
    {
        $storagesWithMaterials = $this->vendorStorageService->getStoragesWithMaterials($vendor->id);
        $materials = $this->materialsService->getActiveMaterials();

        $dateTime = Carbon::now()->format('Y-m-d_H:i:s.u');
        return Excel::download(new EditStoragesExport($materials, $vendor, $storagesWithMaterials, $materials), fileName: "storages_edit_{$dateTime}.xlsx");
    }

    /**
     * Парсит файл созданный в CreateStoragesExport и возвращает коллекцию со значениями и ошибками.
     *
     * @param string $filePath
     * @return Collection
     */
    public function parseStoragesFile(string $filePath): Collection
    {
        $sheets = Excel::toArray(new StoragesImport, $filePath);
        $collection = $this->validateStoragesFile($sheets);
        if ($collection->isNotEmpty()) {
            return $collection;
        }

        $rows = $sheets[0];

        $collection = new collection();
        $storageMaterialsToCreate = [];
        $storagesWithMaterialsToEdit = [];
        $storageIDsToRemove = [];
        $errors = [];


        $startRow = 10;

        $address = "";
        foreach ($rows as $index => $row) {
            $rowIndex = $index + 1;
            if ($rowIndex < $startRow) {
                continue;
            }

            if (count($row) < 15) {
                $errors[] = [
                    'row' => $rowIndex,
                    'длинна' => [
                        'Недостаточно полей в строке',
                    ],
                ];
                continue;
            }

            $isAvailable = $this->parseIsAvailable($row[15]);

            // так как в смердженых ячейках пустое значение везде кроме первого элемента
            // мы используем колонку А с текстом, как флаг, что тут должен быть адрес
            // то есть у нас есть табличка
            // 1) точка отгрузки / ул пушкина / товар
            // 2)                             / товар 2
            // 3) точка отгрузки / ул сереги / товар 3
            // в этом примере для первой точки и товара 1/2, мы будем использовать адрес ул пушкина
            // а для точки 3, уже адрес ул сереги

            if (!is_null($row[0]) && !is_null($row[3])) {
                $address = $this->coordinateService->parseCoordinates((string)$row[3]);
                if (!$address) {
                    $errors[] = [
                        'row' => $rowIndex,
                        'адрес' => [
                            'Некорректные координаты',
                        ],
                    ];
                }
            } else {
                if (!is_null($row[0]) && is_null($row[4])) {
                    $address = false;
                }
            }

            if (is_null($isAvailable)) {
                continue;
            }

            if (!$address) {
                continue;
            }
            $storageID = false;
            // та же логика для смерджаного поля идентификатора точки
            if (isset($row[0]) && isset($row[1])) {
                $storageID = trim((string) $row[1]);
            }

            // когда есть идентификатор точки, но удалили адрес - удаляем точку и материалы
            if ($storageID && !$address) {
                $res = $this->parseRemoveStorageID($storageID);
                if ($res instanceof ValidationValidator) {
                    $errors[] = $this->makeErrArr($res, $rowIndex);
                    continue;
                }

                $storageIDsToRemove[] = $storageID;
                continue;
            }


            // когда is_available true/false и storageID есть, редактируем материал
            if ($isAvailable && $storageID) {
                $res = $this->parseEditMaterialRow($row, $address, $storageID, $isAvailable);
                if ($res instanceof ValidationValidator) {
                    $errors[] = $this->makeErrArr($res, $rowIndex);
                    continue;
                }
                $storagesWithMaterialsToEdit[] = $res;
            }

            // когда is_available true но storageID нет, создаем новую точку и материал
            if (!$storageID) {
                $res = $this->parseCreateStorageWithMaterialRow($row, $address, $isAvailable);
                if ($res instanceof ValidationValidator) {
                    $errors[] = $this->makeErrArr($res, $rowIndex);
                    continue;
                }

                $storageMaterialsToCreate[] = $res;
            }
        }

        if (count($storageMaterialsToCreate) == 0 && count($storageIDsToRemove) == 0
            && count($errors) == 0 && count($storagesWithMaterialsToEdit) == 0
        ) {
            $errors[] = [
                'row' => 'Некорректный файл',
                'err' => ['Для успешной регистрации, Вы должны добавить как минимум один склад и активированный материал для него.'],
            ];
        }

        $collection->put('storages_with_materials_to_create', $storageMaterialsToCreate);
        $collection->put('storage_ids_to_remove', $storageIDsToRemove);

        $collection->put('errors', $errors);
        $collection->put('storages_with_materials_to_edit', $storagesWithMaterialsToEdit);

        return $collection;
    }


    /**
     * @param $row
     * @param $address
     * @param $isAvailable
     * @return array|ValidationValidator
     */
    protected function parseCreateStorageWithMaterialRow($row, $address, $isAvailable)
    {
        $d = [
            'address' => $address,
            'material_id' => trim(strval($row[5])),
            'vendor_material_id' => trim(strval($row[6])),
            'price' => trim(strval($row[7])),
            'delivery_price' => trim(strval($row[11])),
            'is_available' => $isAvailable,
        ];

        $validator = Validator::make($d, $this->rulesToParseCreateStorageWithMaterial, attributes: $this->attributesToParseCreateStorageWithMaterial);

        if ($validator->fails()) {
            return $validator;
        }

        return $d;
    }

    protected function parseEditMaterialRow($row, $address, $storageID, $isAvailable)
    {
        $d = [
            'storage_id' => $storageID,
            'address' => $address,
            'material_id' => trim(strval($row[5])),
            'vendor_material_id' => trim(strval($row[6])),
            'price' => trim(strval($row[7])),
            'delivery_price' => trim(strval($row[11])),
            'is_available' => $isAvailable,
        ];

        $validator = Validator::make($d, $this->rulesToParseCreateStorageWithMaterial, attributes: $this->attributesToParseCreateStorageWithMaterial);

        if ($validator->fails()) {
            return $validator;
        }

        return $d;
    }

    protected function parseRemoveStorageID($storageID)
    {
        $rules = [
            'storage_id' => 'required|max:20',
        ];

        $attributes = [
            'storage_id' => 'Идентификатор точки точки',
        ];

        $d = [
            'storage_id' => $storageID,
        ];

        $validator = Validator::make($d, $rules, attributes: $attributes);

        if ($validator->fails()) {
            return $validator;
        }

        return $storageID;
    }

    /**
     * @param $sheets
     * @return Collection
     */
    protected function validateStoragesFile($sheets): Collection
    {
        $collection = new Collection();
        $errors = [];

        if (count($sheets) < 1) {
            $errors[] = [
                'некорректный файл' => [
                    'некорректный файл',
                ],
            ];

            $collection->put('errors', $errors);
            return $collection;
        }

        $rows = $sheets[0];

        if (count($rows) < 9) {
            $errors[] = [
                'некорректный файл' => [
                    'некорректный файл',
                ],
            ];

            $collection->put('errors', $errors);
            return $collection;
        }

        return $collection;
    }

    /**
     * @param ValidationValidator $validator
     * @param $rowIndex
     * @return array
     */
    protected function makeErrArr(ValidationValidator $validator, $rowIndex): array
    {
        $validatorErrors = $validator->errors();
        $errArr = $validatorErrors->toArray();
        $errArr['row'] = $rowIndex;

        return $errArr;
    }


    protected function parseIsAvailable($string): ?bool
    {
        if (is_null($string)) {
            return null;
        }
        $string = mb_strtolower(trim($string));

        if ($string === self::ACTIVE_STORAGE_POSITIVE_VALUE) {
            return true;
        }
        if ($string === self::ACTIVE_STORAGE_NEGATIVE_VALUE) {
            return false;
        }


        return null;
    }
}
