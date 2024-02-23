<?php

namespace App\Services;

use App\Exceptions\FailedToActivateLicenseException;
use App\Models\License;
use App\Services\ApiClients\ApiClient;
use App\Services\License\Contracts\LicenseServiceInterface;
use App\Values\LicenseInstance;
use App\Values\LicenseMeta;
use App\Values\LicenseStatus;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class LicenseService implements LicenseServiceInterface
{
    public function __construct(private ApiClient $client, private string $hashSalt)
    {
    }

    public function activate(string $key): License
    {
        try {
            $response = $this->client->post('licenses/activate', [
                'license_key' => $key,
                'instance_name' => 'Koel Plus',
            ]);

            if ($response->meta->store_id !== config('lemonsqueezy.store_id')) {
                throw new FailedToActivateLicenseException('This license key is not from Koel’s official store.');
            }

            $license = $this->updateOrCreateLicenseFromApiResponse($response);
            $this->cacheStatus(LicenseStatus::valid($license));

            return $license;
        } catch (ClientException $e) {
            throw FailedToActivateLicenseException::fromClientException($e);
        } catch (Throwable $e) {
            Log::error($e);
            throw FailedToActivateLicenseException::fromThrowable($e);
        }
    }

    public function deactivate(License $license): void
    {
        try {
            $response = $this->client->post('licenses/deactivate', [
                'license_key' => $license->key,
                'instance_id' => $license->instance->id,
            ]);

            if ($response->deactivated) {
                self::deleteLicense($license);
            }
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === Response::HTTP_NOT_FOUND) {
                // The instance ID was not found. The license record must be a leftover from an erroneous attempt.
                self::deleteLicense($license);

                return;
            }

            throw FailedToActivateLicenseException::fromClientException($e);
        } catch (Throwable $e) {
            Log::error($e);
            throw $e;
        }
    }

    public function getStatus(bool $checkCache = true): LicenseStatus
    {
        if ($checkCache && Cache::has('license_status')) {
            return Cache::get('license_status');
        }

        /** @var ?License $license */
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
            $statusCode = $e->getResponse()->getStatusCode();

            if ($statusCode === Response::HTTP_BAD_REQUEST || $statusCode === Response::HTTP_NOT_FOUND) {
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
            'expires_at' => $response->license_key->expires_at,
        ]);
    }

    private static function deleteLicense(License $license): void
    {
        $license->delete();
        Cache::delete('license_status');
    }

    private static function cacheStatus(LicenseStatus $status): LicenseStatus
    {
        Cache::put('license_status', $status, now()->addWeek());

        return $status;
    }

    public function isPlus(): bool
    {
        return $this->getStatus()->isValid();
    }

    public function isCommunity(): bool
    {
        return !$this->isPlus();
    }
}
