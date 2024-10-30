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

### Usuage

without a model

```php
use eluhr\jedi\widgets\JediEditor;

<?php echo JediEditor::widget([
    'name' => 'editor'
]); ?>
```

with a model

```php
use eluhr\jedi\widgets\JediEditor;

<?php echo JediEditor::widget([
    'model' => $model,
    'attribute' => 'attribute_name'
]); ?>
```

For further informations about the usage of the jedi editor please refer to the [docs](https://github.com/germanbisurgi/jedi)
