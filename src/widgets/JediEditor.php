<?php

namespace eluhr\jedi\widgets;

use eluhr\jedi\assets\DeepMergeAsset;
use eluhr\jedi\assets\JediAsset;
use stdClass;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\widgets\InputWidget;

/**
 * @property-write \yii\web\JsExpression|string $theme
 * @property-write stdClass|string|array $schema
 */
class JediEditor extends InputWidget
{

    // Available Themes
    public const THEME_DEFAULT = 'default';
    public const THEME_THEME_BOOTSTRAP3 = 'bootstrap3';
    public const THEME_THEME_BOOTSTRAP4 = 'bootstrap4';
    public const THEME_THEME_BOOTSTRAP5 = 'bootstrap5';

    /**
     * @var array the HTML attributes for the textarea container tag.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public array $containerOptions = [];
    /**
     * Options to be passed to the jedi.
     * List of valid options can be found here:
     * https://github.com/germanbisurgi/jedi?tab=readme-ov-file#options
     */
    public array $pluginOptions = [];
    /**
     * A json that contains the schema to build the form. Values can be given as array, string or stdClass
     * Does not to be set if pluginOptions property has schema set.
     */
    protected array $_schema;
    /**
     * Defined theme. Either set is as a string from the const THEME_ or as a JsExpression
     */
    protected JsExpression $_theme;

    /**
     * @inheritdoc
     * @throws \yii\base\InvalidConfigException if some config is not as expected
     */
    public function init()
    {
        parent::init();

        // If schema is set in plugin options use it from there
        if (isset($this->pluginOptions['schema'])) {
            $this->setSchema($this->pluginOptions['schema']);
            unset($this->pluginOptions['schema']);
        }

        // If theme is set in plugin options use it from there
        if (isset($this->pluginOptions['theme'])) {
            $this->setTheme($this->pluginOptions['theme']);
            unset($this->pluginOptions['theme']);
        }

        $this->ensurePluginOptions();

        if (!isset($this->_schema)) {
            throw new InvalidConfigException("Property 'schema' must be specified.");
        }

        // Always set a unique id for the container
        if (!isset($this->containerOptions['id'])) {
            $this->containerOptions['id'] = $this->options['id'] . '-container';
        }
    }

    /**
     * Convert schema to json array if given as string or stdClass
     */
    public function setSchema(array|stdClass|string $schema): void
    {
        if ($schema instanceof stdClass) {
            $schema = Json::encode($schema);
            // Now that the value is a string, it is converted to an array in the next condition.
        }

        if (is_string($schema)) {
            $schema = Json::decode($schema);
        }

        $this->_schema = $schema;
    }

    /**
     * Allow theme either from a string or a JsExpression
     */
    public function setTheme(JsExpression|string $theme): void
    {
        if (is_string($theme)) {
            $theme = self::themeMap()[$theme] ?? new JsExpression('new Jedi.Theme()');
        }
        $this->_theme = $theme;
    }

    /**
     * List of valid themed indexed as the theme value from const and value must be a JsExpression
     */
    protected static function themeMap(): array
    {
        return [
            self::THEME_DEFAULT => new JsExpression('new Jedi.Theme()'),
            self::THEME_THEME_BOOTSTRAP3 => new JsExpression('new Jedi.ThemeBootstrap3()'),
            self::THEME_THEME_BOOTSTRAP4 => new JsExpression('new Jedi.ThemeBootstrap4()'),
            self::THEME_THEME_BOOTSTRAP5 => new JsExpression('new Jedi.ThemeBootstrap5()'),
        ];
    }

    /**
     * Reset all plugin options that should not be overwritten
     */
    protected function ensurePluginOptions(): void
    {
        unset($this->pluginOptions['container']);
        unset($this->pluginOptions['hiddenInputAttributes']['name']);
        unset($this->pluginOptions['hiddenInputAttributes']['id']);

        if (!isset($this->_theme)) {
            $this->setTheme(self::THEME_DEFAULT);
        }
        // Set theme
        $this->pluginOptions['theme'] = $this->_theme;


        // Set default ref parser if not set
        if (!isset($this->pluginOptions['refParser'])) {
            $this->pluginOptions['refParser'] = new JsExpression('new Jedi.RefParser()');
        }

        // Set default value
        if ($this->hasModel()) {
            $data = $this->model->{$this->attribute};
        } else {
            $data = $this->value;
        }

        // Check if value is set before checking json
        if (!is_null($data)) {
            // Check if value is valid json. json_decode throws an error which we can "catch" with the json_last_error function
            json_decode($data);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->pluginOptions['data'] = new JsExpression($data);
            } else {
                Yii::warning('Data is not a valid JSON.');
            }
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
     * Register all needed asset bundles and scripts
     */
    protected function registerAssets(): void
    {
        DeepMergeAsset::register($this->view);
        JediAsset::register($this->view);

        // Setup variables for later use
        $containerId = $this->containerOptions['id'];
        $inputId = $this->hasModel() ? Html::getInputId($this->model, $this->attribute) : $this->options['id'];
        $inputName = $this->hasModel() ? Html::getInputName($this->model, $this->attribute) : $this->name;
        $id = Inflector::slug($inputId, '');

        // Escape json for config if needed
        $schema = Json::htmlEncode($this->_schema);
        $pluginOptions = Json::htmlEncode($this->pluginOptions);

        $refParser = $this->pluginOptions['refParser'] ?? null;

        // Init editor
        $this->view->registerJs(<<<JS
const initEditor$id = async () => {
    const schema = $schema
    const refParser = $refParser
    
    if (refParser) {
        await refParser.dereference(schema)
    }
    
    const defaultOptions = {
        container: document.getElementById('$containerId'),
        schema: schema,
        hiddenInputAttributes: {
            'name': '$inputName',
            'id': '$inputId'
        }
    }
    
    const customOptions = $pluginOptions
    const editorOptions = deepMerge(defaultOptions, customOptions)
    
    const editor = new Jedi.Create(editorOptions) 
    
    if (editor) {
        window['$inputId'] = editor
    }
}

initEditor$id()
JS
        );
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
}
