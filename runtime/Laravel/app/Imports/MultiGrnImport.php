<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class MultiGrnImport implements ToCollection
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        $existing_column = [
            "Inward No",
            "Material Doc No",
            "Truck Inward Date",
            "PO No",
            "TRUCKNO",
            "Material Desc",
            "Vendor Name",
            "Gross Wt",
            "Tare Wt",
            "Nt Wt with Bag",
            "Nt Wt wo Bag",
            "Noof bag",
            "Av wt bag",
            "Plant"
        ];
        $column_array = [];
        $row_array = [];

        if (!empty($collection)) {
            foreach ($collection as $collection_key => $data) {
                if (!empty($data)) {
                    $dataArray = $data->toArray();
                    $filteredData = array_map(function ($value) {
                        $new_value = str_replace('/', '', $value);
                        return $new_value;
                    }, $dataArray);

                    $filteredData = array_filter($filteredData);

                    if ($collection_key == 0) {
                        if (!empty($filteredData)) {
                            $column_array = $filteredData;
                        }
                    }

                    if ($collection_key > 0) {
                        if (!empty($filteredData)) {
                            $row_array[] = $filteredData;
                        }
                    }
                }
            }
        }
        $column_count = count($column_array);
        if ($column_count == 14) {
            if (empty(array_diff($existing_column, $column_array)) && empty(array_diff($column_array, $existing_column))) {
                return collect($row_array);
            } else {
                dd("Arrays are not identical");
            }
        }
    }
}
