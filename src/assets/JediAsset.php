<?php

namespace eluhr\jedi\assets;

use yii\web\AssetBundle;
use yii\web\View;

class JediAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/web/jedi';

    public $js = [
        'jedi.js',
    ];

    public $jsOptions = [
        'position' => View::POS_HEAD,
    ];
}
