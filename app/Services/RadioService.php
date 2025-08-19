<?php

namespace App\Services;

use App\Models\RadioStation;
use App\Models\User;
use App\Repositories\RadioStationRepository;

class RadioService
{
    public function __construct(
        private readonly RadioStationRepository $repository,
        private readonly ArtworkService $artworkService,
    ) {
    }

    public function createRadioStation(
        string $url,
        string $name,
        ?string $logo,
        ?string $description,
        bool $isPublic,
        User $user,
    ): RadioStation {
        // logo is optional and not critical, so no transaction is needed
        $logoFileName = rescue_if($logo, function () use ($logo) {
            return $this->artworkService->storeRadioStationLogo($logo);
        });

        /** @var RadioStation $station */
        $station = $user->radioStations()->create([
            'url' => $url,
            'name' => $name,
            'logo' => $logoFileName,
            'description' => $description,
            'is_public' => $isPublic,
        ]);

        return $this->repository->findOneWithUserContext($station->id, $user);
    }

    public function updateRadioStation(
        RadioStation $radioStation,
        string $url,
        string $name,
        ?string $logo,
        ?string $description,
        bool $isPublic,
    ): RadioStation {
        // logo is optional and not critical, so no transaction is needed
        $newLogo = rescue_if($logo, function () use ($logo) {
            return $this->artworkService->storeRadioStationLogo($logo);
        });

        $data = [
            'url' => $url,
            'name' => $name,
            'description' => $description,
            'is_public' => $isPublic,
        ];

        if ($newLogo) {
            $data['logo'] = $newLogo;
        }

        $radioStation->update($data);

        return $this->repository->findOneWithUserContext($radioStation->id, $radioStation->user);
    }

    public function removeStationLogo(RadioStation $station): void
    {
        $station->logo = null;
        $station->save();
    }
}
