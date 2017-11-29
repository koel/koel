<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;

class Upload
{
	/**
	 * Get the upload path.
	 *
	 * @return string
	 */
	public function getPath()
	{
		return rtrim(Setting::get('media_path'), DIRECTORY_SEPARATOR)
			.DIRECTORY_SEPARATOR
			.ltrim(config('koel.upload.subdir'), DIRECTORY_SEPARATOR);
	}

	public function upload(UploadedFile $file)
	{
        $file->move($this->getPath(), $file->getClientOriginalName());
	}
}
