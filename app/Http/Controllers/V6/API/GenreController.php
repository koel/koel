<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GenreResource;
use App\Repositories\GenreRepository;
use Illuminate\Http\Response;

class GenreController extends Controller
{
    public function __construct(private GenreRepository $repository)
    {
    }

    public function index()
    {
        return GenreResource::collection($this->repository->getAll());
    }

    public function show(string $name)
    {
        $genre = $this->repository->getOne($name);
        abort_unless((bool) $genre, Response::HTTP_NOT_FOUND);

        return GenreResource::make($genre);
    }
}
