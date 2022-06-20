<?php

namespace Givebutter\LaravelCustomFields\Traits;

trait Archives
{
    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootArchives()
    {
        static::addGlobalScope(new ArchivingScope);
    }
}
