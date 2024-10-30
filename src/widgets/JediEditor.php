<?php

namespace eluhr\jedi\widgets;

use eluhr\jedi\assets\JediAsset;
use yii\widgets\InputWidget;

class JediEditor extends InputWidget
{
    /**
     * @inheritdoc
    */
    public function run(): static
    {
        JediAsset::register($this->view);
        return '[WIP]';
    }
}
