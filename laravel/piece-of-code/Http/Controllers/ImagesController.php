<?php

namespace App\Http\Controllers;

use App\Models\HasImages;
use App\Models\Image;
use Auth;
use DB;
use Illuminate\Http\UploadedFile;
use Log;
use Request;

class ImagesController extends Controller {

    /**
     * @param HasImages $model
     *
     * @return mixed
     */
    public function index($model) {
        return view('includes.fileupload-thumbs', ['images' => $model->images()->get()]);
    }

    /**
     * @param HasImages $model
     *
     * @return mixed
     */
    public function upload($model) {
        $file = Request::file('image');
        if(!($file instanceof UploadedFile))
            return $this->err('Missing parameter: image');

        $ext = $file->guessExtension();
        if(!in_array($ext, ['jpeg', 'jpg', 'png'], true))
            return $this->err('Допустимые форматы: JPEG, PNG.');

        $image = $model->createImageInstance($model->exists ? $model->user_id : Auth::id());

//        if($model->hasMainImage() && !$model->images->count())
//            $image->main = true;

        try {
            $image->saveFile($file);
            if(!$image->save())
                throw new \Exception('Query failed');
        }
        catch(\Exception $e) {
            Log::error($e);
            @unlink($image->file_path);
            return $this->err('Не удалось сохранить файл.');
        }

        $this->checkMain($model);

        return $this->index($model);
    }

    /**
     * @param HasImages $model
     * @param Image $image
     */
    public function setTitle($model, $image) {
        $image->title = mb_substr(Request::get('title'), 0, 255, 'utf8');
        $image->save();
    }

    /**
     * @param HasImages $model
     * @param Image $image
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     * @throws \Throwable
     */
    public function setMain($model, $image) {
        if(!$model->hasMainImage())
            return $this->err('This model has not main image.');
        DB::transaction(function() use($model, $image) {
            $model->images()->whereKeyNot($image->id)->update(['main' => false]);
            $image->main = true;
            $image->save();
        });
    }

    /**
     * @param HasImages $model
     * @param Image $image
     *
     * @throws \Exception
     */
    public function delete($model, $image) {
        $image->delete();
        $this->checkMain($model);
    }

    protected function err($msg) {
        return response([
            'errors' => [],
            'message' => $msg,
        ], 422);
    }

    /**
     * @param HasImages $model
     */
    protected function checkMain($model) {
        if($model->hasMainImage() && !$model->main_image) {
            $image = $model->images()->first();
            if(!$image)
                return;
            try {
                $image->main = true;
                $image->save();
            }
            catch(\Exception $e) {
                Log::error($e);
            }
        }
    }

}