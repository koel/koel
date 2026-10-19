<?php

namespace App\Services;

use App\Models\RadioStation;
use App\Models\User;
use App\Repositories\RadioStationRepository;
use App\Services\Image\ImageStorage;
use App\Values\Radio\RadioStationCreateData;
use App\Values\Radio\RadioStationUpdateData;

class RadioService
{
    public function __construct(
        private readonly RadioStationRepository $repository,
        private readonly ImageStorage $imageStorage,
    ) {}

    public function createRadioStation(RadioStationCreateData $dto, User $user): RadioStation
    {
        $logoFileName = $this->imageStorage->storeOptionalImage($dto->logo)->fileName;

        /** @var RadioStation $station */
        $station = $user->radioStations()->create([
            'url' => $dto->url,
            'name' => $dto->name,
            'logo' => $logoFileName,
            'description' => $dto->description,
            'is_public' => $dto->isPublic,
            'homepage_url' => $dto->homepageUrl,
        ]);

        return $this->repository->findOneWithUserContext($station->id, $user);
    }

    public function updateRadioStation(RadioStation $radioStation, RadioStationUpdateData $dto): RadioStation
    {
        $data = $this->imageStorage->storeOptionalImage($dto->logo)->applyToAttributes([
            'url' => $dto->url,
            'name' => $dto->name,
            'description' => $dto->description,
            'is_public' => $dto->isPublic,
            'homepage_url' => $dto->homepageUrl,
        ], 'logo');

        $radioStation->update($data);

        return $this->repository->findOneWithUserContext($radioStation->id, $radioStation->user);
    }

    public function deleteRadioStation(RadioStation $radioStation): void
    {
        $radioStation->delete();
    }

    public function deleteRadioStationsByUser(User $user): void
    {
        $user
            ->loadMissing('radioStations')
            ->radioStations->each(static fn (RadioStation $station) => $station->delete());
    }
}
