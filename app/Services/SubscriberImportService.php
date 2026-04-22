<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SubscriberImportService
{
    private const REQUIRED_HEADERS = ['first_name', 'last_name', 'phone'];

    public function import(UploadedFile $file, Organization $organization): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $headers = array_map('strtolower', array_map('trim', fgetcsv($handle) ?: []));

        $stats = ['imported' => 0, 'updated' => 0, 'errors' => []];

        foreach (self::REQUIRED_HEADERS as $required) {
            if (! in_array($required, $headers, true)) {
                fclose($handle);
                $stats['errors'][] = "Missing required column: {$required}";
                return $stats;
            }
        }

        $rows = [];
        $lineNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;
            $data = array_combine($headers, $row);

            $phone = preg_replace('/\D/', '', $data['phone'] ?? '');
            if (! $phone) {
                $stats['errors'][] = "Line {$lineNumber}: invalid phone number";
                continue;
            }

            $rows[] = [
                'organization_id' => $organization->id,
                'first_name' => trim($data['first_name'] ?? ''),
                'last_name' => trim($data['last_name'] ?? ''),
                'phone' => $phone,
                'email' => trim($data['email'] ?? '') ?: null,
                'source' => trim($data['source'] ?? '') ?: 'import',
                'status' => 'opted_in',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        fclose($handle);

        foreach (array_chunk($rows, 500) as $chunk) {
            $result = DB::table('subscribers')->upsert(
                $chunk,
                ['organization_id', 'phone'],
                ['first_name', 'last_name', 'email', 'source', 'updated_at']
            );
            $stats['imported'] += count($chunk);
        }

        return $stats;
    }
}
