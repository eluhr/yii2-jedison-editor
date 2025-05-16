Yii2 Jedi Editor
================
Yii2 AssetBundle and Input Widget for [germanbisurgi/jedison](https://github.com/germanbisurgi/jedison)

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

to the `require` section of your `composer.json` file.


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
    // Update theme, see: https://github.com/germanbisurgi/jedison/tree/main?tab=readme-ov-file#theme
    'theme' => JediEditor::THEME_THEME_BOOTSTRAP3,
    'pluginOptions' => [
        // No ref parser
        'refParser' => null
    ],
    'pluginEvents' => [
        'change' => new JsExpression('() => console.log(window["my-jedi"].getValue())'),
    ]
]);

// Alternative example on how to listen to change event
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
        'theme' => new JsExpression('new Jedison.ThemeBootstrap3()'),
        'showErrors' => 'always', // "change" or "never" is also possible
    ],
    'disabled' => false
]);
```

Example model
```php
<?php

namespace app\models;

use eluhr\jedi\validators\JsonSchemaValidator;
use app\filters\MyCustomFilter;
use yii\base\Model;

class MyModel extends Model
{

    public $title;
    public $value;

    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [
            'title',
            'integer',
            'enableClientValidation' => false,
        ];
        $rules[] = [
            'value',
            JsonSchemaValidator::class,
            'schema' => static::getJsonSchema(),
            'filters' => static::getJsonSchemaFilters()
        ];
        return $rules;
    }

    public function getJsonSchemaFilters(): array
    {
        return [
            'custom' => new MyCustomFilter() // Implement your custom filter if needed. See: https://opis.io/json-schema/2.x/php-filter.html Filter must inherit from Opis\JsonSchema\Filter
        ];
    }

    public static function getJsonSchema(): string
    {
        return <<<JSON
{
  "title": "Test",
  "type": "object",
  "required": [
    "name"
  ],
  "properties": {
    "name": {
      "type": "string",
      "\$filters": {
        "\$func": "custom"
      }
    }
  }
}
JSON;
    }
}
?>
```

## Options

- **`$containerOptions`** *(array)*: HTML attributes for the `<textarea>` container tag.
- **`$pluginOptions`** *(array)*: Options to be passed to the Jedi validator. See: https://github.com/germanbisurgi/jedison?tab=readme-ov-file#instance-options
- **`$pluginEvents`** *(array)*: Events to be passed to the Jedi validator.
- **`$showModelErrors`** *(bool)*: Shows model errors.
- **`$mapTranslations`** *(bool)*: Use Jedi-translated error messages when showing model errors, if available.
- **`$filterConstraints`** *(array)*: Filter error messages by constraints to hide unnecessary messages.

For further information about the usage of the jedi editor please refer to the [docs](https://github.com/germanbisurgi/jedison)
