<?php

namespace eluhr\jedi\assets;

use yii\web\AssetBundle;

class DeepMergeAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/web/deep-merge';

    public $js = [
        'deep-merge.js',
    ];
}
