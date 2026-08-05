<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Services\ClientImportService;
use Illuminate\Http\Request;

final class ClientImportController extends ApiController
{
    public function preview(Request $request, ClientImportService $service)
    {
        $file = $request->validate(['file' => ['required', 'file', 'mimes:xlsx', 'max:10240']])['file'];

        return $this->ok($service->preview($file, $request->user()));
    }

    public function import(Request $request, ClientImportService $service)
    {
        $data = $request->validate(['file' => ['required', 'file', 'mimes:xlsx', 'max:10240'], 'duplicate_mode' => ['required', 'in:skip,update']]);

        return $this->ok($service->import($data['file'], $request->user(), $data['duplicate_mode']));
    }
}
