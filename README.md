Yii2 Jedi Editor
================
Yii2 AssetBundle and Input Widget for [germanbisurgi/jedi](https://github.com/germanbisurgi/jedi)

Installation
------------

The preferred way to install this extension is through [composer](https://getcomposer.org/download/).

Either run

```
composer require eluhr/yii2-jedi-editor
```

or add

```
"eluhr/yii2-jedi-editor": "*"
```

to the require section of your `composer.json` file.


Usage
-----

```php
<?php
/**
 * @var \yii\base\Model $model
 * @var yii\web\View $this
*/

use eluhr\jedi\widgets\JediEditor;
use yii\web\JsExpression;

// Schema can either be of type string, array or stdClass.
$schema1 = '{}';
$schema2 = [];
 
// Without a model
echo JediEditor::widget([
    'id' => 'my-jedi',
    'name' => 'editor',
    'schema' => $schema1,
    // Update theme, see: https://github.com/germanbisurgi/jedi/tree/main?tab=readme-ov-file#theme
    'theme' => JediEditor::THEME_THEME_BOOTSTRAP3,
    'pluginOptions' => [
        // No ref parser
        'refParser' => null
    ]
]);

// Example on how to listen to change event
$this->registerJs(<<<JS
window['my-jedi'].on('change', () => {
    console.log(window['my-jedi'].getValue())
})
JS);

// With a model
echo JediEditor::widget([
    'model' => $model,
    'attribute' => 'attribute_name',
    'schema' => $schema2,
    'pluginOptions' => [
        // You can also set the theme like this
        'theme' => new JsExpression('new Jedi.ThemeBootstrap3()')
    ]
]);
```

For further informations about the usage of the jedi editor please refer to the [docs](https://github.com/germanbisurgi/jedi)
