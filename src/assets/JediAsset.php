<?php

namespace eluhr\jedi\assets;

use yii\web\AssetBundle;
use yii\web\View;

class JediAsset extends AssetBundle
{
    public $sourcePath = '@npm/jedison/dist';

    public $js = [
        'umd/jedison.umd.js',
    ];

    public $jsOptions = [
        'position' => View::POS_HEAD,
    ];
}
