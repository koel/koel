<?php

namespace App\Services;

use App\Exceptions\FailedToActivateLicenseException;
use App\Models\License;
use App\Services\ApiClients\ApiClient;
use App\Values\LicenseInstance;
use App\Values\LicenseMeta;
use App\Values\LicenseStatus;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class LicenseService
{
    public function __construct(private ApiClient $client, private string $hashSalt)
    {
    }

    public function activateLicense(string $key): License
    {
        try {
            $response = $this->client->post('licenses/activate', [
                'license_key' => $key,
                'instance_name' => 'Koel Plus',
            ]);

            return $this->updateOrCreateLicenseFromApiResponse($response);
        } catch (ClientException $e) {
            throw new FailedToActivateLicenseException(json_decode($e->getResponse()->getBody())->error, $e->getCode());
        } catch (Throwable $e) {
            throw FailedToActivateLicenseException::fromException($e);
        }
    }

    public function getLicenseStatus(bool $checkCache = true): LicenseStatus
    {
        if ($checkCache && Cache::has('license_status')) {
            return Cache::get('license_status');
        }

        /** @var License $license */
        $license = License::query()->latest()->first();

        if (!$license) {
            return LicenseStatus::noLicense();
        }

        try {
            $response = $this->client->post('licenses/validate', [
                'license_key' => $license->key,
                'instance_id' => $license->instance->id,
            ]);

            $updatedLicense = $this->updateOrCreateLicenseFromApiResponse($response);

            return self::cacheStatus(LicenseStatus::valid($updatedLicense));
        } catch (ClientException $e) {
            Log::error($e);

            if ($e->getCode() === 400 || $e->getCode() === 404) {
                return self::cacheStatus(LicenseStatus::invalid($license));
            }

            throw $e;
        } catch (DecryptException) {
            // the license key has been tampered with somehow
            return self::cacheStatus(LicenseStatus::invalid($license));
        } catch (Throwable $e) {
            Log::error($e);

            return LicenseStatus::unknown($license);
        }
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    private function updateOrCreateLicenseFromApiResponse(object $response): License
    {
        return License::query()->updateOrCreate([
            'hash' => sha1($response->license_key->key . $this->hashSalt),
        ], [
            'key' => $response->license_key->key,
            'instance' => LicenseInstance::fromJsonObject($response->instance),
            'meta' => LicenseMeta::fromJsonObject($response->meta),
            'created_at' => $response->license_key->created_at,
            'expires_at' => $response->instance->created_at,
        ]);
    }

    private static function cacheStatus(LicenseStatus $status): LicenseStatus
    {
        Cache::put('license_status', $status, now()->addWeek());

        return $status;
    }

    public function isPlus(): bool
    {
        return $this->getLicenseStatus()->isValid();
    }

    public function isCommunity(): bool
    {
        return !$this->isPlus();
    }
}
