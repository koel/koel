<?php

namespace Tests\Integration\Http\Resources;

use App\Http\Resources\FolderResource;
use App\Models\Folder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class FolderResourceTest extends TestCase
{
    #[Test]
    public function serializationDoesNotQueryWhenUploaderIsEagerLoaded(): void
    {
        $alice = create_user(['id' => 201, 'name' => 'Alice']);
        $bob = create_user(['id' => 202, 'name' => 'Bob']);
        $eve = create_user(['id' => 203, 'name' => 'Eve']);

        $folders = new Collection([
            Folder::factory()->createOne(['path' => '__KOEL_UPLOADS_$' . $alice->id . '__']),
            Folder::factory()->createOne(['path' => '__KOEL_UPLOADS_$' . $bob->id . '__']),
            Folder::factory()->createOne(['path' => '__KOEL_UPLOADS_$' . $eve->id . '__']),
        ]);

        $folders->loadMissing('uploader');

        DB::flushQueryLog();
        DB::enableQueryLog();

        $request = Request::create('/');
        $rendered = $folders->map(static fn (Folder $folder) => (new FolderResource($folder))->toArray($request));

        self::assertSame([], DB::getQueryLog());
        self::assertSame(['Uploads by Alice', 'Uploads by Bob', 'Uploads by Eve'], $rendered->pluck('name')->all());
    }

    #[Test]
    public function rendersDeletedUploaderFallback(): void
    {
        $orphan = Folder::factory()->createOne(['path' => '__KOEL_UPLOADS_$9999__']);
        $orphan->loadMissing('uploader');

        $request = Request::create('/');
        $rendered = (new FolderResource($orphan))->toArray($request);

        self::assertSame('Uploads by deleted user', $rendered['name']);
    }

    #[Test]
    public function rendersYourUploadsForViewerOwnedFolder(): void
    {
        $alice = create_user(['id' => 204, 'name' => 'Alice']);
        $folder = Folder::factory()->createOne(['path' => '__KOEL_UPLOADS_$' . $alice->id . '__']);

        $request = Request::create('/');
        $request->setUserResolver(static fn () => $alice);

        $rendered = (new FolderResource($folder))->toArray($request);

        self::assertSame('Your uploads', $rendered['name']);
    }
}
