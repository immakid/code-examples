<?php

namespace App\Models;

use Illuminate\Http\UploadedFile;
use File;

/**
 * @property integer    $id
 * @property integer    $user_id
 * @property integer    $parent_id
 * @property string     $title
 * @property string     $filename
 * @property boolean    $main
 * @property string     $directory
 * @property string     $dirname
 * @property string     $path
 * @property string     $file_path
 * @property string     $url
 */
abstract class Image extends Model {

    public $timestamps = false;

    abstract public function getParentIdAttribute(): int;
    abstract public function setParentIdAttribute(int $val);

    abstract public function getDirectoryAttribute(): string;

    public function canBeMain() {
        return false;
    }

    public function saveFile(UploadedFile $file) {
        $this->title = mb_substr(preg_replace('/\.+[^\.]*$/', '', $file->getClientOriginalName()), 0, 255, 'utf8');
        $this->filename = uniqtid() . '.' . $file->guessExtension();
        File::makeDirectory($this->dirname, 0777, true, true);
        $file->move(public_path($this->dirname), $this->filename);
        \Image::make($this->file_path)->orientate()->save();
    }

    public function getDirnameAttribute() {
        $pid = $this->parent_id ? $this->parent_id : 0;
        return "uploads/{$this->user_id}/{$this->directory}/$pid";
    }

    public function getPathAttribute() {
        return "{$this->dirname}/{$this->filename}";
    }

    public function getFilePathAttribute() {
        return public_path($this->path);
    }

    public function getUrlAttribute() {
        return '/' . $this->path;
    }

    /**
     * @return bool|null|void
     * @throws \Exception
     */
    public function delete() {
        parent::delete();
        \Croppa::delete($this->url);
    }

}