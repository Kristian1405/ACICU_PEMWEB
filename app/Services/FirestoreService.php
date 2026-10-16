<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * FirestoreService
 * ------------------------------------------------------------------
 * Akses Cloud Firestore dari sisi SERVER memakai Firestore REST API v1
 * yang diautentikasi dengan Service Account (JWT RS256 -> OAuth2 access
 * token). Pendekatan ini dipilih agar tidak perlu menambah paket pihak
 * ketiga (mis. kreait/firebase-php) yang akan menurunkan versi Symfony
 * pada project ini.
 *
 * Dipakai untuk:
 *  - webhook Midtrans : menandai pesanan sudah dibayar;
 *  - panel admin      : membaca daftar pesanan.
 *
 * Catatan: akses server memakai Service Account sehingga MELEWATI
 * Firestore Security Rules (seperti Admin SDK).
 */
class FirestoreService
{
    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';

    private const SCOPE = 'https://www.googleapis.com/auth/datastore';

    public function __construct(
        private ?string $projectId = null,
        private ?string $credentialsPath = null,
    ) {
        $this->projectId = $this->projectId ?: config('firebase.project_id');
        $this->credentialsPath = $this->credentialsPath ?: config('firebase.credentials');
    }

    public function isConfigured(): bool
    {
        try {
            return filled($this->projectId) && $this->serviceAccount() !== [];
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * Tulis penuh sebuah dokumen (buat / timpa). Cocok untuk membuat pesanan.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function set(string $collection, string $id, array $data): array
    {
        $url = $this->baseUrl()."/{$collection}/".rawurlencode($id);

        $response = $this->request()->patch($url, ['fields' => self::encodeFields($data)]);

        $this->ensureSuccess($response, "menulis {$collection}/{$id}");

        return self::decodeDocument($response->json()) ?? [];
    }

    /**
     * Perbarui sebagian field saja (merge), field lain dipertahankan.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $collection, string $id, array $data): array
    {
        $fields = self::encodeFields($data);
        $query = implode('&', array_map(
            static fn (string $field): string => 'updateMask.fieldPaths='.rawurlencode($field),
            array_keys($fields)
        ));

        $url = $this->baseUrl()."/{$collection}/".rawurlencode($id).'?'.$query;

        $response = $this->request()->patch($url, ['fields' => $fields]);

        $this->ensureSuccess($response, "memperbarui {$collection}/{$id}");

        return self::decodeDocument($response->json()) ?? [];
    }

    /**
     * Ambil satu dokumen.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $collection, string $id): ?array
    {
        $url = $this->baseUrl()."/{$collection}/".rawurlencode($id);

        $response = $this->request()->get($url);

        if ($response->status() === 404) {
            return null;
        }

        $this->ensureSuccess($response, "membaca {$collection}/{$id}");

        return self::decodeDocument($response->json());
    }

    /**
     * Ambil daftar dokumen dari sebuah koleksi.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(string $collection, int $limit = 100, ?string $orderBy = 'createdAt', string $direction = 'DESCENDING'): array
    {
        $structuredQuery = [
            'from' => [['collectionId' => $collection]],
            'limit' => $limit,
        ];

        if ($orderBy) {
            $structuredQuery['orderBy'] = [[
                'field' => ['fieldPath' => $orderBy],
                'direction' => strtoupper($direction),
            ]];
        }

        $url = $this->baseUrl().':runQuery';

        $response = $this->request()->post($url, ['structuredQuery' => $structuredQuery]);

        $this->ensureSuccess($response, "membaca koleksi {$collection}");

        $documents = [];

        foreach ((array) $response->json() as $row) {
            if (isset($row['document'])) {
                $documents[] = self::decodeDocument($row['document']);
            }
        }

        return $documents;
    }

    /* ==============================================================
     |  Internal
     * ============================================================== */

    private function baseUrl(): string
    {
        if (blank($this->projectId)) {
            throw new RuntimeException('FIREBASE_PROJECT_ID belum diisi pada file .env.');
        }

        return sprintf(
            'https://firestore.googleapis.com/v1/projects/%s/databases/(default)/documents',
            $this->projectId
        );
    }

    private function request(): PendingRequest
    {
        return Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    private function ensureSuccess(Response $response, string $action): void
    {
        if ($response->failed()) {
            throw new RuntimeException(
                "Firestore gagal {$action}: ".$response->status().' '.$response->body()
            );
        }
    }

    /**
     * Access token OAuth2 dari Service Account, di-cache agar tidak meminta
     * token baru setiap request.
     */
    private function accessToken(): string
    {
        $cacheKey = 'firebase_access_token_'.md5((string) $this->projectId);

        return Cache::remember($cacheKey, now()->addMinutes(50), function (): string {
            $account = $this->serviceAccount();
            $now = time();

            $header = ['alg' => 'RS256', 'typ' => 'JWT'];
            $claims = [
                'iss' => $account['client_email'],
                'scope' => self::SCOPE,
                'aud' => self::TOKEN_ENDPOINT,
                'iat' => $now,
                'exp' => $now + 3600,
            ];

            $signingInput = self::base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR))
                .'.'.self::base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR));

            $signature = '';
            $signed = openssl_sign($signingInput, $signature, $account['private_key'], 'sha256WithRSAEncryption');

            if (! $signed) {
                throw new RuntimeException('Gagal menandatangani JWT untuk Firebase (periksa private key).');
            }

            $assertion = $signingInput.'.'.self::base64UrlEncode($signature);

            $response = Http::asForm()->timeout(30)->post(self::TOKEN_ENDPOINT, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);

            if ($response->failed()) {
                throw new RuntimeException(
                    'Firebase auth gagal: '.$response->status().' '.$response->body()
                );
            }

            return (string) $response->json('access_token');
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceAccount(): array
    {
        $path = (string) $this->credentialsPath;

        if ($path === '') {
            throw new RuntimeException('FIREBASE_CREDENTIALS belum diisi pada file .env.');
        }

        $isAbsolute = str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || (bool) preg_match('#^[A-Za-z]:[\\\\/]#', $path);

        if (! $isAbsolute) {
            $path = base_path($path);
        }

        if (! is_file($path)) {
            throw new RuntimeException("File Service Account Firebase tidak ditemukan: {$path}");
        }

        $account = json_decode((string) file_get_contents($path), true);

        if (! is_array($account) || empty($account['client_email']) || empty($account['private_key'])) {
            throw new RuntimeException('File Service Account Firebase tidak valid.');
        }

        return $account;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * Ubah array PHP menjadi format "fields" Firestore.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    private static function encodeFields(array $data): array
    {
        $fields = [];

        foreach ($data as $key => $value) {
            $fields[$key] = self::encodeValue($value);
        }

        return $fields;
    }

    /**
     * @return array<string, mixed>
     */
    private static function encodeValue(mixed $value): array
    {
        if ($value === null) {
            return ['nullValue' => null];
        }

        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }

        if (is_int($value)) {
            return ['integerValue' => (string) $value];
        }

        if (is_float($value)) {
            return ['doubleValue' => $value];
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                return ['arrayValue' => ['values' => array_map(self::encodeValue(...), $value)]];
            }

            return ['mapValue' => ['fields' => self::encodeFields($value)]];
        }

        return ['stringValue' => (string) $value];
    }

    /**
     * @param  array<string, mixed>|null  $document
     * @return array<string, mixed>|null
     */
    private static function decodeDocument(?array $document): ?array
    {
        if (! is_array($document) || ! isset($document['fields'])) {
            return null;
        }

        $data = self::decodeFields($document['fields']);

        if (isset($document['name'])) {
            $data['_id'] = basename($document['name']);
        }

        if (isset($document['updateTime'])) {
            $data['_updatedAt'] = $document['updateTime'];
        }

        return $data;
    }

    /**
     * @param  array<string, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private static function decodeFields(array $fields): array
    {
        $data = [];

        foreach ($fields as $key => $value) {
            $data[$key] = self::decodeValue($value);
        }

        return $data;
    }

    private static function decodeValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return null;
        }

        return match (true) {
            array_key_exists('nullValue', $value) => null,
            array_key_exists('booleanValue', $value) => (bool) $value['booleanValue'],
            array_key_exists('integerValue', $value) => (int) $value['integerValue'],
            array_key_exists('doubleValue', $value) => (float) $value['doubleValue'],
            array_key_exists('timestampValue', $value) => (string) $value['timestampValue'],
            array_key_exists('stringValue', $value) => (string) $value['stringValue'],
            isset($value['mapValue']) => self::decodeFields($value['mapValue']['fields'] ?? []),
            isset($value['arrayValue']) => array_map(
                self::decodeValue(...),
                $value['arrayValue']['values'] ?? []
            ),
            default => null,
        };
    }
}
