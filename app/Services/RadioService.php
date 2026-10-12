<?php

namespace App\Services;

use App\Enums\ImageChangeType;
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
        $data = [
            'url' => $dto->url,
            'name' => $dto->name,
            'description' => $dto->description,
            'is_public' => $dto->isPublic,
            'homepage_url' => $dto->homepageUrl,
        ];

        $logoChange = $this->imageStorage->storeOptionalImage($dto->logo);

        if ($logoChange->type !== ImageChangeType::KEEP) {
            $data['logo'] = $logoChange->fileName ?? '';
        }

        $radioStation->update($data);

        return $this->repository->findOneWithUserContext($radioStation->id, $radioStation->user);
    }

    public function deleteRadioStationsByUser(User $user): void
    {
        $user
            ->loadMissing('radioStations')
            ->radioStations->each(static fn (RadioStation $station) => $station->delete());
    }
}
