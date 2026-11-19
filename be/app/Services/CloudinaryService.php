<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * CloudinaryService
 *
 * Dùng Cloudinary Upload API (unsigned/signed) để lưu ảnh và file
 * lên đám mây vĩnh viễn, tránh mất dữ liệu khi Render khởi động lại.
 *
 * Env cần thiết:
 *   CLOUDINARY_CLOUD_NAME=your_cloud_name
 *   CLOUDINARY_API_KEY=your_api_key
 *   CLOUDINARY_API_SECRET=your_api_secret
 */
class CloudinaryService
{
    private string $cloudName;
    private string $apiKey;
    private string $apiSecret;
    private string $uploadUrl;

    public function __construct()
    {
        $this->cloudName  = config('services.cloudinary.cloud_name');
        $this->apiKey     = config('services.cloudinary.api_key');
        $this->apiSecret  = config('services.cloudinary.api_secret');
        $this->uploadUrl  = "https://api.cloudinary.com/v1_1/{$this->cloudName}";
    }

    /**
     * Upload ảnh (jpg/png/webp) lên Cloudinary.
     *
     * @param  UploadedFile  $file
     * @param  string  $folder   Thư mục trên Cloudinary, ví dụ 'exam-evidence/1/12'
     * @param  string  $publicId Tên file không có extension, ví dụ 'event-uuid'
     * @return array{url: string, public_id: string}
     * @throws \RuntimeException
     */
    public function uploadImage(UploadedFile $file, string $folder = 'uploads', string $publicId = ''): array
    {
        return $this->upload($file, $folder, $publicId, 'image');
    }

    /**
     * Upload file tài liệu (pdf, docx...) lên Cloudinary dạng raw.
     *
     * @param  UploadedFile  $file
     * @param  string  $folder
     * @param  string  $publicId
     * @return array{url: string, public_id: string}
     * @throws \RuntimeException
     */
    public function uploadRaw(UploadedFile $file, string $folder = 'uploads', string $publicId = ''): array
    {
        return $this->upload($file, $folder, $publicId, 'raw');
    }

    /**
     * Xóa một asset khỏi Cloudinary theo public_id.
     *
     * @param  string  $publicId   VD: 'exam-evidence/1/12/event-uuid'
     * @param  string  $resourceType  'image' hoặc 'raw'
     * @return bool
     */
    public function delete(string $publicId, string $resourceType = 'image'): bool
    {
        $timestamp  = time();
        $params     = "public_id={$publicId}&timestamp={$timestamp}";
        $signature  = sha1($params . $this->apiSecret);

        $url = "{$this->uploadUrl}/{$resourceType}/destroy";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => [
                'public_id'  => $publicId,
                'timestamp'  => $timestamp,
                'api_key'    => $this->apiKey,
                'signature'  => $signature,
            ],
        ]);

        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return ($response['result'] ?? '') === 'ok';
    }

    // =========================================================================
    // Private
    // =========================================================================

    private function upload(UploadedFile $file, string $folder, string $publicId, string $resourceType): array
    {
        $timestamp  = time();
        $paramStr   = "folder={$folder}&timestamp={$timestamp}";

        if ($publicId !== '') {
            $paramStr = "folder={$folder}&public_id={$publicId}&timestamp={$timestamp}";
        }

        $signature = sha1($paramStr . $this->apiSecret);

        $postFields = [
            'file'          => new \CURLFile($file->getRealPath(), $file->getMimeType(), $file->getClientOriginalName()),
            'folder'        => $folder,
            'timestamp'     => $timestamp,
            'api_key'       => $this->apiKey,
            'signature'     => $signature,
        ];

        if ($publicId !== '') {
            $postFields['public_id'] = $publicId;
        }

        $url = "{$this->uploadUrl}/{$resourceType}/upload";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => $postFields,
        ]);

        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($result, true);

        if ($httpCode !== 200 || empty($data['secure_url'])) {
            $error = $data['error']['message'] ?? 'Unknown Cloudinary error';
            throw new \RuntimeException("Cloudinary upload failed: {$error}");
        }

        return [
            'url'       => $data['secure_url'],
            'public_id' => $data['public_id'],
        ];
    }
}
