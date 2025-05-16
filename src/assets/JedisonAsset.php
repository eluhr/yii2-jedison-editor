<?php

namespace eluhr\jedison\assets;

use yii\web\AssetBundle;
use yii\web\View;

class JedisonAsset extends AssetBundle
{
    public $sourcePath = '@npm/jedison/dist';

    public $js = [
        'umd/jedison.umd.js',
    ];

    public $jsOptions = [
        'position' => View::POS_HEAD,
    ];
}
