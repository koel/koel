<?php

namespace App\Services;

use App\Exceptions\FailedToActivateLicenseException;
use App\Http\Integrations\LemonSqueezy\LemonSqueezyConnector;
use App\Http\Integrations\LemonSqueezy\Requests\ActivateLicenseRequest;
use App\Http\Integrations\LemonSqueezy\Requests\DeactivateLicenseRequest;
use App\Http\Integrations\LemonSqueezy\Requests\ValidateLicenseRequest;
use App\Models\License;
use App\Services\License\Contracts\LicenseServiceInterface;
use App\Values\LicenseInstance;
use App\Values\LicenseMeta;
use App\Values\LicenseStatus;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\RequestException;
use Throwable;

class LicenseService implements LicenseServiceInterface
{
    public function __construct(private readonly LemonSqueezyConnector $connector, private ?string $hashSalt = null)
    {
        $this->hashSalt ??= config('app.key');
    }

    public function activate(string $key): License
    {
        try {
            $result = $this->connector->send(new ActivateLicenseRequest($key))->object();

            if ($result->meta->store_id !== config('lemonsqueezy.store_id')) {
                throw new FailedToActivateLicenseException('This license key is not from Koel’s official store.');
            }

            $license = $this->updateOrCreateLicenseFromApiResponseBody($result);
            $this->cacheStatus(LicenseStatus::valid($license));

            return $license;
        } catch (RequestException $e) {
            throw FailedToActivateLicenseException::fromRequestException($e);
        } catch (Throwable $e) {
            Log::error($e);
            throw FailedToActivateLicenseException::fromThrowable($e);
        }
    }

    public function deactivate(License $license): void
    {
        try {
            $result = $this->connector->send(new DeactivateLicenseRequest($license))->object();

            if ($result->deactivated) {
                self::deleteLicense($license);
            }
        } catch (RequestException $e) {
            if ($e->getStatus() === Response::HTTP_NOT_FOUND) {
                // The instance ID was not found. The license record must be a leftover from an erroneous attempt.
                self::deleteLicense($license);

                return;
            }

            throw FailedToActivateLicenseException::fromRequestException($e);
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

        $license = License::query()->latest()->first();

        if (!$license) {
            return LicenseStatus::noLicense();
        }

        try {
            $result = $this->connector->send(new ValidateLicenseRequest($license))->object();
            $updatedLicense = $this->updateOrCreateLicenseFromApiResponseBody($result);

            return self::cacheStatus(LicenseStatus::valid($updatedLicense));
        } catch (RequestException $e) {
            if ($e->getStatus() === Response::HTTP_BAD_REQUEST || $e->getStatus() === Response::HTTP_NOT_FOUND) {
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
    private function updateOrCreateLicenseFromApiResponseBody(object $body): License
    {
        return License::query()->updateOrCreate([
            'hash' => sha1($body->license_key->key . $this->hashSalt),
        ], [
            'key' => $body->license_key->key,
            'instance' => LicenseInstance::fromJsonObject($body->instance),
            'meta' => LicenseMeta::fromJsonObject($body->meta),
            'created_at' => $body->license_key->created_at,
            'expires_at' => $body->license_key->expires_at,
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
