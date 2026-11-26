<?php

namespace App\Support;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;

/**
 * Builds the storage path of the uploaded media files.
 */
class MediaPathGenerator extends DefaultPathGenerator
{
    /**
     * Gives every file its own folder named after its owner, for example users/5/12.
     */
    protected function getBasePath(Media $media): string
    {
        return Str::plural(Str::lower(class_basename($media->model_type))).'/'.$media->model_id.'/'.$media->getKey();
    }
}
