<?php
namespace backend\models;

use yii\base\Model;
use yii\web\UploadedFile;

class FileForm extends Model
{
    /** @var UploadedFile */
    public $file;
}