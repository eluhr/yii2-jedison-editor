<?php

namespace eluhr\jedi\widgets;

use eluhr\jedi\assets\JediAsset;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\widgets\InputWidget;

class JediEditor extends InputWidget
{
    /**
     * @var array the HTML attributes for the textarea container tag.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public array $containerOptions = [];

    public array $schema;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!isset($this->schema)) {
            throw new InvalidConfigException("Property 'schema' must be specified.");
        }

        // Always set a unique id for the container
        if (!isset($this->containerOptions['id'])) {
            $this->containerOptions['id'] = $this->options['id'] . '-container';
        }
    }

    /**
     * @inheritdoc
     */
    public function run(): string
    {
        $this->registerAssets();
        return Html::tag('div', '', $this->containerOptions);
    }

    /**
     * Render a HTML textarea tag.
     *
     * This will call [[Html::activeTextarea()]] if the input widget is [[hasModel()|tied to a model]],
     * or [[Html::textarea()]] if not.
     *
     * @return string the HTML of the textarea field.
     * @see Html::activeTextarea()
     * @see Html::textarea()
     */
    protected function renderTextareaHtml(): string
    {
        if ($this->hasModel()) {
            return Html::activeTextarea($this->model, $this->attribute, $this->options);
        }
        return Html::textarea($this->name, $this->value, $this->options);
    }

    /**
     * Register all needed asset bundles and scripts
     */
    protected function registerAssets(): void
    {
        JediAsset::register($this->view);

        $containerId = $this->containerOptions['id'];
        $inputId = $this->hasModel() ? Html::getInputId($this->model, $this->attribute) : $this->options['id'];
        $inputName = $this->hasModel() ? Html::getInputName($this->model, $this->attribute) : $this->name;
        $id = Inflector::slug($inputId, '');

        $schema = Json::htmlEncode($this->schema);

        $this->view->registerJs(<<<JS
const initEditor$id = () => {
    const editorOptions = {
        container: document.getElementById('$containerId'),
        theme: new Jedi.ThemeBootstrap3(),
        schema: $schema,
        hiddenInputAttributes: {
            'name': '$inputName',
            'id': '$inputId'
        }
    }
    const editor = new Jedi.Create(editorOptions) 
}

initEditor$id()
JS
        );
    }
}
